<?php
session_start();
if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'admin_monitor'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieFlex Admin - Nhật ký hệ thống</title>
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
                        <h1>Nhật ký hệ thống</h1>
                        <p>Theo dõi và quản lý mọi hoạt động trên hệ thống MovieFlex.</p>
                    </div>
                    <div class="dashboard-actions">
                        <button class="btn btn-outline"><i class="fa-solid fa-download"></i> Xuất file</button>
                    </div>
                </div>

                <div class="card">
                    <div class="filter-bar" style="display: flex; gap: 16px; margin-bottom: 20px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                        <div class="search-bar" style="width: 260px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-logs" placeholder="Tìm người dùng hoặc mô tả..." oninput="filterLogs(true)">
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                            <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 600; color: var(--text-muted);">
                                <span>Từ</span>
                                <input type="date" id="filter-start-date" onchange="filterLogs(true)" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                            </div>
                            <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 600; color: var(--text-muted);">
                                <span>Đến</span>
                                <input type="date" id="filter-end-date" onchange="filterLogs(true)" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                            </div>
                            <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-filter" style="color: var(--text-muted);"></i>
                                <select id="filter-action" onchange="filterLogs(true)" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                                    <option value="">Tất cả hành động</option>
                                    <option value="Thêm">Thêm mới</option>
                                    <option value="Cập nhật">Cập nhật</option>
                                    <option value="Xóa">Xóa bỏ</option>
                                    <option value="Hủy">Hủy khẩn cấp</option>
                                    <option value="Bán vé">Bán vé quầy</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>THỜI GIAN</th>
                                <th>NGƯỜI DÙNG</th>
                                <th>HÀNH ĐỘNG</th>
                                <th>MÔ TẢ CHI TIẾT</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="fullLogsBody">
                            <tr><td colspan="5" style="text-align: center;">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>

                    <div class="pagination-area">
                        <div class="page-info" id="logs-page-info">HIỂN THỊ ...</div>
                        <div class="pagination" id="logs-pagination"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
