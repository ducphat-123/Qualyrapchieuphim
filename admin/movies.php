<?php
session_start();
// Check if user is logged in and is admin
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../db.php';

$message = '';
$messageType = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $duration_min = (int)($_POST['duration_min'] ?? 120);
        $release_date = $_POST['release_date'] ?? null;
        $rating = (float)($_POST['rating'] ?? 8.0);
        $poster_url = trim($_POST['poster_url'] ?? '');
        $backdrop_url = trim($_POST['backdrop_url'] ?? '');
        $trailer_url = trim($_POST['trailer_url'] ?? '');
        $director = trim($_POST['director'] ?? '');
        $cast_list = trim($_POST['cast_list'] ?? '');
        $age_rating = trim($_POST['age_rating'] ?? 'P');
        $status = $_POST['status'] ?? 'now_showing';

        if ($title) {
            $today = date('Y-m-d');
            if ($status === 'now_showing' && $release_date && $release_date > $today) {
                $message = "Lỗi logic: Phim có ngày khởi chiếu trong tương lai (" . date('d/m/Y', strtotime($release_date)) . ") không thể đặt trạng thái là <b>Đang chiếu</b>. Vui lòng chỉnh sửa ngày khởi chiếu hoặc chọn trạng thái 'Sắp chiếu'.";
                $messageType = 'error';
            } else {
                try {
                    if ($id > 0) {
                        // Update
                        $stmt = $pdo->prepare("UPDATE movies SET title=?, description=?, genre=?, duration_min=?, release_date=?, rating=?, poster_url=?, backdrop_url=?, trailer_url=?, director=?, cast_list=?, age_rating=?, status=? WHERE id=?");
                        $stmt->execute([$title, $description ?: null, $genre ?: null, $duration_min, $release_date ?: null, $rating, $poster_url ?: null, $backdrop_url ?: null, $trailer_url ?: null, $director ?: null, $cast_list ?: null, $age_rating, $status, $id]);
                        
                        // Log
                        $logDesc = "Đã cập nhật thông tin phim: \"$title\"";
                        $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Cập nhật phim', ?)")
                            ->execute([$_SESSION['user_name'], $logDesc]);

                        $message = "Đã cập nhật phim <b>" . htmlspecialchars($title) . "</b> thành công!";
                    } else {
                        // Create
                        $stmt = $pdo->prepare("INSERT INTO movies (title, description, genre, duration_min, release_date, rating, poster_url, backdrop_url, trailer_url, director, cast_list, age_rating, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$title, $description ?: null, $genre ?: null, $duration_min, $release_date ?: null, $rating, $poster_url ?: null, $backdrop_url ?: null, $trailer_url ?: null, $director ?: null, $cast_list ?: null, $age_rating, $status]);
                        
                        // Log
                        $logDesc = "Đã thêm phim mới: \"$title\"";
                        $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Thêm phim mới', ?)")
                            ->execute([$_SESSION['user_name'], $logDesc]);

                        $message = "Đã thêm phim mới <b>" . htmlspecialchars($title) . "</b> thành công!";
                    }
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = "Lỗi hệ thống: " . $e->getMessage();
                    $messageType = 'error';
                }
            }
        } else {
            $message = "Vui lòng điền tiêu đề phim.";
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // Get movie title first for logging
                $mStmt = $pdo->prepare("SELECT title FROM movies WHERE id = ?");
                $mStmt->execute([$id]);
                $title = $mStmt->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
                $stmt->execute([$id]);
                
                // Log
                $logDesc = "Đã xóa phim: \"$title\"";
                $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, 'admin', 'Xóa phim', ?)")
                    ->execute([$_SESSION['user_name'], $logDesc]);

                $message = "Đã xóa phim thành công!";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Không thể xóa phim này do đã có Suất chiếu được xếp lịch.";
                $messageType = 'error';
            }
        }
    }
}

// Fetch stats
$total_movies = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
$now_showing = $pdo->query("SELECT COUNT(*) FROM movies WHERE status = 'now_showing'")->fetchColumn();
$coming_soon = $pdo->query("SELECT COUNT(*) FROM movies WHERE status = 'coming_soon'")->fetchColumn();

