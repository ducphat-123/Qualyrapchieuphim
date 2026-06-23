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
    <title>MovieFlex Admin - Đối soát dữ liệu</title>
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
                        <h1>Nhập dữ liệu đối soát</h1>
                        <p>Tải lên sao kê ngân hàng và dữ liệu hệ thống để hệ thống tự động kiểm tra sai lệch.</p>
                    </div>
                </div>

                <div class="card" style="max-width: 800px; margin: 0 auto;">
                    <div class="card-header">
                        <h3>1. Tải lên sao kê ngân hàng (Bank Statement)</h3>
                    </div>
                    
                    <div class="drop-zone">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Kéo thả file vào đây hoặc <a href="#" style="color: var(--primary-blue);">tải lên từ thiết bị</a></p>
                        <span>Hỗ trợ định dạng: .csv, .xlsx, .xls (Tối đa 50MB)</span>
                    </div>

                    <div class="form-group" style="margin-top: 24px;">
                        <label>Chọn Ngân hàng</label>
                        <select>
                            <option>Vietcombank (VCB)</option>
                            <option>Techcombank</option>
                            <option>MB Bank</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Chọn chi nhánh rạp</label>
                        <select>
                            <option>Tất cả chi nhánh</option>
                            <option>Galaxy Nguyễn Du</option>
                            <option>CGV Vincom Center</option>
                        </select>
                    </div>

                    <div style="margin-top: 32px; text-align: right; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button class="btn btn-outline" style="margin-right: 12px;">Hủy bỏ</button>
                        <button class="btn btn-primary" onclick="alert('Tính năng đang được phát triển')">Bắt đầu chạy đối soát</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
