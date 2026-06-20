<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo" style="display: flex; align-items: center; width: 100%; cursor: pointer;" title="Thu gọn / Mở rộng Sidebar" data-tooltip="Thu gọn / Mở rộng">
            <i class="fa-solid fa-film"></i>
            <div class="logo-text">
                <h2>MovieFlex Admin</h2>
                <p>HỆ THỐNG QUẢN TRỊ</p>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-group">
            <p class="nav-label">DỮ LIỆU & HỆ THỐNG</p>
            <ul>
                <?php 
                $user_role = $_SESSION['user_role'] ?? '';
                if ($user_role === 'admin' || $user_role === 'admin_monitor'): 
                ?>
                    <li class="<?= $current_page == 'index.php' ? 'active' : '' ?>"><a href="index.php" title="Tổng quan" data-tooltip="Tổng quan"><i class="fa-solid fa-border-all"></i> <span>Tổng quan</span></a></li>
                    <li class="<?= $current_page == 'movies.php' ? 'active' : '' ?>"><a href="movies.php" title="Quản lý phim" data-tooltip="Quản lý phim"><i class="fa-solid fa-film"></i> <span>Quản lý phim</span></a></li>
                    <li class="<?= $current_page == 'cinemas.php' ? 'active' : '' ?>"><a href="cinemas.php" title="Quản lý rạp" data-tooltip="Quản lý rạp"><i class="fa-solid fa-shop"></i> <span>Quản lý rạp</span></a></li>
                    <li class="<?= $current_page == 'showtimes.php' ? 'active' : '' ?>"><a href="showtimes.php" title="Quản lý suất chiếu" data-tooltip="Quản lý suất chiếu"><i class="fa-solid fa-calendar-days"></i> <span>Quản lý suất chiếu</span></a></li>
                    <li class="<?= $current_page == 'vouchers.php' ? 'active' : '' ?>"><a href="vouchers.php" title="Quản lý Voucher" data-tooltip="Quản lý Voucher"><i class="fa-solid fa-tag"></i> <span>Quản lý Voucher</span></a></li>
                    <li class="<?= $current_page == 'revenue.php' ? 'active' : '' ?>"><a href="revenue.php" title="Báo cáo doanh thu" data-tooltip="Báo cáo doanh thu"><i class="fa-solid fa-chart-bar"></i> <span>Báo cáo doanh thu</span></a></li>
                    <li class="<?= $current_page == 'users.php' ? 'active' : '' ?>"><a href="users.php" title="Quản lý nhân viên" data-tooltip="Quản lý nhân viên"><i class="fa-solid fa-users"></i> <span>Quản lý nhân sự</span></a></li>
                    <li class="<?= $current_page == 'logs.php' ? 'active' : '' ?>"><a href="logs.php" title="Nhật ký hệ thống" data-tooltip="Nhật ký hệ thống"><i class="fa-solid fa-clipboard-list"></i> <span>Nhật ký hệ thống</span></a></li>
                <?php 
                endif; 
                ?>
            </ul>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <div class="help-center" style="margin-bottom:0; display:flex; align-items:center; gap:10px;">
                <i class="fa-regular fa-circle-question"></i> Trợ giúp
            </div>
            <button onclick="adminLogout()" title="Đăng xuất" data-tooltip="Đăng xuất" style="background:rgba(239, 68, 68, 0.1); border:none; color:#EF4444; width:30px; height:30px; border-radius:6px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.background='#EF4444'; this.style.color='#FFFFFF';" onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#EF4444';">
                <i class="fa-solid fa-arrow-right-from-bracket" style="font-size:13px;"></i>
            </button>
        </div>
        <div class="version-info">
            <p>Admin Portal v2.4</p>
            <span>Cập nhật 2 phút trước</span>
        </div>
    </div>
</aside>

<!-- Run class check immediately to prevent page-load transition flash/jitter -->
<script>
if (localStorage.getItem('sidebar-collapsed') === 'true') {
    document.body.classList.add('sidebar-collapsed');
}
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const logoToggle = document.querySelector('.sidebar .logo');
    if (logoToggle) {
        logoToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('sidebar-collapsed');
            const nowCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', nowCollapsed);
        });
    }
});

async function adminLogout() {
    const ok = await mfConfirm({
        title: 'Đăng xuất Quản trị viên',
        desc: 'Bạn có chắc chắn muốn đăng xuất khỏi tài khoản Quản trị viên không? Mọi thao tác đang thực hiện sẽ bị dừng lại.',
        type: 'warning',
        confirmText: 'Đăng xuất',
        confirmIcon: 'fa-arrow-right-from-bracket',
        cancelText: 'Ở lại'
    });
    if (!ok) return;
    const fd = new FormData();
    fd.append('action', 'logout');
    try {
        const r = await fetch('../auth.php', {method: 'POST', body: fd});
        const d = await r.json();
        window.location.href = '../login.php';
    } catch(e) {
        window.location.href = '../login.php';
    }
}
</script>

