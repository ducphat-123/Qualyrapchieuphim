<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';
$uid = $_SESSION['user_id'];
$active_page = 'profile';

$user = $pdo->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$user->execute([$uid]);
$user = $user->fetch();

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name  = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (!$name) {
            $msg = 'Họ tên không được để trống.';
            $msgType = 'error';
        } elseif (!empty($phone) && !preg_match('/^(0[35789])[0-9]{8}$/', $phone)) {
            $msg = 'Số điện thoại không hợp lệ. Vui lòng nhập số gồm 10 chữ số (bắt đầu bằng 03, 05, 07, 08, 09).';
            $msgType = 'error';
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")->execute([$name, $phone ?: null, $uid]);
            $_SESSION['user_name'] = $name;
            $msg = 'Cập nhật hồ sơ thành công!'; $msgType = 'success';
            $user['full_name'] = $name; $user['phone'] = $phone;
        }
    }

    if ($action === 'change_password') {
        $old  = $_POST['old_password']     ?? '';
        $new  = $_POST['new_password']     ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        if (!password_verify($old, $user['password_hash'])) {
            $msg = 'Mật khẩu hiện tại không đúng.'; $msgType = 'error';
        } elseif (strlen($new) < 6) {
            $msg = 'Mật khẩu mới tối thiểu 6 ký tự.'; $msgType = 'error';
        } elseif ($new !== $conf) {
            $msg = 'Mật khẩu xác nhận không khớp.'; $msgType = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>10]);
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $uid]);
            $msg = 'Đổi mật khẩu thành công!'; $msgType = 'success';
        }
    }
}

// Thống kê
$stats = $pdo->prepare("SELECT COUNT(*) as total, SUM(total_amount) as spent FROM bookings WHERE user_id=? AND status!='cancelled'");
$stats->execute([$uid]);
$stats = $stats->fetch();

// Lấy danh sách Voucher khả dụng của người dùng (chưa dùng trong các giao dịch active)
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
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hồ sơ - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--blue:#3B82F6;--indigo:#6366F1;--sb:#0F172A;--sbw:240px;--bg:#F1F5F9;--card:#fff;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--r:16px;--sh:0 4px 24px rgba(15,23,42,.08)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}

