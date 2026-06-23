<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../../be/config/db.php';

$showtime_id = (int)($_GET['showtime_id'] ?? 0);
if (!$showtime_id) { header('Location: home.php'); exit; }

$st = $pdo->prepare("
  SELECT s.*, m.title, m.duration_min, m.poster_url, m.age_rating,
         c.name as cinema_name, c.address
  FROM showtimes s
  JOIN movies m ON s.movie_id = m.id
  JOIN cinemas c ON s.cinema_id = c.id
  WHERE s.id = ? LIMIT 1
");
$st->execute([$showtime_id]);
$show = $st->fetch();
if (!$show) { header('Location: home.php'); exit; }

// Chặn truy cập nếu suất chiếu đã bị hủy hoặc bắt đầu quá 20 phút
if ($show['is_cancelled'] == 1) {
    header('Location: home.php');
    exit;
}

$showtime_dt = strtotime($show['show_date'] . ' ' . $show['start_time']);
if ($showtime_dt + 20 * 60 < time()) {
    header('Location: home.php');
    exit;
}

// Lấy ghế đã đặt
$booked = $pdo->prepare("
  SELECT seats_json FROM bookings
  WHERE showtime_id = ? AND status != 'cancelled' AND payment_status = 'paid'
");
$booked->execute([$showtime_id]);
$bookedSeats = [];
foreach ($booked->fetchAll() as $row) {
    $seats = json_decode($row['seats_json'], true) ?? [];
    foreach ($seats as $s) $bookedSeats[] = $s;
}

// Cấu hình phòng chiếu: A-J, 10 ghế/hàng
$rows = ['A','B','C','D','E','F','G','H','I','J'];
$cols = 10;
// VIP: hàng E,F,G; Sweetbox: cặp ghế cuối hàng J
$vipRows = ['E','F','G'];
$sweetboxPairs = [9,10]; // cặp ghế cuối
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chọn ghế - <?= htmlspecialchars($show['title']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--blue:#2563EB;--bg:#F1F5F9;--card:#fff;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--r:14px}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
/* TOPBAR */
.topbar{background:var(--card);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 24px;gap:16px;position:sticky;top:0;z-index:100;box-shadow:0 1px 8px rgba(15,23,42,.06)}
.logo{display:flex;align-items:center;gap:9px;text-decoration:none}
.logo-icon{width:30px;height:30px;background:var(--blue);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px}
.logo-name{font-size:16px;font-weight:800;color:var(--blue)}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);font-size:13.5px;font-weight:600;text-decoration:none;border:1.5px solid var(--border);padding:6px 14px;border-radius:8px;transition:all .2s}
.back-btn:hover{border-color:var(--blue);color:var(--blue)}
.step-bar{display:flex;align-items:center;gap:0;flex:1;justify-content:center}
.step{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--muted)}
.step.active{color:var(--blue)}
.step.done{color:#22C55E}
.step-num{width:24px;height:24px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700}
.step.done .step-num{background:#22C55E;border-color:#22C55E;color:#fff}
.step.active .step-num{background:var(--blue);border-color:var(--blue);color:#fff}
.step-line{width:40px;height:2px;background:var(--border);margin:0 8px}
.step-line.done{background:#22C55E}
/* MAIN LAYOUT */
.layout{display:grid;grid-template-columns:1fr 320px;gap:20px;padding:20px 24px;max-width:1200px;margin:0 auto}
/* SCREEN + SEATS */
.seat-section{background:var(--card);border-radius:var(--r);padding:24px;box-shadow:0 2px 16px rgba(15,23,42,.07)}
.screen-wrap{text-align:center;margin-bottom:28px}
.screen{height:8px;background:linear-gradient(to bottom,#94A3B8,#CBD5E1);border-radius:4px 4px 0 0;margin:0 auto 6px;width:80%;box-shadow:0 4px 16px rgba(0,0,0,.15)}
.screen-label{font-size:11px;color:var(--muted);letter-spacing:.5px;font-weight:600;text-transform:uppercase}
/* LEGEND */
.legend{display:flex;justify-content:center;gap:20px;margin-bottom:20px;flex-wrap:wrap}
.legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);font-weight:500}
.leg-box{width:18px;height:18px;border-radius:4px;border:1.5px solid var(--border)}
.leg-std{background:#F1F5F9;border-color:#CBD5E1}
.leg-vip{background:#FEF3C7;border-color:#F59E0B}
.leg-sweet{background:#FCE7F3;border-color:#EC4899}
.leg-sel{background:#2563EB;border-color:#2563EB}
.leg-taken{background:#E2E8F0;border-color:#CBD5E1;opacity:.5}
/* SEAT GRID */
.seat-grid{display:flex;flex-direction:column;gap:8px;align-items:center}
.seat-row{display:flex;align-items:center;gap:6px}
.row-label{width:20px;font-size:12px;font-weight:700;color:var(--muted);text-align:center}
.seat{width:34px;height:34px;border-radius:7px;border:1.5px solid #CBD5E1;background:#F8FAFC;cursor:pointer;font-size:11px;font-weight:700;color:var(--muted);display:flex;align-items:center;justify-content:center;transition:all .15s;user-select:none;position:relative}
.seat:hover:not(.taken){transform:scale(1.08);border-color:var(--blue);color:var(--blue)}
.seat.vip{background:#FEF9C3;border-color:#FBBF24;color:#92400E}
.seat.vip:hover:not(.taken){border-color:#F59E0B;background:#FEF3C7}
.seat.sweet{background:#FCE7F3;border-color:#F9A8D4;color:#9D174D;width:72px;border-radius:10px}
.seat.selected{background:var(--blue);border-color:var(--blue);color:#fff;transform:scale(1.05)}
.seat.taken{background:#E2E8F0;border-color:#E2E8F0;color:#CBD5E1;cursor:not-allowed;opacity:.6}
.seat.taken:hover{transform:none}
.aisle{width:16px}
/* PANEL */
.panel{background:var(--card);border-radius:var(--r);box-shadow:0 2px 16px rgba(15,23,42,.07);overflow:hidden;position:sticky;top:76px}
.panel-head{padding:18px 20px;border-bottom:1px solid var(--border);background:var(--blue)}
.panel-head h3{font-size:15px;font-weight:700;color:#fff}
.panel-head p{font-size:12px;color:rgba(255,255,255,.75);margin-top:2px}
.movie-info{padding:16px 20px;display:flex;gap:12px;border-bottom:1px solid var(--border)}
.m-poster{width:48px;height:68px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#e2e8f0}
.m-poster-ph{width:48px;height:68px;border-radius:8px;flex-shrink:0;background:linear-gradient(135deg,#334155,#1e293b);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:18px}
.m-info{flex:1}
.m-title{font-size:13.5px;font-weight:700;margin-bottom:5px;line-height:1.3}
.m-meta{font-size:12px;color:var(--muted);line-height:1.6}
.order-body{padding:16px 20px}
.order-row{display:flex;justify-content:space-between;align-items:center;font-size:13.5px;margin-bottom:10px}
.order-label{color:var(--muted)}
.order-val{font-weight:600}
.seats-chosen{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px}
.seat-tag{background:var(--blue);color:#fff;font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:6px}
.divider{height:1px;background:var(--border);margin:12px 0}
.total-row{display:flex;justify-content:space-between;align-items:center;font-size:16px;font-weight:800;margin-bottom:16px}
.total-price{color:var(--blue);font-size:20px}
.btn-continue{width:100%;height:46px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .2s}
.btn-continue:hover{background:#1D4ED8}
.btn-continue:disabled{opacity:.45;cursor:not-allowed}
.max-note{text-align:center;font-size:12px;color:var(--muted);margin-top:10px}
.ticket-count{display:flex;align-items:center;justify-content:space-between;background:var(--bg);border-radius:10px;padding:10px 14px;margin-bottom:14px}
.tc-label{font-size:13px;font-weight:600}
.tc-ctrl{display:flex;align-items:center;gap:10px}
.tc-btn{width:28px;height:28px;border-radius:8px;border:1.5px solid var(--border);background:#fff;cursor:pointer;font-size:16px;font-weight:700;display:flex;align-items:center;justify-content:center;transition:all .2s;color:var(--blue)}
.tc-btn:hover{border-color:var(--blue);background:#EFF6FF}
.tc-val{font-size:16px;font-weight:800;width:24px;text-align:center}
/* TOAST NOTIFICATION */
.toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
.toast { background: #fff; border-left: 4px solid #EF4444; box-shadow: 0 10px 25px -5px rgba(0,0,0,.1), 0 8px 10px -6px rgba(0,0,0,.1); border-radius: 8px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; transform: translateX(120%); opacity: 0; transition: all 0.4s cubic-bezier(0.21, 1.02, 0.73, 1); min-width: 300px; }
.toast.show { transform: translateX(0); opacity: 1; }
.toast-icon { background: #FEF2F2; color: #EF4444; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.toast-content { flex: 1; }
.toast-title { font-size: 14px; font-weight: 700; color: #0F172A; margin-bottom: 3px; }
.toast-desc { font-size: 13px; color: var(--muted); line-height: 1.4; }
</style>
</head>
<body>

<div class="toast-container" id="toast-container"></div>

<div class="topbar">
  <a href="home.php" class="logo"><div class="logo-icon"><i class="fa-solid fa-clapperboard"></i></div><span class="logo-name">MovieFlex</span></a>
  <a href="movie-detail.php?id=<?= $show['movie_id'] ?>" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
  <div class="step-bar">
    <div class="step done"><div class="step-num"><i class="fa-solid fa-check" style="font-size:9px"></i></div> Chọn phim</div>
    <div class="step-line done"></div>
    <div class="step active"><div class="step-num">2</div> Chọn ghế</div>
    <div class="step-line"></div>
    <div class="step"><div class="step-num">3</div> Thanh toán</div>
    <div class="step-line"></div>
    <div class="step"><div class="step-num">4</div> Xác nhận</div>
  </div>
</div>

<div class="layout">
  <!-- SEAT MAP -->
  <div class="seat-section">
    <div class="screen-wrap">
      <div class="screen"></div>
      <div class="screen-label">MÀN HÌNH</div>
    </div>

    <div class="legend">
      <div class="legend-item"><div class="leg-box leg-std"></div> Ghế thường</div>
      <div class="legend-item"><div class="leg-box leg-vip"></div> Ghế VIP</div>
      <div class="legend-item"><div class="leg-box leg-sweet"></div> Sweetbox</div>
      <div class="legend-item"><div class="leg-box leg-sel"></div> Đang chọn</div>
      <div class="legend-item"><div class="leg-box leg-taken"></div> Đã đặt</div>
    </div>

    <div class="seat-grid">
      <?php foreach($rows as $rowLetter):
        $isVip = in_array($rowLetter, $vipRows);
        $isLastRow = ($rowLetter === 'J');
      ?>
      <div class="seat-row">
        <div class="row-label"><?= $rowLetter ?></div>
        <?php
        $col = 1;
        while ($col <= $cols):
          $seatKey = $rowLetter . '-' . str_pad($col, 2, '0', STR_PAD_LEFT);
          $isTaken = in_array($seatKey, $bookedSeats);
          // Sweetbox: hàng J, cặp ghế 9-10
          if ($isLastRow && $col === 9):
            $sk2 = $rowLetter . '-' . str_pad(10, 2, '0', STR_PAD_LEFT);
            $takenSweet = $isTaken || in_array($sk2, $bookedSeats);
        ?>
          <div class="seat sweet <?= $takenSweet?'taken':'' ?>"
            data-seat="<?= $seatKey ?>"
            data-seat2="<?= $sk2 ?>"
            data-type="sweetbox"
            data-price="<?= $show['price'] * 2 ?>"
            onclick="<?= $takenSweet?'':'toggleSeat(this)' ?>">
            🛋 J9-10
          </div>
          <?php $col += 2; continue; endif; ?>

          <?php if($col === 5): ?><div class="aisle"></div><?php endif; ?>
          <div class="seat <?= $isVip?'vip':'' ?> <?= $isTaken?'taken':'' ?>"
            data-seat="<?= $seatKey ?>"
            data-type="<?= $isVip?'vip':'standard' ?>"
            data-price="<?= $isVip ? round($show['price']*1.3) : $show['price'] ?>"
            onclick="<?= $isTaken?'':'toggleSeat(this)' ?>">
            <?= $col ?>
          </div>
          <?php $col++; endwhile; ?>
        <div class="row-label"><?= $rowLetter ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ORDER PANEL -->
  <div class="panel">
    <div class="panel-head">
      <h3><i class="fa-solid fa-ticket"></i> Thông tin đặt vé</h3>
      <p>Chọn ghế để tiếp tục</p>
    </div>
    <div class="movie-info">
      <?php if($show['poster_url']): ?>
      <img class="m-poster" src="<?= htmlspecialchars($show['poster_url']) ?>" alt="">
      <?php else: ?>
      <div class="m-poster-ph"><i class="fa-solid fa-film"></i></div>
      <?php endif; ?>
      <div class="m-info">
        <div class="m-title"><?= htmlspecialchars($show['title']) ?></div>
        <div class="m-meta">
          📅 <?= date('d/m/Y', strtotime($show['show_date'])) ?><br>
          ⏰ <?= substr($show['start_time'],0,5) ?> – <?= $show['end_time'] ? substr($show['end_time'],0,5) : '—' ?><br>
          🎬 <?= $show['format'] ?> · <?= $show['subtitle_type'] ?> · <?= htmlspecialchars($show['hall_name'] ?? 'Phòng chiếu 1') ?><br>
          📍 <?= htmlspecialchars($show['cinema_name']) ?>
        </div>
      </div>
    </div>
    <div class="order-body">
      <div class="ticket-count">
        <span class="tc-label">Số vé đã chọn</span>
        <div class="tc-ctrl" style="font-weight: 800; font-size: 16px; color: var(--blue);">
          <span class="tc-val" id="qty">0</span> vé
        </div>
      </div>

      <div class="order-row">
        <span class="order-label">Ghế đã chọn</span>
      </div>
      <div class="seats-chosen" id="seats-chosen">
        <span style="color:var(--muted);font-size:13px">Chưa chọn ghế</span>
      </div>

      <div class="divider"></div>

      <div class="order-row">
        <span class="order-label">Đơn giá</span>
        <span class="order-val" id="unit-price">—</span>
      </div>
      <div class="order-row">
        <span class="order-label">Số ghế</span>
        <span class="order-val" id="seat-count">0 ghế</span>
      </div>

      <div class="divider"></div>

      <div class="total-row">
        <span>Tổng cộng</span>
        <span class="total-price" id="total-price">0 ₫</span>
      </div>

      <button class="btn-continue" id="btn-continue" disabled onclick="goCheckout()">
        <i class="fa-solid fa-arrow-right"></i> Tiếp tục thanh toán
      </button>
      <div class="max-note">Tối đa 8 ghế / lần đặt</div>
    </div>
  </div>
</div>

<script>
const MAX_SEATS = 8;
let selectedSeats = []; // [{seat, type, price}]

function showToast(title, desc) {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.innerHTML = `
    <div class="toast-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div class="toast-content">
      <div class="toast-title">${title}</div>
      <div class="toast-desc">${desc}</div>
    </div>
  `;
  container.appendChild(toast);
  
  // Trigger animation
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      toast.classList.add('show');
    });
  });
  
  // Remove after 3s
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 400);
  }, 3500);
}

function toggleSeat(el) {
  const seat  = el.dataset.seat;
  const seat2 = el.dataset.seat2 || null;
  const type  = el.dataset.type;
  const price = parseInt(el.dataset.price);

  const idx = selectedSeats.findIndex(s => s.seat === seat);
  if (idx > -1) {
    // deselect
    selectedSeats.splice(idx, 1);
    el.classList.remove('selected');
  } else {
    const seatCount = selectedSeats.reduce((a,s) => a + (s.seat2?2:1), 0);
    const adding = seat2 ? 2 : 1;
    if (seatCount + adding > MAX_SEATS) {
      showToast('Vượt quá giới hạn', `Bạn chỉ có thể chọn tối đa ${MAX_SEATS} ghế cho mỗi lần đặt.`);
      return;
    }
    selectedSeats.push({seat, seat2, type, price});
    el.classList.add('selected');
  }
  updatePanel();
}

function updatePanel() {
  const chosen = document.getElementById('seats-chosen');
  const total  = document.getElementById('total-price');
  const cnt    = document.getElementById('seat-count');
  const uPrice = document.getElementById('unit-price');
  const btn    = document.getElementById('btn-continue');
  const qtyEl  = document.getElementById('qty');

  if (!selectedSeats.length) {
    chosen.innerHTML = '<span style="color:var(--muted);font-size:13px">Chưa chọn ghế</span>';
    total.textContent = '0 ₫';
    cnt.textContent = '0 ghế';
    uPrice.textContent = '—';
    btn.disabled = true;
    if (qtyEl) qtyEl.textContent = '0';
    return;
  }

  let html = '', sum = 0, seatQty = 0;
  selectedSeats.forEach(s => {
    const label = s.seat2 ? s.seat + '+' + s.seat2 : s.seat;
    html += `<span class="seat-tag">${label}</span>`;
    sum += s.price;
    seatQty += s.seat2 ? 2 : 1;
  });
  chosen.innerHTML = html;
  cnt.textContent = seatQty + ' ghế';
  uPrice.textContent = selectedSeats.length ? Number(selectedSeats[0].price).toLocaleString('vi-VN') + '₫' : '—';
  total.textContent = sum.toLocaleString('vi-VN') + ' ₫';
  btn.disabled = false;
  if (qtyEl) qtyEl.textContent = seatQty;
}

function goCheckout() {
  if (!selectedSeats.length) return;
  const seats = selectedSeats.map(s => s.seat2 ? s.seat + ',' + s.seat2 : s.seat).join('|');
  const total = selectedSeats.reduce((a,s) => a+s.price, 0);
  window.location.href = 'checkout.php?showtime_id=<?= $showtime_id ?>&seats=' + encodeURIComponent(seats) + '&total=' + total;
}
</script>
</body>
</html>
