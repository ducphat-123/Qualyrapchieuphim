<?php
$active_page = 'vouchers';
require_once __DIR__ . '/../../be/config/db.php';
session_start();

if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$uid = $_SESSION['user_id'];

// Database migration: Ensure 'user_id' column exists in 'vouchers' table
try {
    $pdo->query("SELECT user_id FROM vouchers LIMIT 1");
} catch (Exception $e) {
    $pdo->query("ALTER TABLE vouchers ADD COLUMN user_id INT UNSIGNED DEFAULT NULL");
}

// Get user member tier and points
$user = $pdo->prepare("SELECT member_tier, loyalty_points FROM users WHERE id = ? LIMIT 1");
$user->execute([$uid]);
$user = $user->fetch();

$user_points = (int)($user['loyalty_points'] ?? 0);

// Fetch active status of redemption templates and welcome templates from database
$templates = $pdo->query("
    SELECT code, is_active FROM vouchers 
    WHERE code IN ('REDM30K', 'REDM50K', 'REDM100K', 'GIFTPOP', 'SUMMER30', 'NEWUSER50', 'MOVIE20') 
      AND user_id IS NULL
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Auto-create global templates if they don't exist yet
if (count($templates) < 7) {
    $defaults = [
        'REDM30K'   => ['desc' => '[Chương trình đổi thưởng] Voucher giảm giá 30.000₫', 'pct' => 0, 'amt' => 30000, 'min' => 0],
        'REDM50K'   => ['desc' => '[Chương trình đổi thưởng] Voucher giảm giá 50.000₫', 'pct' => 0, 'amt' => 50000, 'min' => 0],
        'REDM100K'  => ['desc' => '[Chương trình đổi thưởng] Voucher giảm giá 100.000₫', 'pct' => 0, 'amt' => 100000, 'min' => 0],
        'GIFTPOP'   => ['desc' => '[Chương trình đổi thưởng] Combo Bắp + Nước miễn phí', 'pct' => 100, 'amt' => 0, 'min' => 0],
        'SUMMER30'  => ['desc' => 'Ưu đãi mùa hè giảm 30k', 'pct' => 0, 'amt' => 30000, 'min' => 90000],
        'NEWUSER50' => ['desc' => 'Giảm 50k cho thành viên mới', 'pct' => 0, 'amt' => 50000, 'min' => 150000],
        'MOVIE20'   => ['desc' => 'Giảm 20% cho đơn từ 100k', 'pct' => 20, 'amt' => 0, 'min' => 100000]
    ];
    foreach ($defaults as $code => $d) {
        if (!isset($templates[$code])) {
            $pdo->prepare("
                INSERT INTO vouchers (code, description, discount_pct, discount_amt, min_order, max_uses, used_count, expire_date, is_active, user_id)
                VALUES (?, ?, ?, ?, ?, 9999, 0, NULL, 1, NULL)
            ")->execute([$code, $d['desc'], $d['pct'], $d['amt'], $d['min']]);
            $templates[$code] = 1;
        }
    }
}

// Auto-seed welcome vouchers for existing user if they do not have them yet (and only if the templates are active)
$hasWelcome = $pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE user_id = ? AND (code LIKE 'SUMMER30%' OR code LIKE 'NEWUSER50%' OR code LIKE 'MOVIE20%')");
$hasWelcome->execute([$uid]);
if ($hasWelcome->fetchColumn() == 0) {
    $welcome_templates = ['SUMMER30', 'NEWUSER50', 'MOVIE20'];
    foreach ($welcome_templates as $code) {
        if (isset($templates[$code]) && $templates[$code] == 1) {
            $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? AND user_id IS NULL LIMIT 1");
            $stmt->execute([$code]);
            $t = $stmt->fetch();
            if ($t) {
                $uniq = strtoupper(substr(uniqid(), -6));
                $vcode = $code . '-' . $uniq;
                $ins = $pdo->prepare("
                    INSERT INTO vouchers (code, description, discount_pct, discount_amt, min_order, max_uses, used_count, expire_date, is_active, user_id)
                    VALUES (?, ?, ?, ?, ?, 1, 0, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, ?)
                ");
                $ins->execute([$vcode, $t['description'], $t['discount_pct'], $t['discount_amt'], $t['min_order'], $uid]);
            }
        }
    }
}

$rewards = [
    [
        'id' => 'r1',
        'name' => 'Voucher giảm giá 30.000₫',
        'desc' => 'Áp dụng cho mọi hóa đơn đặt vé hoặc combo bắp nước.',
        'points' => 15,
        'type' => 'discount',
        'value' => 30000,
        'code_prefix' => 'REDM30K',
        'is_active' => (int)($templates['REDM30K'] ?? 1)
    ],
    [
        'id' => 'r2',
        'name' => 'Voucher giảm giá 50.000₫',
        'desc' => 'Áp dụng cho mọi hóa đơn đặt vé hoặc combo bắp nước.',
        'points' => 25,
        'type' => 'discount',
        'value' => 50000,
        'code_prefix' => 'REDM50K',
        'is_active' => (int)($templates['REDM50K'] ?? 1)
    ],
    [
        'id' => 'r3',
        'name' => 'Voucher giảm giá 100.000₫',
        'desc' => 'Món quà đặc biệt dành cho thành viên tích cực.',
        'points' => 50,
        'type' => 'discount',
        'value' => 100000,
        'code_prefix' => 'REDM100K',
        'is_active' => (int)($templates['REDM100K'] ?? 1)
    ],
    [
        'id' => 'r4',
        'name' => 'Combo Bắp + Nước miễn phí',
        'desc' => 'Quy đổi lấy 1 bắp cỡ L và 1 nước cỡ L tại quầy rạp.',
        'points' => 35,
        'type' => 'gift',
        'value' => 'Bắp L + Pepsi L',
        'code_prefix' => 'GIFTPOP',
        'is_active' => (int)($templates['GIFTPOP'] ?? 1)
    ]
];

// Handle Point Redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'redeem_gift') {
    $rid = $_POST['reward_id'] ?? '';
    $selected_reward = null;
    foreach ($rewards as $r) {
        if ($r['id'] === $rid) {
            $selected_reward = $r;
            break;
        }
    }
    
    if ($selected_reward) {
        if (!$selected_reward['is_active']) {
            $_SESSION['redeem_error'] = "Chương trình đổi thưởng này hiện đang tạm dừng.";
            header("Location: vouchers.php"); exit;
        }

        $cost = $selected_reward['points'];
        if ($user_points >= $cost) {
            try {
                $pdo->beginTransaction();
                
                // Deduct user points
                $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points - ? WHERE id = ?")->execute([$cost, $uid]);
                
                // Generate a unique voucher code
                $uniq = strtoupper(substr(uniqid(), -6));
                $vcode = $selected_reward['code_prefix'] . '-' . $uniq;
                
                $desc = 'Đổi thưởng (' . $cost . ' điểm): ' . $selected_reward['name'];
                
                if ($selected_reward['type'] === 'discount') {
                    $ins = $pdo->prepare("
                      INSERT INTO vouchers (code, description, discount_pct, discount_amt, min_order, max_uses, used_count, expire_date, is_active, user_id)
                      VALUES (?, ?, 0, ?, 0, 1, 0, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, ?)
                    ");
                    $ins->execute([$vcode, $desc, $selected_reward['value'], $uid]);
                } else {
                    $ins = $pdo->prepare("
                      INSERT INTO vouchers (code, description, discount_pct, discount_amt, min_order, max_uses, used_count, expire_date, is_active, user_id)
                      VALUES (?, ?, 100, 0, 0, 1, 0, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, ?)
                    ");
                    $ins->execute([$vcode, $desc, $uid]);
                }
                
                $pdo->commit();
                $_SESSION['redeem_success'] = "Đổi quà thành công! Mã Voucher " . $vcode . " đã được thêm vào tài khoản của bạn.";
                header("Location: vouchers.php"); exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['redeem_error'] = "Có lỗi xảy ra khi đổi điểm. Vui lòng thử lại.";
                header("Location: vouchers.php"); exit;
            }
        } else {
            $_SESSION['redeem_error'] = "Bạn không có đủ điểm để đổi món quà này.";
            header("Location: vouchers.php"); exit;
        }
    }
}

// Fetch only the user's active personal vouchers from their wallet
$vouchers = $pdo->prepare("
  SELECT v.* 
  FROM vouchers v
  WHERE v.is_active=1 
    AND (v.expire_date IS NULL OR v.expire_date >= CURDATE())
    AND v.user_id = ? 
    AND v.used_count < v.max_uses
  ORDER BY v.id DESC
");
$vouchers->execute([$uid]);
$vouchers = $vouchers->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Voucher & Ưu đãi - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --blue:#2563EB;
  --bg:#F1F5F9;
  --card:#fff;
  --text:#0F172A;
  --muted:#64748B;
  --light:#94A3B8;
  --border:#E2E8F0;
  --r:14px;
  --sh:0 2px 16px rgba(15,23,42,.08);
  --sbw:240px;
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}

/* MAIN */
.main{margin-left:var(--sbw);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:all .3s}
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 28px;height:64px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50}
.topbar h1{font-size:18px;font-weight:800}

/* CONTENT */
.content{padding:24px 28px;flex:1;display:grid;grid-template-columns:1fr 340px;gap:24px;max-width:1400px;margin:0 auto;width:100%}

/* BANNER HERO */
.banner-hero{background:linear-gradient(135deg, #2563EB, #7C3AED);color:#fff;border-radius:var(--r);padding:32px;margin-bottom:24px;box-shadow:var(--sh);grid-column:1 / -1;display:flex;justify-content:space-between;align-items:center;gap:24px;position:relative;overflow:hidden}
.banner-hero::before{content:'';position:absolute;width:150px;height:150px;background:rgba(255,255,255,.05);border-radius:50%;top:-30px;right:-30px}
.banner-content h2{font-size:24px;font-weight:800;margin-bottom:8px;letter-spacing:-0.5px}
.banner-content p{font-size:14px;opacity:0.9;max-width:500px;line-height:1.5}
.banner-badge{background:rgba(255,255,255,.2);padding:10px 20px;border-radius:24px;font-size:13.5px;font-weight:700;backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,.3)}

/* VOUCHERS GRID */
.vouchers-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
.voucher-card { display: flex; background: var(--card); border-radius: 12px; border: 1.5px solid var(--border); overflow: hidden; position: relative; box-shadow: 0 2px 10px rgba(15,23,42,.03); }
.voucher-left { background: linear-gradient(135deg, var(--blue), #3B82F6); color: #fff; width: 120px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 14px; text-align: center; position: relative; flex-shrink: 0; }
.voucher-left::after { content: ''; position: absolute; right: -5px; top: 0; bottom: 0; width: 10px; background-image: radial-gradient(circle at 10px 5px, var(--bg) 4px, transparent 4px); background-size: 10px 10px; background-position: right top; }
.v-val { font-size: 22px; font-weight: 800; }
.v-type { font-size: 10.5px; opacity: .9; margin-top: 2px; font-weight: 700; text-transform: uppercase; }
.voucher-right { flex: 1; padding: 16px 20px; display: flex; flex-direction: column; justify-content: space-between; }
.v-code-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.v-code { font-family: monospace; font-size: 15px; font-weight: 700; color: var(--blue); background: #EFF6FF; padding: 3px 10px; border-radius: 6px; border: 1.5px dashed #BFDBFE; width: fit-content; letter-spacing: 0.5px; }
.v-desc { font-size: 14px; font-weight: 700; color: var(--text); line-height: 1.4; margin-bottom: 8px; }
.v-meta { font-size: 11.5px; color: var(--muted); display: flex; align-items: center; gap: 14px; }
.v-copy-btn { border: none; background: var(--blue); color: #fff; font-size: 11.5px; font-weight: 700; padding: 6px 14px; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
.v-copy-btn:hover { background: #1D4ED8; }
.v-copy-btn.copied { background: #10B981; }

/* PRIVILEGE SIDE CARD */
.privilege-card{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);padding:24px;position:sticky;top:88px}
.privilege-head{font-size:15px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.privilege-head i{color:#F59E0B}
.privilege-list{display:flex;flex-direction:column;gap:14px}
.privilege-item{display:flex;gap:12px;align-items:flex-start}
.privilege-icon{width:28px;height:28px;border-radius:6px;background:#FEF3C7;color:#D97706;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;margin-top:2px}
.privilege-info h4{font-size:13px;font-weight:700;margin-bottom:2px}
.privilege-info p{font-size:11.5px;color:var(--muted);line-height:1.4}

@media(max-width:992px){
  .content{grid-template-columns:1fr}
  .privilege-card{position:static}
}
@media(max-width:768px){
  .main{margin-left:0}
}
/* MODAL & OVERLAY */
.overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.8);z-index:200;align-items:center;justify-content:center}
.overlay.show{display:flex}
.modal{background:var(--card);border-radius:18px;padding:28px;max-width:380px;width:100%;box-shadow:0 16px 48px rgba(0,0,0,.4);text-align:center}
.modal-btns{display:flex;gap:10px}
.mbtn{flex:1;height:42px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;border:none;font-family:inherit;transition:all .2s}
.mbtn-cancel{background:var(--bg);color:var(--text)}
.mbtn-confirm{background:#10B981;color:#fff}

/* ALERT BANNER */
.alert{display:flex;align-items:center;gap:10px;padding:14px 18px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,.03);grid-column:1 / -1}
.alert-success{background:#F0FDF4;color:#16A34A;border:1px solid #BBF7D0}
.alert-error{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
</style>
</head>
<body>

<?php include __DIR__ . '/../components/sidebar.php'; ?>

<!-- MAIN -->
<div class="main">
  <!-- TOPBAR -->
  <div class="topbar">
    <h1><i class="fa-solid fa-tag" style="color:var(--blue);margin-right:8px"></i>Voucher & Khuyến mãi</h1>
  </div>

  <!-- CONTENT -->
  <div class="content">
    
    <?php if(isset($_SESSION['redeem_success'])): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check" style="font-size: 16px; margin-right: 4px;"></i>
        <span><?= $_SESSION['redeem_success'] ?></span>
      </div>
      <?php unset($_SESSION['redeem_success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['redeem_error'])): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation" style="font-size: 16px; margin-right: 4px;"></i>
        <span><?= $_SESSION['redeem_error'] ?></span>
      </div>
      <?php unset($_SESSION['redeem_error']); ?>
    <?php endif; ?>

    <!-- BANNER HERO -->
    <div class="banner-hero">
      <div class="banner-content">
        <h2>Săn Voucher xem phim cực đã!</h2>
        <p>Hạng hội viên hiện tại: <b><?= htmlspecialchars($user['member_tier'] ?? 'STANDARD') ?></b> (<?= number_format($user['loyalty_points'] ?? 0) ?> điểm). Tích lũy điểm khi mua vé để thăng hạng và nhận thêm nhiều ưu đãi độc quyền.</p>
      </div>
      <div class="banner-badge">
        <i class="fa-solid fa-gem" style="margin-right:6px"></i>Đặc quyền hội viên
      </div>
    </div>

    <!-- LEFT VOUCHER LIST -->
    <div>
      <div style="background:var(--card);border-radius:var(--r);box-shadow:var(--sh);padding:24px;margin-bottom:24px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:14px">
          <i class="fa-solid fa-tags" style="color:var(--blue);font-size:18px"></i>
          <h2 style="font-size:16px;font-weight:800">Mã giảm giá đang diễn ra</h2>
        </div>
        
        <?php if(empty($vouchers)): ?>
          <div style="text-align:center;padding:60px 20px;color:var(--muted)">
            <i class="fa-solid fa-ticket-simple" style="font-size:48px;opacity:.2;display:block;margin-bottom:12px"></i>
            <h3 style="font-size:15px;font-weight:700">Chưa có voucher khả dụng</h3>
            <p style="font-size:12.5px;margin-top:4px">Vui lòng quay lại sau để đón nhận các ưu đãi mới từ MovieFlex.</p>
          </div>
        <?php else: ?>
          <div class="vouchers-grid">
            <?php foreach($vouchers as $v):
              $valText = $v['discount_pct'] > 0 ? $v['discount_pct'] . '%' : number_format($v['discount_amt']/1000) . 'K';
              $typeText = $v['discount_pct'] > 0 ? 'GIẢM GIÁ' : 'TIỀN MẶT';
            ?>
              <div class="voucher-card">
                <div class="voucher-left">
                  <span class="v-val"><?= $valText ?></span>
                  <span class="v-type"><?= $typeText ?></span>
                </div>
                <div class="voucher-right">
                  <div class="v-code-row">
                    <span class="v-code"><?= htmlspecialchars($v['code']) ?></span>
                    <button class="v-copy-btn" onclick="copyVoucherCode(this, '<?= htmlspecialchars($v['code']) ?>')">
                      <i class="fa-regular fa-copy"></i> Sao chép mã
                    </button>
                  </div>
                  <div class="v-desc"><?= htmlspecialchars($v['description']) ?></div>
                  <div class="v-meta">
                    <span><i class="fa-regular fa-clock" style="margin-right:4px"></i>HSD: <?= $v['expire_date'] ? date('d/m/Y', strtotime($v['expire_date'])) : 'Vô thời hạn' ?></span>
                    <span><i class="fa-solid fa-circle-info" style="margin-right:4px"></i>Đơn tối thiểu: <?= number_format($v['min_order'],0,',','.') ?>₫</span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- CỬA HÀNG ĐỔI ĐIỂM -->
      <div style="background:var(--card);border-radius:var(--r);box-shadow:var(--sh);padding:24px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:14px">
          <i class="fa-solid fa-gift" style="color:#10B981;font-size:18px"></i>
          <h2 style="font-size:16px;font-weight:800">Cửa hàng đổi quà tích lũy</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
          <?php foreach($rewards as $r):
            $canRedeem = ($user_points >= $r['points']);
            $ptsBadge = '⭐ ' . $r['points'] . ' điểm';
            $isActive = (bool)($r['is_active']);
          ?>
            <div style="border: 1.5px solid <?= $isActive ? 'var(--border)' : '#FCA5A5' ?>; border-radius: 12px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between; position: relative; background: <?= $isActive ? '#FAFBFD' : '#FEF2F2' ?>; transition: all 0.2s; opacity: <?= $isActive ? '1' : '0.85' ?>;" 
                 <?= $isActive ? 'onmouseover="this.style.borderColor=\'#10B981\'; this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.borderColor=\'var(--border)\'; this.style.transform=\'none\'"' : '' ?>>
              <div>
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                  <?php if($isActive): ?>
                    <span style="font-size: 11px; font-weight: 800; background: #ECFDF5; color: #10B981; padding: 4px 10px; border-radius: 20px; border: 1px solid #A7F3D0;"><?= $ptsBadge ?></span>
                  <?php else: ?>
                    <span style="font-size: 11px; font-weight: 800; background: #FEE2E2; color: #EF4444; padding: 4px 10px; border-radius: 20px; border: 1px solid #FCA5A5;"><?= $ptsBadge ?> (Tắt)</span>
                  <?php endif; ?>
                  <span style="font-size: 20px; opacity: <?= $isActive ? '1' : '0.5' ?>;"><?= $r['type']==='discount'?'🎟️':'🍿' ?></span>
                </div>
                <h3 style="font-size: 14.5px; font-weight: 800; color: <?= $isActive ? 'var(--text)' : '#991B1B' ?>; margin-bottom: 6px;"><?= htmlspecialchars($r['name']) ?></h3>
                <p style="font-size: 12.5px; color: <?= $isActive ? 'var(--muted)' : '#94A3B8' ?>; line-height: 1.4; margin-bottom: 16px;"><?= htmlspecialchars($r['desc']) ?></p>
              </div>
              
              <?php if(!$isActive): ?>
                <button disabled style="width: 100%; border: none; background: #F3F4F6; color: #9CA3AF; font-size: 13px; font-weight: 700; height: 36px; border-radius: 8px; cursor: not-allowed;">Voucher hết hàng</button>
              <?php elseif($canRedeem): ?>
                <button onclick="confirmRedeem('<?= $r['id'] ?>', '<?= htmlspecialchars($r['name']) ?>', <?= $r['points'] ?>)" style="width: 100%; border: none; background: #10B981; color: #fff; font-size: 13px; font-weight: 700; height: 36px; border-radius: 8px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10B981'">Đổi quà ngay</button>
              <?php else: ?>
                <button disabled style="width: 100%; border: none; background: #E2E8F0; color: #94A3B8; font-size: 13px; font-weight: 700; height: 36px; border-radius: 8px; cursor: not-allowed;">Chưa đủ điểm</button>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- RIGHT PRIVILEGE CARD -->
    <div>
      <div class="privilege-card">
        <div class="privilege-head">
          <i class="fa-solid fa-award"></i>
          <span>Lợi ích theo cấp bậc</span>
        </div>
        
        <div class="privilege-list">
          <div class="privilege-item">
            <div class="privilege-icon">🥈</div>
            <div class="privilege-info">
              <h4>Silver (1.000 điểm)</h4>
              <p>Tặng 1 voucher giảm 20k vào ngày sinh nhật. Tích lũy điểm thưởng nhanh hơn 1.1x.</p>
            </div>
          </div>

          <div class="privilege-item">
            <div class="privilege-icon">🥇</div>
            <div class="privilege-info">
              <h4>Gold (5.000 điểm)</h4>
              <p>Tặng 1 bắp + 1 nước miễn phí mỗi tháng. Voucher sinh nhật giảm 50k. Tích lũy 1.2x.</p>
            </div>
          </div>

          <div class="privilege-item">
            <div class="privilege-icon">💎</div>
            <div class="privilege-info">
              <h4>Platinum (10.000 điểm)</h4>
              <p>2 vé phim miễn phí/tháng. Phòng chờ VIP tại cụm rạp chính. Tích lũy điểm thưởng 1.5x.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Redeem Confirm Modal -->
<div class="overlay" id="redeem-overlay">
  <div class="modal" style="max-width: 400px; width: 100%; text-align: center;">
    <div style="width: 56px; height: 56px; border-radius: 50%; background: #ECFDF5; color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 16px;">
      <i class="fa-solid fa-gift"></i>
    </div>
    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin-bottom: 8px;">Xác nhận đổi quà</h3>
    <p style="font-size: 13.5px; color: var(--muted); line-height: 1.5; margin-bottom: 20px;">
      Bạn có chắc chắn muốn dùng <strong id="red-points" style="color:#10B981;"></strong> điểm để đổi phần quà này?
    </p>
    
    <div style="background: #F8FAFC; border-radius: 12px; padding: 14px; text-align: left; font-size: 13.5px; margin-bottom: 24px; border: 1.5px solid #E2E8F0;">
      <span style="color:#64748B; display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Quà tặng quy đổi:</span>
      <strong id="red-name" style="color:#0F172A; font-size:14.5px;"></strong>
    </div>

    <form method="POST" id="redeem-form">
      <input type="hidden" name="action" value="redeem_gift">
      <input type="hidden" name="reward_id" id="redeem-reward-id">
      <div class="modal-btns">
        <button type="button" class="mbtn mbtn-cancel" onclick="closeRedeem()" style="background:#F1F5F9; color:#475569;">Đóng</button>
        <button type="submit" class="mbtn mbtn-confirm" style="background:#10B981; color:#fff;">Đổi ngay</button>
      </div>
    </form>
  </div>
</div>

<script>
function copyVoucherCode(btn, code) {
  navigator.clipboard.writeText(code).then(() => {
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Đã sao chép!';
    btn.classList.add('copied');
    setTimeout(() => {
      btn.innerHTML = originalText;
      btn.classList.remove('copied');
    }, 2000);
  });
}

function confirmRedeem(id, name, points) {
  document.getElementById('redeem-reward-id').value = id;
  document.getElementById('red-name').textContent = name;
  document.getElementById('red-points').textContent = points;
  document.getElementById('redeem-overlay').classList.add('show');
}

function closeRedeem() {
  document.getElementById('redeem-overlay').classList.remove('show');
}
</script>
</body>
</html>
