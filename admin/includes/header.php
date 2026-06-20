<?php
// Detect current page for breadcrumb label
$pageLabels = [
    'index.php'           => ['icon' => 'fa-border-all',      'label' => 'Tổng quan'],
    'movies.php'          => ['icon' => 'fa-film',            'label' => 'Quản lý phim'],
    'cinemas.php'         => ['icon' => 'fa-shop',            'label' => 'Quản lý rạp'],
    'showtimes.php'       => ['icon' => 'fa-calendar-days',   'label' => 'Quản lý suất chiếu'],
    'vouchers.php'        => ['icon' => 'fa-tag',             'label' => 'Quản lý Voucher'],
    'revenue.php'         => ['icon' => 'fa-chart-bar',       'label' => 'Báo cáo doanh thu'],
    'logs.php'            => ['icon' => 'fa-clipboard-list',  'label' => 'Nhật ký hệ thống'],
    'users.php'           => ['icon' => 'fa-users',           'label' => 'Nhân viên'],
    'reconciliation.php'  => ['icon' => 'fa-file-invoice',    'label' => 'Đối soát dữ liệu'],
];
$cp   = $current_page ?? basename($_SERVER['PHP_SELF']);
$page = $pageLabels[$cp] ?? ['icon' => 'fa-house', 'label' => 'Admin Portal'];

// Real admin name from session
$adminName = $_SESSION['user_name'] ?? 'Quản trị viên';
$adminRole = $_SESSION['user_role'] ?? 'admin';
$roleLabel = match(strtolower($adminRole)) {
    'admin'         => 'Quản trị viên',
    'admin_monitor' => 'Giám sát hệ thống',
    'staff'         => 'Nhân viên',
    'manager'       => 'Quản lý',
    default         => strtoupper($adminRole),
};
?>
<!-- Top Header -->
<header class="top-header">
    <!-- Left: breadcrumb / page title -->
    <div class="header-breadcrumb">
        <div class="header-page-icon">
            <i class="fa-solid <?= $page['icon'] ?>"></i>
        </div>
        <div class="header-page-info">
            <span class="header-page-root">Admin Portal</span>
            <span class="header-page-sep"><i class="fa-solid fa-chevron-right"></i></span>
            <span class="header-page-name"><?= htmlspecialchars($page['label']) ?></span>
        </div>
    </div>

    <!-- Right: live clock + user profile -->
    <div class="header-actions">
        <!-- Live Date & Time -->
        <div class="header-datetime">
            <i class="fa-regular fa-clock" style="color: var(--primary-blue); font-size: 14px;"></i>
            <span id="header-live-time" style="font-weight: 700; font-size: 13px; color: var(--text-main);"></span>
        </div>

        <!-- Notification bell (visual only) -->
        <button class="icon-btn" title="Thông báo" style="position: relative;">
            <i class="fa-regular fa-bell"></i>
        </button>

        <!-- User Profile -->
        <div class="user-profile">
            <div class="user-info">
                <span class="user-role"><?= htmlspecialchars($roleLabel) ?></span>
                <span class="user-name"><?= htmlspecialchars($adminName) ?></span>
            </div>
            <div class="header-avatar-initials">
                <?php
                    $parts = explode(' ', $adminName);
                    $initials = count($parts) >= 2
                        ? strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1))
                        : strtoupper(mb_substr($adminName, 0, 2));
                    echo htmlspecialchars($initials);
                ?>
            </div>
        </div>
    </div>
</header>

<script>
(function() {
    function updateHeaderClock() {
        const el = document.getElementById('header-live-time');
        if (!el) return;
        const now = new Date();
        const days = ['CN','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
        const pad = n => String(n).padStart(2, '0');
        el.textContent = `${days[now.getDay()]}, ${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()} · ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    }
    updateHeaderClock();
    setInterval(updateHeaderClock, 1000);
})();
</script>
