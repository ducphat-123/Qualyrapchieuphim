<?php
session_start();
// Check if user is logged in and is admin
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../be/config/db.php';

function checkShowtimeConflict($id, $cinema_id, $hall_name, $show_date, $start_time, $end_time, $movie_id) {
    global $pdo;

    // Check operating hours: 08:00 to 23:30
    $start_time_val = substr(trim($start_time), 0, 5); // get HH:MM
    if ($start_time_val < '08:00' || $start_time_val > '23:30') {
        return "Giờ bắt đầu suất chiếu không hợp lệ! Suất chiếu chỉ được phép bắt đầu từ 08:00 đến 23:30.";
    }

    $new_duration = 120;
    $mStmt = $pdo->prepare("SELECT duration_min FROM movies WHERE id = ?");
    $mStmt->execute([$movie_id]);
    $movie_row = $mStmt->fetch();
    if ($movie_row) {
        $new_duration = (int)$movie_row['duration_min'];
    }

    $new_start_ts = strtotime($show_date . ' ' . $start_time);
    if (!empty($end_time)) {
        $new_end_ts = strtotime($show_date . ' ' . $end_time);
        if ($new_end_ts <= $new_start_ts) {
            $new_end_ts += 86400; // ends next day
        }
    } else {
        $new_end_ts = $new_start_ts + ($new_duration * 60);
    }
    $new_block_end_ts = $new_end_ts + (60 * 60); // 60 min buffer

    // Fetch existing showtimes in same cinema/hall around same date
    $sql = "
        SELECT s.id, s.show_date, s.start_time, s.end_time, m.duration_min, m.title as movie_title
        FROM showtimes s
        JOIN movies m ON s.movie_id = m.id
        WHERE s.cinema_id = ? 
          AND s.hall_name = ? 
          AND s.is_cancelled = 0
          AND s.show_date BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 1 DAY)
    ";
    
    $params = [$cinema_id, $hall_name, $show_date, $show_date];
    if ($id > 0) {
        $sql .= " AND s.id != ?";
        $params[] = $id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $existing = $stmt->fetchAll();

    foreach ($existing as $row) {
        $duration = (int)($row['duration_min'] ?: 120);
        $db_start_ts = strtotime($row['show_date'] . ' ' . $row['start_time']);
        
        if (!empty($row['end_time'])) {
            $db_end_ts = strtotime($row['show_date'] . ' ' . $row['end_time']);
            if ($db_end_ts <= $db_start_ts) {
                $db_end_ts += 86400;
            }
        } else {
            $db_end_ts = $db_start_ts + ($duration * 60);
        }
        $db_block_end_ts = $db_end_ts + (60 * 60);

        if ($new_start_ts < $db_block_end_ts && $db_start_ts < $new_block_end_ts) {
            $existing_start = date('H:i', $db_start_ts);
            $existing_end = date('H:i', $db_end_ts);
            $existing_cleanup = date('H:i', $db_block_end_ts);
            return "Trùng lịch chiếu tại {$hall_name}! Suất chiếu của phim \"{$row['movie_title']}\" diễn ra từ {$existing_start} đến {$existing_end} (Cần dọn dẹp đến {$existing_cleanup}).";
        }
    }

    return null; // No conflict
}

// AJAX endpoint to get halls for a selected cinema
if (isset($_GET['action']) && $_GET['action'] === 'get_cinema_halls') {
    header('Content-Type: application/json; charset=utf-8');
    $cinema_id = (int)($_GET['cinema_id'] ?? 0);
    if ($cinema_id > 0) {
        $stmt = $pdo->prepare("SELECT name FROM cinema_halls WHERE cinema_id = ? ORDER BY name ASC");
        $stmt->execute([$cinema_id]);
        $halls = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'halls' => $halls]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cinema ID không hợp lệ']);
    }
    exit;
}

// AJAX endpoint to check showtime conflict
if (isset($_GET['action']) && $_GET['action'] === 'check_conflict') {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    $movie_id = (int)($_GET['movie_id'] ?? 0);
    $cinema_id = (int)($_GET['cinema_id'] ?? 0);
    $hall_name = trim($_GET['hall_name'] ?? '');
    $show_date = $_GET['show_date'] ?? '';
    $start_time = $_GET['start_time'] ?? '';
    $end_time = $_GET['end_time'] ?? null;

    if (!$movie_id || !$cinema_id || !$show_date || !$start_time || !$hall_name) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
        exit;
    }

    // Check movie release date vs show date
    $mStmt = $pdo->prepare("SELECT release_date, title FROM movies WHERE id = ?");
    $mStmt->execute([$movie_id]);
    $movie_info = $mStmt->fetch();
    if ($movie_info && $movie_info['release_date'] && $show_date < $movie_info['release_date']) {
        $formatted_release = date('d/m/Y', strtotime($movie_info['release_date']));
        echo json_encode([
            'success' => false,
            'message' => "Không thể tạo suất chiếu cho phim \"" . $movie_info['title'] . "\" trước ngày công chiếu ($formatted_release)."
        ]);
        exit;
    }

    $conflict = checkShowtimeConflict($id, $cinema_id, $hall_name, $show_date, $start_time, $end_time, $movie_id);
    if ($conflict) {
        echo json_encode(['success' => false, 'conflict' => true, 'message' => $conflict]);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}

$message = '';
$messageType = '';

