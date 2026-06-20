<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

$uid = $_SESSION['user_id'];
$active_page = 'tickets';
$tab = $_GET['tab'] ?? 'upcoming';

// Create movie_reviews table if it doesn't exist
$pdo->query("
  CREATE TABLE IF NOT EXISTS movie_reviews (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      movie_id INT UNSIGNED NOT NULL,
      booking_code VARCHAR(50) NOT NULL,
      rating INT NOT NULL,
      comment TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY unique_user_movie_booking (user_id, movie_id, booking_code)
  ) ENGINE=InnoDB;
");

function javascript_escape($str) {
    return str_replace(["\r", "\n", "'", '"'], ["", "", "\\'", '\\"'], $str);
}

// Handle Review Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    $bcode = $_POST['booking_code'] ?? '';
    $rating = (int)($_POST['rating'] ?? 10);
    $comment = trim($_POST['comment'] ?? '');

    if ($bcode && $rating >= 1 && $rating <= 10) {
        $bkQuery = $pdo->prepare("
          SELECT b.showtime_id, s.movie_id 
          FROM bookings b
          JOIN showtimes s ON b.showtime_id = s.id
          WHERE b.booking_code=? AND b.user_id=? LIMIT 1
        ");
        $bkQuery->execute([$bcode, $uid]);
        $bk = $bkQuery->fetch();

        if ($bk) {
            try {
                $pdo->beginTransaction();
                
                $insReview = $pdo->prepare("
                  INSERT INTO movie_reviews (user_id, movie_id, booking_code, rating, comment)
                  VALUES (?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)
                ");
                $insReview->execute([$uid, $bk['movie_id'], $bcode, $rating, $comment]);

                $avgQuery = $pdo->prepare("SELECT AVG(rating) FROM movie_reviews WHERE movie_id=?");
                $avgQuery->execute([$bk['movie_id']]);
                $newAvg = round($avgQuery->fetchColumn(), 1);

                $updMovie = $pdo->prepare("UPDATE movies SET rating=? WHERE id=?");
                $updMovie->execute([$newAvg ?: 10.0, $bk['movie_id']]);

                $pdo->commit();
                $_SESSION['cancel_success'] = "Gửi đánh giá cho phim thành công! Cảm ơn đóng góp của bạn.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['cancel_error'] = "Có lỗi xảy ra khi lưu đánh giá. Vui lòng thử lại.";
            }
        }
    }
    header("Location: my-tickets.php?tab=past"); exit;
}

$bookings = $pdo->prepare("
  SELECT b.*, m.title, m.poster_url, m.duration_min, m.id as movie_id,
         s.show_date, s.start_time, s.format, s.subtitle_type, s.hall_name,
         c.name as cinema_name,
         r.rating as user_rating, r.comment as user_comment
  FROM bookings b
  JOIN showtimes s ON b.showtime_id = s.id
  JOIN movies m ON s.movie_id = m.id
  JOIN cinemas c ON s.cinema_id = c.id
  LEFT JOIN movie_reviews r ON b.booking_code = r.booking_code AND b.user_id = r.user_id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC
");
$bookings->execute([$uid]);
$all = $bookings->fetchAll();

$upcoming  = array_filter($all, fn($b) => $b['status'] !== 'cancelled' && strtotime($b['show_date'] . ' ' . $b['start_time']) >= time());
$past      = array_filter($all, fn($b) => $b['status'] !== 'cancelled' && strtotime($b['show_date'] . ' ' . $b['start_time']) < time());
$cancelled = array_filter($all, fn($b) => $b['status'] === 'cancelled');

$statusLabel = ['confirmed'=>'Đã xác nhận','cancelled'=>'Đã hủy','checked_in'=>'Đã check-in'];
$statusColor = ['confirmed'=>'#22C55E','cancelled'=>'#EF4444','checked_in'=>'#2563EB'];
$payLabels   = ['momo'=>'MoMo','vnpay'=>'VNPay','zalopay'=>'ZaloPay','cash'=>'Tiền mặt'];

// Handle cancel
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='cancel') {
    $bcode = $_POST['booking_code'] ?? '';
    if ($bcode) {
        $check = $pdo->prepare("
          SELECT b.*, s.show_date, s.start_time
          FROM bookings b
          JOIN showtimes s ON b.showtime_id = s.id
          WHERE b.booking_code=? AND b.user_id=? AND b.status='confirmed' LIMIT 1
        ");
        $check->execute([$bcode, $uid]);
        $bk = $check->fetch();
        if ($bk) {
            $showtime_dt = strtotime($bk['show_date'] . ' ' . $bk['start_time']);
            $minutes_left = ($showtime_dt - time()) / 60;
            if ($minutes_left >= 60) {
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare("UPDATE bookings SET status='cancelled', cancel_reason=NULL, payment_status='refunded' WHERE booking_code=?")->execute([$bcode]);
                    $pdo->prepare("UPDATE showtimes SET available_seats=available_seats+? WHERE id=?")->execute([$bk['num_tickets'], $bk['showtime_id']]);
                    
                    if ($bk['voucher_code']) {
                        $pdo->prepare("UPDATE vouchers SET used_count=GREATEST(0, used_count-1) WHERE code=?")->execute([$bk['voucher_code']]);
                    }

                    // Không trừ điểm lúc hủy vé nữa, vì điểm chỉ được cộng sau khi xem xong.

                    $pdo->commit();
                    $_SESSION['cancel_success'] = "Đã hủy vé $bcode thành công! Số tiền " . number_format($bk['total_amount'],0,',','.') . "₫ đã được hoàn trả về tài khoản nguồn (" . strtoupper($bk['payment_method']) . ").";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['cancel_error'] = "Có lỗi xảy ra khi hoàn vé. Vui lòng thử lại.";
                }
            } else {
                $_SESSION['cancel_error'] = "Không thể hủy vé này do đã quá hạn thời gian cho phép (tối thiểu trước giờ chiếu 60 phút).";
            }
        }
    }
    header('Location: my-tickets.php?tab=cancelled'); exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vé của tôi - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--blue:#2563EB;--sb:#0F172A;--sbw:240px;--bg:#F1F5F9;--card:#fff;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--r:14px;--sh:0 2px 16px rgba(15,23,42,.07)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}

.main{margin-left:var(--sbw);flex:1}
.topbar{background:var(--card);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 26px;gap:14px;position:sticky;top:0;z-index:50}
.topbar h1{font-size:18px;font-weight:800}
.content{padding:24px 28px}
/* TABS */
.tabs{display:flex;gap:4px;background:var(--card);border-radius:12px;padding:4px;box-shadow:var(--sh);width:fit-content;margin-bottom:24px}
.tab{padding:8px 22px;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;color:var(--muted);transition:all .2s;text-decoration:none}
.tab.active{background:var(--blue);color:#fff}
/* TICKET CARD */
.ticket-list{display:flex;flex-direction:column;gap:16px}
.bk-card{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden;transition:box-shadow .2s}
.bk-card:hover{box-shadow:0 4px 28px rgba(15,23,42,.12)}
.bk-top{display:flex;gap:16px;padding:18px 20px;border-bottom:1px dashed var(--border)}
.bk-poster{width:64px;height:90px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#e2e8f0}
.bk-poster-ph{width:64px;height:90px;border-radius:8px;flex-shrink:0;background:linear-gradient(135deg,#334155,#1e293b);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:22px}
.bk-info{flex:1}
.bk-title{font-size:15px;font-weight:700;margin-bottom:6px}
.bk-meta{font-size:13px;color:var(--muted);line-height:1.7}
.bk-status{flex-shrink:0;display:flex;flex-direction:column;align-items:flex-end;gap:8px}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700}
.bk-code{font-size:13px;font-weight:700;color:var(--muted)}
.bk-bot{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:var(--bg)}
.seats-row{display:flex;flex-wrap:wrap;gap:5px}
.seat-chip{background:#EFF6FF;color:var(--blue);font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:5px;border:1px solid #BFDBFE}
.bk-actions{display:flex;gap:8px}
.btn-sm{height:34px;padding:0 14px;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;border:none;font-family:inherit;display:flex;align-items:center;gap:5px;text-decoration:none;transition:all .2s}
.btn-sm-blue{background:var(--blue);color:#fff}
.btn-sm-blue:hover{background:#1D4ED8}
.btn-sm-red{background:#FEF2F2;color:#EF4444;border:1px solid #FECACA}
.btn-sm-red:hover{background:#FEE2E2}
.btn-sm-gray{background:var(--card);color:var(--muted);border:1px solid var(--border)}
.bk-price{font-size:16px;font-weight:800;color:var(--blue)}
/* EMPTY */
.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty i{font-size:48px;opacity:.2;display:block;margin-bottom:16px}
.empty h3{font-size:18px;font-weight:700;margin-bottom:8px}
/* MODAL */
.overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.8);z-index:200;align-items:center;justify-content:center}
.overlay.show{display:flex}
.modal{background:var(--card);border-radius:18px;padding:28px;max-width:380px;width:100%;box-shadow:0 16px 48px rgba(0,0,0,.4);text-align:center}
.modal h3{font-size:18px;font-weight:800;margin-bottom:8px}
.modal p{font-size:14px;color:var(--muted);margin-bottom:22px}
.modal-btns{display:flex;gap:10px}
.mbtn{flex:1;height:42px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;border:none;font-family:inherit;transition:all .2s}
.mbtn-cancel{background:var(--bg);color:var(--text)}
.mbtn-confirm{background:#EF4444;color:#fff}

/* ALERT BANNER */
.alert{display:flex;align-items:center;gap:10px;padding:14px 18px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.03)}
.alert-success{background:#F0FDF4;color:#16A34A;border:1px solid #BBF7D0}
.alert-error{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main">
  <div class="topbar"><h1><i class="fa-solid fa-receipt" style="color:var(--blue);margin-right:8px"></i>Vé của tôi</h1></div>
  <div class="content">
    <?php if(isset($_SESSION['cancel_success'])): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= $_SESSION['cancel_success'] ?></span>
      </div>
      <?php unset($_SESSION['cancel_success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['cancel_error'])): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= $_SESSION['cancel_error'] ?></span>
      </div>
      <?php unset($_SESSION['cancel_error']); ?>
    <?php endif; ?>

    <div class="tabs">
      <a class="tab <?= $tab==='upcoming'?'active':'' ?>" href="?tab=upcoming">Sắp chiếu (<?= count($upcoming) ?>)</a>
      <a class="tab <?= $tab==='past'?'active':'' ?>" href="?tab=past">Đã chiếu (<?= count($past) ?>)</a>
      <a class="tab <?= $tab==='cancelled'?'active':'' ?>" href="?tab=cancelled">Đã hủy (<?= count($cancelled) ?>)</a>
    </div>

    <?php
    $list = $tab==='upcoming' ? $upcoming : ($tab==='past' ? $past : $cancelled);
    if (empty($list)):
    ?>
    <div class="empty">
      <i class="fa-solid fa-ticket"></i>
      <h3>Chưa có vé nào</h3>
      <p><?= $tab==='upcoming' ? 'Bạn chưa có vé sắp chiếu. Hãy đặt vé ngay!' : ($tab==='past' ? 'Bạn chưa có vé đã chiếu.' : 'Bạn chưa có vé đã hủy.') ?></p>
      <a href="home.php" style="display:inline-flex;align-items:center;gap:7px;margin-top:16px;padding:10px 22px;background:var(--blue);color:#fff;border-radius:10px;font-weight:700;text-decoration:none"><i class="fa-solid fa-film"></i> Xem phim ngay</a>
    </div>
    <?php else: ?>
    <div class="ticket-list">
      <?php foreach($list as $b):
        $seats = json_decode($b['seats_json'], true) ?? [];
        $sc = $statusColor[$b['status']] ?? '#64748B';
        $sl = $statusLabel[$b['status']] ?? $b['status'];
        
        $showtime_dt = strtotime($b['show_date'] . ' ' . $b['start_time']);
        $minutes_left = ($showtime_dt - time()) / 60;
        $canCancel = ($b['status'] === 'confirmed' && $minutes_left >= 60);
      ?>
      <div class="bk-card">
        <div class="bk-top">
          <?php if($b['poster_url']): ?>
          <img class="bk-poster" src="<?= htmlspecialchars($b['poster_url']) ?>" alt="">
          <?php else: ?><div class="bk-poster-ph"><i class="fa-solid fa-film"></i></div><?php endif; ?>
          <div class="bk-info">
            <div class="bk-title"><?= htmlspecialchars($b['title']) ?></div>
            <div class="bk-meta">
              📅 <?= date('d/m/Y', strtotime($b['show_date'])) ?> &nbsp;·&nbsp; ⏰ <?= substr($b['start_time'],0,5) ?><br>
              🎬 <?= $b['format'] ?> · <?= $b['subtitle_type'] ?> · <?= htmlspecialchars($b['hall_name'] ?? 'Phòng chiếu 1') ?><br>
              📍 <?= htmlspecialchars($b['cinema_name']) ?>
            </div>
          </div>
          <div class="bk-status">
            <span class="status-badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><i class="fa-solid fa-circle" style="font-size:7px"></i><?= $sl ?></span>
            <div class="bk-code"><?= htmlspecialchars($b['booking_code']) ?></div>
            <div class="bk-price"><?= number_format($b['total_amount'],0,',','.') ?>₫</div>
          </div>
        </div>
        <?php if ($b['status'] === 'cancelled' && !empty($b['cancel_reason'])): ?>
          <div style="background: #FEF2F2; border-top: 1px dashed #FECACA; border-bottom: 1px dashed #FECACA; padding: 12px 20px; font-size: 13px; color: #991B1B; display: flex; flex-direction: column; gap: 6px; text-align: left;">
             <div style="display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-circle-exclamation" style="color:#EF4444; font-size:14px;"></i> <span><strong>Lý do hủy vé:</strong> <?= htmlspecialchars($b['cancel_reason'] ?? 'Hệ thống tự động hủy khẩn cấp do thay đổi lịch chiếu') ?></span></div>
             <?php if ($b['payment_status'] === 'refunded'): ?>
               <div style="color: #16A34A; font-weight: 700; display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-circle-check" style="font-size:14px;"></i> <span><strong>Trạng thái:</strong> Đã hoàn tiền thành công <?= number_format($b['total_amount'], 0, ',', '.') ?>₫ về ví/tài khoản <strong><?= strtoupper($b['payment_method']) ?></strong>.</span></div>
             <?php else: ?>
               <div style="color: #D97706; font-weight: 700; display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:14px;"></i> <span><strong>Trạng thái:</strong> Đang xử lý hoàn tiền <?= number_format($b['total_amount'], 0, ',', '.') ?>₫ về tài khoản <strong><?= strtoupper($b['payment_method']) ?></strong>.</span></div>
             <?php endif; ?>
          </div>
        <?php endif; ?>
        <div class="bk-bot">
          <div class="seats-row">
            <?php foreach($seats as $s): ?>
            <span class="seat-chip"><?= htmlspecialchars($s) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="bk-actions">
            <a href="booking-confirm.php?code=<?= urlencode($b['booking_code']) ?>" class="btn-sm btn-sm-blue"><i class="fa-solid fa-eye"></i> Xem vé</a>
            <?php if($canCancel): ?>
              <button class="btn-sm btn-sm-red cancel-btn"
                data-showtime="<?= $showtime_dt ?>"
                data-code="<?= htmlspecialchars($b['booking_code']) ?>"
                data-amount="<?= number_format($b['total_amount'],0,',','.') ?>"
                data-points="<?= floor($b['total_amount']/10000) ?>"
                data-method="<?= strtoupper($b['payment_method']) ?>"
                onclick="confirmCancel(this.dataset.code,this.dataset.amount,this.dataset.points,this.dataset.method)">
                <i class="fa-solid fa-xmark"></i> Hủy vé
              </button>
            <?php elseif($b['status'] === 'confirmed'): ?>
              <span style="font-size:12px;color:var(--muted);font-weight:600;display:flex;align-items:center;gap:4px;"><i class="fa-regular fa-clock"></i> Quá hạn hủy vé</span>
            <?php endif; ?>

            <?php if ($tab === 'past' && ($b['status'] === 'confirmed' || $b['status'] === 'checked_in')): ?>
              <?php if (isset($b['user_rating'])): ?>
                <button class="btn-sm btn-sm-gray" onclick="openReview('<?= htmlspecialchars($b['booking_code']) ?>', '<?= htmlspecialchars(javascript_escape($b['title'])) ?>', <?= $b['user_rating'] ?>, '<?= htmlspecialchars(javascript_escape($b['user_comment'])) ?>')">
                  ⭐ <?= $b['user_rating'] ?>/10 (Sửa)
                </button>
              <?php else: ?>
                <button class="btn-sm" style="background:#10B981; color:#fff;" onclick="openReview('<?= htmlspecialchars($b['booking_code']) ?>', '<?= htmlspecialchars(javascript_escape($b['title'])) ?>', 10, '')">
                  <i class="fa-regular fa-star"></i> Đánh giá
                </button>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Cancel Modal -->
<div class="overlay" id="cancel-overlay">
  <div class="modal" style="max-width: 440px;">
    <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF2F2; color: #EF4444; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 16px;">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Xác nhận hủy vé & Hoàn tiền</h3>
    <p style="font-size: 13.5px; color: #64748B; line-height: 1.5; margin-bottom: 20px;">Bạn đang yêu cầu hoàn hủy vé xem phim. Vui lòng kiểm tra kỹ các thông tin hoàn trả bên dưới.</p>
    
    <div style="background: #F8FAFC; border-radius: 12px; padding: 16px; text-align: left; font-size: 13.5px; margin-bottom: 24px; border: 1.5px solid #E2E8F0; display: flex; flex-direction: column; gap: 8px;">
      <div style="display:flex; justify-content:space-between;"><span style="color:#64748B;">Mã đặt vé:</span><strong id="m-code" style="color:#0F172A;"></strong></div>
      <div style="display:flex; justify-content:space-between;"><span style="color:#64748B;">Số tiền hoàn trả:</span><strong id="m-amount" style="color:#16A34A;"></strong></div>
      <div style="display:flex; justify-content:space-between;"><span style="color:#64748B;">Phương thức hoàn:</span><strong id="m-method" style="color:#2563EB;"></strong></div>
    </div>

    <form method="POST" id="cancel-form">
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="booking_code" id="cancel-code">
      <div class="modal-btns">
        <button type="button" class="mbtn mbtn-cancel" onclick="closeCancel()" style="background:#F1F5F9; color:#475569;">Không, giữ lại</button>
        <button type="submit" class="mbtn mbtn-confirm" style="background:#EF4444; color:#fff;">Xác nhận hủy vé</button>
      </div>
    </form>
  </div>
</div>

<!-- Review Modal -->
<div class="overlay" id="review-overlay">
  <div class="modal" style="max-width: 440px; text-align: center;">
    <div style="width: 56px; height: 56px; border-radius: 50%; background: #ECFDF5; color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 16px;">
      <i class="fa-regular fa-face-smile"></i>
    </div>
    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Đánh giá phim</h3>
    <p id="rev-movie-title" style="font-size: 14px; font-weight: 700; color: var(--blue); margin-bottom: 20px;"></p>
    
    <form method="POST" id="review-form">
      <input type="hidden" name="action" value="submit_review">
      <input type="hidden" name="booking_code" id="rev-booking-code">
      
      <!-- Rating Stars 1-10 -->
      <div style="margin-bottom: 20px;">
        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748B; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Điểm số của bạn:</label>
        <div style="display:flex; justify-content:center; gap:6px; font-size: 22px;">
          <?php for($i=1; $i<=10; $i++): ?>
            <span class="star-item" data-val="<?= $i ?>" onclick="setStar(<?= $i ?>)" style="cursor:pointer; color:#CBD5E1; transition:all 0.15s; display: inline-block; padding: 0 2px;"><i class="fa-solid fa-star"></i></span>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="rev-rating-input" value="10">
        <div id="rating-label" style="font-size: 13px; font-weight: 800; color: #F59E0B; margin-top: 6px;">10 / 10 - Xuất sắc</div>
      </div>

      <!-- Comment Text -->
      <div style="margin-bottom: 24px; text-align: left;">
        <label style="display:block; font-size: 12px; font-weight: 700; color: #64748B; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Lời nhận xét:</label>
        <textarea name="comment" id="rev-comment-input" rows="4" placeholder="Nhập cảm nghĩ của bạn về phim..." style="width: 100%; border: 1.5px solid #E2E8F0; border-radius: 12px; padding: 12px; font-family: inherit; font-size: 13.5px; outline: none; transition: border-color 0.2s; resize: none;"></textarea>
      </div>

      <div class="modal-btns">
        <button type="button" class="mbtn mbtn-cancel" onclick="closeReview()" style="background:#F1F5F9; color:#475569;">Đóng</button>
        <button type="submit" class="mbtn mbtn-confirm" style="background:#10B981; color:#fff;">Gửi đánh giá</button>
      </div>
    </form>
  </div>
</div>

<script>
function confirmCancel(code, amount, points, method) {
  document.getElementById('cancel-code').value = code;
  document.getElementById('m-code').textContent = code;
  document.getElementById('m-amount').textContent = amount + '₫';
  document.getElementById('m-method').textContent = method;
  document.getElementById('cancel-overlay').classList.add('show');
}
function closeCancel() { document.getElementById('cancel-overlay').classList.remove('show'); }

const starLabels = {
  1: "Tệ hại", 2: "Rất tệ", 3: "Kém", 4: "Trung bình kém", 
  5: "Tạm ổn", 6: "Khá", 7: "Tốt", 8: "Rất tốt", 
  9: "Tuyệt vời", 10: "Xuất sắc"
};

function setStar(val) {
  document.getElementById('rev-rating-input').value = val;
  document.getElementById('rating-label').textContent = val + ' / 10 - ' + starLabels[val];
  document.querySelectorAll('.star-item').forEach(s => {
    const sval = parseInt(s.dataset.val);
    if (sval <= val) {
      s.style.color = '#F59E0B';
    } else {
      s.style.color = '#CBD5E1';
    }
  });
}

function openReview(code, title, rating, comment) {
  document.getElementById('rev-booking-code').value = code;
  document.getElementById('rev-movie-title').textContent = title;
  document.getElementById('rev-comment-input').value = comment;
  setStar(rating);
  document.getElementById('review-overlay').classList.add('show');
  setTimeout(() => document.getElementById('rev-comment-input').focus(), 100);
}

function closeReview() {
  document.getElementById('review-overlay').classList.remove('show');
}

async function logout(){
  const fd=new FormData();fd.append('action','logout');
  const r=await fetch('auth.php',{method:'POST',body:fd});
  const d=await r.json();location.href=d.redirect||'login.php';
}

// Realtime: Ẩn nút hủy vé khi < 60 phút trước giờ chiếu
function checkCancelBtns() {
  const now = Math.floor(Date.now() / 1000);
  document.querySelectorAll('.cancel-btn').forEach(btn => {
    const showtime = parseInt(btn.dataset.showtime);
    const minsLeft = (showtime - now) / 60;
    if (minsLeft < 60) {
      const wrap = btn.parentElement;
      btn.remove();
      // Thêm dòng "Quá hạn hủy vé"
      const msg = document.createElement('span');
      msg.style.cssText = 'font-size:12px;color:var(--muted);font-weight:600;display:flex;align-items:center;gap:4px';
      msg.innerHTML = '<i class="fa-regular fa-clock"></i> Quá hạn hủy vé';
      wrap.appendChild(msg);
    }
  });
}
checkCancelBtns();
setInterval(checkCancelBtns, 60000); // kiểm tra mỗi 1 phút
</script>
</body>
</html>
