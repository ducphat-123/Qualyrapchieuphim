<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../../be/config/db.php';
require_once __DIR__ . '/../../be/services/UserService.php';

$uid = $_SESSION['user_id'];
$active_page = 'tickets';
$tab = $_GET['tab'] ?? 'upcoming';

$service = new UserService($pdo);
$ticketsResult = $service->getMyTickets($uid);

$upcoming  = $ticketsResult['upcoming'];
$past      = $ticketsResult['past'];
$cancelled = $ticketsResult['cancelled'];

$statusLabel = ['confirmed'=>'Đã xác nhận','cancelled'=>'Đã hủy','checked_in'=>'Đã check-in'];
$statusColor = ['confirmed'=>'#22C55E','cancelled'=>'#EF4444','checked_in'=>'#2563EB'];
$payLabels   = ['momo'=>'MoMo','vnpay'=>'VNPay','zalopay'=>'ZaloPay','cash'=>'Tiền mặt'];

function javascript_escape($str) {
    return str_replace(["\r", "\n", "'", '"'], ["", "", "\\'", '\\"'], $str);
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
.btn-sm:disabled{opacity:.6;cursor:not-allowed}
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
.mbtn:disabled{opacity:.6;cursor:not-allowed}
.mbtn-cancel{background:var(--bg);color:var(--text)}
.mbtn-confirm{background:#EF4444;color:#fff}

/* ALERT BANNER */
.alert{display:flex;align-items:center;gap:10px;padding:14px 18px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.03)}
.alert-success{background:#F0FDF4;color:#16A34A;border:1px solid #BBF7D0}
.alert-error{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
</style>
</head>
<body>
<?php include __DIR__ . '/../components/sidebar.php'; ?>

<div class="main">
  <div class="topbar"><h1><i class="fa-solid fa-receipt" style="color:var(--blue);margin-right:8px"></i>Vé của tôi</h1></div>
  <div class="content">
    <div id="alert-box"></div>

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

    <form id="cancel-form">
      <input type="hidden" name="action" value="cancel_booking">
      <input type="hidden" name="booking_code" id="cancel-code">
      <div class="modal-btns">
        <button type="button" class="mbtn mbtn-cancel" onclick="closeCancel()" style="background:#F1F5F9; color:#475569;">Không, giữ lại</button>
        <button type="submit" class="mbtn mbtn-confirm" id="btn-confirm-cancel" style="background:#EF4444; color:#fff;">Xác nhận hủy vé</button>
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
    
    <form id="review-form">
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
        <button type="submit" class="mbtn mbtn-confirm" id="btn-submit-review" style="background:#10B981; color:#fff;">Gửi đánh giá</button>
      </div>
    </form>
  </div>
</div>

<script>
const USER_ENDPOINT = '../../be/api.php';

function showAlert(type, message) {
  const box = document.getElementById('alert-box');
  const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
  box.innerHTML = `<div class="alert alert-${type}"><i class="fa-solid ${icon}"></i><span>${message}</span></div>`;
  box.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function confirmCancel(code, amount, points, method) {
  document.getElementById('cancel-code').value = code;
  document.getElementById('m-code').textContent = code;
  document.getElementById('m-amount').textContent = amount + '₫';
  document.getElementById('m-method').textContent = method;
  document.getElementById('cancel-overlay').classList.add('show');
}
function closeCancel() { document.getElementById('cancel-overlay').classList.remove('show'); }

// CANCEL BOOKING SUBMIT
document.getElementById('cancel-form').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('btn-confirm-cancel');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';

  const fd = new FormData(this);

  try {
    const r = await fetch(USER_ENDPOINT, { method: 'POST', body: fd });
    const d = await r.json();
    closeCancel();
    if (d.success) {
      showAlert('success', d.message);
      setTimeout(() => location.href = 'my-tickets.php?tab=cancelled', 1200);
    } else {
      showAlert('error', d.message);
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  } catch {
    showAlert('error', 'Lỗi kết nối. Vui lòng thử lại.');
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
});

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

// SUBMIT REVIEW
document.getElementById('review-form').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('btn-submit-review');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang gửi...';

  const fd = new FormData(this);

  try {
    const r = await fetch(USER_ENDPOINT, { method: 'POST', body: fd });
    const d = await r.json();
    closeReview();
    if (d.success) {
      showAlert('success', d.message);
      setTimeout(() => location.reload(), 1200);
    } else {
      showAlert('error', d.message);
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  } catch {
    showAlert('error', 'Lỗi kết nối. Vui lòng thử lại.');
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
});

async function logout(){
  const fd=new FormData();fd.append('action','logout');
  const r=await fetch('../../be/api.php',{method:'POST',body:fd});
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
