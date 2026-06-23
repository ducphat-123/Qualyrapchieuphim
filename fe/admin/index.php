<?php
session_start();
if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'admin_monitor'])) {
    header('Location: ../pages/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieFlex Admin - Trang tổng quan</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <!-- Dashboard Content -->
            <div class="dashboard">
                <div class="dashboard-header">
                    <div>
                        <h1>Chào buổi sáng, Admin</h1>
                        <p>Đây là những gì đang diễn ra trong hệ thống của bạn ngày hôm nay.</p>
                    </div>
                    <div class="dashboard-actions">
                        <button class="btn btn-outline">Xuất báo cáo</button>
                        <button class="btn btn-primary">+ Thêm mới</button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-icon blue"><i class="fa-solid fa-ticket"></i></div>
                        <p class="kpi-label">VÉ BÁN HÔM NAY</p>
                        <h3 class="kpi-value" id="kpi-tickets">...</h3>
                        <p class="kpi-trend positive" id="trend-tickets">Đang tải...</p>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fa-solid fa-user-check"></i></div>
                        <p class="kpi-label">CHECK-IN HÔM NAY</p>
                        <h3 class="kpi-value" id="kpi-checkins">...</h3>
                        <p class="kpi-trend positive" id="trend-checkins">Đang tải...</p>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon red"><i class="fa-solid fa-coins"></i></div>
                        <p class="kpi-label">DOANH THU HÔM NAY</p>
                        <h3 class="kpi-value" id="kpi-revenue">...</h3>
                        <p class="kpi-trend positive" id="trend-revenue">Đang tải...</p>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon teal"><i class="fa-solid fa-headset"></i></div>
                        <p class="kpi-label">NHÂN VIÊN TRỰC</p>
                        <h3 class="kpi-value" id="kpi-staff">...</h3>
                        <p class="kpi-trend neutral"><i class="fa-solid fa-circle active-dot"></i> Đang hoạt động</p>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon gray"><i class="fa-solid fa-lock"></i></div>
                        <p class="kpi-label">TÀI KHOẢN KHÓA</p>
                        <h3 class="kpi-value" id="kpi-locked">...</h3>
                        <p class="kpi-trend neutral"><i class="fa-regular fa-clock"></i> Trong vòng 24h</p>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <div class="chart-card line-chart">
                        <div class="chart-header">
                            <h3>Xu hướng bán vé</h3>
                            <span class="chart-period-badge">7 NGÀY GẦN ĐÂY</span>
                        </div>
                        <div class="chart-body">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card bar-chart">
                        <div class="chart-header">
                            <h3>Check-in theo giờ</h3>
                        </div>
                        <div class="chart-body">
                            <canvas id="checkinChart"></canvas>
                        </div>
                        <div class="chart-footer-note" id="checkin-footer-note">
                            <i class="fa-solid fa-circle-info"></i> Đang tải thông tin cao điểm...
                        </div>
                    </div>
                </div>

                <!-- Tables Section -->
                <div class="tables-grid" style="display: grid; grid-template-columns: 1fr; width: 100%;">
                    <div class="table-card">
                        <div class="table-header">
                            <h3>Nhật ký hệ thống gần đây</h3>
                            <a href="logs.php" class="view-all">Xem tất cả</a>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>THỜI GIAN</th>
                                    <th>NGƯỜI DÙNG</th>
                                    <th>HÀNH ĐỘNG</th>
                                    <th>MÔ TẢ</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <tr><td colspan="4">Đang tải...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
