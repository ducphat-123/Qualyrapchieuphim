<?php
session_start();
// Check if user is logged in and is admin
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../be/config/db.php';

$message = '';
$messageType = '';

// Handle CRUD operations for Cinema
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? 'Hà Nội');
        $phone = trim($_POST['phone'] ?? '');
        $logo_url = trim($_POST['logo_url'] ?? '');

        if ($name && $address) {
            try {
                if ($id > 0) {
                    // Update Cinema
                    $stmt = $pdo->prepare("UPDATE cinemas SET name=?, address=?, city=?, phone=?, logo_url=? WHERE id=?");
                    $stmt->execute([$name, $address, $city, $phone ?: null, $logo_url ?: null, $id]);

                    // Log
                    $logDesc = "Đã cập nhật thông tin rạp chiếu: \"$name\"";
                    $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Cập nhật rạp chiếu', ?)")
                        ->execute([$_SESSION['user_name'], $logDesc]);

                    $message = "Đã cập nhật rạp chiếu <b>" . htmlspecialchars($name) . "</b> thành công!";
                    $messageType = 'success';
                } else {
                    $pdo->beginTransaction();

                    // Create Cinema
                    $stmt = $pdo->prepare("INSERT INTO cinemas (name, address, city, phone, logo_url) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $address, $city, $phone ?: null, $logo_url ?: null]);
                    $newCinemaId = $pdo->lastInsertId();

                    // Automatically seed 6 standard rooms (100 seats each) for this new cinema
                    $stmtInsertHall = $pdo->prepare("INSERT INTO cinema_halls (cinema_id, name, total_seats) VALUES (?, ?, 100)");
                    for ($i = 1; $i <= 6; $i++) {
                        $hallName = sprintf("Phòng %02d", $i);
                        $stmtInsertHall->execute([$newCinemaId, $hallName]);
                    }

                    // Log
                    $logDesc = "Đã thêm rạp chiếu mới: \"$name\" (Đã khởi tạo 6 phòng chiếu mặc định)";
                    $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Thêm rạp mới', ?)")
                        ->execute([$_SESSION['user_name'], $logDesc]);

                    $pdo->commit();

                    $message = "Đã thêm rạp mới <b>" . htmlspecialchars($name) . "</b> thành công (Đã tự động khởi tạo 6 phòng chiếu 100 ghế)!";
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = "Lỗi hệ thống: " . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = "Vui lòng điền đầy đủ các thông tin bắt buộc (Tên rạp và Địa chỉ).";
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // 1. Safety Check: Verify if there are any active showtimes scheduled in this cinema
                $stCount = $pdo->prepare("SELECT COUNT(*) FROM showtimes WHERE cinema_id = ?");
                $stCount->execute([$id]);
                if ($stCount->fetchColumn() > 0) {
                    $message = "Không thể xóa rạp này do đang có các Suất chiếu (Lịch chiếu) được xếp lịch. Hãy hủy hoặc xóa các suất chiếu trước.";
                    $messageType = 'error';
                } else {
                    // Get cinema name for logging
                    $cStmt = $pdo->prepare("SELECT name FROM cinemas WHERE id = ?");
                    $cStmt->execute([$id]);
                    $name = $cStmt->fetchColumn();

                    // Delete Cinema (halls will be cascade deleted due to FOREIGN KEY constraints)
                    $stmt = $pdo->prepare("DELETE FROM cinemas WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    // Log
                    $logDesc = "Đã xóa rạp chiếu: \"$name\"";
                    $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Xóa rạp chiếu', ?)")
                        ->execute([$_SESSION['user_name'], $logDesc]);

                    $message = "Đã xóa rạp chiếu <b>" . htmlspecialchars($name) . "</b> thành công!";
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                $message = "Không thể xóa rạp này: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Fetch all cinemas and count of halls per cinema
$cinemas = $pdo->query("
    SELECT c.*, COALESCE(h.halls_count, 0) as halls_count
    FROM cinemas c
    LEFT JOIN (
        SELECT cinema_id, COUNT(*) as halls_count
        FROM cinema_halls
        GROUP BY cinema_id
    ) h ON c.id = h.cinema_id
    ORDER BY c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Selected cinema logic
$selected_cinema_id = (int)($_GET['selected_id'] ?? 0);
if (!$selected_cinema_id && count($cinemas) > 0) {
    $selected_cinema_id = $cinemas[0]['id'];
}

$selected_cinema = null;
foreach ($cinemas as $c) {
    if ($c['id'] == $selected_cinema_id) {
        $selected_cinema = $c;
        break;
    }
}

// Fetch halls for the selected cinema
$selected_halls = [];
if ($selected_cinema_id) {
    $hStmt = $pdo->prepare("SELECT * FROM cinema_halls WHERE cinema_id = ? ORDER BY name ASC");
    $hStmt->execute([$selected_cinema_id]);
    $selected_halls = $hStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieFlex Admin - Quản lý Rạp chiếu</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cinemas-layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 24px;
            align-items: start;
        }
        
        .cinema-sidebar-card {
            background: white;
            border-radius: var(--border-radius, 14px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #E2E8F0;
            overflow: hidden;
        }

        .cinema-sidebar-card h3 {
            padding: 16px 20px;
            font-size: 15px;
            font-weight: 800;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cinema-list-wrapper {
            max-height: 70vh;
            overflow-y: auto;
        }

        .cinema-list-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border-bottom: 1px solid #F1F5F9;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .cinema-list-item:hover {
            background-color: #F8FAFC;
        }

        .cinema-list-item.active {
            background-color: #EEF2FF;
            border-left: 4px solid var(--primary-blue, #4F46E5);
        }

        .cinema-list-logo {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: #EEF2FF;
            color: var(--primary-blue, #4F46E5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            object-fit: cover;
            border: 1px solid #E2E8F0;
        }

        .cinema-list-info {
            flex: 1;
            min-width: 0;
        }

        .cinema-list-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cinema-list-addr {
            font-size: 12px;
            color: #64748B;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cinema-list-badge {
            font-size: 10.5px;
            font-weight: 700;
            background: #E0F2F1;
            color: #00897B;
            padding: 2px 8px;
            border-radius: 12px;
            margin-top: 4px;
            display: inline-block;
        }

        .cinema-details-card {
            background: white;
            border-radius: var(--border-radius, 14px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #E2E8F0;
            padding: 24px;
        }

        .details-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            border-bottom: 1px dashed #E2E8F0;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .details-logo {
            width: 72px;
            height: 72px;
            border-radius: 14px;
            background: #EEF2FF;
            color: var(--primary-blue, #4F46E5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            border: 1.5px solid #C7D2FE;
            object-fit: cover;
        }

        .details-title-info {
            flex: 1;
        }

        .details-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 6px;
            color: #0F172A;
        }

        .details-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 13.5px;
            color: #64748B;
        }

        .details-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .details-meta-item i {
            color: var(--primary-blue, #4F46E5);
        }

        .halls-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .halls-table th {
            text-align: left;
            padding: 12px 16px;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            font-size: 11px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .halls-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #F1F5F9;
            font-size: 13.5px;
        }

        .halls-badge {
            background: #EEF2FF;
            color: var(--primary-blue, #4F46E5);
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            border: 1px solid #C7D2FE;
        }

        .btn-cinema-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }

        .btn-cinema-edit {
            background-color: #EFF6FF;
            color: var(--primary-blue, #4F46E5);
            border: 1px solid #BFDBFE;
        }

        .btn-cinema-edit:hover {
            background-color: #DBEAFE;
        }

        .btn-cinema-delete {
            background-color: #FEF2F2;
            color: #EF4444;
            border: 1px solid #FEE2E2;
        }

        .btn-cinema-delete:hover {
            background-color: #FEE2E2;
        }

        .modal-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .full-row {
            grid-column: 1 / -1;
        }

        .form-group-custom {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group-custom label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .form-input-custom {
            height: 40px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 0 12px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            font-family: inherit;
        }

        .form-input-custom:focus {
            border-color: var(--primary-blue, #4F46E5);
        }

        .alert-bar {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-bar.success {
            background-color: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .alert-bar.error {
            background-color: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FEE2E2;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-title">
                    <h1>Quản lý Rạp chiếu</h1>
                    <p>Hệ thống quản lý chi nhánh rạp và cơ cấu phòng chiếu</p>
                </div>
            </header>

            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="alert-bar <?= $messageType ?>">
                    <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>

            <div class="cinemas-layout">
                <!-- Left Cinema List -->
                <div class="cinema-sidebar-card">
                    <h3>
                        <span><i class="fa-solid fa-shop" style="color:var(--primary-blue);margin-right:6px"></i>Danh sách Rạp</span>
                        <button class="btn btn-primary" onclick="openAddCinemaModal()" style="font-size:11.5px; padding:6px 12px; height:auto; border-radius:6px;">
                            <i class="fa-solid fa-plus"></i> Thêm rạp
                        </button>
                    </h3>
                    
                    <div class="cinema-list-wrapper">
                        <?php if (count($cinemas) === 0): ?>
                            <div style="padding:40px 20px; text-align:center; color:#64748B;">
                                <i class="fa-solid fa-shop-slash" style="font-size:32px; opacity:0.3; margin-bottom:12px; display:block;"></i>
                                Chưa có rạp chiếu nào.
                            </div>
                        <?php else: ?>
                            <?php foreach ($cinemas as $c): 
                                $isActive = ($c['id'] == $selected_cinema_id) ? 'active' : '';
                            ?>
                                <a href="cinemas.php?selected_id=<?= $c['id'] ?>" class="cinema-list-item <?= $isActive ?>">
                                    <?php if ($c['logo_url']): ?>
                                        <img src="<?= htmlspecialchars($c['logo_url']) ?>" class="cinema-list-logo" alt="">
                                    <?php else: ?>
                                        <div class="cinema-list-logo"><i class="fa-solid fa-location-dot"></i></div>
                                    <?php endif; ?>
                                    <div class="cinema-list-info">
                                        <div class="cinema-list-name" title="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></div>
                                        <div class="cinema-list-addr" title="<?= htmlspecialchars($c['address']) ?>"><?= htmlspecialchars($c['address']) ?></div>
                                        <span class="cinema-list-badge"><i class="fa-solid fa-door-open"></i> <?= $c['halls_count'] ?> Phòng chiếu</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Selected Cinema Details & Halls -->
                <div class="cinema-details-card">
                    <?php if (!$selected_cinema): ?>
                        <div style="padding:80px 20px; text-align:center; color:#64748B;">
                            <i class="fa-solid fa-shop" style="font-size:48px; opacity:0.2; margin-bottom:16px; display:block;"></i>
                            <h3>Chọn một rạp từ danh sách để xem chi tiết phòng chiếu</h3>
                            <p style="margin-top:6px; font-size:13px;">Hoặc nhấn nút "Thêm rạp" để đăng ký chi nhánh rạp mới.</p>
                        </div>
                    <?php else: ?>
                        <!-- Details Header -->
                        <div class="details-header">
                            <?php if ($selected_cinema['logo_url']): ?>
                                <img src="<?= htmlspecialchars($selected_cinema['logo_url']) ?>" class="details-logo" alt="">
                            <?php else: ?>
                                <div class="details-logo"><i class="fa-solid fa-location-dot"></i></div>
                            <?php endif; ?>
                            
                            <div class="details-title-info">
                                <h2 class="details-title"><?= htmlspecialchars($selected_cinema['name']) ?></h2>
                                <div class="details-meta-row">
                                    <div class="details-meta-item"><i class="fa-solid fa-map-pin"></i> <span><?= htmlspecialchars($selected_cinema['address']) ?> (<?= htmlspecialchars($selected_cinema['city'] ?: 'Hà Nội') ?>)</span></div>
                                    <?php if ($selected_cinema['phone']): ?>
                                        <div class="details-meta-item"><i class="fa-solid fa-phone"></i> <span><?= htmlspecialchars($selected_cinema['phone']) ?></span></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="action-btns" style="align-self: flex-start;">
                                <button class="btn-cinema-action btn-cinema-edit" onclick='openEditCinemaModal(<?= json_encode($selected_cinema) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Sửa rạp
                                </button>
                                
                                <form method="POST" id="form-delete-cinema" style="display:none;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $selected_cinema['id'] ?>">
                                </form>
                                <button type="button" class="btn-cinema-action btn-cinema-delete" onclick="confirmDeleteCinema('<?= htmlspecialchars(addslashes($selected_cinema['name'])) ?>')">
                                    <i class="fa-solid fa-trash-can"></i> Xóa rạp
                                </button>
                            </div>
                        </div>

                        <!-- Halls Section -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                            <h3 style="font-size:15px; font-weight:800; color:#334155;"><i class="fa-solid fa-door-open" style="color:var(--primary-blue);margin-right:6px"></i>Sơ đồ Phòng chiếu (Halls)</h3>
                            <span style="font-size:12.5px; color:#64748B; font-weight:500;">Quy chuẩn rạp: <strong><?= count($selected_halls) ?> Phòng tiêu chuẩn</strong></span>
                        </div>

                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #475569; line-height: 1.5;">
                            <i class="fa-solid fa-circle-info" style="color: var(--primary-blue); margin-right: 6px;"></i>
                            Mỗi rạp chiếu phim trong hệ thống MovieFlex được chuẩn hóa đồng bộ **6 phòng chiếu tiêu chuẩn** (Phòng 01 đến Phòng 06, với sức chứa **100 ghế ngồi** tương đương sơ đồ 10 hàng ghế A-J). Tính năng thêm/xóa phòng chiếu thủ công được bỏ qua để đảm bảo tính nhất quán của sơ đồ phòng chiếu toàn hệ thống.
                        </div>

                        <table class="halls-table">
                            <thead>
                                <tr>
                                    <th>Tên phòng chiếu</th>
                                    <th>Loại phòng</th>
                                    <th>Sức chứa</th>
                                    <th>Cơ cấu hàng ghế</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selected_halls as $hall): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($hall['name']) ?></strong></td>
                                        <td><span class="halls-badge">Tiêu chuẩn (Standard)</span></td>
                                        <td><strong><?= $hall['total_seats'] ?> ghế</strong></td>
                                        <td>Hàng A đến J (10 ghế/hàng)</td>
                                        <td><span style="color:#10B981; font-weight:700; font-size:12.5px;"><i class="fa-solid fa-circle-check"></i> Đang hoạt động</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Cinema Modal -->
    <div id="cinemaModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 540px;">
            <div class="modal-header" style="border-bottom: 1px solid #E2E8F0; padding-bottom: 16px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
                <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-shop" style="color:var(--primary-blue); font-size:18px;"></i>
                    <h3 id="modal-title-text" style="font-size:16px; font-weight:800;">Thêm Rạp chiếu mới</h3>
                </div>
                <button class="close-modal" onclick="closeCinemaModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:#64748B;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="form-id" value="0">
                
                <div class="modal-form-grid">
                    <div class="form-group-custom full-row">
                        <label>Tên rạp chiếu *</label>
                        <input type="text" name="name" id="form-name" class="form-input-custom" placeholder="Ví dụ: CGV Vincom Center" required>
                    </div>

                    <div class="form-group-custom full-row">
                        <label>Địa chỉ rạp *</label>
                        <input type="text" name="address" id="form-address" class="form-input-custom" placeholder="Số, tên đường, quận/huyện..." required>
                    </div>

                    <div class="form-group-custom">
                        <label>Thành phố *</label>
                        <select name="city" id="form-city" class="form-input-custom" required>
                            <option value="Hà Nội">Hà Nội</option>
                            <option value="Hồ Chí Minh">TP. Hồ Chí Minh</option>
                            <option value="Đà Nẵng">Đà Nẵng</option>
                            <option value="Cần Thơ">Cần Thơ</option>
                            <option value="Hải Phòng">Hải Phòng</option>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label>Số điện thoại liên hệ</label>
                        <input type="text" name="phone" id="form-phone" class="form-input-custom" placeholder="Ví dụ: 024.3974.8888">
                    </div>

                    <div class="form-group-custom full-row">
                        <label>URL Ảnh đại diện / Logo Rạp</label>
                        <input type="url" name="logo_url" id="form-logo" class="form-input-custom" placeholder="Nhập liên kết hình ảnh rạp...">
                    </div>
                </div>

                <div id="add-cinema-hint" style="margin-top:16px; background:#ECFDF5; border:1px solid #A7F3D0; border-radius:8px; padding:10px; color:#065F46; font-size:12px; line-height:1.4;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    <strong>Tự động hóa thông minh:</strong> Khi thêm rạp thành công, hệ thống sẽ tự động khởi tạo và gán 6 phòng chiếu tiêu chuẩn (mỗi phòng 100 ghế) cho rạp này ngay lập tức.
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; border-top: 1px solid #E2E8F0; padding-top: 16px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="closeCinemaModal()">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Lưu rạp chiếu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function confirmDeleteCinema(cinemaName) {
            const ok = await mfConfirm({
                title: 'Xóa rạp chiếu',
                desc: `Bạn có chắc chắn muốn XÓA rạp <strong>${cinemaName}</strong>?<br><br>⚠️ Tất cả <strong>phòng chiếu</strong> liên kết sẽ bị xóa theo. Hành động này <strong>không thể hoàn tác</strong>.`,
                type: 'danger',
                confirmText: 'Xóa vĩnh viễn',
                confirmIcon: 'fa-trash-can',
                cancelText: 'Giữ lại'
            });
            if (ok) {
                document.getElementById('form-delete-cinema').submit();
            }
        }

        function openAddCinemaModal() {
            document.getElementById('modal-title-text').textContent = 'Thêm Rạp chiếu mới';
            document.getElementById('form-id').value = '0';
            document.getElementById('form-name').value = '';
            document.getElementById('form-address').value = '';
            document.getElementById('form-city').value = 'Hà Nội';
            document.getElementById('form-phone').value = '';
            document.getElementById('form-logo').value = '';
            document.getElementById('add-cinema-hint').style.display = 'block';

            document.getElementById('cinemaModal').classList.add('active');
        }

        function openEditCinemaModal(c) {
            document.getElementById('modal-title-text').textContent = 'Sửa thông tin Rạp #' + c.id;
            document.getElementById('form-id').value = c.id;
            document.getElementById('form-name').value = c.name;
            document.getElementById('form-address').value = c.address;
            document.getElementById('form-city').value = c.city || 'Hà Nội';
            document.getElementById('form-phone').value = c.phone || '';
            document.getElementById('form-logo').value = c.logo_url || '';
            document.getElementById('add-cinema-hint').style.display = 'none';

            document.getElementById('cinemaModal').classList.add('active');
        }

        function closeCinemaModal() {
            document.getElementById('cinemaModal').classList.remove('active');
        }
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>