// Fetch movie list
$movies = $pdo->query("SELECT * FROM movies ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieFlex Admin - Quản lý Phim</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .movie-modal-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            max-height: 70vh;
            overflow-y: auto;
            padding-right: 8px;
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
        textarea.form-input-custom {
            height: 80px;
            padding: 8px 12px;
            resize: vertical;
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
        .text-center {
            text-align: center;
        }
        .badge-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
        }
        .badge-status.now_showing {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        .badge-status.coming_soon {
            background-color: #E0F2F1;
            color: #00897B;
        }
        .badge-status.ended {
            background-color: #F1F3F5;
            color: #636e72;
        }
        .action-btns {
            display: flex;
            gap: 8px;
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
            color: var(--danger-text);
        }
        .action-btn.delete:hover {
            background-color: var(--danger-bg);
        }
        .movie-thumb {
            width: 45px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            background-color: #f1f3f5;
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
                        <h1>Quản lý Phim điện ảnh</h1>
                        <p>Thêm, sửa đổi hoặc dừng chiếu các bộ phim trên toàn hệ thống rạp MovieFlex.</p>
                    </div>
                    <div class="dashboard-actions">
                        <button class="btn btn-primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Thêm Phim mới</button>
                    </div>
                </div>

                <!-- Alert Message -->
                <?php if ($message): ?>
                <div class="alert-bar <?= $messageType ?>">
                    <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                    <span><?= $message ?></span>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="stat-cards-bottom" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px; margin-top: 0;">
                    <div class="kpi-card" style="padding: 16px 20px;">
                        <span class="kpi-label">TỔNG SỐ PHIM</span>
                        <h3 class="kpi-value" style="font-size: 24px; margin-bottom:4px;"><?= $total_movies ?></h3>
                        <span style="font-size:12px; color:var(--text-muted);">Đã đăng ký hệ thống</span>
                    </div>
                    <div class="kpi-card" style="padding: 16px 20px;">
                        <span class="kpi-label" style="color:var(--success-text);">ĐANG TRÌNH CHIẾU</span>
                        <h3 class="kpi-value" style="font-size: 24px; margin-bottom:4px; color:var(--success-text);"><?= $now_showing ?></h3>
                        <span style="font-size:12px; color:var(--success-text);"><i class="fa-solid fa-circle active-dot" style="background:var(--success-text); margin-right:4px;"></i> Đang phục vụ suất chiếu</span>
                    </div>
                    <div class="kpi-card" style="padding: 16px 20px;">
                        <span class="kpi-label" style="color:#00897B;">SẮP TRÌNH CHIẾU</span>
                        <h3 class="kpi-value" style="font-size: 24px; margin-bottom:4px; color:#00897B;"><?= $coming_soon ?></h3>
                        <span style="font-size:12px; color:#00897B;">Phim đã lên lịch ra mắt</span>
                    </div>
                </div>

                <!-- Filter & Search Bar -->
                <div class="card" style="padding: 24px;">
                    <div class="filter-bar" style="display: flex; gap: 16px; margin-bottom: 20px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                        <div class="search-bar" style="width: 320px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-input" placeholder="Tìm theo tên phim, thể loại..." oninput="filterTable()">
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-circle-info" style="color: var(--text-muted);"></i>
                                <select id="filter-status" onchange="filterTable()" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit;">
                                    <option value="">Mọi trạng thái</option>
                                    <option value="now_showing">Đang chiếu</option>
                                    <option value="coming_soon">Sắp chiếu</option>
                                    <option value="ended">Đã dừng chiếu</option>
                                </select>
                            </div>
                            <div class="filter-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; background: white; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-shield-halved" style="color: var(--text-muted);"></i>
                                <select id="filter-age" onchange="filterTable()" style="border: none; outline: none; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: inherit;">
                                    <option value="">Mọi lứa tuổi</option>
                                    <option value="P">P - Mọi lứa tuổi</option>
                                    <option value="T13">T13 - Trên 13 tuổi</option>
                                    <option value="T16">T16 - Trên 16 tuổi</option>
                                    <option value="T18">T18 - Trên 18 tuổi</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Poster</th>
                                <th>TÊN BỘ PHIM</th>
                                <th>THỂ LOẠI</th>
                                <th class="text-center">THỜI LƯỢNG</th>
                                <th>ĐẠO DIỄN</th>
                                <th>KHỞI CHIẾU</th>
                                <th class="text-center">ĐỘ TUỔI</th>
                                <th>TRẠNG THÁI</th>
                                <th style="width: 100px;">THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody id="moviesBody">
                            <?php if (empty($movies)): ?>
                            <tr><td colspan="9" class="text-center" style="padding: 40px; color: var(--text-muted);">Không tìm thấy bộ phim nào trong hệ thống.</td></tr>
                            <?php else: ?>
                            <?php foreach ($movies as $m): 
                                $status_class = $m['status'];
                                $status_text = 'Đang chiếu';
                                if ($m['status'] === 'coming_soon') $status_text = 'Sắp chiếu';
                                if ($m['status'] === 'ended') $status_text = 'Đã kết thúc';
                            ?>
                            <tr data-status="<?= $m['status'] ?>" data-age="<?= htmlspecialchars($m['age_rating']) ?>">
                                <td>
                                    <?php if ($m['poster_url']): ?>
                                        <img src="<?= htmlspecialchars($m['poster_url']) ?>" alt="Poster" class="movie-thumb">
                                    <?php else: ?>
                                        <div class="movie-thumb" style="display:flex;align-items:center;justify-content:center;color:#adb5bd;font-size:20px;"><i class="fa-solid fa-film"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: #111; font-size:14.5px;"><?= htmlspecialchars($m['title']) ?></strong>
                                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">⭐ <?= number_format($m['rating'], 1) ?>/10</div>
                                </td>
                                <td><?= htmlspecialchars($m['genre'] ?? '—') ?></td>
                                <td class="text-center"><strong><?= $m['duration_min'] ?></strong> phút</td>
                                <td><?= htmlspecialchars($m['director'] ?? '—') ?></td>
                                <td><?= $m['release_date'] ? date('d/m/Y', strtotime($m['release_date'])) : '—' ?></td>
                                <td class="text-center"><strong style="color:#d9480f;"><?= htmlspecialchars($m['age_rating']) ?></strong></td>
                                <td><span class="badge-status <?= $status_class ?>"><?= $status_text ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="action-btn edit" title="Chỉnh sửa" onclick='openEditModal(<?= json_encode($m) ?>)'><i class="fa-solid fa-pen-to-square"></i></button>
                                        
                                        <form method="POST" id="delete-movie-form-<?= $m['id'] ?>" style="display:none;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        </form>
                                        <button type="button" class="action-btn delete" title="Xóa" onclick="confirmDeleteMovie(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['title'])) ?>')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Movie Add / Edit Modal -->
    <div id="movieModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 680px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
                <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-film" style="color:var(--primary-blue); font-size:18px;"></i>
                    <h3 id="modal-title-text" style="font-size:17px; font-weight:700;">Thêm Phim mới</h3>
                </div>
                <button class="close-modal" onclick="closeModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:var(--text-muted);"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form method="POST" onsubmit="return validateMovieForm(this);">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="form-id" value="0">
                
                <div class="movie-modal-form">
                    <div class="form-group-custom full-width">
                        <label>Tiêu đề phim *</label>
                        <input type="text" name="title" id="form-title" class="form-input-custom" placeholder="Ví dụ: Lật Mặt 7: Một Điều Ước" required>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Thể loại *</label>
                        <input type="text" name="genre" id="form-genre" class="form-input-custom" placeholder="Ví dụ: Hành động, Kịch tính" required>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Trạng thái trình chiếu</label>
                        <select name="status" id="form-status" class="form-input-custom">
                            <option value="now_showing">Đang chiếu</option>
                            <option value="coming_soon">Sắp chiếu</option>
                            <option value="ended">Đã kết thúc (Dừng chiếu)</option>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label>Thời lượng (phút) *</label>
                        <input type="number" name="duration_min" id="form-duration" class="form-input-custom" placeholder="Ví dụ: 120" required min="1">
                    </div>

                    <div class="form-group-custom">
                        <label>Điểm đánh giá (0 - 10)</label>
                        <input type="number" name="rating" id="form-rating" class="form-input-custom" placeholder="Ví dụ: 8.5" step="0.1" min="0" max="10" value="8.0">
                    </div>

                    <div class="form-group-custom">
                        <label>Đạo diễn</label>
                        <input type="text" name="director" id="form-director" class="form-input-custom" placeholder="Ví dụ: Lý Hải">
                    </div>

                    <div class="form-group-custom">
                        <label>Giới hạn độ tuổi (Giới hạn độ tuổi rạp)</label>
                        <select name="age_rating" id="form-age" class="form-input-custom">
                            <option value="P">P (Mọi lứa tuổi)</option>
                            <option value="T13">T13 (13+ trở lên)</option>
                            <option value="T16">T16 (16+ trở lên)</option>
                            <option value="T18">T18 (18+ trở lên - Khán giả người lớn)</option>
                        </select>
                    </div>

                    <div class="form-group-custom full-width">
                        <label>Diễn viên chính</label>
                        <input type="text" name="cast_list" id="form-cast" class="form-input-custom" placeholder="Ví dụ: Thanh Hằng, Chi Pu, Kiều Minh Tuấn...">
                    </div>

                    <div class="form-group-custom">
                        <label>Ngày khởi chiếu</label>
                        <input type="date" name="release_date" id="form-release" class="form-input-custom">
                    </div>

                    <div class="form-group-custom">
                        <label>URL Trailer (YouTube)</label>
                        <input type="url" name="trailer_url" id="form-trailer" class="form-input-custom" placeholder="https://www.youtube.com/watch?...">
                    </div>

                    <div class="form-group-custom full-width">
                        <label>Đường dẫn Poster (URL) *</label>
                        <input type="url" name="poster_url" id="form-poster" class="form-input-custom" placeholder="https://images.unsplash.com/..." required>
                    </div>

                    <div class="form-group-custom full-width">
                        <label>Đường dẫn Banner (Backdrop URL)</label>
                        <input type="url" name="backdrop_url" id="form-backdrop" class="form-input-custom" placeholder="https://images.unsplash.com/...">
                    </div>

                    <div class="form-group-custom full-width">
                        <label>Nội dung phim ngắn gọn *</label>
                        <textarea name="description" id="form-desc" class="form-input-custom" placeholder="Mô tả nội dung tóm tắt cốt truyện của bộ phim..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:12px; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Lưu thông tin</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modal-title-text').textContent = 'Thêm Phim mới';
            document.getElementById('form-id').value = '0';
            document.getElementById('form-title').value = '';
            document.getElementById('form-genre').value = '';
            document.getElementById('form-status').value = 'now_showing';
            document.getElementById('form-duration').value = '';
            document.getElementById('form-rating').value = '8.0';
            document.getElementById('form-director').value = '';
            document.getElementById('form-age').value = 'P';
            document.getElementById('form-cast').value = '';
            document.getElementById('form-release').value = '';
            document.getElementById('form-trailer').value = '';
            document.getElementById('form-poster').value = '';
            document.getElementById('form-backdrop').value = '';
            document.getElementById('form-desc').value = '';
            
            document.getElementById('movieModal').classList.add('active');
        }

        function openEditModal(m) {
            document.getElementById('modal-title-text').textContent = 'Chỉnh sửa Phim #' + m.id;
            document.getElementById('form-id').value = m.id;
            document.getElementById('form-title').value = m.title;
            document.getElementById('form-genre').value = m.genre || '';
            document.getElementById('form-status').value = m.status;
            document.getElementById('form-duration').value = m.duration_min;
            document.getElementById('form-rating').value = m.rating;
            document.getElementById('form-director').value = m.director || '';
            document.getElementById('form-age').value = m.age_rating || 'P';
            document.getElementById('form-cast').value = m.cast_list || '';
            document.getElementById('form-release').value = m.release_date || '';
            document.getElementById('form-trailer').value = m.trailer_url || '';
            document.getElementById('form-poster').value = m.poster_url || '';
            document.getElementById('form-backdrop').value = m.backdrop_url || '';
            document.getElementById('form-desc').value = m.description || '';
            
            document.getElementById('movieModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('movieModal').classList.remove('active');
        }

        function filterTable() {
            const query = document.getElementById('search-input').value.toLowerCase().trim();
            const statusFilter = document.getElementById('filter-status').value;
            const ageFilter = document.getElementById('filter-age').value;
            
            const tbody = document.getElementById('moviesBody');
            const rows = tbody.querySelectorAll('tr[data-status]');
            
            // Remove existing empty-state row if any
            const existingEmpty = document.getElementById('filter-empty-row');
            if (existingEmpty) existingEmpty.remove();
            
            let visibleCount = 0;
            rows.forEach(row => {
                const titleEl = row.cells[1]?.querySelector('strong');
                if (!titleEl) return;
                
                const title = titleEl.textContent.toLowerCase();
                const genre = row.cells[2]?.textContent.toLowerCase() ?? '';
                const director = row.cells[4]?.textContent.toLowerCase() ?? '';
                const status = row.dataset.status;
                const age = row.dataset.age;
                
                const matchesQuery = !query || title.includes(query) || genre.includes(query) || director.includes(query);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesAge = !ageFilter || age === ageFilter;

                if (matchesQuery && matchesStatus && matchesAge) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show empty state row if nothing matches
            if (visibleCount === 0 && rows.length > 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.id = 'filter-empty-row';
                emptyRow.innerHTML = `
                    <td colspan="9" style="text-align:center; padding: 48px 20px;">
                        <div style="display:flex; flex-direction:column; align-items:center; gap:12px; color:var(--text-muted);">
                            <div style="width:56px;height:56px;border-radius:16px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-size:22px;">
                                <i class="fa-solid fa-film-slash" style="opacity:.4;"></i>
                            </div>
                            <div>
                                <div style="font-size:15px;font-weight:700;color:var(--text-main);margin-bottom:4px;">Không tìm thấy phim nào</div>
                                <div style="font-size:13px;">Thử thay đổi từ khoá hoặc bộ lọc để xem kết quả khác.</div>
                            </div>
                        </div>
                    </td>`;
                tbody.appendChild(emptyRow);
            }
        }


        async function confirmDeleteMovie(id, title) {
            const ok = await mfConfirm({
                title: 'Xóa bộ phim',
                desc: `Bạn có chắc chắn muốn xóa phim <strong>${title}</strong>?<br><br>⚠️ Hành động sẽ thất bại nếu phim này đang có suất chiếu được xếp lịch trong hệ thống.`,
                type: 'danger',
                confirmText: 'Xóa phim',
                confirmIcon: 'fa-trash-can',
                cancelText: 'Giữ lại'
            });
            if (ok) document.getElementById(`delete-movie-form-${id}`).submit();
        }

        function validateMovieForm(form) {
            const status = document.getElementById('form-status').value;
            const releaseDateVal = document.getElementById('form-release').value;
            
            if (status === 'now_showing' && releaseDateVal) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const todayStr = `${year}-${month}-${day}`;
                
                if (releaseDateVal > todayStr) {
                    mfToast(
                        'Lỗi logic ngày khởi chiếu',
                        `Phim có ngày khởi chiếu ${formatDateStr(releaseDateVal)} nằm trong tương lai, không thể đặt trạng thái "Đang chiếu". Hãy đổi trạng thái thành "Sắp chiếu".`,
                        'warning', 6000
                    );
                    document.getElementById('form-release').focus();
                    return false;
                }
            }
            return true;
        }

        function formatDateStr(dateStr) {
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return parts[2] + '/' + parts[1] + '/' + parts[0];
            }
            return dateStr;
        }
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>

