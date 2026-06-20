<?php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieFlex Admin - Quản lý Người dùng</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="dashboard">
                <div class="dashboard-header">
                    <div>
                        <h1>Quản lý Nhân sự</h1>
                        <p>Kiểm soát quyền truy cập và thông tin nhân viên trong hệ thống.</p>
                    </div>
                    <div class="dashboard-actions">
                        <button class="btn btn-outline"><i class="fa-solid fa-download"></i> Xuất danh sách</button>
                        <button class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Thêm nhân viên</button>
                    </div>
                </div>

                <div class="stat-cards-bottom" style="margin-bottom: 24px; margin-top: 0;">
                    <div class="stat-card-sm">
                        <span class="title">TỔNG NHÂN VIÊN</span>
                        <span class="value" id="stat-total">0</span>
                        <span class="desc">Trên toàn hệ thống</span>
                    </div>
                    <div class="stat-card-sm">
                        <span class="title">ĐANG HOẠT ĐỘNG</span>
                        <span class="value" id="stat-active">0</span>
                        <span class="desc green"><i class="fa-solid fa-arrow-up"></i> Tăng 2 so với tháng trước</span>
                    </div>
                    <div class="stat-card-sm">
                        <span class="title">CẦN PHÊ DUYỆT</span>
                        <span class="value" id="stat-pending">0</span>
                        <span class="desc orange">Tài khoản mới tạo</span>
                    </div>
                </div>

                <div class="card">
                    <div class="filter-bar">
                        <div class="search-bar" style="width: 300px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" placeholder="Tìm theo tên, email hoặc mã NV...">
                        </div>
                        <div class="filter-item">
                            <i class="fa-solid fa-layer-group"></i>
                            <select>
                                <option>Tất cả phòng ban</option>
                                <option>Kế toán</option>
                                <option>Kỹ thuật</option>
                                <option>Marketing</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <i class="fa-solid fa-toggle-on"></i>
                            <select>
                                <option>Mọi trạng thái</option>
                                <option>Hoạt động</option>
                                <option>Đã khóa</option>
                            </select>
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>MÃ NV</th>
                                <th>NHÂN VIÊN</th>
                                <th>PHÒNG BAN / ROLE</th>
                                <th>TRẠNG THÁI</th>
                                <th>NGÀY TẠO</th>
                                <th>THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody id="usersBody">
                            <tr><td colspan="6" style="text-align: center;">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>

                    <div class="pagination-area">
                        <div class="page-info">HIỂN THỊ 1-10 CỦA 85</div>
                        <div class="pagination">
                            <div class="page-item"><i class="fa-solid fa-chevron-left"></i></div>
                            <div class="page-item active">1</div>
                            <div class="page-item">2</div>
                            <div class="page-item">3</div>
                            <div class="page-item">...</div>
                            <div class="page-item">9</div>
                            <div class="page-item"><i class="fa-solid fa-chevron-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