// Handle Showtime actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        $cinema_id = (int)($_POST['cinema_id'] ?? 0);
        $hall_name = trim($_POST['hall_name'] ?? 'Phòng chiếu 1');
        $show_date = $_POST['show_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? null;
        $format = $_POST['format'] ?? '2D';
        $subtitle_type = $_POST['subtitle_type'] ?? 'Phụ đề';
        $price = (float)($_POST['price'] ?? 80000);
        $total_seats = (int)($_POST['total_seats'] ?? 100);

        if ($movie_id && $cinema_id && $show_date && $start_time) {
            $today = date('Y-m-d');
            if ($show_date < $today) {
                $message = "Lỗi logic: Không thể thêm hoặc cập nhật suất chiếu vào ngày trong quá khứ ($show_date). Vui lòng chọn từ ngày hôm nay trở đi.";
                $messageType = 'error';
            } else {
                try {
                    // Check release date conflict
                    $mStmt = $pdo->prepare("SELECT release_date, title FROM movies WHERE id = ?");
                    $mStmt->execute([$movie_id]);
                    $movie_info = $mStmt->fetch();
                    $release_date = $movie_info ? $movie_info['release_date'] : null;
                    $movie_title = $movie_info ? $movie_info['title'] : '';

                    if ($release_date && $show_date < $release_date) {
                        $formatted_release = date('d/m/Y', strtotime($release_date));
                        $message = "Lỗi logic: Không thể tạo suất chiếu cho phim \"$movie_title\" trước ngày công chiếu ($formatted_release).";
                        $messageType = 'error';
                    } else {
                        // Calculate end_time dynamically (start_time + movie duration) if not provided
                        if (empty($end_time)) {
                            $dStmt = $pdo->prepare("SELECT duration_min FROM movies WHERE id = ?");
                            $dStmt->execute([$movie_id]);
                            $duration = (int)$dStmt->fetchColumn() ?: 120;
                            $end_ts = strtotime($show_date . ' ' . $start_time) + ($duration * 60);
                            $end_time = date('H:i:s', $end_ts);
                        }

                        // Check conflict
                        $conflict = checkShowtimeConflict($id, $cinema_id, $hall_name, $show_date, $start_time, $end_time, $movie_id);
                        if ($conflict) {
                            $message = $conflict;
                            $messageType = 'error';
                        } else {
                            if ($id > 0) {
                                // Check if there are active bookings
                                $bStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE showtime_id = ? AND status != 'cancelled'");
                                $bStmt->execute([$id]);
                                $booked_count = (int)$bStmt->fetchColumn();

                                if ($booked_count > 0) {
                                    $message = "Lỗi logic: Suất chiếu này đã có $booked_count vé được đặt/mua. Không được phép chỉnh sửa thông tin (chỉ hủy khẩn cấp khi gặp sự cố).";
                                    $messageType = 'error';
                                } else {
                                    // Update
                                    $stmt = $pdo->prepare("UPDATE showtimes SET movie_id=?, cinema_id=?, hall_name=?, show_date=?, start_time=?, end_time=?, format=?, subtitle_type=?, price=?, total_seats=? WHERE id=?");
                                    $stmt->execute([$movie_id, $cinema_id, $hall_name, $show_date, $start_time, $end_time, $format, $subtitle_type, $price, $total_seats, $id]);
                                    
                                    // Log
                                    $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Cập nhật suất chiếu', ?)")
                                        ->execute([$_SESSION['user_name'], "Đã cập nhật suất chiếu #$id"]);

                                    $message = "Đã cập nhật suất chiếu thành công!";
                                    $messageType = 'success';
                                }
                            } else {
                                // Create
                                $stmt = $pdo->prepare("INSERT INTO showtimes (movie_id, cinema_id, hall_name, show_date, start_time, end_time, format, subtitle_type, price, total_seats, available_seats) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$movie_id, $cinema_id, $hall_name, $show_date, $start_time, $end_time, $format, $subtitle_type, $price, $total_seats, $total_seats]);
                                $newId = $pdo->lastInsertId();

                                // Log
                                $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Thêm suất chiếu', ?)")
                                    ->execute([$_SESSION['user_name'], "Đã tạo suất chiếu mới #$newId"]);

                                $message = "Đã thêm suất chiếu mới thành công!";
                                $messageType = 'success';
                            }
                        }
                    }
                } catch (Exception $e) {
                    $message = "Lỗi hệ thống: " . $e->getMessage();
                    $messageType = 'error';
                }
            }
        } else {
            $message = "Vui lòng điền đầy đủ các thông tin bắt buộc.";
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // Check if any bookings exist for this showtime ID
                $bStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE showtime_id = ? AND status != 'cancelled'");
                $bStmt->execute([$id]);
                $booked_count = (int)$bStmt->fetchColumn();

                if ($booked_count > 0) {
                    $message = "Lỗi logic: Suất chiếu này đã có $booked_count vé được đặt/mua. Không được phép xóa suất chiếu (chỉ hủy khẩn cấp khi gặp sự cố).";
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM showtimes WHERE id = ?");
                    $stmt->execute([$id]);

                    // Log
                    $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Xóa suất chiếu', ?)")
                        ->execute([$_SESSION['user_name'], "Đã xóa suất chiếu #$id"]);

                    $message = "Đã xóa suất chiếu thành công!";
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                $message = "Không thể xóa suất chiếu này do đã có vé được đặt liên kết.";
                $messageType = 'error';
            }
        }
    } elseif ($action === 'cancel_urgent') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // Fetch showtime details first to verify status
                $sStmt = $pdo->prepare("
                    SELECT s.*, m.title as movie_title, m.duration_min, c.name as cinema_name 
                    FROM showtimes s
                    JOIN movies m ON s.movie_id = m.id
                    JOIN cinemas c ON s.cinema_id = c.id
                    WHERE s.id = ? LIMIT 1
                ");
                $sStmt->execute([$id]);
                $showtime = $sStmt->fetch();

                if (!$showtime) {
                    throw new Exception("Suất chiếu không tồn tại.");
                }

                // Check status: must not be past or showing
                $now = time();
                $start_ts = strtotime($showtime['show_date'] . ' ' . $showtime['start_time']);
                if (!empty($showtime['end_time'])) {
                    $end_ts = strtotime($showtime['show_date'] . ' ' . $showtime['end_time']);
                    if ($end_ts <= $start_ts) {
                        $end_ts += 86400; // ends next day
                    }
                } else {
                    $duration = (int)($showtime['duration_min'] ?: 120);
                    $end_ts = $start_ts + ($duration * 60);
                }

                if ($now > $end_ts) {
                    throw new Exception("Suất chiếu này đã chiếu xong. Không được phép hủy khẩn cấp.");
                }
                if ($now >= $start_ts && $now <= $end_ts) {
                    throw new Exception("Suất chiếu này đang chiếu. Không được phép hủy khẩn cấp.");
                }

                $pdo->beginTransaction();

                // 2. Mark showtime as cancelled
                $pdo->prepare("UPDATE showtimes SET is_cancelled = 1 WHERE id = ?")->execute([$id]);

                // 3. Find and refund all active bookings
                $bStmt = $pdo->prepare("SELECT * FROM bookings WHERE showtime_id = ? AND status != 'cancelled'");
                $bStmt->execute([$id]);
                $bookings = $bStmt->fetchAll();

                $refundCount = 0;
                $totalRefunded = 0;
                foreach ($bookings as $bk) {
                    // Update booking status with cancellation reason
                    $pdo->prepare("UPDATE bookings SET status = 'cancelled', payment_status = 'refunded', cancel_reason = 'Hệ thống tự động hủy và hoàn tiền do suất chiếu gặp sự cố kỹ thuật' WHERE id = ?")
                        ->execute([$bk['id']]);

                    // Refund applied vouchers if any
                    if ($bk['voucher_code']) {
                        $pdo->prepare("UPDATE vouchers SET used_count = GREATEST(0, used_count - 1) WHERE code = ?")
                            ->execute([$bk['voucher_code']]);
                    }

                    $refundCount++;
                    $totalRefunded += $bk['total_amount'];
                }

                // 4. Write system log
                $logDesc = "Đã HỦY KHẨN CẤP suất chiếu #$id (Phim: \"{$showtime['movie_title']}\", Ngày: " . date('d/m/Y', strtotime($showtime['show_date'])) . " lúc " . substr($showtime['start_time'], 0, 5) . ") tại {$showtime['cinema_name']} ({$showtime['hall_name']}). Đã hoàn trả $refundCount vé với tổng số tiền " . number_format($totalRefunded, 0, ',', '.') . "₫.";
                
                $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Hủy suất chiếu khẩn cấp', ?)")
                    ->execute([$_SESSION['user_name'], $logDesc]);

                $pdo->commit();
                
                $message = "Đã HỦY KHẨN CẤP suất chiếu thành công! Hoàn trả $refundCount hóa đơn đặt vé với tổng trị giá " . number_format($totalRefunded, 0, ',', '.') . "₫. Khách hàng đã được gửi thông báo hoàn tiền.";
                $messageType = 'success';

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = "Lỗi khi thực hiện hủy khẩn cấp: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Fetch Movies and Cinemas lists for forms
$movies_list = $pdo->query("SELECT id, title, duration_min, release_date, status FROM movies WHERE status IN ('now_showing', 'coming_soon') ORDER BY title ASC")->fetchAll();
$cinemas_list = $pdo->query("SELECT id, name FROM cinemas ORDER BY name ASC")->fetchAll();

// Fetch showtimes list
$showtimes = $pdo->query("
    SELECT s.*, m.title as movie_title, m.poster_url, m.duration_min, c.name as cinema_name,
           (SELECT COUNT(*) FROM bookings b WHERE b.showtime_id = s.id AND b.status != 'cancelled') as booked_tickets_count
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.id
    JOIN cinemas c ON s.cinema_id = c.id
    ORDER BY s.show_date DESC, s.start_time DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieFlex Admin - Quản lý Suất chiếu</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .showtime-modal-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .full-width {
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
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0 12px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input-custom:focus {
            border-color: var(--primary-blue);
        }
        select.form-input-custom {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 10px center;
            background-repeat: no-repeat;
            background-size: 20px;
            padding-right: 30px;
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
            background-color: var(--success-bg);
            color: var(--success-text);
            border: 1px solid #C9F7D0;
        }
        .alert-bar.error {
            background-color: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid #FFCDD2;
        }
        .badge-status {
            display: inline-block;
            padding: 3.5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-status.upcoming {
            background-color: #E0F2FE;
            color: #0369A1;
        }
        .badge-status.showing {
            background-color: #FEF3C7;
            color: #D97706;
            animation: pulse-showing 2s infinite ease-in-out;
        }
        .badge-status.past {
            background-color: #F3F4F6;
            color: #4B5563;
        }
        .badge-status.cancelled {
            background-color: var(--danger-bg);
            color: var(--danger-text);
        }
        @keyframes pulse-showing {
            0% { box-shadow: 0 0 0 0 rgba(217, 119, 6, 0.4); }
            70% { box-shadow: 0 0 0 5px rgba(217, 119, 6, 0); }
            100% { box-shadow: 0 0 0 0 rgba(217, 119, 6, 0); }
        }
        .action-btns {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .action-btn {
            border: none;
            background: none;
            cursor: pointer;
            font-size: 15px;
            padding: 6px;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
        }
        .action-btn.edit {
            color: var(--primary-blue);
        }
        .action-btn.edit:hover {
            background-color: #E8F0FE;
        }
        .action-btn.delete {
            color: var(--text-muted);
        }
        .action-btn.delete:hover {
            background-color: #F1F3F5;
            color: #111;
        }
        .btn-cancel-urgent {
            background-color: var(--danger-text);
            color: white;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-cancel-urgent:hover {
            background-color: #900;
            box-shadow: 0 2px 8px rgba(198, 40, 40, 0.25);
        }
        /* Warnings inside modals */
        .urgent-warning-box {
            background: #FFF9DB;
            border: 1px solid #FFE066;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            color: #856404;
        }
        .urgent-warning-box i {
            font-size: 24px;
            margin-top: 2px;
        }
        .urgent-warning-box h4 {
            font-weight: 700;
            margin-bottom: 4px;
            color: #664d03;
        }
        /* Pagination CSS */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }
        .pagination-btn {
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
            color: var(--text-main);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            user-select: none;
            gap: 6px;
        }
        .pagination-btn:hover:not(.disabled) {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            background-color: #F0F7FF;
        }
        .pagination-btn.active {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #F9FAFB;
            color: var(--text-muted);
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
                        <h1>Quản lý Suất chiếu rạp</h1>
                        <p>Xếp lịch chiếu, điều chỉnh thông tin suất chiếu hoặc hủy khẩn cấp khi xảy ra sự cố kỹ thuật.</p>
                    </div>
                    <div class="dashboard-actions">
                        <button class="btn btn-primary" onclick="openAddModal()"><i class="fa-solid fa-calendar-plus"></i> Thêm Suất chiếu mới</button>
                    </div>
                </div>

                <!-- Alert Message -->
                <?php if ($message): ?>
                <div class="alert-bar <?= $messageType ?>">
                    <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <span><?= $message ?></span>
                </div>
                <?php endif; ?>

                <!-- Table Content Card -->
                <div class="card" style="padding: 24px;">
                    <div class="filter-bar" style="display: flex; gap: 16px; margin-bottom: 20px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                        <div class="search-bar" style="width: 300px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-input" placeholder="Tìm theo tên phim, tên rạp..." oninput="filterTable(true)">
                        </div>
                        
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <!-- Bộ lọc rạp chiếu -->
                            <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-location-dot" style="color: var(--text-muted);"></i>
                                <select id="filter-cinema" onchange="filterTable(true)" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                                    <option value="">Tất cả rạp</option>
                                    <?php foreach ($cinemas_list as $cn): ?>
                                        <option value="<?= htmlspecialchars($cn['name']) ?>"><?= htmlspecialchars($cn['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Bộ lọc ngày chiếu -->
                            <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-calendar-days" style="color: var(--text-muted);"></i>
                                <input type="date" id="filter-date" onchange="filterTable(true)" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                            </div>

                             <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-filter" style="color: var(--text-muted);"></i>
                                <select id="filter-status" onchange="filterTable(true)" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="upcoming">Sắp chiếu</option>
                                    <option value="showing">Đang chiếu</option>
                                    <option value="past">Đã chiếu</option>
                                    <option value="cancelled">Đã hủy khẩn cấp</option>
                                </select>
                            </div>
                            <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-clapperboard" style="color: var(--text-muted);"></i>
                                <select id="filter-format" onchange="filterTable(true)" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit; color: var(--text-main);">
                                    <option value="">Mọi định dạng</option>
                                    <option value="2D">2D</option>
                                    <option value="3D">3D</option>
                                    <option value="IMAX">IMAX</option>
                                    <option value="4DX">4DX</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>PHIM & ĐỊNH DẠNG</th>
                                <th>CHI NHÁNH RẠP</th>
                                <th>PHÒNG CHIẾU</th>
                                <th>NGÀY CHIẾU</th>
                                <th>GIỜ CHIẾU</th>
                                <th>GIÁ VÉ</th>
                                <th class="text-center">SỨC CHỨA</th>
                                <th class="text-center">TRẠNG THÁI</th>
                                <th style="width: 240px;">THAO TÁC QUẢN TRỊ</th>
                            </tr>
                        </thead>
                        <tbody id="showtimesBody">
                            <?php if (empty($showtimes)): ?>
                            <tr><td colspan="9" class="text-center" style="padding: 40px; color: var(--text-muted);">Không tìm thấy suất chiếu nào được tạo lập.</td></tr>
                            <?php else: ?>
                            <?php foreach ($showtimes as $s): 
                                $isCancelled = ($s['is_cancelled'] == 1);
                                $bookedTicketsCount = (int)$s['booked_tickets_count'];
                                $hasBookings = ($bookedTicketsCount > 0);
                                if ($isCancelled) {
                                    $status_class = 'cancelled';
                                    $status_text = 'Đã hủy khẩn cấp';
                                } else {
                                    $now = time();
                                    $start_ts = strtotime($s['show_date'] . ' ' . $s['start_time']);
                                    if (!empty($s['end_time'])) {
                                        $end_ts = strtotime($s['show_date'] . ' ' . $s['end_time']);
                                        if ($end_ts <= $start_ts) {
                                            $end_ts += 86400; // ends next day
                                        }
                                    } else {
                                        $duration = (int)($s['duration_min'] ?: 120);
                                        $end_ts = $start_ts + ($duration * 60);
                                    }

                                    if ($now > $end_ts) {
                                        $status_class = 'past';
                                        $status_text = 'Đã chiếu';
                                    } elseif ($now >= $start_ts && $now <= $end_ts) {
                                        $status_class = 'showing';
                                        $status_text = 'Đang chiếu';
                                    } else {
                                        $status_class = 'upcoming';
                                        $status_text = 'Sắp chiếu';
                                    }
                                }
                            ?>
                              <tr data-status="<?= $status_class ?>" data-format="<?= $s['format'] ?>" data-date="<?= $s['show_date'] ?>" data-cinema="<?= htmlspecialchars($s['cinema_name']) ?>">
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php if ($s['poster_url']): ?>
                                            <img src="<?= htmlspecialchars($s['poster_url']) ?>" alt="" style="width:30px; height:40px; object-fit:cover; border-radius:4px;">
                                        <?php endif; ?>
                                        <div>
                                            <strong style="color: #111;"><?= htmlspecialchars($s['movie_title']) ?></strong>
                                            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">
                                                <span style="font-weight:700; color:var(--primary-blue);"><?= $s['format'] ?></span> &middot; <?= $s['subtitle_type'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($s['cinema_name']) ?></td>
                                <td><strong><?= htmlspecialchars($s['hall_name'] ?? 'Phòng chiếu 1') ?></strong></td>
                                <td><strong><?= date('d/m/Y', strtotime($s['show_date'])) ?></strong></td>
                                <td>
                                    <span style="font-weight: 700; color: #111;"><?= substr($s['start_time'], 0, 5) ?></span>
                                    <span style="font-size:11.5px; color:var(--text-muted);"> – <?= $s['end_time'] ? substr($s['end_time'], 0, 5) : '—' ?></span>
                                </td>
                                <td><strong><?= number_format($s['price'], 0, ',', '.') ?>₫</strong></td>
                                <td class="text-center">
                                    <strong><?= $s['available_seats'] ?></strong> / <?= $s['total_seats'] ?>
                                    <div style="font-size: 11px; color:var(--text-muted); margin-top:2px;">Đã bán: <?= $s['booked_tickets_count'] ?> vé</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-status <?= $status_class ?>"><?= $status_text ?></span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <?php if (!$isCancelled): ?>
                                            <?php if ($hasBookings): ?>
                                                <button class="action-btn edit" style="opacity: 0.5; cursor: not-allowed;" title="Không thể sửa suất chiếu đã có vé được đặt" onclick="mfToast('Không thể chỉnh sửa', 'Suất chiếu đã có <?= $bookedTicketsCount ?> vé được đặt. Không được phép chỉnh sửa thông tin.', 'warning')"><i class="fa-solid fa-pen-to-square"></i></button>
                                            <?php else: ?>
                                                <button class="action-btn edit" title="Chỉnh sửa" onclick='openEditModal(<?= json_encode($s) ?>)'><i class="fa-solid fa-pen-to-square"></i></button>
                                            <?php endif; ?>
                                            
                                            <?php if ($status_class === 'upcoming'): ?>
                                                <!-- Hủy suất chiếu khẩn cấp -->
                                                <button class="btn-cancel-urgent" onclick="triggerUrgentCancel(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['movie_title'])) ?>', '<?= date('d/m/Y', strtotime($s['show_date'])) ?>', '<?= substr($s['start_time'], 0, 5) ?>', <?= $s['booked_tickets_count'] ?>)">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> HỦY KHẨN CẤP
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color:var(--danger-text); font-weight:700;"><i class="fa-solid fa-circle-xmark"></i> Suất chiếu đã hủy</span>
                                        <?php endif; ?>
                                        
                                        <form method="POST" id="delete-showtime-form-<?= $s['id'] ?>" style="display:none;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        </form>
                                        
                                        <?php if ($hasBookings): ?>
                                            <button type="button" class="action-btn delete" style="opacity: 0.5; cursor: not-allowed;" title="Không thể xóa suất chiếu đã có vé được đặt" onclick="mfToast('Không thể xóa', 'Suất chiếu đã có <?= $bookedTicketsCount ?> vé được đặt. Chỉ có thể thực hiện Hủy khẩn cấp.', 'danger')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="action-btn delete" title="Xóa bỏ" onclick="confirmDeleteShowtime(<?= $s['id'] ?>, 0)">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination Controls -->
                    <div id="pagination-controls" class="pagination-container"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Showtime Modal -->
    <div id="showtimeModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 580px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
                <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-calendar-days" style="color:var(--primary-blue); font-size:18px;"></i>
                    <h3 id="modal-title-text" style="font-size:17px; font-weight:700;">Thêm Suất chiếu mới</h3>
                </div>
                <button class="close-modal" onclick="closeModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:var(--text-muted);"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form id="showtimeForm" method="POST">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="form-id" value="0">
                
                <div class="showtime-modal-form">
                    <div class="form-group-custom full-width">
                        <label>Chọn phim trình chiếu *</label>
                        <select name="movie_id" id="form-movie" class="form-input-custom" required>
                            <option value="">-- Chọn phim --</option>
                            <?php foreach ($movies_list as $mv): 
                                $isComing = ($mv['status'] === 'coming_soon');
                                $releaseText = $mv['release_date'] ? date('d/m/Y', strtotime($mv['release_date'])) : '';
                                $optionLabel = htmlspecialchars($mv['title']) . " (" . $mv['duration_min'] . " phút)";
                                if ($isComing) {
                                    $optionLabel .= " [Sắp chiếu - Khởi chiếu: $releaseText]";
                                }
                            ?>
                                <option value="<?= $mv['id'] ?>" data-release-date="<?= htmlspecialchars($mv['release_date'] ?? '') ?>" data-title="<?= htmlspecialchars($mv['title']) ?>"><?= $optionLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Chọn chi nhánh rạp *</label>
                        <select name="cinema_id" id="form-cinema" class="form-input-custom" required onchange="loadCinemaHalls(this.value)">
                            <option value="">-- Chọn rạp chiếu --</option>
                            <?php foreach ($cinemas_list as $cn): ?>
                                <option value="<?= $cn['id'] ?>"><?= htmlspecialchars($cn['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Phòng chiếu (Hall) *</label>
                        <select name="hall_name" id="form-hall" class="form-input-custom" required>
                            <option value="">-- Chọn rạp trước --</option>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label>Ngày chiếu *</label>
                        <input type="date" name="show_date" id="form-date" class="form-input-custom" required>
                    </div>

                    <div class="form-group-custom">
                        <label>Giờ bắt đầu chiếu *</label>
                        <input type="time" name="start_time" id="form-start" class="form-input-custom" required step="1">
                    </div>

                    <div class="form-group-custom">
                        <label>Giờ kết thúc (Không bắt buộc)</label>
                        <input type="time" name="end_time" id="form-end" class="form-input-custom" step="1" placeholder="Hệ thống tự tính nếu trống">
                    </div>

                    <div class="form-group-custom">
                        <label>Định dạng chiếu</label>
                        <select name="format" id="form-format" class="form-input-custom">
                            <option value="2D">2D Digital</option>
                            <option value="3D">3D Digital</option>
                            <option value="IMAX">IMAX Cinema</option>
                            <option value="PREMIUM">Premium Lounge</option>
                            <option value="4DX">4DX Motion</option>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label>Hình thức dịch thuật</label>
                        <select name="subtitle_type" id="form-subtitle" class="form-input-custom">
                            <option value="Phụ đề">Phụ đề tiếng Việt (Sub)</option>
                            <option value="Lồng tiếng">Lồng tiếng tiếng Việt (Dub)</option>
                            <option value="Thuyết minh">Thuyết minh tiếng Việt (Voiceover)</option>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label>Giá vé cơ bản (₫) *</label>
                        <input type="number" name="price" id="form-price" class="form-input-custom" placeholder="Ví dụ: 80000" required min="10000" value="80000">
                    </div>

                    <div class="form-group-custom">
                        <label>Tổng sức chứa (ghế) *</label>
                        <input type="number" name="total_seats" id="form-seats" class="form-input-custom" placeholder="Ví dụ: 100" required min="10" value="100">
                    </div>
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:12px; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Lưu lịch chiếu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Urgent Cancel Showtime Warning Modal -->
    <div id="urgentCancelModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px; display:flex; justify-content:space-between; align-items:center;">
                <div class="modal-title" style="display:flex; align-items:center; gap:8px; color:var(--danger-text);">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:20px;"></i>
                    <h3 style="font-size:16px; font-weight:800;">CẢNH BÁO: HỦY SUẤT CHIẾU KHẨN CẤP</h3>
                </div>
                <button class="close-modal" onclick="closeUrgentModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:var(--text-muted);"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="urgent-warning-box">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <h4>Hành động có rủi ro cao</h4>
                    <p>Hủy khẩn cấp suất chiếu sẽ ngay lập tức vô hiệu hóa mọi vé phim đã phát hành, chuyển trạng thái thanh toán sang hoàn tiền, và giải phóng ghế ngồi. Hãy kiểm tra kỹ thông tin bên dưới.</p>
                </div>
            </div>

            <div style="font-size: 14px; line-height: 1.6; margin-bottom: 24px; border-bottom:1px solid var(--border-color); padding-bottom:16px; color:#4B5563;">
                <div style="margin-bottom: 8px;">🎬 Phim: <strong id="cancel-movie" style="color:#111;">—</strong></div>
                <div style="margin-bottom: 8px;">📅 Lịch chiếu: <strong id="cancel-schedule" style="color:#111;">—</strong></div>
                <div style="margin-bottom: 8px; padding: 10px; border-radius:8px; background:#FEE2E2; border:1px solid #FCA5A5; color:var(--danger-text); display:inline-block; font-weight:700;">
                    ⚠️ Số lượng vé đã bán thực tế: <span id="cancel-tickets-count">0</span> vé
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="cancel_urgent">
                <input type="hidden" name="id" id="cancel-showtime-id" value="0">
                
                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" class="btn btn-outline" onclick="closeUrgentModal()">Hủy bỏ</button>
                    <button type="submit" class="btn" style="background-color:var(--danger-text); color:white; font-weight:700;"><i class="fa-solid fa-check"></i> Xác nhận hủy khẩn cấp</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function loadCinemaHalls(cinemaId, selectedHallName = '') {
            const hallSelect = document.getElementById('form-hall');
            hallSelect.innerHTML = '<option value="">-- Đang tải phòng... --</option>';
            
            if (!cinemaId) {
                hallSelect.innerHTML = '<option value="">-- Chọn rạp trước --</option>';
                return;
            }
            
            try {
                const res = await fetch(`showtimes.php?action=get_cinema_halls&cinema_id=${cinemaId}`);
                const json = await res.json();
                
                if (json.success && json.halls.length > 0) {
                    hallSelect.innerHTML = '';
                    json.halls.forEach(hall => {
                        const opt = document.createElement('option');
                        opt.value = hall;
                        opt.textContent = hall;
                        if (hall === selectedHallName) {
                            opt.selected = true;
                        }
                        hallSelect.appendChild(opt);
                    });
                } else {
                    hallSelect.innerHTML = '<option value="">-- Không tìm thấy phòng --</option>';
                }
            } catch (e) {
                console.error('Error loading halls:', e);
                hallSelect.innerHTML = '<option value="">-- Lỗi tải phòng --</option>';
            }
        }

        async function confirmDeleteShowtime(id, bookedCount = 0) {
            if (bookedCount > 0) {
                mfToast('Không thể xóa', 'Suất chiếu đã có vé được đặt. Không được phép xóa suất chiếu (chỉ hủy khẩn cấp khi gặp sự cố).', 'danger');
                return;
            }
            const ok = await mfConfirm({
                title: 'Xóa suất chiếu',
                desc: 'Bạn có chắc chắn muốn <strong>XÓA VĨNH VIỄN</strong> suất chiếu này?<br><br>⚠️ Hành động sẽ thất bại nếu đã có vé được phát hành cho suất chiếu này.',
                type: 'danger',
                confirmText: 'Xóa suất chiếu',
                confirmIcon: 'fa-trash-can',
                cancelText: 'Giữ lại'
            });
            if (ok) document.getElementById(`delete-showtime-form-${id}`).submit();
        }

        document.getElementById('showtimeForm').addEventListener('submit', async function(e) {
            e.preventDefault(); // Chặn submit mặc định
            
            // 1. Kiểm tra ngày chiếu trong quá khứ
            const dateInput = this.querySelector('#form-date');
            if (dateInput) {
                const selectedDate = dateInput.value;
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const todayStr = `${year}-${month}-${day}`;
                
                if (selectedDate < todayStr) {
                    mfToast(
                        'Ngày chiếu không hợp lệ',
                        'Ngày chiếu không thể nằm trong quá khứ. Vui lòng chọn từ ngày hôm nay trở đi.',
                        'warning', 5000
                    );
                    dateInput.focus();
                    return;
                }
            }

            // 1b. Kiểm tra ngày chiếu so với ngày công chiếu của phim
            const movieSelect = document.getElementById('form-movie');
            const selectedOption = movieSelect.options[movieSelect.selectedIndex];
            if (selectedOption && selectedOption.value && dateInput) {
                const releaseDate = selectedOption.getAttribute('data-release-date');
                const movieTitle = selectedOption.getAttribute('data-title');
                const selectedDate = dateInput.value;
                if (releaseDate && selectedDate < releaseDate) {
                    const parts = releaseDate.split('-');
                    const formattedRelease = parts[2] + '/' + parts[1] + '/' + parts[0];
                    mfToast(
                        'Ngày chiếu không hợp lệ',
                        `Không thể tạo suất chiếu cho phim "${movieTitle}" trước ngày công chiếu (${formattedRelease}).`,
                        'warning', 5000
                    );
                    dateInput.focus();
                    return;
                }
            }

            // 2. Kiểm tra giờ hoạt động: 08:00 đến 23:30
            const startInput = this.querySelector('#form-start');
            if (startInput) {
                const startTime = startInput.value;
                const startTimeVal = startTime.substring(0, 5);
                if (startTimeVal < '08:00' || startTimeVal > '23:30') {
                    mfToast(
                        'Giờ chiếu không hợp lệ',
                        'Suất chiếu chỉ được phép bắt đầu trong khoảng thời gian từ 08:00 đến 23:30 hàng ngày.',
                        'warning', 5000
                    );
                    startInput.focus();
                    return;
                }
            }

            // 3. Thực hiện kiểm tra trùng lịch phòng chiếu qua AJAX
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnHTML = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xác thực lịch...';

            const id = document.getElementById('form-id').value;
            const movieId = document.getElementById('form-movie').value;
            const cinemaId = document.getElementById('form-cinema').value;
            const hallName = document.getElementById('form-hall').value;
            const showDate = document.getElementById('form-date').value;
            const startTime = document.getElementById('form-start').value;
            const endTime = document.getElementById('form-end').value;

            try {
                const url = `showtimes.php?action=check_conflict&id=${id}&movie_id=${movieId}&cinema_id=${cinemaId}&hall_name=${encodeURIComponent(hallName)}&show_date=${showDate}&start_time=${startTime}&end_time=${endTime}`;
                const res = await fetch(url);
                const json = await res.json();

                if (!json.success) {
                    mfToast('Xung đột lịch chiếu', json.message || 'Phòng chiếu này đang bận hoặc thời gian không hợp lệ.', 'danger', 7000);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHTML;
                    return;
                }

                // Hợp lệ -> submit form thực tế
                this.submit();
            } catch (err) {
                console.error('Lỗi khi kiểm tra trùng lịch:', err);
                mfToast('Lỗi hệ thống', 'Không thể kết nối máy chủ để kiểm tra lịch chiếu. Vui lòng thử lại.', 'warning', 5000);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHTML;
            }
        });

        function openAddModal() {
            document.getElementById('modal-title-text').textContent = 'Thêm Suất chiếu mới';
            document.getElementById('form-id').value = '0';
            document.getElementById('form-movie').value = '';
            document.getElementById('form-cinema').value = '';
            document.getElementById('form-hall').innerHTML = '<option value="">-- Chọn rạp trước --</option>';
            document.getElementById('form-date').value = '';
            document.getElementById('form-start').value = '';
            document.getElementById('form-end').value = '';
            document.getElementById('form-format').value = '2D';
            document.getElementById('form-subtitle').value = 'Phụ đề';
            document.getElementById('form-price').value = '80000';
            document.getElementById('form-seats').value = '100';
            
            document.getElementById('showtimeModal').classList.add('active');
        }

        function openEditModal(s) {
            if (parseInt(s.booked_tickets_count || 0) > 0) {
                mfToast('Không thể chỉnh sửa', 'Suất chiếu đã có vé được đặt. Không được phép chỉnh sửa thông tin.', 'warning');
                return;
            }
            document.getElementById('modal-title-text').textContent = 'Chỉnh sửa Suất chiếu #' + s.id;
            document.getElementById('form-id').value = s.id;
            document.getElementById('form-movie').value = s.movie_id;
            document.getElementById('form-cinema').value = s.cinema_id;
            loadCinemaHalls(s.cinema_id, s.hall_name);
            document.getElementById('form-date').value = s.show_date;
            document.getElementById('form-start').value = s.start_time;
            document.getElementById('form-end').value = s.end_time || '';
            document.getElementById('form-format').value = s.format;
            document.getElementById('form-subtitle').value = s.subtitle_type;
            document.getElementById('form-price').value = Math.round(s.price);
            document.getElementById('form-seats').value = s.total_seats;
            
            document.getElementById('showtimeModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('showtimeModal').classList.remove('active');
        }

        function triggerUrgentCancel(id, movieTitle, showDate, startTime, ticketsCount) {
            document.getElementById('cancel-showtime-id').value = id;
            document.getElementById('cancel-movie').textContent = movieTitle;
            document.getElementById('cancel-schedule').textContent = showDate + ' lúc ' + startTime;
            document.getElementById('cancel-tickets-count').textContent = ticketsCount;
            
            document.getElementById('urgentCancelModal').classList.add('active');
        }

        function closeUrgentModal() {
            document.getElementById('urgentCancelModal').classList.remove('active');
        }

        const rowsPerPage = 50;
        let currentPage = 1;

        function filterTable(resetPage = false) {
            if (resetPage) {
                currentPage = 1;
            }

            const query = document.getElementById('search-input').value.toLowerCase().trim();
            const statusFilter = document.getElementById('filter-status').value;
            const formatFilter = document.getElementById('filter-format').value;
            const cinemaFilter = document.getElementById('filter-cinema').value.toLowerCase();
            const dateFilter = document.getElementById('filter-date').value; // YYYY-MM-DD
            
            const tbody = document.getElementById('showtimesBody');
            
            // Remove existing empty-state row if any
            const existingEmpty = document.getElementById('filter-empty-row');
            if (existingEmpty) existingEmpty.remove();

            const rows = tbody.querySelectorAll('tr[data-status]');
            const matchedRows = [];
            
            rows.forEach(row => {
                const movie = row.cells[0].querySelector('strong').textContent.toLowerCase();
                const format = row.cells[0].querySelector('span').textContent.toLowerCase();
                const cinema = row.cells[1].textContent.toLowerCase();
                
                const status = row.dataset.status;
                const formatAttr = row.dataset.format;
                const dateText = row.dataset.date;
                const cinemaAttr = (row.dataset.cinema || '').toLowerCase();
                
                const matchesQuery = movie.includes(query) || cinema.includes(query);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesFormat = !formatFilter || formatAttr === formatFilter;
                const matchesCinema = !cinemaFilter || cinemaAttr.includes(cinemaFilter);
                const matchesDate = !dateFilter || dateText === dateFilter;
                
                if (matchesQuery && matchesStatus && matchesFormat && matchesCinema && matchesDate) {
                    matchedRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            // Show empty state row if nothing matches
            if (matchedRows.length === 0 && rows.length > 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.id = 'filter-empty-row';
                emptyRow.innerHTML = `
                    <td colspan="9" style="text-align:center; padding: 48px 20px;">
                        <div style="display:flex; flex-direction:column; align-items:center; gap:12px; color:var(--text-muted);">
                            <div style="width:56px;height:56px;border-radius:16px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-size:22px;">
                                <i class="fa-solid fa-calendar-xmark" style="opacity:.4;"></i>
                            </div>
                            <div>
                                <div style="font-size:15px;font-weight:700;color:var(--text-main);margin-bottom:4px;">Không tìm thấy suất chiếu nào</div>
                                <div style="font-size:13px;">Thử thay đổi từ khoá hoặc bộ lọc để xem kết quả khác.</div>
                            </div>
                        </div>
                    </td>`;
                tbody.appendChild(emptyRow);
            }

            // Pagination calculations
            const totalRows = matchedRows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }
            if (currentPage < 1) {
                currentPage = 1;
            }

            // Hide/Show matched rows depending on currentPage
            matchedRows.forEach((row, index) => {
                const startIdx = (currentPage - 1) * rowsPerPage;
                const endIdx = currentPage * rowsPerPage;
                if (index >= startIdx && index < endIdx) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            renderPaginationControls(totalPages);
        }

        function renderPaginationControls(totalPages) {
            const container = document.getElementById('pagination-controls');
            if (!container) return;

            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';

            // Previous button
            const prevDisabled = currentPage === 1 ? 'disabled' : '';
            html += `<button type="button" class="pagination-btn ${prevDisabled}" onclick="changePage(${currentPage - 1})"><i class="fa-solid fa-chevron-left"></i> Trước</button>`;

            // Page numbers
            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);

            if (endPage - startPage + 1 < maxVisible) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            if (startPage > 1) {
                html += `<button type="button" class="pagination-btn" onclick="changePage(1)">1</button>`;
                if (startPage > 2) {
                    html += `<span style="color:var(--text-muted); margin:0 4px; display:inline-block; line-height:36px;">...</span>`;
                }
            }

            for (let p = startPage; p <= endPage; p++) {
                const activeClass = p === currentPage ? 'active' : '';
                html += `<button type="button" class="pagination-btn ${activeClass}" onclick="changePage(${p})">${p}</button>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<span style="color:var(--text-muted); margin:0 4px; display:inline-block; line-height:36px;">...</span>`;
                }
                html += `<button type="button" class="pagination-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
            }

            // Next button
            const nextDisabled = currentPage === totalPages ? 'disabled' : '';
            html += `<button type="button" class="pagination-btn ${nextDisabled}" onclick="changePage(${currentPage + 1})">Sau <i class="fa-solid fa-chevron-right"></i></button>`;

            container.innerHTML = html;
        }

        function changePage(page) {
            currentPage = page;
            filterTable(false);
            
            // Smooth scroll to card title
            const header = document.querySelector('.dashboard-header');
            if (header) {
                header.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Initialize pagination
        filterTable(true);
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>

