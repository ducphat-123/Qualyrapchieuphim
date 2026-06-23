<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../../be/config/db.php';

$showtime_id = (int)($_GET['showtime_id'] ?? 0);
$seatsParam  = $_GET['seats'] ?? '';
$clientTotal = (int)($_GET['total'] ?? 0);
if (!$showtime_id || !$seatsParam) { header('Location: home.php'); exit; }

// Parse seats
$seatGroups = explode('|', $seatsParam);
$allSeats = [];
foreach ($seatGroups as $g) {
    foreach (explode(',', $g) as $s) {
        if (trim($s)) $allSeats[] = trim($s);
    }
}

$show = $pdo->prepare("
  SELECT s.*, m.title, m.duration_min, m.poster_url, m.age_rating, m.id as movie_id,
         c.name as cinema_name
  FROM showtimes s
  JOIN movies m ON s.movie_id = m.id
  JOIN cinemas c ON s.cinema_id = c.id
  WHERE s.id = ? LIMIT 1
");
$show->execute([$showtime_id]);
$show = $show->fetch();
if (!$show) { header('Location: home.php'); exit; }

// Chặn thanh toán nếu suất chiếu đã bị hủy hoặc bắt đầu quá 20 phút
if ($show['is_cancelled'] == 1) {
    header('Location: home.php');
    exit;
}

$showtime_dt = strtotime($show['show_date'] . ' ' . $show['start_time']);
if ($showtime_dt + 20 * 60 < time()) {
    header('Location: home.php');
    exit;
}

// Vouchers
$vQuery = $pdo->prepare("
  SELECT v.* 
  FROM vouchers v
  WHERE v.is_active=1 
    AND (v.expire_date IS NULL OR v.expire_date >= CURDATE())
    AND v.user_id = ? 
    AND v.used_count < v.max_uses
  LIMIT 20
");
$vQuery->execute([$_SESSION['user_id']]);
$vouchers = $vQuery->fetchAll();

// Snacks
$snacks = $pdo->query("SELECT * FROM snacks ORDER BY category, price")->fetchAll();

// Server-side price recalc
$vipRows = ['E','F','G'];
$basePrice = $show['price'];
$serverTotal = 0;
foreach ($seatGroups as $g) {
    $parts = explode(',', $g);
    $row = substr(trim($parts[0]),0,1);
    if (count($parts) === 2) { // sweetbox
        $serverTotal += $basePrice * 2;
    } elseif (in_array($row, $vipRows)) {
        $serverTotal += round($basePrice * 1.3);
    } else {
        $serverTotal += $basePrice;
    }
}

// POST: process booking
$error = ''; $success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucherCode  = trim($_POST['voucher_code'] ?? '');
    $payMethod    = $_POST['payment_method'] ?? 'momo';
    $snacksJson   = $_POST['snacks_json'] ?? '[]';
    $snacksTotal  = (int)($_POST['snacks_total'] ?? 0);

    $discount = 0;
    if ($voucherCode) {
        $v = $pdo->prepare("
          SELECT * FROM vouchers 
          WHERE code=? 
            AND is_active=1 
            AND (expire_date IS NULL OR expire_date>=CURDATE()) 
            AND used_count<max_uses 
            AND user_id=?
          LIMIT 1
        ");
        $v->execute([$voucherCode, $_SESSION['user_id']]);
        $voucher = $v->fetch();
        if ($voucher) {
            // Check if the master template for this voucher is active
            $parts = explode('-', $voucherCode);
            $prefix = $parts[0];
            $parent = $pdo->prepare("SELECT is_active FROM vouchers WHERE code = ? AND user_id IS NULL LIMIT 1");
            $parent->execute([$prefix]);
            $parentActive = $parent->fetch();
            
            if ($parentActive && $parentActive['is_active'] == 0) {
                $error = 'Mã voucher này đã hết hạn hoặc tạm dừng áp dụng.';
            } else {
                // Check if current user has already used this voucher
                $used = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id=? AND voucher_code=? AND status!='cancelled'");
                $used->execute([$_SESSION['user_id'], $voucherCode]);
                if ($used->fetchColumn() > 0) {
                    $error = 'Bạn đã sử dụng mã voucher này cho một giao dịch trước đó.';
                } else {
                    // GIFTPOP = voucher đổi điểm, chỉ giảm tiền bắp nước, KHÔNG giảm tiền vé
                    $isSnackVoucher = str_starts_with(strtoupper($voucherCode), 'GIFTPOP');
                    if ($isSnackVoucher) {
                        // Chỉ giảm tối đa bằng snacksTotal, không được giảm tiền vé
                        $discount = min((int)$voucher['discount_amt'], max(0, $snacksTotal));
                    } elseif ($voucher['discount_pct']) {
                        // Kiểm tra đơn tối thiểu
                        if ($serverTotal + $snacksTotal >= $voucher['min_order']) {
                            $discount = round(($serverTotal + $snacksTotal) * $voucher['discount_pct'] / 100);
                        } else {
                            $error = 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã này.';
                        }
                    } elseif ($voucher['discount_amt']) {
                        $discount = min((int)$voucher['discount_amt'], $serverTotal + $snacksTotal);
                    }
                }
            }
        } else {
            $error = 'Mã voucher không hợp lệ, đã hết lượt dùng hoặc đã hết hạn.';
        }
    }

    if (!$error) {
        $transaction_id = 'TX-' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        $first_booking_code = null;
        $accumulated_discount = 0;
        $n = count($seatGroups);

        try {
            $pdo->beginTransaction();

            $ins = $pdo->prepare("
                INSERT INTO bookings (
                    booking_code, user_id, showtime_id, seats_json, num_tickets, 
                    subtotal, discount, total_amount, payment_method, 
                    payment_status, status, voucher_code, transaction_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'confirmed', ?, ?)
            ");

            foreach ($seatGroups as $idx => $g) {
                $parts = explode(',', $g);
                $clean_parts = array_map('trim', $parts);
                $row = substr($clean_parts[0], 0, 1);
                
                if (count($clean_parts) === 2) { // sweetbox couple seat
                    $seat_subtotal = $basePrice * 2;
                    $ticket_count = 2;
                } elseif (in_array($row, $vipRows)) {
                    $seat_subtotal = round($basePrice * 1.3);
                    $ticket_count = 1;
                } else {
                    $seat_subtotal = $basePrice;
                    $ticket_count = 1;
                }

                // Proportional discount allocation
                if ($idx === $n - 1) {
                    $seat_discount = $discount - $accumulated_discount;
                } else {
                    $seat_discount = round($discount * ($seat_subtotal / $serverTotal));
                }
                $accumulated_discount += $seat_discount;

                // Add snacks total to first ticket
                $seat_total = $seat_subtotal - $seat_discount;
                if ($idx === 0) {
                    $seat_total += $snacksTotal;
                }
                $seat_total = max(0, $seat_total);

                $code = 'MF' . date('Ymd') . strtoupper(substr(md5(uniqid(microtime(), true)), -6));
                if ($idx === 0) {
                    $first_booking_code = $code;
                }

                $ins->execute([
                    $code,
                    $_SESSION['user_id'],
                    $showtime_id,
                    json_encode($clean_parts),
                    $ticket_count,
                    $seat_subtotal,
                    $seat_discount,
                    $seat_total,
                    $payMethod,
                    $voucherCode ?: null,
                    $transaction_id
                ]);
            }

            // Update showtimes available seats
            $pdo->prepare("UPDATE showtimes SET available_seats = available_seats - ? WHERE id=?")->execute([count($allSeats), $showtime_id]);
            
            if ($voucherCode && isset($voucher)) {
                $pdo->prepare("UPDATE vouchers SET used_count=used_count+1 WHERE code=?")->execute([$voucherCode]);
            }

            $pdo->commit();
            header("Location: booking-confirm.php?code=$first_booking_code"); exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Đặt vé thất bại. Vui lòng thử lại. Lỗi: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Thanh toán - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--blue:#2563EB;--bg:#F1F5F9;--card:#fff;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--r:14px;--green:#22C55E}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.topbar{background:var(--card);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 24px;gap:16px;position:sticky;top:0;z-index:100;box-shadow:0 1px 8px rgba(15,23,42,.06)}
.logo{display:flex;align-items:center;gap:9px;text-decoration:none}
.logo-icon{width:30px;height:30px;background:var(--blue);border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px}
.logo-name{font-size:16px;font-weight:800;color:var(--blue)}
.step-bar{display:flex;align-items:center;gap:0;flex:1;justify-content:center}
.step{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--muted)}
.step.active{color:var(--blue)}.step.done{color:var(--green)}
.step-num{width:24px;height:24px;border-radius:50%;border:2px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700}
.step.done .step-num{background:var(--green);border-color:var(--green);color:#fff}
.step.active .step-num{background:var(--blue);border-color:var(--blue);color:#fff}
.step-line{width:40px;height:2px;background:var(--border);margin:0 8px}
.step-line.done{background:var(--green)}
.layout{display:grid;grid-template-columns:1fr 360px;gap:20px;padding:24px;max-width:1100px;margin:0 auto}
.card{background:var(--card);border-radius:var(--r);box-shadow:0 2px 16px rgba(15,23,42,.07);margin-bottom:16px;overflow:hidden}
.card-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:9px}
.card-head h3{font-size:15px;font-weight:700}
.card-head .icon{width:32px;height:32px;border-radius:8px;background:#EFF6FF;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:14px}
.card-body{padding:18px 20px}
/* ORDER SUMMARY */
.order-movie{display:flex;gap:14px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border)}
.op{width:60px;height:85px;border-radius:9px;object-fit:cover;flex-shrink:0;background:#e2e8f0}
.op-ph{width:60px;height:85px;border-radius:9px;flex-shrink:0;background:linear-gradient(135deg,#334155,#1e293b);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:20px}
.om-info h4{font-size:15px;font-weight:700;margin-bottom:6px}
.om-meta{font-size:13px;color:var(--muted);line-height:1.7}
.seats-row{display:flex;flex-wrap:wrap;gap:6px;margin:10px 0}
.seat-tag{background:#EFF6FF;color:var(--blue);font-size:12px;font-weight:700;padding:3px 9px;border-radius:6px;border:1px solid #BFDBFE}
/* SNACKS */
.snack-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.snack-card{border:1.5px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;transition:all .2s;position:relative}
.snack-card:hover{border-color:var(--blue)}
.snack-card.selected{border-color:var(--blue);background:#EFF6FF}
.snack-card.selected::after{content:'✓';position:absolute;top:8px;right:10px;color:var(--blue);font-weight:800;font-size:13px}
.snack-name{font-size:13px;font-weight:600;margin-bottom:3px}
.snack-price{font-size:13px;font-weight:700;color:var(--blue)}
/* VOUCHER */
.voucher-row{display:flex;gap:8px}
.voucher-input{flex:1;height:42px;border:1.5px solid var(--border);border-radius:10px;padding:0 14px;font-size:14px;font-family:inherit;outline:none;transition:border-color .2s}
.voucher-input:focus{border-color:var(--blue)}
.btn-apply{height:42px;padding:0 18px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s;white-space:nowrap}
.btn-apply:hover{background:#1D4ED8}
.voucher-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.vtag{padding:4px 10px;border-radius:6px;border:1.5px dashed var(--border);font-size:12px;font-weight:600;cursor:pointer;color:var(--muted);transition:all .2s}
.vtag:hover{border-color:var(--blue);color:var(--blue)}
.vtag.applied{border-color:var(--green);color:var(--green);background:#F0FDF4}
/* PAYMENT */
.pay-methods{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.pay-opt{display:flex;align-items:center;gap:10px;padding:12px 14px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:all .2s}
.pay-opt.selected,.pay-opt:hover{border-color:var(--blue)}
.pay-opt input[type=radio]{accent-color:var(--blue)}
.pay-icon{width:32px;height:32px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:16px}
.pay-name{font-size:13.5px;font-weight:600}
/* SUMMARY */
.sum-section{position:sticky;top:76px}
.sum-rows{padding:16px 20px}
.sum-row{display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:10px}
.sum-label{color:var(--muted)}
.sum-val{font-weight:600}
.sum-discount{color:var(--green)}
.sum-divider{height:1px;background:var(--border);margin:12px 0}
.sum-total{display:flex;justify-content:space-between;font-size:17px;font-weight:800;padding:0 20px 16px}
.sum-total-price{color:var(--blue)}
.btn-pay{width:calc(100% - 40px);margin:0 20px 20px;height:48px;background:var(--blue);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .2s}
.btn-pay:hover{background:#1D4ED8}
.alert-err{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;font-size:13.5px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.info-note{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:5px;margin-top:10px}
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
    <div class="step active"><div class="step-num">3</div> Thanh toán</div>
    <div class="step-line"></div>
    <div class="step"><div class="step-num">4</div> Xác nhận</div>
  </div>
</div>

<div class="layout">
  <div>
    <?php if($error): ?>
    <div class="alert-err"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- TÓM TẮT ĐƠN -->
    <div class="card">
      <div class="card-head"><div class="icon"><i class="fa-solid fa-film"></i></div><h3>Tóm tắt đặt vé</h3></div>
      <div class="card-body">
        <div class="order-movie">
          <?php if($show['poster_url']): ?>
          <img class="op" src="<?= htmlspecialchars($show['poster_url']) ?>" alt="">
          <?php else: ?><div class="op-ph"><i class="fa-solid fa-film"></i></div><?php endif; ?>
          <div class="om-info">
            <h4><?= htmlspecialchars($show['title']) ?></h4>
            <div class="om-meta">
              📅 <?= date('d/m/Y', strtotime($show['show_date'])) ?><br>
              ⏰ <?= substr($show['start_time'],0,5) ?> &nbsp;·&nbsp; <?= $show['format'] ?> · <?= $show['subtitle_type'] ?> · <?= htmlspecialchars($show['hall_name'] ?? 'Phòng chiếu 1') ?><br>
              📍 <?= htmlspecialchars($show['cinema_name']) ?>
            </div>
          </div>
        </div>
        <div style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px">Ghế đã chọn:</div>
        <div class="seats-row">
          <?php foreach($allSeats as $s): ?>
          <span class="seat-tag"><?= htmlspecialchars($s) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- SNACKS -->
    <div class="card">
      <div class="card-head"><div class="icon"><i class="fa-solid fa-popcorn"></i></div><h3>Thêm bắp nước (tuỳ chọn)</h3></div>
      <div class="card-body">
        <div class="snack-grid" id="snack-grid">
          <?php foreach($snacks as $sn): ?>
          <div class="snack-card" data-id="<?= $sn['id'] ?>" data-price="<?= $sn['price'] ?>" data-name="<?= htmlspecialchars($sn['name']) ?>" onclick="toggleSnack(this)">
            <div class="snack-name"><?= htmlspecialchars($sn['name']) ?></div>
            <div class="snack-price"><?= number_format($sn['price'],0,',','.') ?>₫</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- VOUCHER -->
    <div class="card">
      <div class="card-head"><div class="icon"><i class="fa-solid fa-tag"></i></div><h3>Mã giảm giá</h3></div>
      <div class="card-body">
        <div class="voucher-row">
          <input class="voucher-input" type="text" id="voucher-input" placeholder="Nhập mã voucher..." maxlength="30">
          <button class="btn-apply" onclick="applyVoucher()">Áp dụng</button>
        </div>
        <div class="voucher-tags" id="voucher-tags">
          <?php foreach($vouchers as $v): ?>
          <span class="vtag" data-code="<?= htmlspecialchars($v['code']) ?>" onclick="pickVoucher(this)">
            <?= htmlspecialchars($v['code']) ?> - <?= $v['description'] ?>
          </span>
          <?php endforeach; ?>
        </div>
        <div id="voucher-msg" style="font-size:13px;margin-top:8px"></div>
      </div>
    </div>

    <!-- THANH TOÁN -->
    <div class="card">
      <div class="card-head"><div class="icon"><i class="fa-solid fa-credit-card"></i></div><h3>Phương thức thanh toán</h3></div>
      <div class="card-body">
        <div class="pay-methods" id="pay-methods">
          <label class="pay-opt selected"><input type="radio" name="pay" value="momo" checked> <div class="pay-icon" style="background:#FCE4EC">💳</div><span class="pay-name">MoMo</span></label>
          <label class="pay-opt"><input type="radio" name="pay" value="vnpay"> <div class="pay-icon" style="background:#E3F2FD">🏦</div><span class="pay-name">VNPay</span></label>
          <label class="pay-opt"><input type="radio" name="pay" value="zalopay"> <div class="pay-icon" style="background:#E8F5E9">📱</div><span class="pay-name">ZaloPay</span></label>
          <label class="pay-opt"><input type="radio" name="pay" value="cash"> <div class="pay-icon" style="background:#FFF9C4">💵</div><span class="pay-name">Tiền mặt</span></label>
        </div>
        <div class="info-note"><i class="fa-solid fa-circle-info"></i>Thanh toán an toàn và được mã hóa</div>
      </div>
    </div>
  </div>

  <!-- SUMMARY PANEL -->
  <div class="sum-section">
    <div class="card">
      <div class="card-head"><div class="icon"><i class="fa-solid fa-receipt"></i></div><h3>Chi tiết thanh toán</h3></div>
      <div class="sum-rows">
        <div class="sum-row"><span class="sum-label">Vé phim (<?= count($seatGroups) ?> ghế)</span><span class="sum-val" id="s-tickets"><?= number_format($serverTotal,0,',','.') ?>₫</span></div>
        <div class="sum-row"><span class="sum-label">Bắp nước</span><span class="sum-val" id="s-snacks">0₫</span></div>
        <div class="sum-row"><span class="sum-label">Mã giảm giá</span><span class="sum-val sum-discount" id="s-discount">−0₫</span></div>
        <div class="sum-divider"></div>
      </div>
      <div class="sum-total"><span>Tổng cộng</span><span class="sum-total-price" id="s-total"><?= number_format($serverTotal,0,',','.') ?>₫</span></div>

      <form method="POST" id="pay-form">
        <input type="hidden" name="voucher_code" id="f-voucher">
        <input type="hidden" name="payment_method" id="f-method" value="momo">
        <input type="hidden" name="snacks_json" id="f-snacks" value="[]">
        <input type="hidden" name="snacks_total" id="f-snacks-total" value="0">
        <button type="button" class="btn-pay" onclick="submitPay()"><i class="fa-solid fa-lock"></i> Xác nhận thanh toán</button>
      </form>
    </div>
  </div>
</div>

<script>
const BASE = <?= $serverTotal ?>;
let snacksTotal = 0, discount = 0, appliedVoucher = '';
let selectedSnacks = [];

const vouchers = <?= json_encode(array_map(fn($v)=>['code'=>$v['code'],'pct'=>$v['discount_pct'],'amt'=>$v['discount_amt'],'min'=>$v['min_order']], $vouchers)) ?>;

function toggleSnack(el) {
  el.classList.toggle('selected');
  const price = parseInt(el.dataset.price);
  if (el.classList.contains('selected')) {
    snacksTotal += price;
    selectedSnacks.push({id: el.dataset.id, name: el.dataset.name, price});
  } else {
    snacksTotal -= price;
    selectedSnacks = selectedSnacks.filter(s => s.id !== el.dataset.id);
  }
  // Tính lại discount nếu là GIFTPOP (phụ thuộc snacksTotal)
  if (appliedVoucher && appliedVoucher.startsWith('GIFTPOP')) reapplyVoucher();
  updateTotal();
}

function pickVoucher(el) {
  document.getElementById('voucher-input').value = el.dataset.code;
  applyVoucher();
}

function reapplyVoucher() {
  if (!appliedVoucher) return;
  const v = vouchers.find(x => x.code.toUpperCase() === appliedVoucher);
  if (!v) return;
  const isSnackOnly = appliedVoucher.startsWith('GIFTPOP');
  if (isSnackOnly) {
    // Chỉ giảm tối đa bằng snacksTotal
    discount = Math.min(parseInt(v.amt) || 0, snacksTotal);
  }
}

function applyVoucher() {
  const code = document.getElementById('voucher-input').value.trim().toUpperCase();
  const msg  = document.getElementById('voucher-msg');
  if (!code) { appliedVoucher=''; discount=0; updateTotal(); msg.textContent=''; return; }

  const v = vouchers.find(x => x.code.toUpperCase() === code);
  const isSnackOnly = code.startsWith('GIFTPOP'); // Voucher đổi điểm = chỉ giảm bắp nước

  if (!v) {
    msg.innerHTML = '<span style="color:#ef4444">❌ Mã không hợp lệ hoặc chưa được hỗ trợ trực tiếp</span>';
    discount = 0; appliedVoucher = '';
  } else if (!isSnackOnly && (BASE + snacksTotal) < v.min) {
    msg.innerHTML = `<span style="color:#ef4444">❌ Đơn tối thiểu ${Number(v.min).toLocaleString('vi-VN')}₫</span>`;
    discount = 0; appliedVoucher = '';
  } else if (isSnackOnly && snacksTotal === 0) {
    // Voucher bắp nước nhưng chưa chọn bắp nước
    msg.innerHTML = '<span style="color:#f59e0b">⚠️ Voucher này chỉ áp dụng cho bắp nước. Vui lòng chọn thêm bắp nước để sử dụng.</span>';
    discount = 0; appliedVoucher = code;
  } else {
    if (isSnackOnly) {
      // Chỉ giảm trong phạm vi snacksTotal, không chạm tiền vé
      discount = Math.min(parseInt(v.amt) || 0, snacksTotal);
    } else if (v.pct) {
      discount = Math.round((BASE + snacksTotal) * v.pct / 100);
    } else {
      discount = Math.min(parseInt(v.amt) || 0, BASE + snacksTotal);
    }
    appliedVoucher = code;
    const label = isSnackOnly ? '🍿 Miễn phí bắp nước! Giảm' : '✅ Áp dụng thành công! Giảm';
    msg.innerHTML = `<span style="color:#22C55E">${label} ${Number(discount).toLocaleString('vi-VN')}₫</span>`;
    document.querySelectorAll('.vtag').forEach(t => {
      t.classList.toggle('applied', t.dataset.code.toUpperCase() === code);
    });
  }
  updateTotal();
}

function updateTotal() {
  const total = Math.max(0, BASE + snacksTotal - discount);
  document.getElementById('s-snacks').textContent = snacksTotal.toLocaleString('vi-VN') + '₫';
  document.getElementById('s-discount').textContent = discount > 0 ? '−' + discount.toLocaleString('vi-VN') + '₫' : '−0₫';
  document.getElementById('s-total').textContent = total.toLocaleString('vi-VN') + '₫';
}

document.querySelectorAll('.pay-opt').forEach(opt => {
  opt.addEventListener('click', function() {
    document.querySelectorAll('.pay-opt').forEach(o => o.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('f-method').value = this.querySelector('input').value;
  });
});

function submitPay() {
  document.getElementById('f-voucher').value = appliedVoucher;
  document.getElementById('f-snacks').value = JSON.stringify(selectedSnacks);
  document.getElementById('f-snacks-total').value = snacksTotal;
  document.getElementById('f-method').value = document.querySelector('input[name="pay"]:checked')?.value || 'momo';
  document.getElementById('pay-form').submit();
}
</script>
</body>
</html>
