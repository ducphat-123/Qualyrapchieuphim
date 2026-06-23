<?php
session_start();
if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'admin_monitor'])) {
    header('Location: ../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../be/config/db.php';

$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';

if (!empty($startDate) && !empty($endDate)) {
    $filter = 'custom';
} else {
    $filter = $_GET['filter'] ?? 'today';
    if ($filter === 'week') {
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
    } elseif ($filter === 'month') {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
    } elseif ($filter === 'year') {
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
    } else {
        $filter = 'today';
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
    }
}

$safe_start = $pdo->quote($startDate);
$safe_end = $pdo->quote($endDate);

$where_time = "DATE(created_at) BETWEEN $safe_start AND $safe_end";
$where_time_b = "DATE(b.created_at) BETWEEN $safe_start AND $safe_end";

$trend_title = "Xu hướng doanh số bán hàng (Từ " . date('d/m/Y', strtotime($startDate)) . " đến " . date('d/m/Y', strtotime($endDate)) . ")";

// 1. Overall stats
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status != 'cancelled' AND $where_time")->fetchColumn() ?: 0;
$total_tickets = $pdo->query("SELECT SUM(num_tickets) FROM bookings WHERE status != 'cancelled' AND $where_time")->fetchColumn() ?: 0;
$total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'cancelled' AND $where_time")->fetchColumn() ?: 0;

$ticket_revenue = $pdo->query("SELECT SUM(subtotal) FROM bookings WHERE status != 'cancelled' AND $where_time")->fetchColumn() ?: 0;
$snack_revenue = $pdo->query("SELECT SUM(GREATEST(0, total_amount - subtotal + discount)) FROM bookings WHERE status != 'cancelled' AND $where_time")->fetchColumn() ?: 0;

// Online stats
$online_revenue = $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status != 'cancelled' AND payment_method != 'cash' AND $where_time")->fetchColumn() ?: 0;
$online_tickets = $pdo->query("SELECT SUM(num_tickets) FROM bookings WHERE status != 'cancelled' AND payment_method != 'cash' AND $where_time")->fetchColumn() ?: 0;
$online_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'cancelled' AND payment_method != 'cash' AND $where_time")->fetchColumn() ?: 0;
$online_ticket_revenue = $pdo->query("SELECT SUM(subtotal) FROM bookings WHERE status != 'cancelled' AND payment_method != 'cash' AND $where_time")->fetchColumn() ?: 0;
$online_snack_revenue = $pdo->query("SELECT SUM(GREATEST(0, total_amount - subtotal + discount)) FROM bookings WHERE status != 'cancelled' AND payment_method != 'cash' AND $where_time")->fetchColumn() ?: 0;

// Direct / Counter stats
$direct_revenue = $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status != 'cancelled' AND payment_method = 'cash' AND $where_time")->fetchColumn() ?: 0;
$direct_tickets = $pdo->query("SELECT SUM(num_tickets) FROM bookings WHERE status != 'cancelled' AND payment_method = 'cash' AND $where_time")->fetchColumn() ?: 0;
$direct_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'cancelled' AND payment_method = 'cash' AND $where_time")->fetchColumn() ?: 0;
$direct_ticket_revenue = $pdo->query("SELECT SUM(subtotal) FROM bookings WHERE status != 'cancelled' AND payment_method = 'cash' AND $where_time")->fetchColumn() ?: 0;
$direct_snack_revenue = $pdo->query("SELECT SUM(GREATEST(0, total_amount - subtotal + discount)) FROM bookings WHERE status != 'cancelled' AND payment_method = 'cash' AND $where_time")->fetchColumn() ?: 0;

// 2. Revenue by Movie
$movie_revenue = $pdo->query("
    SELECT m.title, m.poster_url, COUNT(b.id) as bookings_count, SUM(b.num_tickets) as tickets_count, SUM(b.total_amount) as revenue
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE b.status != 'cancelled' AND $where_time_b
    GROUP BY m.id
    ORDER BY revenue DESC
")->fetchAll();

// 3. Revenue by Cinema
$cinema_revenue = $pdo->query("
    SELECT c.name as cinema_name, COUNT(b.id) as bookings_count, SUM(b.num_tickets) as tickets_count, SUM(b.total_amount) as revenue
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN cinemas c ON s.cinema_id = c.id
    WHERE b.status != 'cancelled' AND $where_time_b
    GROUP BY c.id
    ORDER BY revenue DESC
")->fetchAll();

// 4. Detailed Bookings
$detailed_bookings = $pdo->query("
    SELECT b.*, u.full_name as customer_name, m.title as movie_title, c.name as cinema_name
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN cinemas c ON s.cinema_id = c.id
    WHERE b.status != 'cancelled' AND $where_time_b
    ORDER BY b.created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

// 5. Daily Sales Trend for the Chart (scales hourly vs daily)
if ($startDate === $endDate) {
    // Single day: group by hour
    $daily_sales = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%H:00') as day_label, 
            SUM(CASE WHEN payment_method != 'cash' THEN total_amount ELSE 0 END) as online_revenue,
            SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as direct_revenue,
            SUM(total_amount) as revenue
        FROM bookings
        WHERE status != 'cancelled' AND DATE(created_at) = $safe_start
        GROUP BY HOUR(created_at)
        ORDER BY created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Multi day: group by date
    $daily_sales = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%d/%m') as day_label, 
            SUM(CASE WHEN payment_method != 'cash' THEN total_amount ELSE 0 END) as online_revenue,
            SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as direct_revenue,
            SUM(total_amount) as revenue
        FROM bookings
        WHERE status != 'cancelled' AND DATE(created_at) BETWEEN $safe_start AND $safe_end
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Generate placeholders if empty
if (empty($daily_sales)) {
    $daily_sales = [];
    if ($startDate === $endDate) {
        for ($h = 8; $h <= 23; $h += 2) {
            $daily_sales[] = [
                'day_label' => sprintf('%02d:00', $h),
                'online_revenue' => 0,
                'direct_revenue' => 0,
                'revenue' => 0
            ];
        }
    } else {
        $curr = strtotime($startDate);
        $last = strtotime($endDate);
        $days_diff = round(($last - $curr) / 86400);
        if ($days_diff <= 31) {
            while ($curr <= $last) {
                $daily_sales[] = [
                    'day_label' => date('d/m', $curr),
                    'online_revenue' => 0,
                    'direct_revenue' => 0,
                    'revenue' => 0
                ];
                $curr = strtotime("+1 day", $curr);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieFlex Admin - Báo cáo Doanh thu</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .revenue-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }
        .progress-bar-wrap {
            width: 100%;
            height: 8px;
            background-color: #E2E8F0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 6px;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-blue), #7C3AED);
            border-radius: 4px;
        }
        .stat-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed var(--border-color);
        }
        .stat-detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="dashboard">
                <div class="dashboard-header">
                    <div>
                        <h1>Báo cáo & Thống kê Doanh thu</h1>
                        <p>Theo dõi các chỉ số tài chính, doanh số bán vé, và hiệu suất doanh thu phòng vé thời gian thực.</p>
                    </div>
                    <div class="dashboard-actions" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <!-- Bộ lọc thời gian -->
                        <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 12px; background: white; display: flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <i class="fa-solid fa-clock-rotate-left" style="color: var(--text-muted); font-size: 14px;"></i>
                            <select id="time-filter" onchange="applyTimeFilter(this.value)" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main); background: transparent; padding-right: 4px;">
                                <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Hôm nay</option>
                                <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Tuần này</option>
                                <option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Tháng này</option>
                                <option value="year" <?= $filter === 'year' ? 'selected' : '' ?>>Năm nay</option>
                                <option value="custom" <?= $filter === 'custom' ? 'selected' : '' ?> disabled>Khoảng ngày</option>
                            </select>
                        </div>

                        <!-- Lọc theo khoảng ngày -->
                        <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 600; color: var(--text-muted); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <span>Từ</span>
                            <input type="date" id="filter-start-date" value="<?= htmlspecialchars($startDate) ?>" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                        </div>
                        <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 600; color: var(--text-muted); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <span>Đến</span>
                            <input type="date" id="filter-end-date" value="<?= htmlspecialchars($endDate) ?>" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                        </div>
                        <button class="btn btn-primary" onclick="applyCustomDateFilter()" style="padding: 8px 16px; font-size: 13.5px; font-weight: 600; border-radius: 8px;"><i class="fa-solid fa-filter"></i> Lọc</button>

                        <button class="btn btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> In báo cáo</button>
                    </div>
                </div>

                <!-- KPI Overview Cards -->
                <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div class="kpi-card" style="display:flex; flex-direction:column; justify-content:space-between; padding: 20px; box-shadow: var(--shadow-sm); border-radius: var(--radius-md);">
                        <div>
                            <div class="kpi-icon blue" style="width:40px; height:40px; border-radius:10px;"><i class="fa-solid fa-coins"></i></div>
                            <p class="kpi-label" style="font-size:11px; margin-top:12px;">TỔNG DOANH THU</p>
                            <h3 class="kpi-value" style="font-size:20px; font-weight:700; color:var(--text-main); margin:4px 0 8px;"><?= number_format($total_revenue, 0, ',', '.') ?>₫</h3>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: auto; padding-top: 10px; border-top: 1px dashed var(--border-color); font-size: 11px; color: var(--text-muted);">
                            <span><i class="fa-solid fa-globe" style="color:var(--primary-blue); margin-right:3px;"></i> Online: <strong><?= number_format($online_revenue, 0, ',', '.') ?>₫</strong></span>
                            <span><i class="fa-solid fa-shop" style="color:#F59E0B; margin-right:3px;"></i> Quầy: <strong><?= number_format($direct_revenue, 0, ',', '.') ?>₫</strong></span>
                        </div>
                    </div>
                    <div class="kpi-card" style="display:flex; flex-direction:column; justify-content:space-between; padding: 20px; box-shadow: var(--shadow-sm); border-radius: var(--radius-md);">
                        <div>
                            <div class="kpi-icon green" style="width:40px; height:40px; border-radius:10px;"><i class="fa-solid fa-ticket"></i></div>
                            <p class="kpi-label" style="font-size:11px; margin-top:12px;">VÉ PHIM ĐÃ BÁN</p>
                            <h3 class="kpi-value" style="font-size:20px; font-weight:700; color:var(--text-main); margin:4px 0 8px;"><?= number_format($total_tickets, 0, ',', '.') ?> vé</h3>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: auto; padding-top: 10px; border-top: 1px dashed var(--border-color); font-size: 11px; color: var(--text-muted);">
                            <span><i class="fa-solid fa-globe" style="color:var(--primary-blue); margin-right:3px;"></i> Online: <strong><?= number_format($online_tickets, 0, ',', '.') ?></strong></span>
                            <span><i class="fa-solid fa-shop" style="color:#F59E0B; margin-right:3px;"></i> Quầy: <strong><?= number_format($direct_tickets, 0, ',', '.') ?></strong></span>
                        </div>
                    </div>
                    <div class="kpi-card" style="display:flex; flex-direction:column; justify-content:space-between; padding: 20px; box-shadow: var(--shadow-sm); border-radius: var(--radius-md);">
                        <div>
                            <div class="kpi-icon teal" style="width:40px; height:40px; border-radius:10px;"><i class="fa-solid fa-cart-shopping"></i></div>
                            <p class="kpi-label" style="font-size:11px; margin-top:12px;">DOANH THU BẮP NƯỚC</p>
                            <h3 class="kpi-value" style="font-size:20px; font-weight:700; color:var(--text-main); margin:4px 0 8px;"><?= number_format($snack_revenue, 0, ',', '.') ?>₫</h3>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: auto; padding-top: 10px; border-top: 1px dashed var(--border-color); font-size: 11px; color: var(--text-muted);">
                            <span><i class="fa-solid fa-globe" style="color:var(--primary-blue); margin-right:3px;"></i> Online: <strong><?= number_format($online_snack_revenue, 0, ',', '.') ?>₫</strong></span>
                            <span><i class="fa-solid fa-shop" style="color:#F59E0B; margin-right:3px;"></i> Quầy: <strong><?= number_format($direct_snack_revenue, 0, ',', '.') ?>₫</strong></span>
                        </div>
                    </div>
                    <div class="kpi-card" style="display:flex; flex-direction:column; justify-content:space-between; padding: 20px; box-shadow: var(--shadow-sm); border-radius: var(--radius-md);">
                        <div>
                            <div class="kpi-icon red" style="width:40px; height:40px; border-radius:10px;"><i class="fa-solid fa-receipt"></i></div>
                            <p class="kpi-label" style="font-size:11px; margin-top:12px;">TỔNG GIAO DỊCH</p>
                            <h3 class="kpi-value" style="font-size:20px; font-weight:700; color:var(--text-main); margin:4px 0 8px;"><?= $total_bookings ?> đơn</h3>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: auto; padding-top: 10px; border-top: 1px dashed var(--border-color); font-size: 11px; color: var(--text-muted);">
                            <span><i class="fa-solid fa-globe" style="color:var(--primary-blue); margin-right:3px;"></i> Online: <strong><?= $online_bookings ?></strong></span>
                            <span><i class="fa-solid fa-shop" style="color:#F59E0B; margin-right:3px;"></i> Quầy: <strong><?= $direct_bookings ?></strong></span>
                        </div>
                    </div>
                    <div class="kpi-card" style="display:flex; flex-direction:column; justify-content:space-between; padding: 20px; box-shadow: var(--shadow-sm); border-radius: var(--radius-md);">
                        <div>
                            <div class="kpi-icon gray" style="width:40px; height:40px; border-radius:10px;"><i class="fa-solid fa-calculator"></i></div>
                            <p class="kpi-label" style="font-size:11px; margin-top:12px;">GIÁ VÉ TRUNG BÌNH</p>
                            <h3 class="kpi-value" style="font-size:20px; font-weight:700; color:var(--text-main); margin:4px 0 8px;"><?= $total_tickets > 0 ? number_format($ticket_revenue / $total_tickets, 0, ',', '.') : '0' ?>₫</h3>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: auto; padding-top: 10px; border-top: 1px dashed var(--border-color); font-size: 11px; color: var(--text-muted);">
                            <span><i class="fa-solid fa-globe" style="color:var(--primary-blue); margin-right:3px;"></i> Online: <strong><?= $online_tickets > 0 ? number_format($online_ticket_revenue / $online_tickets, 0, ',', '.') : '0' ?>₫</strong></span>
                            <span><i class="fa-solid fa-shop" style="color:#F59E0B; margin-right:3px;"></i> Quầy: <strong><?= $direct_tickets > 0 ? number_format($direct_ticket_revenue / $direct_tickets, 0, ',', '.') : '0' ?>₫</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Charts & Stats Details Grid -->
                <div class="revenue-grid">
                    <!-- Line Chart Card -->
                    <div class="card" style="margin-bottom: 0;">
                        <div class="card-header">
                            <h3><?= htmlspecialchars($trend_title) ?></h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- Revenue Details Breakdowns -->
                    <div class="card" style="margin-bottom: 0; display:flex; flex-direction:column; justify-content:space-between; padding: 24px; box-shadow: var(--shadow-sm); border-radius: var(--radius-md);">
                        <div>
                            <div class="card-header" style="margin-bottom: 16px;">
                                <h3 style="font-size:15px; font-weight:700; color:var(--text-main);"><i class="fa-solid fa-chart-pie" style="color:var(--primary-blue); margin-right:8px;"></i>Cơ cấu Doanh thu chi tiết</h3>
                            </div>
                            <div class="stat-detail-item" style="padding-bottom: 16px; margin-bottom: 16px;">
                                <div>
                                    <span style="font-weight:600; color:var(--text-main); font-size:13.5px;">Doanh thu vé xem phim</span>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                                        <span style="margin-right:12px;"><i class="fa-solid fa-globe" style="color:var(--primary-blue); margin-right:4px;"></i>Online: <strong><?= number_format($online_ticket_revenue, 0, ',', '.') ?>₫</strong></span>
                                        <span><i class="fa-solid fa-shop" style="color:#F59E0B; margin-right:4px;"></i>Tại quầy: <strong><?= number_format($direct_ticket_revenue, 0, ',', '.') ?>₫</strong></span>
                                    </div>
                                </div>
                                <strong style="color:var(--primary-blue); font-size:15px;"><?= number_format($ticket_revenue, 0, ',', '.') ?>₫</strong>
                            </div>
                            <div class="stat-detail-item" style="padding-bottom: 16px; margin-bottom: 16px;">
                                <div>
                                    <span style="font-weight:600; color:var(--text-main); font-size:13.5px;">Doanh thu bắp nước & Dịch vụ</span>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                                        <span style="margin-right:12px;"><i class="fa-solid fa-globe" style="color:var(--primary-blue); margin-right:4px;"></i>Online: <strong><?= number_format($online_snack_revenue, 0, ',', '.') ?>₫</strong></span>
                                        <span><i class="fa-solid fa-shop" style="color:#F59E0B; margin-right:4px;"></i>Tại quầy: <strong><?= number_format($direct_snack_revenue, 0, ',', '.') ?>₫</strong></span>
                                    </div>
                                </div>
                                <strong style="color:var(--primary-blue); font-size:15px;"><?= number_format($snack_revenue, 0, ',', '.') ?>₫</strong>
                            </div>
                        </div>

                        <div style="margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                            <span style="font-weight:700; font-size:13px; color:var(--text-main);">Tỉ lệ cơ cấu Doanh thu (Vé vs Bắp nước)</span>
                            <?php 
                                $ticketPct = $total_revenue > 0 ? ($ticket_revenue / $total_revenue) * 100 : 80;
                                $snackPct = 100 - $ticketPct;
                            ?>
                            <div style="display:flex; justify-content:space-between; font-size:12px; margin-top:10px; font-weight:600; color:var(--text-muted);">
                                <span>Vé: <?= round($ticketPct) ?>%</span>
                                <span>Bắp nước: <?= round($snackPct) ?>%</span>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill" style="width: <?= $ticketPct ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables breakdowns: Movie vs Cinema Performance -->
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-bottom: 24px;">
                    <!-- Movie Performance Table -->
                    <div class="card" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-md);">
                        <div class="card-header">
                            <h3 style="font-size:15px; font-weight:700;"><i class="fa-solid fa-film" style="color:var(--primary-blue); margin-right:8px;"></i>Hiệu suất Doanh thu theo Phim</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tên Phim</th>
                                    <th class="text-center">Số đơn</th>
                                    <th class="text-center">Vé bán</th>
                                    <th style="text-align: right;">Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movie_revenue)): ?>
                                <tr><td colspan="4" class="text-center" style="padding: 20px; color: var(--text-muted);">Chưa có giao dịch phim nào.</td></tr>
                                <?php else: ?>
                                <?php foreach (array_slice($movie_revenue, 0, 5) as $mr): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <?php if ($mr['poster_url']): ?>
                                                <img src="<?= htmlspecialchars($mr['poster_url']) ?>" alt="" style="width:25px; height:35px; object-fit:cover; border-radius:4px;">
                                            <?php endif; ?>
                                            <span style="font-weight:700; color:#111;"><?= htmlspecialchars($mr['title']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center"><strong><?= $mr['bookings_count'] ?></strong> đơn</td>
                                    <td class="text-center"><strong><?= $mr['tickets_count'] ?></strong> vé</td>
                                    <td style="text-align: right; font-weight:700; color:var(--primary-blue);"><?= number_format($mr['revenue'], 0, ',', '.') ?>₫</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Cinema Performance Table -->
                    <div class="card" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-md);">
                        <div class="card-header">
                            <h3 style="font-size:15px; font-weight:700;"><i class="fa-solid fa-location-dot" style="color:var(--primary-blue); margin-right:8px;"></i>Hiệu suất Doanh thu theo Chi nhánh rạp</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tên Rạp chiếu</th>
                                    <th class="text-center">Số đơn</th>
                                    <th class="text-center">Vé bán</th>
                                    <th style="text-align: right;">Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cinema_revenue)): ?>
                                <tr><td colspan="4" class="text-center" style="padding: 20px; color: var(--text-muted);">Chưa có giao dịch rạp nào.</td></tr>
                                <?php else: ?>
                                <?php foreach ($cinema_revenue as $cr): ?>
                                <tr>
                                    <td><strong style="color:#111;"><i class="fa-solid fa-location-dot" style="color:var(--primary-blue); margin-right:6px;"></i><?= htmlspecialchars($cr['cinema_name']) ?></strong></td>
                                    <td class="text-center"><strong><?= $cr['bookings_count'] ?></strong> đơn</td>
                                    <td class="text-center"><strong><?= $cr['tickets_count'] ?></strong> vé</td>
                                    <td style="text-align: right; font-weight:700; color:var(--primary-blue);"><?= number_format($cr['revenue'], 0, ',', '.') ?>₫</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Detailed Transactions Table -->
                <div class="card" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-md); margin-bottom: 24px;">
                    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <h3 style="font-size:15px; font-weight:700;"><i class="fa-solid fa-list-check" style="color:var(--primary-blue); margin-right:8px;"></i>Danh sách giao dịch chi tiết (Tối đa 50 giao dịch gần nhất)</h3>
                        <span style="font-size:11.5px; color:var(--text-muted); font-weight:600;"><i class="fa-solid fa-circle-info"></i> Hiển thị đầy đủ vé & bắp nước trong ngày đã lọc</span>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="data-table" style="min-width:1050px;">
                            <thead>
                                <tr>
                                    <th>MÃ HÓA ĐƠN</th>
                                    <th>THỜI GIAN</th>
                                    <th>KHÁCH HÀNG</th>
                                    <th class="text-center">HÌNH THỨC</th>
                                    <th>PHIM & RẠP CHIẾU</th>
                                    <th class="text-center">SỐ VÉ</th>
                                    <th>THANH TOÁN</th>
                                    <th style="text-align: right;">TỔNG TIỀN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detailed_bookings)): ?>
                                <tr><td colspan="8" class="text-center" style="padding: 40px; color: var(--text-muted); font-weight:600;">Không tìm thấy giao dịch nào phù hợp trong khoảng thời gian này.</td></tr>
                                <?php else: ?>
                                <?php foreach ($detailed_bookings as $b): ?>
                                <tr>
                                    <td><strong style="color:var(--text-main); font-family:monospace; font-size:13px;"><?= htmlspecialchars($b['booking_code']) ?></strong></td>
                                    <td style="font-size:12px; color:var(--text-muted);"><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
                                    <td>
                                        <div style="font-weight:600; color:#111;"><?= htmlspecialchars($b['customer_name'] ?: 'Khách vãng lai') ?></div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($b['payment_method'] !== 'cash'): ?>
                                            <span class="badge" style="background-color: var(--info-bg); color: var(--info-text); padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-globe"></i> Online</span>
                                        <?php else: ?>
                                            <span class="badge" style="background-color: var(--warning-bg); color: var(--warning-text); padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-shop"></i> Tại quầy</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:var(--text-main); font-size:13px;"><?= htmlspecialchars($b['movie_title']) ?></div>
                                        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                            Rạp: <strong><?= htmlspecialchars($b['cinema_name']) ?></strong> 
                                            <?php 
                                            $seats = json_decode($b['seats_json'], true) ?: [];
                                            if (!empty($seats)): ?>
                                            | Ghế: <strong><?= implode(', ', $seats) ?></strong>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center"><strong><?= $b['num_tickets'] ?></strong></td>
                                    <td>
                                        <span style="font-size:12px; font-weight:600; color:var(--text-main); text-transform:uppercase;">
                                            <?php if ($b['payment_method'] === 'cash'): ?>
                                                <i class="fa-solid fa-money-bill-1" style="color:#15803D; margin-right:4px;"></i> Tiền mặt
                                            <?php elseif ($b['payment_method'] === 'card'): ?>
                                                <i class="fa-solid fa-credit-card" style="color:#0369A1; margin-right:4px;"></i> Thẻ (POS)
                                            <?php else: ?>
                                                <i class="fa-solid fa-wallet" style="color:#4F46E5; margin-right:4px;"></i> <?= htmlspecialchars($b['payment_method']) ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight:700; color:var(--primary-blue); font-size:14px;"><?= number_format($b['total_amount'], 0, ',', '.') ?>₫</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Include global script for dynamic utility methods and toasts -->
    <script src="../assets/js/script.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const salesData = <?= json_encode($daily_sales) ?>;
            const labels = salesData.map(item => item.day_label);
            const onlineRevenues = salesData.map(item => parseFloat(item.online_revenue) || 0);
            const directRevenues = salesData.map(item => parseFloat(item.direct_revenue) || 0);
            const revenues = salesData.map(item => parseFloat(item.revenue) || 0);

            const ctx = document.getElementById('salesTrendChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Doanh thu Online (₫)',
                            data: onlineRevenues,
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79, 70, 229, 0.04)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#4F46E5',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Doanh thu tại Quầy (₫)',
                            data: directRevenues,
                            borderColor: '#F59E0B',
                            backgroundColor: 'rgba(245, 158, 11, 0.04)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#F59E0B',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Inter',
                                    weight: '600',
                                    size: 12
                                },
                                color: '#0F172A',
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#F1F3F5'
                            },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return (value / 1000000).toFixed(1) + 'M';
                                    }
                                    if (value >= 1000) {
                                        return (value / 1000).toFixed(0) + 'K';
                                    }
                                    return value;
                                }
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        });

        function applyTimeFilter(value) {
            window.location.href = 'revenue.php?filter=' + value;
        }

        function applyCustomDateFilter() {
            const start = document.getElementById('filter-start-date').value;
            const end = document.getElementById('filter-end-date').value;
            
            if (!start || !end) {
                if (window.mfToast) {
                    window.mfToast('Chọn thiếu ngày', 'Vui lòng chọn đầy đủ cả Ngày bắt đầu và Ngày kết thúc.', 'warning', 5000);
                } else {
                    alert('Vui lòng chọn đầy đủ cả Ngày bắt đầu và Ngày kết thúc.');
                }
                return;
            }
            if (start > end) {
                if (window.mfToast) {
                    window.mfToast('Ngày không hợp lệ', 'Ngày bắt đầu không thể lớn hơn Ngày kết thúc.', 'danger', 5000);
                } else {
                    alert('Ngày bắt đầu không được lớn hơn Ngày kết thúc.');
                }
                return;
            }
            window.location.href = `revenue.php?startDate=${start}&endDate=${end}`;
        }
    </script>
</body>
</html>
