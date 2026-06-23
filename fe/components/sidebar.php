<?php
/**
 * Shared Sidebar Component
 * Usage: include 'includes/sidebar.php';
 * Set $active_page before including, e.g.: $active_page = 'home';
 * Values: 'home', 'movies', 'cinemas', 'booking', 'profile', 'tickets'
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$_ap = $active_page ?? '';
$_isLoggedIn = !empty($_SESSION['user_id']);
$_userName   = $_SESSION['user_name'] ?? '';
$_userEmail  = $_SESSION['user_email'] ?? '';
$_userRole   = $_SESSION['user_role'] ?? 'user';
$_userInitial = mb_strtoupper(mb_substr($_userName, 0, 1)) ?: 'U';

function _sb_cls(string $page, string $ap): string {
    return $page === $ap ? ' active' : '';
}
?>
<nav class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-icon"><i class="fa-solid fa-clapperboard"></i></div>
    <span class="sb-logo-name">MovieFlex</span>
  </div>

  <div class="sb-nav">
    <div class="sb-section">Menu chính</div>
    <a href="home.php" class="sb-item<?= _sb_cls('home',$_ap) ?>"><i class="fa-solid fa-house"></i> Tổng quan</a>
    <a href="movies.php" class="sb-item<?= _sb_cls('movies',$_ap) ?>"><i class="fa-solid fa-film"></i> Phim</a>
    <a href="cinemas.php" class="sb-item<?= _sb_cls('cinemas',$_ap) ?>"><i class="fa-solid fa-location-dot"></i> Rạp chiếu</a>
    
    <div class="sb-section">Tài khoản</div>
    <?php if ($_isLoggedIn): ?>
      <a href="my-tickets.php" class="sb-item<?= _sb_cls('tickets',$_ap) ?>"><i class="fa-solid fa-receipt"></i> Vé của tôi</a>
      <a href="vouchers.php" class="sb-item<?= _sb_cls('vouchers',$_ap) ?>"><i class="fa-solid fa-tag"></i> Voucher</a>
      <a href="help.php" class="sb-item<?= _sb_cls('help',$_ap) ?>"><i class="fa-solid fa-message"></i> Phản hồi</a>
      <a href="profile.php" class="sb-item<?= _sb_cls('profile',$_ap) ?>"><i class="fa-regular fa-user"></i> Tài khoản</a>
    <?php else: ?>
      <a href="login.php" class="sb-item<?= _sb_cls('login',$_ap) ?>"><i class="fa-solid fa-right-to-bracket"></i> Đăng nhập</a>
    <?php endif; ?>

    <?php if ($_isLoggedIn && $_userRole === 'admin'): ?>
      <div class="sb-section">Khác</div>
      <a href="../admin/index.php" class="sb-item<?= _sb_cls('admin',$_ap) ?>"><i class="fa-solid fa-gauge-high"></i> Quản trị</a>
    <?php endif; ?>
  </div>

  <?php if ($_isLoggedIn): ?>
  <!-- Member Privilege Card -->
  <div class="sb-promo-box">
    <h4>Tận hưởng đặc quyền hội viên</h4>
    <p>Đặt vé nhanh hơn, tích lũy điểm thưởng đổi quà hấp dẫn!</p>
  </div>

  <div class="sb-bottom">
    <div class="sb-user">
      <div class="sb-avatar"><?= htmlspecialchars($_userInitial) ?></div>
      <div class="sb-uinfo">
        <div class="sb-uname"><?= htmlspecialchars($_userName) ?></div>
        <div class="sb-uemail"><?= htmlspecialchars($_userEmail) ?></div>
      </div>
      <button class="sb-logout" onclick="sidebarLogout()" title="Đăng xuất">
        <i class="fa-solid fa-arrow-right-from-bracket"></i>
      </button>
    </div>
  </div>
  <?php else: ?>
  <div class="sb-bottom">
    <a href="login.php" style="display:flex;align-items:center;justify-content:center;gap:8px;margin:0 10px 14px;height:42px;background:var(--blue);color:#fff;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;transition:background .2s">
      <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
    </a>
  </div>
  <?php endif; ?>
</nav>

<style>
.sidebar{width:var(--sbw,240px);background:#fff;min-height:100vh;position:fixed;left:0;top:0;display:flex;flex-direction:column;z-index:100;border-right:1px solid var(--border,#E2E8F0)}
.sb-logo{display:flex;align-items:center;gap:12px;padding:26px 20px 20px;border-bottom:1px solid var(--border,#E2E8F0)}
.sb-logo-icon{width:36px;height:36px;background:var(--blue,#2563EB);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0}
.sb-logo-name{font-size:19px;font-weight:800;color:#0F172A;letter-spacing:-.3px}
.sb-nav{padding:20px 16px;flex:1;overflow-y:auto}
.sb-section{font-size:11px;font-weight:700;letter-spacing:.8px;color:#94A3B8;text-transform:uppercase;padding:0 12px;margin:18px 0 8px}
.sb-section:first-child{margin-top:0}
.sb-item{display:flex;align-items:center;gap:14px;padding:12px 14px;border-radius:12px;color:#64748B;font-size:14.5px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;margin-bottom:4px}
.sb-item:hover{background:#F8FAFC;color:#0F172A}
.sb-item.active{background:var(--blue,#2563EB);color:#fff;box-shadow:0 4px 12px rgba(37,99,235,.25)}
.sb-item i{width:16px;font-size:15px;text-align:center;flex-shrink:0;opacity:.8}
.sb-item.active i{opacity:1}
.sb-bottom{padding:16px;border-top:1px solid var(--border,#E2E8F0)}
.sb-user{display:flex;align-items:center;gap:12px;padding:0}
.sb-avatar{width:38px;height:38px;border-radius:50%;background:var(--blue,#2563EB);display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;font-weight:700;flex-shrink:0}
.sb-uinfo{flex:1;min-width:0}
.sb-uname{font-size:13.5px;font-weight:700;color:#0F172A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-uemail{font-size:12px;color:#64748B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-logout{width:30px;height:30px;background:#F1F5F9;border:none;color:#64748B;font-size:13px;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
.sb-logout:hover{background:#FEE2E2;color:#EF4444}

/* Member Promo Box */
.sb-promo-box {
  background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
  border-radius: 12px;
  padding: 16px;
  margin: 10px 14px 10px;
  border: 1px solid #BFDBFE;
}
.sb-promo-box h4 {
  font-size: 12.5px;
  font-weight: 800;
  color: var(--blue,#2563EB);
  margin-bottom: 6px;
  letter-spacing: -0.2px;
  line-height: 1.3;
}
.sb-promo-box p {
  font-size: 11px;
  color: #1E3A8A;
  line-height: 1.4;
  font-weight: 500;
}
</style>

<script>
async function sidebarLogout() {
  const fd = new FormData();
  fd.append('action', 'logout');
  try {
    const r = await fetch('../../be/api.php', {method:'POST', body:fd});
    const d = await r.json();
    window.location.href = d.redirect || 'login.php';
  } catch(e) {
    window.location.href = 'login.php';
  }
}
</script>
