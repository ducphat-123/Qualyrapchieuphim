<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

$code = trim($_GET['code'] ?? '');
if (!$code) { header('Location: home.php'); exit; }

$user_role = $_SESSION['user_role'] ?? 'user';

$bk = $pdo->prepare("
  SELECT b.*, m.title, m.poster_url, m.duration_min, m.age_rating,
         s.show_date, s.start_time, s.end_time, s.format, s.subtitle_type, s.hall_name,
         c.name as cinema_name, c.address as cinema_address,
         u.full_name, u.email, u.phone
  FROM bookings b
  JOIN showtimes s ON b.showtime_id = s.id
  JOIN movies m ON s.movie_id = m.id
  JOIN cinemas c ON s.cinema_id = c.id
  JOIN users u ON b.user_id = u.id
  WHERE b.booking_code = ? LIMIT 1
");
$bk->execute([$code]);
$booking = $bk->fetch();
if (!$booking) { header('Location: home.php'); exit; }

// Authorization check: standard users can only view their own bookings.
if ($user_role !== 'staff' && $user_role !== 'admin') {
    if ((int)$booking['user_id'] !== (int)$_SESSION['user_id']) {
        header('Location: home.php');
        exit;
    }
}

$seats = json_decode($booking['seats_json'], true) ?? [];
$related_bookings = [];
$total_subtotal = $booking['subtotal'];
$total_discount = $booking['discount'];
$total_amount = $booking['total_amount'];
$ticket_codes_map = [];

foreach ($seats as $s) {
    $ticket_codes_map[$s] = $booking['booking_code'];
}

if (!empty($booking['transaction_id'])) {
    if ($user_role === 'staff' || $user_role === 'admin') {
        $rel = $pdo->prepare("
            SELECT booking_code, seats_json, subtotal, discount, total_amount, status
            FROM bookings
            WHERE transaction_id = ?
        ");
        $rel->execute([$booking['transaction_id']]);
    } else {
        $rel = $pdo->prepare("
            SELECT booking_code, seats_json, subtotal, discount, total_amount, status
            FROM bookings
            WHERE transaction_id = ? AND user_id = ?
        ");
        $rel->execute([$booking['transaction_id'], $_SESSION['user_id']]);
    }
    $related_bookings = $rel->fetchAll();
    
    if (count($related_bookings) > 0) {
        $seats = [];
        $total_subtotal = 0;
        $total_discount = 0;
        $total_amount = 0;
        $ticket_codes_map = [];
        
        foreach ($related_bookings as $rb) {
            $rb_seats = json_decode($rb['seats_json'], true) ?? [];
            foreach ($rb_seats as $s) {
                $seats[] = $s;
                $ticket_codes_map[$s] = $rb['booking_code'];
            }
            $total_subtotal += $rb['subtotal'];
            $total_discount += $rb['discount'];
            $total_amount += $rb['total_amount'];
        }
    }
}

$payLabels = ['momo'=>'MoMo','vnpay'=>'VNPay','zalopay'=>'ZaloPay','cash'=>'Tiền mặt','napas'=>'Napas'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đặt vé thành công - <?= htmlspecialchars($booking['title']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--blue:#2563EB;--bg:#F1F5F9;--card:#fff;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--green:#22C55E;--r:14px}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column}
.topbar{background:var(--card);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 24px;gap:16px;box-shadow:0 1px 8px rgba(15,23,42,.06)}
.logo{display:flex;align-items:center;gap:9px;text-decoration:none}
.logo-icon{width:30px;height:30px;background:var(--blue);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px}
.logo-name{font-size:16px;font-weight:800;color:var(--blue)}
.step-bar{display:flex;align-items:center;gap:0;flex:1;justify-content:center}
.step{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--muted)}
.step.done{color:var(--green)}
.step-num{width:24px;height:24px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700}
.step.done .step-num{background:var(--green);border-color:var(--green);color:#fff}
.step-line{width:40px;height:2px;background:var(--border);margin:0 8px}
.step-line.done{background:var(--green)}

.page{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 16px}
.container{width:100%;max-width:700px}

/* SUCCESS HEADER */
.success-box{background:var(--card);border-radius:20px;box-shadow:0 4px 32px rgba(15,23,42,.10);overflow:hidden;margin-bottom:20px}
.success-hero{background:linear-gradient(135deg,#22C55E,#16A34A);padding:36px;text-align:center;color:#fff}
.check-circle{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px;animation:pop .4s ease}
@keyframes pop{0%{transform:scale(0)}80%{transform:scale(1.1)}100%{transform:scale(1)}}
.success-hero h1{font-size:24px;font-weight:800;margin-bottom:6px}
.success-hero p{font-size:14px;opacity:.9}
.booking-code{display:inline-block;background:rgba(255,255,255,.2);border:2px dashed rgba(255,255,255,.5);border-radius:10px;padding:8px 20px;font-size:20px;font-weight:800;letter-spacing:2px;margin-top:14px}

/* TICKET */
.ticket{background:var(--card);border-radius:20px;box-shadow:0 4px 32px rgba(15,23,42,.10);overflow:hidden;margin-bottom:20px}
.ticket-top{display:flex;gap:20px;padding:24px;border-bottom:2px dashed var(--border);position:relative}
.ticket-poster{width:80px;height:112px;border-radius:10px;object-fit:cover;flex-shrink:0;background:#e2e8f0}
.ticket-poster-ph{width:80px;height:112px;border-radius:10px;flex-shrink:0;background:linear-gradient(135deg,#334155,#1e293b);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:24px}
.ticket-info h2{font-size:18px;font-weight:800;margin-bottom:8px}
.ticket-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px 20px}
.tm-item label{display:block;font-size:10.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px}
.tm-item span{font-size:13.5px;font-weight:600}
.tear{position:absolute;left:0;right:0;bottom:-1px;height:12px;background:radial-gradient(circle at 50% 0%,var(--bg) 50%,transparent 50%)}
.ticket-bot{display:flex;align-items:center;justify-content:space-between;padding:18px 24px}
.seats-info{flex:1}
.seats-info label{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:6px}
.seats-row{display:flex;flex-wrap:wrap;gap:5px}
.seat-chip{background:#EFF6FF;color:var(--blue);font-size:12px;font-weight:700;padding:3px 9px;border-radius:6px;border:1px solid #BFDBFE}
.qr-box{width:100px;height:100px;border:3px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--bg);flex-shrink:0}
.qr-box i{font-size:56px;color:var(--border)}

/* PRICE BOX */
.price-box{background:var(--card);border-radius:var(--r);box-shadow:0 2px 16px rgba(15,23,42,.07);padding:20px 24px;margin-bottom:20px}
.price-row{display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:8px}
.pr-label{color:var(--muted)}
.pr-val{font-weight:600}
.pr-green{color:var(--green)}
.pr-total{display:flex;justify-content:space-between;font-size:17px;font-weight:800;margin-top:12px;padding-top:12px;border-top:1px solid var(--border)}
.pr-total-price{color:var(--blue)}

/* ACTIONS */
.actions{display:flex;gap:12px;flex-wrap:wrap}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;flex:1;height:46px;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;border:none;font-family:inherit;text-decoration:none;transition:all .2s;min-width:140px}
.btn-blue{background:var(--blue);color:#fff}
.btn-blue:hover{background:#1D4ED8}
.btn-outline{background:#fff;color:var(--blue);border:2px solid var(--blue)}
.btn-outline:hover{background:#EFF6FF}
.btn-print{background:linear-gradient(135deg,#7C3AED,#6D28D9);color:#fff}
.btn-print:hover{opacity:.9}

/* INVOICE MODAL */
.inv-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center;padding:16px}
.inv-overlay.open{display:flex}
.inv-modal{background:#fff;width:100%;max-width:640px;max-height:90vh;overflow-y:auto;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.inv-toolbar{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:#fff;z-index:10}
.inv-toolbar h3{font-size:15px;font-weight:800}
.inv-toolbar-btns{display:flex;gap:8px}
.btn-do-print{background:var(--blue);color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit}
.btn-close-inv{background:var(--border);color:var(--text);border:none;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit}

/* INVOICE CONTENT */
.inv-body{padding:32px}
.inv-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:20px;border-bottom:2px solid var(--border)}
.inv-brand{display:flex;align-items:center;gap:10px}
.inv-brand-icon{width:40px;height:40px;background:var(--blue);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px}
.inv-brand-name{font-size:20px;font-weight:800;color:var(--blue)}
.inv-brand-sub{font-size:11px;color:var(--muted)}
.inv-meta{text-align:right}
.inv-meta h2{font-size:18px;font-weight:800;color:var(--text);margin-bottom:4px}
.inv-meta p{font-size:12px;color:var(--muted)}
.inv-code-badge{display:inline-block;background:#EFF6FF;color:var(--blue);border:1.5px dashed var(--blue);border-radius:8px;padding:4px 12px;font-size:13px;font-weight:700;letter-spacing:1px;margin-top:4px}

.inv-section{margin-bottom:20px}
.inv-section-title{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid var(--border)}
.inv-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 20px}
.inv-field label{display:block;font-size:10.5px;color:var(--muted);font-weight:600;margin-bottom:2px}
.inv-field span{font-size:13px;font-weight:600;color:var(--text)}

.inv-table{width:100%;border-collapse:collapse;margin-bottom:16px}
.inv-table th{background:#F8FAFC;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;padding:8px 12px;text-align:left;border-bottom:1px solid var(--border)}
.inv-table td{padding:10px 12px;font-size:13px;border-bottom:1px solid var(--border)}
.inv-table .ta-r{text-align:right;font-weight:600}
.inv-total-row{background:#EFF6FF}
.inv-total-row td{font-weight:800;color:var(--blue)}

.inv-footer{text-align:center;font-size:12px;color:var(--muted);padding-top:16px;border-top:1px solid var(--border);line-height:1.7}

/* Print styles */
@media print{
  body>*{display:none!important}
  .inv-overlay{display:block!important;position:static!important;background:none!important;padding:0!important}
  .inv-modal{box-shadow:none!important;max-height:none!important;border-radius:0!important}
  .inv-toolbar{display:none!important}
  .inv-body{padding:20px!important}
}
</style>
</head>
<body>
<div class="topbar">
  <a href="home.php" class="logo"><div class="logo-icon"><i class="fa-solid fa-clapperboard"></i></div><span class="logo-name">MovieFlex</span></a>
  <div class="step-bar">
    <div class="step done"><div class="step-num"><i class="fa-solid fa-check" style="font-size:9px"></i></div> Chọn phim</div>
    <div class="step-line done"></div>
    <div class="step done"><div class="step-num"><i class="fa-solid fa-check" style="font-size:9px"></i></div> Chọn ghế</div>
    <div class="step-line done"></div>
    <div class="step done"><div class="step-num"><i class="fa-solid fa-check" style="font-size:9px"></i></div> Thanh toán</div>
    <div class="step-line done"></div>
    <div class="step done"><div class="step-num"><i class="fa-solid fa-check" style="font-size:9px"></i></div> Xác nhận</div>
  </div>
</div>

<div class="page">
  <div class="container">

    <!-- SUCCESS -->
    <div class="success-box">
      <div class="success-hero">
        <div class="check-circle"><i class="fa-solid fa-check"></i></div>
        <h1>Đặt vé thành công! 🎉</h1>
        <p>Vé của bạn đã được xác nhận. Chúc bạn xem phim vui vẻ!</p>
      </div>
    </div>

    <!-- TICKET -->
    <div class="ticket">
      <div class="ticket-top">
        <?php if($booking['poster_url']): ?>
        <img class="ticket-poster" src="<?= htmlspecialchars($booking['poster_url']) ?>" alt="">
        <?php else: ?><div class="ticket-poster-ph"><i class="fa-solid fa-film"></i></div><?php endif; ?>
        <div class="ticket-info">
          <h2><?= htmlspecialchars($booking['title']) ?></h2>
          <div class="ticket-meta">
            <div class="tm-item"><label>Ngày chiếu</label><span><?= date('d/m/Y', strtotime($booking['show_date'])) ?></span></div>
            <div class="tm-item"><label>Giờ chiếu</label><span><?= substr($booking['start_time'],0,5) ?></span></div>
            <div class="tm-item"><label>Định dạng & Phòng</label><span><?= $booking['format'] ?> · <?= $booking['subtitle_type'] ?> · <?= htmlspecialchars($booking['hall_name'] ?? 'Phòng chiếu 1') ?></span></div>
            <div class="tm-item"><label>Rạp chiếu</label><span><?= htmlspecialchars($booking['cinema_name']) ?></span></div>
            <div class="tm-item"><label>Khách hàng</label><span><?= htmlspecialchars($booking['full_name']) ?></span></div>
            <div class="tm-item"><label>Thanh toán</label><span><?= $payLabels[$booking['payment_method']] ?? $booking['payment_method'] ?></span></div>
          </div>
        </div>
        <div class="tear"></div>
      </div>
      <div class="ticket-bot" style="padding: 24px;">
        <div class="seats-info" style="width: 100%;">
          <label style="margin-bottom: 12px; font-weight: 800; font-size: 12px; display: block; color: var(--muted); text-transform: uppercase;">CHI TIẾT MÃ VÉ TỪNG GHẾ (XUẤT TRÌNH KHI SOÁT VÉ)</label>
          <div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
            <?php foreach($seats as $s): 
              $tcode = $ticket_codes_map[$s] ?? $booking['booking_code'];
            ?>
              <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px dashed var(--border); padding-bottom: 10px; width: 100%;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <span class="seat-chip" style="margin: 0; background: var(--blue); color: #fff; font-size: 13px; padding: 4px 10px; border-radius: 6px; font-weight: 800;"><?= htmlspecialchars($s) ?></span>
                  <span style="font-family: monospace; font-size: 14.5px; font-weight: 800; color: var(--text); letter-spacing: 1px;"><?= htmlspecialchars($tcode) ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 11px; padding: 4px 10px; border-radius: 12px; font-weight: 700; background: #ECFDF5; color: #10B981; border: 1px solid #A7F3D0; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-circle-check"></i> Có hiệu lực</span>
                  <div style="width: 28px; height: 28px; border: 1.5px solid var(--border); border-radius: 6px; display: flex; align-items: center; justify-content: center; background: var(--bg); color: var(--muted);"><i class="fa-solid fa-qrcode" style="font-size: 14px;"></i></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- PRICE -->
    <div class="price-box">
      <div class="price-row"><span class="pr-label">Vé phim (<?= count($seats) ?> ghế)</span><span class="pr-val"><?= number_format($total_subtotal,0,',','.') ?>₫</span></div>
      <?php if($total_discount > 0): ?>
      <div class="price-row"><span class="pr-label">Giảm giá (<?= htmlspecialchars($booking['voucher_code']) ?>)</span><span class="pr-val pr-green">−<?= number_format($total_discount,0,',','.') ?>₫</span></div>
      <?php endif; ?>
      <div class="pr-total"><span>Tổng đã thanh toán</span><span class="pr-total-price"><?= number_format($total_amount,0,',','.') ?>₫</span></div>
    </div>

    <!-- ACTIONS -->
    <div class="actions">
      <button class="btn btn-print" onclick="document.getElementById('inv-overlay').classList.add('open')"><i class="fa-solid fa-file-invoice"></i> In hóa đơn</button>
      <?php if ($_SESSION['user_role'] === 'staff'): ?>
        <a href="staff.php" class="btn btn-blue"><i class="fa-solid fa-house"></i> Về ca trực nhân viên</a>
      <?php else: ?>
        <a href="my-tickets.php" class="btn btn-outline"><i class="fa-solid fa-receipt"></i> Xem vé của tôi</a>
        <a href="home.php" class="btn btn-blue"><i class="fa-solid fa-house"></i> Về trang chủ</a>
      <?php endif; ?>
    </div>

    <!-- INVOICE MODAL (Moved outside to fix printing) -->
  </div>
</div>

<!-- INVOICE MODAL -->
    <div class="inv-overlay" id="inv-overlay" onclick="closeInv(event)">
      <div class="inv-modal" id="inv-modal">
        <div class="inv-toolbar">
          <h3><i class="fa-solid fa-file-invoice" style="color:var(--blue);margin-right:6px"></i>Hóa đơn điện tử</h3>
          <div class="inv-toolbar-btns">
            <button class="btn-do-print" onclick="window.print()"><i class="fa-solid fa-print"></i> In hóa đơn</button>
            <button class="btn-close-inv" onclick="document.getElementById('inv-overlay').classList.remove('open')">✕ Đóng</button>
          </div>
        </div>
        <div class="inv-body" id="inv-print-area">
          <!-- Header -->
          <div class="inv-header">
            <div class="inv-brand">
              <div class="inv-brand-icon"><i class="fa-solid fa-clapperboard"></i></div>
              <div>
                <div class="inv-brand-name">MovieFlex</div>
                <div class="inv-brand-sub">Nền tảng đặt vé xem phim trực tuyến</div>
              </div>
            </div>
            <div class="inv-meta">
              <h2>HÓA ĐƠN ĐIỆN TỬ</h2>
              <p>Ngày xuất: <?= date('d/m/Y H:i') ?></p>
              <div class="inv-code-badge"><?= htmlspecialchars($booking['booking_code']) ?></div>
            </div>
          </div>

          <!-- Khách hàng -->
          <div class="inv-section">
            <div class="inv-section-title">Thông tin giao dịch & Khách hàng</div>
            <div class="inv-grid">
              <div class="inv-field"><label>Họ và tên</label><span><?= htmlspecialchars($booking['full_name']) ?></span></div>
              <div class="inv-field"><label>Email</label><span><?= htmlspecialchars($booking['email']) ?></span></div>
              <div class="inv-field"><label>Số điện thoại</label><span><?= htmlspecialchars($booking['phone'] ?: '—') ?></span></div>
              <div class="inv-field"><label>Mã giao dịch</label><span><?= htmlspecialchars($booking['transaction_id'] ?: 'N/A') ?></span></div>
              <div class="inv-field"><label>Thời gian đặt</label><span><?= date('H:i d/m/Y', strtotime($booking['created_at'])) ?></span></div>
              <div class="inv-field"><label>Phương thức TT</label><span><?= $payLabels[$booking['payment_method']] ?? $booking['payment_method'] ?></span></div>
            </div>
          </div>

          <!-- Thông tin suất chiếu -->
          <div class="inv-section">
            <div class="inv-section-title">Thông tin suất chiếu</div>
            <div class="inv-grid">
              <div class="inv-field"><label>Tên phim</label><span><?= htmlspecialchars($booking['title']) ?></span></div>
              <div class="inv-field"><label>Thời lượng</label><span><?= $booking['duration_min'] ?> phút</span></div>
              <div class="inv-field"><label>Định dạng</label><span><?= $booking['format'] ?> · <?= $booking['subtitle_type'] ?></span></div>
              <div class="inv-field"><label>Phòng chiếu</label><span><?= htmlspecialchars($booking['hall_name'] ?? 'Phòng chiếu tiêu chuẩn') ?></span></div>
              <div class="inv-field"><label>Ngày chiếu</label><span><?= date('d/m/Y', strtotime($booking['show_date'])) ?></span></div>
              <div class="inv-field"><label>Giờ chiếu</label><span><?= substr($booking['start_time'],0,5) ?></span></div>
              <div class="inv-field" style="grid-column:1/-1"><label>Rạp chiếu</label><span><strong><?= htmlspecialchars($booking['cinema_name']) ?></strong> — Hotline: <?= htmlspecialchars($booking['cinema_phone'] ?: '1900 6017') ?><br><span style="font-weight:normal;color:#64748B"><?= htmlspecialchars($booking['cinema_address']) ?></span></span></div>
              <div class="inv-field" style="grid-column:1/-1"><label>Ghế ngồi</label><span><?= implode(', ', array_map('htmlspecialchars', $seats)) ?></span></div>
            </div>
          </div>

          <!-- Bảng chi tiết -->
          <div class="inv-section">
            <div class="inv-section-title">Chi tiết thanh toán</div>
            <table class="inv-table">
              <thead><tr><th>Mô tả</th><th>Số lượng / Đơn giá</th><th class="ta-r">Thành tiền</th></tr></thead>
              <tbody>
                <tr>
                  <td>Vé xem phim — <?= htmlspecialchars($booking['title']) ?><br><span style="font-size:11px;color:#64748B">Ghế: <?= implode(', ', array_map('htmlspecialchars', $seats)) ?></span></td>
                  <td><?= count($seats) ?> vé<br><span style="font-size:11px;color:#64748B"><?= number_format($total_subtotal / max(1, count($seats)),0,',','.') ?>₫/vé</span></td>
                  <td class="ta-r"><?= number_format($total_subtotal,0,',','.') ?>₫</td>
                </tr>
                <?php if($total_discount > 0): ?>
                <tr><td>Khuyến mãi (Mã: <?= htmlspecialchars($booking['voucher_code']) ?>)</td><td>—</td><td class="ta-r" style="color:#16A34A">−<?= number_format($total_discount,0,',','.') ?>₫</td></tr>
                <?php endif; ?>
                <?php
                  $snackTotal = $total_amount - $total_subtotal + $total_discount;
                  if($snackTotal > 0):
                ?>
                <tr><td>Bắp nước & dịch vụ cộng thêm</td><td>—</td><td class="ta-r"><?= number_format($snackTotal,0,',','.') ?>₫</td></tr>
                <?php endif; ?>
                <!-- Subtotal and VAT formatting -->
                <tr style="border-top:1.5px dashed var(--border); border-bottom:none">
                  <td colspan="2" style="text-align:right; font-size:12.5px; padding-bottom:4px">Tổng tiền hàng:</td>
                  <td class="ta-r" style="font-size:13px; padding-bottom:4px"><?= number_format($total_amount,0,',','.') ?>₫</td>
                </tr>
                <tr style="border:none">
                  <td colspan="2" style="text-align:right; font-size:12.5px; color:#64748B; padding-top:4px">Thuế GTGT (VAT 10%):</td>
                  <td class="ta-r" style="font-size:13px; color:#64748B; padding-top:4px">Bao gồm</td>
                </tr>
                <tr class="inv-total-row"><td colspan="2"><strong>Tổng cộng đã thanh toán</strong></td><td class="ta-r"><strong><?= number_format($total_amount,0,',','.') ?>₫</strong></td></tr>
              </tbody>
            </table>
          </div>

          <!-- Footer -->
          <div class="inv-footer">
            <p>🎬 Cảm ơn bạn đã sử dụng dịch vụ của <strong>MovieFlex</strong>!</p>
            <p>Vui lòng xuất trình mã đặt vé <strong><?= htmlspecialchars($booking['booking_code']) ?></strong> khi đến rạp.</p>
            <p style="color:var(--muted);font-size:11px;margin-top:6px">Hóa đơn này được tạo tự động bởi hệ thống MovieFlex vào lúc <?= date('H:i d/m/Y') ?></p>
          </div>
        </div>
      </div>
    </div>
<script>
function closeInv(e){
  if(e.target===document.getElementById('inv-overlay')){
    document.getElementById('inv-overlay').classList.remove('open');
  }
}
</script>
</body>
</html>