.main{margin-left:var(--sbw);flex:1;min-height:100vh}
.topbar{background:var(--card);border-bottom:1px solid var(--border);height:64px;display:flex;align-items:center;padding:0 32px;position:sticky;top:0;z-index:50}
.topbar h1{font-size:20px;font-weight:800}
.content{padding:32px;display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start;width:100%;min-height:calc(100vh - 64px)}
.right-col{min-width:0;}
/* PROFILE HEADER */
.profile-card{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden;margin-bottom:16px}
.profile-hero{background:linear-gradient(135deg,#3B82F6 0%,#6366F1 50%,#8B5CF6 100%);padding:36px 24px;text-align:center;position:relative;overflow:hidden}
.profile-hero::before{content:'';position:absolute;top:-40px;right:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.08)}
.profile-hero::after{content:'';position:absolute;bottom:-30px;left:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.05)}
.big-avatar{width:88px;height:88px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:34px;font-weight:800;margin:0 auto 14px;border:3px solid rgba(255,255,255,.4);backdrop-filter:blur(8px);position:relative;z-index:1;box-shadow:0 8px 24px rgba(0,0,0,.2)}
.profile-name{font-size:20px;font-weight:800;color:#fff;margin-bottom:5px;position:relative;z-index:1}
.profile-email{font-size:13px;color:rgba(255,255,255,.75);position:relative;z-index:1}
.profile-tier{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;color:#fff;margin-top:10px;position:relative;z-index:1}
.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border-top:1px solid var(--border)}
.stat-item{padding:14px 16px;text-align:center;border-right:1px solid var(--border)}
.stat-item:last-child{border-right:none}
.stat-num{font-size:22px;font-weight:800;background:linear-gradient(135deg,var(--blue),var(--indigo));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.stat-label{font-size:12px;color:var(--muted);margin-top:3px;font-weight:500}
/* MENU */
.profile-menu{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden}
.pm-item{display:flex;align-items:center;gap:14px;padding:15px 18px;border-bottom:1px solid var(--border);cursor:pointer;transition:all .2s;text-decoration:none;color:var(--text)}
.pm-item:last-child{border-bottom:none}
.pm-item:hover{background:#F8FAFC;padding-left:22px}
.pm-item.active{background:#EFF6FF;color:var(--blue);border-left:3px solid var(--blue)}
.pm-icon{width:36px;height:36px;border-radius:10px;background:#F1F5F9;color:var(--muted);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;transition:all .2s}
.pm-item:hover .pm-icon,.pm-item.active .pm-icon{background:linear-gradient(135deg,var(--blue),var(--indigo));color:#fff}
.pm-label{font-size:14px;font-weight:600}
/* FORM PANEL */
.panel{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden}
.panel-head{padding:22px 26px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;background:linear-gradient(to right,#F8FAFF,#fff)}
.panel-head h2{font-size:17px;font-weight:800;color:var(--text)}
.ph-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--blue),var(--indigo));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px}
.panel-body{padding:28px}
.alert{display:flex;align-items:center;gap:10px;padding:13px 16px;border-radius:12px;font-size:13.5px;font-weight:600;margin-bottom:20px}
.alert-success{background:#F0FDF4;color:#166534;border:1px solid #BBF7D0}
.alert-error{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA}
.fg{margin-bottom:20px}
.fg label{display:block;font-size:13px;font-weight:700;color:var(--text);margin-bottom:8px}
.fg input{width:100%;height:48px;background:#F8FAFC;border:2px solid var(--border);border-radius:12px;padding:0 16px;font-size:14px;font-family:inherit;outline:none;transition:all .25s;color:var(--text);font-weight:500}
.fg input:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(59,130,246,.1)}
.fg input[disabled]{opacity:.55;cursor:not-allowed;background:#F1F5F9}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.btn-save{height:48px;padding:0 28px;background:linear-gradient(135deg,var(--blue),var(--indigo));color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:8px;transition:all .25s;box-shadow:0 4px 16px rgba(59,130,246,.3)}
.btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(59,130,246,.4)}
.points-box{background:linear-gradient(135deg,#F59E0B,#D97706,#B45309);border-radius:16px;padding:28px;color:#fff;margin-bottom:24px;position:relative;overflow:hidden}
.points-box::before{content:'★';position:absolute;right:-10px;top:-20px;font-size:120px;opacity:.08}
.points-num{font-size:44px;font-weight:800;line-height:1;letter-spacing:-2px}
.points-label{font-size:14px;opacity:.9;margin-top:6px;font-weight:600}
.points-note{font-size:12.5px;opacity:.75;margin-top:10px;background:rgba(255,255,255,.15);padding:6px 12px;border-radius:8px;display:inline-block}
.tab-btns{display:flex;gap:8px;margin-bottom:20px}
.tab-btn{padding:7px 18px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--card);color:var(--muted);transition:all .2s}
.tab-btn.active{background:var(--blue);color:#fff;border-color:var(--blue)}
.form-section{display:none}
.form-section.active{display:block}
.tip-box{background:linear-gradient(135deg,#EFF6FF,#F5F3FF);border:1px solid #C7D2FE;border-radius:14px;padding:20px 22px;margin-top:20px}
.tip-box h4{font-size:13px;font-weight:800;color:#4338CA;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.tip-item{display:flex;align-items:flex-start;gap:10px;margin-bottom:10px;font-size:13px;color:#4338CA}
.tip-item:last-child{margin-bottom:0}
.tip-item i{margin-top:2px;font-size:12px;flex-shrink:0}

/* VOUCHERS GRID */
.vouchers-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 8px; }
.voucher-card{display:flex;background:var(--card);border-radius:14px;border:1.5px solid var(--border);overflow:hidden;box-shadow:0 2px 12px rgba(15,23,42,.04);transition:transform .2s,box-shadow .2s}
.voucher-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(15,23,42,.1)}
.voucher-left{background:linear-gradient(135deg,var(--blue),var(--indigo));color:#fff;width:115px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:16px;text-align:center;position:relative;flex-shrink:0}
.voucher-left::after{content:'';position:absolute;right:-6px;top:0;bottom:0;width:12px;background-image:radial-gradient(circle at 12px 6px,var(--bg) 5px,transparent 5px);background-size:12px 12px}
.v-val{font-size:22px;font-weight:800}
.v-type{font-size:10px;opacity:.9;margin-top:3px;font-weight:700;letter-spacing:.5px}
.voucher-right{flex:1;padding:16px 20px;display:flex;flex-direction:column;justify-content:space-between}
.v-code-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.v-code{font-family:monospace;font-size:14px;font-weight:800;color:var(--blue);background:#EFF6FF;padding:4px 10px;border-radius:6px;border:1.5px dashed #93C5FD;letter-spacing:1px}
.v-desc{font-size:13.5px;font-weight:700;color:var(--text);line-height:1.4;margin-bottom:6px}
.v-meta{font-size:11.5px;color:var(--muted);display:flex;align-items:center;gap:12px}
.v-copy-btn{border:none;background:linear-gradient(135deg,var(--blue),var(--indigo));color:#fff;font-size:11.5px;font-weight:700;padding:6px 14px;border-radius:8px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:5px;box-shadow:0 2px 8px rgba(59,130,246,.3)}
.v-copy-btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(59,130,246,.4)}
.v-copy-btn.copied{background:linear-gradient(135deg,#10B981,#059669)}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main">
  <div class="topbar"><h1><i class="fa-regular fa-user" style="color:var(--blue);margin-right:8px"></i>Hồ sơ cá nhân</h1></div>
  <div class="content">
    <!-- LEFT -->
    <div>
      <div class="profile-card">
        <div class="profile-hero">
          <div class="big-avatar"><?= mb_strtoupper(mb_substr($user['full_name'],0,1)) ?></div>
          <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
          <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
          <div class="profile-tier"><i class="fa-solid fa-star"></i><?= $user['member_tier'] ?? 'STANDARD' ?></div>
        </div>
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-num"><?= $stats['total'] ?? 0 ?></div>
            <div class="stat-label">Vé đã đặt</div>
          </div>
          <div class="stat-item">
            <div class="stat-num"><?= number_format(($stats['spent']??0)/1000) ?>K</div>
            <div class="stat-label">Đã chi tiêu</div>
          </div>
        </div>
      </div>
      <div class="profile-menu">
        <div class="pm-item active" onclick="showTab('profile')"><div class="pm-icon"><i class="fa-regular fa-user"></i></div><span class="pm-label">Thông tin cá nhân</span></div>
        <div class="pm-item" onclick="showTab('password')"><div class="pm-icon"><i class="fa-solid fa-lock"></i></div><span class="pm-label">Đổi mật khẩu</span></div>
        <div class="pm-item" onclick="showTab('points')"><div class="pm-icon"><i class="fa-solid fa-star"></i></div><span class="pm-label">Điểm thưởng</span></div>
        <div class="pm-item" onclick="showTab('vouchers')"><div class="pm-icon"><i class="fa-solid fa-tag"></i></div><span class="pm-label">Voucher của tôi</span></div>
        <a href="my-tickets.php" class="pm-item"><div class="pm-icon"><i class="fa-solid fa-receipt"></i></div><span class="pm-label">Lịch sử đặt vé</span></a>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="right-col">
      <?php if($msg): ?>
      <div class="alert alert-<?= $msgType ?>"><i class="fa-solid fa-<?= $msgType==='success'?'circle-check':'circle-exclamation' ?>"></i><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <!-- PROFILE INFO -->
      <div class="panel form-section active" id="tab-profile" style="display:block">
        <div class="panel-head"><div class="ph-icon"><i class="fa-regular fa-user"></i></div><h2>Thông tin cá nhân</h2></div>
        <div class="panel-body">
          <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="grid2">
              <div class="fg"><label>Họ và tên</label><input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
              <div class="fg"><label>Số điện thoại</label><input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
            </div>
            <div class="fg"><label>Email</label><input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
            <div class="grid2">
              <div class="fg"><label>Ngày tham gia</label><input type="text" value="<?= date('d/m/Y', strtotime($user['created_at'])) ?>" disabled></div>
              <div class="fg"><label>Hạng thành viên</label><input type="text" value="<?= $user['member_tier'] ?? 'STANDARD' ?>" disabled></div>
            </div>
            <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button>
          </form>
          <div class="tip-box">
            <h4><i class="fa-solid fa-lightbulb"></i> Mẹo nâng cấp tài khoản</h4>
            <div class="tip-item"><i class="fa-solid fa-check-circle"></i> Cập nhật số điện thoại để nhận OTP và thông báo vé nhanh hơn.</div>
            <div class="tip-item"><i class="fa-solid fa-check-circle"></i> Tích điểm mỗi lần đặt vé để nâng hạng lên SILVER, GOLD, PLATINUM.</div>
            <div class="tip-item"><i class="fa-solid fa-check-circle"></i> Thành viên GOLD được ưu tiên chọn ghế sớm hơn 30 phút.</div>
          </div>
        </div>
      </div>

      <!-- CHANGE PW -->
      <div class="panel form-section" id="tab-password" style="display:none">
        <div class="panel-head"><div class="ph-icon"><i class="fa-solid fa-lock"></i></div><h2>Đổi mật khẩu</h2></div>
        <div class="panel-body">
          <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="fg"><label>Mật khẩu hiện tại</label><input type="password" name="old_password" placeholder="••••••••" required></div>
            <div class="fg"><label>Mật khẩu mới</label><input type="password" name="new_password" placeholder="Tối thiểu 6 ký tự" required></div>
            <div class="fg"><label>Xác nhận mật khẩu mới</label><input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required></div>
            <button type="submit" class="btn-save"><i class="fa-solid fa-key"></i> Đổi mật khẩu</button>
          </form>
        </div>
      </div>

      <!-- POINTS -->
      <div class="panel form-section" id="tab-points" style="display:none">
        <div class="panel-head"><div class="ph-icon"><i class="fa-solid fa-star"></i></div><h2>Điểm thưởng</h2></div>
        <div class="panel-body">
          <div class="points-box">
            <div class="points-num"><?= number_format($user['loyalty_points'] ?? 0) ?></div>
            <div class="points-label">Điểm tích lũy</div>
            <div class="points-note">Hạng thành viên: <?= $user['member_tier'] ?? 'STANDARD' ?></div>
          </div>
          <p style="font-size:14px;color:var(--muted);line-height:1.7">
            🏅 <b>STANDARD</b>: 0 – 999 điểm<br>
            🥈 <b>SILVER</b>: 1.000 – 4.999 điểm<br>
            🥇 <b>GOLD</b>: 5.000 – 9.999 điểm<br>
            💎 <b>PLATINUM</b>: 10.000+ điểm<br><br>
            Mỗi 10.000₫ chi tiêu = 1 điểm thưởng. Điểm có thể đổi lấy voucher giảm giá.
          </p>
        </div>
      </div>

      <!-- MY VOUCHERS -->
      <div class="panel form-section" id="tab-vouchers" style="display:none">
        <div class="panel-head"><div class="ph-icon"><i class="fa-solid fa-tag"></i></div><h2>Voucher của tôi</h2></div>
        <div class="panel-body">
          <?php if(empty($vouchers)): ?>
            <div style="text-align:center;padding:40px 20px;color:var(--muted)">
              <i class="fa-solid fa-tag" style="font-size:40px;opacity:.2;display:block;margin-bottom:12px"></i>
              <h3 style="font-size:15px;font-weight:700">Không có voucher khả dụng</h3>
              <p style="font-size:12.5px;margin-top:4px">Hiện tại không có mã giảm giá nào đang diễn ra.</p>
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
                        <i class="fa-regular fa-copy"></i> Sao chép
                      </button>
                    </div>
                    <div class="v-desc"><?= htmlspecialchars($v['description']) ?></div>
                    <div class="v-meta">
                      <span><i class="fa-regular fa-clock" style="margin-right:4px"></i>HSD: <?= $v['expire_date'] ? date('d/m/Y', strtotime($v['expire_date'])) : 'Không giới hạn' ?></span>
                      <span><i class="fa-solid fa-circle-info" style="margin-right:4px"></i>Đơn từ: <?= number_format($v['min_order'],0,',','.') ?>₫</span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function showTab(tab) {
  // Hide all panels using inline style
  document.querySelectorAll('.form-section').forEach(s => {
    s.style.display = 'none';
    s.classList.remove('active');
  });
  // Show selected panel
  const target = document.getElementById('tab-' + tab);
  if (target) { target.style.display = 'block'; target.classList.add('active'); }
  // Update menu active state
  document.querySelectorAll('.pm-item').forEach(i => i.classList.remove('active'));
  event.currentTarget.classList.add('active');
}
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
async function logout(){
  const fd=new FormData();fd.append('action','logout');
  const r=await fetch('auth.php',{method:'POST',body:fd});
  const d=await r.json();location.href=d.redirect||'login.php';
}
</script>
</body>
</html>
