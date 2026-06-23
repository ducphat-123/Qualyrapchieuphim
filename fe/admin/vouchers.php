<?php
session_start();
if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'admin_monitor'])) {
    header('Location: ../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../be/config/db.php';

$message = '';
$messageType = '';

// Handle CREATE / UPDATE / DELETE operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $discount_type = $_POST['discount_type'] ?? 'pct';
        $discount_val = (int)($_POST['discount_value'] ?? 0);
        $min_order = (int)($_POST['min_order'] ?? 0);
        $max_uses = (int)($_POST['max_uses'] ?? 100);
        $expire_date = $_POST['expire_date'] ?? null;
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        $discount_pct = 0;
        $discount_amt = 0;
        if ($discount_type === 'pct') {
            $discount_pct = min(100, max(0, $discount_val));
        } else {
            $discount_amt = max(0, $discount_val);
        }

        if ($code && $description) {
            try {
                if ($id > 0) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE vouchers SET code=?, description=?, discount_pct=?, discount_amt=?, min_order=?, max_uses=?, expire_date=?, is_active=? WHERE id=?");
                    $stmt->execute([$code, $description, $discount_pct, $discount_amt, $min_order, $max_uses, $expire_date ?: null, $is_active, $id]);
                    $message = "Đã cập nhật voucher <b>$code</b> thành công!";
                } else {
                    // Create
                    $stmt = $pdo->prepare("INSERT INTO vouchers (code, description, discount_pct, discount_amt, min_order, max_uses, expire_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $description, $discount_pct, $discount_amt, $min_order, $max_uses, $expire_date ?: null, $is_active]);
                    $message = "Đã thêm voucher mới <b>$code</b> thành công!";
                }
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Lỗi: " . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = "Vui lòng nhập đầy đủ Mã và Mô tả voucher.";
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Đã xóa voucher thành công!";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Không thể xóa voucher này do đang có đơn hàng liên kết.";
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 1);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE vouchers SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $message = "Đã thay đổi trạng thái hoạt động của voucher!";
            $messageType = 'success';
        }
    }
}

// Fetch stats (only master templates/campaigns where user_id IS NULL)
$total_vouchers = $pdo->query("SELECT COUNT(*) FROM vouchers WHERE user_id IS NULL")->fetchColumn();
$active_vouchers = $pdo->query("SELECT COUNT(*) FROM vouchers WHERE user_id IS NULL AND is_active = 1 AND (expire_date IS NULL OR expire_date >= CURDATE())")->fetchColumn();
$expired_vouchers = $pdo->query("SELECT COUNT(*) FROM vouchers WHERE user_id IS NULL AND (is_active = 0 OR (expire_date IS NOT NULL AND expire_date < CURDATE()))")->fetchColumn();

// Fetch voucher list (only master templates/campaigns where user_id IS NULL)
$vouchers = $pdo->query("SELECT * FROM vouchers WHERE user_id IS NULL ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieFlex Admin - Quản lý Voucher</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .voucher-modal-form {
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
        .badge-status.active {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        .badge-status.inactive {
            background-color: var(--danger-bg);
            color: var(--danger-text);
        }
        .badge-status.expired {
            background-color: var(--warning-bg);
            color: var(--warning-text);
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
        .action-btn.toggle {
            color: #4B5563;
        }
        .action-btn.toggle:hover {
            background-color: #F3F4F6;
        }

        /* VOUCHERS ADMIN GRID & CARDS */
        .vouchers-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 24px;
            padding: 20px 0;
        }
        .admin-voucher-card {
            display: flex;
            background: #fff;
            border-radius: 14px;
            border: 1.5px solid var(--border-color);
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(15,23,42,.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .admin-voucher-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(15,23,42,.08);
            border-color: var(--primary-blue);
        }
        .admin-voucher-left {
            background: linear-gradient(135deg, var(--primary-blue), #4F46E5);
            color: #fff;
            width: 105px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px 10px;
            text-align: center;
            position: relative;
            flex-shrink: 0;
        }
        .admin-voucher-left.inactive-left {
            background: linear-gradient(135deg, #94A3B8, #64748B) !important;
        }
        .admin-voucher-left::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 0;
            bottom: 0;
            width: 12px;
            background-image: radial-gradient(circle at 12px 6px, #FAFBFD 5px, transparent 5px);
            background-size: 12px 12px;
        }
        .admin-v-val {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .admin-v-type {
            font-size: 9px;
            opacity: .9;
            margin-top: 3px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .admin-voucher-right {
            flex: 1;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #FAFBFD;
        }
        .admin-v-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            gap: 8px;
        }
        .admin-v-code {
            font-family: monospace;
            font-size: 14px;
            font-weight: 800;
            color: var(--primary-blue);
            background: #EFF6FF;
            padding: 3px 8px;
            border-radius: 6px;
            border: 1.5px dashed #93C5FD;
            letter-spacing: 0.5px;
        }
        .admin-v-code.inactive-code {
            color: #64748B !important;
            background: #F1F5F9 !important;
            border-color: #CBD5E1 !important;
        }
        .admin-v-desc {
            font-size: 13.5px;
            font-weight: 800;
            color: #1F2937;
            line-height: 1.4;
            margin-bottom: 8px;
        }
        .admin-v-meta {
            font-size: 11.5px;
            color: #6B7280;
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }
        .admin-v-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .admin-v-meta-item i {
            color: #9CA3AF;
            width: 12px;
        }
        .admin-v-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
        }

        /* PREMIUM ADMIN STAT CARDS */
        .admin-stat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            padding: 24px;
            border-radius: var(--radius-md);
            box-shadow: 0 4px 20px rgba(15,23,42,.02);
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .admin-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(15,23,42,.06);
            border-color: var(--primary-blue);
        }
        .admin-stat-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .admin-stat-info .title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .admin-stat-info .value {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
        }
        .admin-stat-info .desc {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .admin-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .admin-stat-icon.blue {
            background-color: #EFF6FF;
            color: var(--primary-blue);
            border: 1px solid #DBEAFE;
        }
        .admin-stat-icon.green {
            background-color: #ECFDF5;
            color: #10B981;
            border: 1px solid #D1FAE5;
        }
        .admin-stat-icon.red {
            background-color: #FEF2F2;
            color: #EF4444;
            border: 1px solid #FEE2E2;
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
                        <h1>Quản lý Mã giảm giá (Voucher)</h1>
                        <p>Tạo và quản lý các chương trình ưu đãi, mã voucher giảm giá cho rạp phim.</p>
                    </div>
                    <div class="dashboard-actions">
                        <button class="btn btn-primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Thêm Voucher mới</button>
                    </div>
                </div>

                <!-- Alert Message -->
                <?php if ($message): ?>
                <div class="alert-bar <?= $messageType ?>">
                    <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                    <span><?= $message ?></span>
                </div>
                <?php endif; ?>

                <!-- Voucher Stats -->
                <div class="stat-cards-bottom" style="margin-bottom: 24px; margin-top: 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div class="admin-stat-card">
                        <div class="admin-stat-info">
                            <span class="title">TỔNG SỐ VOUCHER</span>
                            <span class="value" id="stat-total"><?= $total_vouchers ?></span>
                            <span class="desc">Chương trình khuyến mãi</span>
                        </div>
                        <div class="admin-stat-icon blue">
                            <i class="fa-solid fa-tags"></i>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-info">
                            <span class="title">ĐANG HOẠT ĐỘNG</span>
                            <span class="value" style="color: #10B981;" id="stat-active"><?= $active_vouchers ?></span>
                            <span class="desc" style="color: #10B981;"><i class="fa-solid fa-circle" style="font-size: 8px; margin-right: 4px; vertical-align: middle;"></i> Đang có hiệu lực</span>
                        </div>
                        <div class="admin-stat-icon green">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="admin-stat-info">
                            <span class="title">ĐÃ HẾT HẠN / TẮT</span>
                            <span class="value" style="color: #EF4444;" id="stat-expired"><?= $expired_vouchers ?></span>
                            <span class="desc" style="color: #EF4444;">Không còn sử dụng</span>
                        </div>
                        <div class="admin-stat-icon red">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </div>
                    </div>
                </div>

                <!-- Voucher Table Card -->
                <div class="card">
                    <div class="filter-bar">
                        <div class="search-bar" style="width: 300px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="search-input" placeholder="Tìm theo mã hoặc mô tả..." oninput="filterTable()">
                        </div>
                        <div class="filter-item">
                            <i class="fa-solid fa-percent"></i>
                            <select id="filter-type" onchange="filterTable()">
                                <option value="">Mọi loại giảm giá</option>
                                <option value="percentage">Giảm theo %</option>
                                <option value="fixed">Giảm tiền mặt</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <i class="fa-solid fa-toggle-on"></i>
                            <select id="filter-status" onchange="filterTable()">
                                <option value="">Mọi trạng thái</option>
                                <option value="active">Đang hoạt động</option>
                                <option value="inactive">Đã tắt</option>
                                <option value="expired">Hết hạn</option>
                            </select>
                        </div>
                    </div>

                    <!-- VOUCHER CARDS GRID -->
                    <div class="vouchers-admin-grid" id="vouchersBody">
                        <?php if (empty($vouchers)): ?>
                            <div style="text-align:center; padding:60px 20px; color:var(--text-muted); grid-column:1 / -1;">
                                <i class="fa-solid fa-tag" style="font-size:48px; opacity:.2; display:block; margin-bottom:12px"></i>
                                <h3 style="font-size:15px; font-weight:700">Không tìm thấy voucher nào</h3>
                            </div>
                        <?php else: ?>
                            <?php foreach ($vouchers as $v): 
                                $is_expired = ($v['expire_date'] !== null && strtotime($v['expire_date']) < strtotime(date('Y-m-d')));
                                $status_class = 'inactive';
                                $status_text = 'Đã tắt';
                                if ($v['is_active'] == 1) {
                                    if ($is_expired) {
                                        $status_class = 'expired';
                                        $status_text = 'Hết hạn';
                                    } else {
                                        $status_class = 'active';
                                        $status_text = 'Đang chạy';
                                    }
                                }
                                $valText = $v['discount_pct'] > 0 ? $v['discount_pct'] . '%' : number_format($v['discount_amt']/1000) . 'K';
                                $typeText = $v['discount_pct'] > 0 ? 'GIẢM GIÁ' : 'TIỀN MẶT';
                                $type_val = $v['discount_pct'] > 0 ? 'percentage' : 'fixed';
                                $isActive = ($v['is_active'] == 1 && !$is_expired);
                                
                                // Determine voucher type
                                $type_badge = '';
                                if ($v['user_id'] !== null) {
                                    $type_badge = '<span style="font-size: 9px; font-weight: 800; background: #FFF7ED; color: #EA580C; padding: 2px 6px; border-radius: 4px; border: 1px solid #FFEDD5; display: inline-flex; align-items: center; gap: 3px;"><i class="fa-solid fa-user"></i> KH đã đổi</span>';
                                } elseif (in_array($v['code'], ['REDM30K', 'REDM50K', 'REDM100K', 'GIFTPOP'])) {
                                    $type_badge = '<span style="font-size: 9px; font-weight: 800; background: #EEF2F6; color: #4F46E5; padding: 2px 6px; border-radius: 4px; border: 1px solid #C7D2FE; display: inline-flex; align-items: center; gap: 3px;"><i class="fa-solid fa-gift"></i> Shop đổi quà</span>';
                                } else {
                                    $type_badge = '<span style="font-size: 9px; font-weight: 800; background: #ECFDF5; color: #059669; padding: 2px 6px; border-radius: 4px; border: 1px solid #A7F3D0; display: inline-flex; align-items: center; gap: 3px;"><i class="fa-solid fa-globe"></i> Mặc định / Sự kiện</span>';
                                }
                            ?>
                            <div class="admin-voucher-card" data-type="<?= $type_val ?>" data-status="<?= $status_class ?>" data-code="<?= htmlspecialchars($v['code']) ?>" data-desc="<?= htmlspecialchars($v['description']) ?>">
                                <div class="admin-voucher-left <?= $isActive ? '' : 'inactive-left' ?>">
                                    <span class="admin-v-val"><?= $valText ?></span>
                                    <span class="admin-v-type"><?= $typeText ?></span>
                                </div>
                                <div class="admin-voucher-right">
                                    <div>
                                        <div class="admin-v-header">
                                            <span class="admin-v-code <?= $isActive ? '' : 'inactive-code' ?>"><?= htmlspecialchars($v['code']) ?></span>
                                            <?= $type_badge ?>
                                        </div>
                                        <div class="admin-v-desc"><?= htmlspecialchars($v['description']) ?></div>
                                    </div>
                                    
                                    <div>
                                        <div class="admin-v-meta">
                                            <div class="admin-v-meta-item">
                                                <i class="fa-regular fa-user"></i>
                                                <span>Lượt dùng: <strong><?= $v['used_count'] ?></strong> / <?= $v['max_uses'] ?></span>
                                            </div>
                                            <div class="admin-v-meta-item">
                                                <i class="fa-solid fa-circle-info"></i>
                                                <span>Đơn tối thiểu: <strong><?= number_format($v['min_order'], 0, ',', '.') ?>₫</strong></span>
                                            </div>
                                            <div class="admin-v-meta-item">
                                                <i class="fa-regular fa-clock"></i>
                                                <span>HSD: <strong><?= $v['expire_date'] ? date('d/m/Y', strtotime($v['expire_date'])) : 'Vô thời hạn' ?></strong></span>
                                            </div>
                                            <div class="admin-v-meta-item">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span>Trạng thái: <span class="badge-status <?= $status_class ?>" style="padding: 2px 8px; font-size:10px;"><?= $status_text ?></span></span>
                                            </div>
                                        </div>
                                        
                                        <div class="admin-v-actions">
                                            <!-- Toggle Switch Form -->
                                            <form method="POST" style="margin: 0; display: inline-flex; align-items: center;">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $v['is_active'] == 1 ? 0 : 1 ?>">
                                                <button type="submit" class="admin-toggle-btn" title="<?= $v['is_active'] == 1 ? 'Tắt hoạt động' : 'Kích hoạt' ?>">
                                                    <i class="fa-solid <?= $v['is_active'] == 1 ? 'fa-toggle-on' : 'fa-toggle-off' ?>" style="font-size: 24px; color: <?= $v['is_active'] == 1 ? 'var(--success-text)' : 'var(--text-muted)' ?>; transition: color 0.2s;"></i>
                                                    <span style="font-size: 11.5px; font-weight: 700; color: #4B5563; margin-left: 6px;"><?= $v['is_active'] == 1 ? 'Đang bật' : 'Đang tắt' ?></span>
                                                </button>
                                            </form>
                                            
                                            <!-- Edit / Delete Actions -->
                                            <div class="action-btns" style="margin: 0;">
                                                <button class="action-btn edit" title="Chỉnh sửa" onclick='openEditModal(<?= json_encode($v) ?>)' style="padding: 5px 8px; border: 1.5px solid #D1D5DB; border-radius: 8px;"><i class="fa-solid fa-pen-to-square"></i></button>
                                                
                                                <form method="POST" id="delete-voucher-form-<?= $v['id'] ?>" style="display:none;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                                </form>
                                                <button type="button" class="action-btn delete" title="Xóa" style="padding: 5px 8px; border: 1.5px solid rgba(239, 68, 68, 0.2); border-radius: 8px;" onclick="confirmDeleteVoucher(<?= $v['id'] ?>, '<?= $v['code'] ?>')">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Voucher Add / Edit Modal -->
    <div id="voucherModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px;">
                <div class="modal-title" style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-tag" style="color:var(--primary-blue); font-size:18px;"></i>
                    <h3 id="modal-title-text" style="font-size:17px; font-weight:700;">Thêm Voucher mới</h3>
                </div>
                <button class="close-modal" onclick="closeModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:var(--text-muted);"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="form-id" value="0">
                
                <div class="voucher-modal-form">
                    <div class="form-group-custom">
                        <label>Mã Voucher *</label>
                        <input type="text" name="code" id="form-code" class="form-input-custom" placeholder="Ví dụ: SALE30" style="text-transform: uppercase;" required>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Trạng thái kích hoạt</label>
                        <select name="is_active" id="form-active" class="form-input-custom">
                            <option value="1">Kích hoạt</option>
                            <option value="0">Tạm tắt</option>
                        </select>
                    </div>

                    <div class="form-group-custom full-width">
                        <label>Mô tả ưu đãi *</label>
                        <input type="text" name="description" id="form-desc" class="form-input-custom" placeholder="Ví dụ: Giảm 30k cho hóa đơn từ 150k" required>
                    </div>

                    <div class="form-group-custom">
                        <label>Loại giảm giá</label>
                        <select name="discount_type" id="form-type" class="form-input-custom" onchange="toggleDiscountPlaceholder()">
                            <option value="pct">Giảm theo %</option>
                            <option value="amt">Giảm tiền mặt (₫)</option>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label id="discount-label">Mức giảm (%) *</label>
                        <input type="number" name="discount_value" id="form-discount" class="form-input-custom" placeholder="Nhập mức giảm..." required min="1">
                    </div>

                    <div class="form-group-custom">
                        <label>Đơn tối thiểu (₫)</label>
                        <input type="number" name="min_order" id="form-min-order" class="form-input-custom" placeholder="Ví dụ: 100000" min="0" value="0">
                    </div>

                    <div class="form-group-custom">
                        <label>Giới hạn lượt dùng</label>
                        <input type="number" name="max_uses" id="form-max" class="form-input-custom" placeholder="Ví dụ: 100" min="1" value="100">
                    </div>

                    <div class="form-group-custom full-width">
                        <label>Ngày hết hạn</label>
                        <input type="date" name="expire_date" id="form-expire" class="form-input-custom">
                    </div>
                </div>

                <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:12px; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 24px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Lưu thông tin</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function confirmDeleteVoucher(id, code) {
            const ok = await mfConfirm({
                title: 'Xóa mã Voucher',
                desc: `Bạn có chắc chắn muốn xóa vĩnh viễn voucher <strong>${code}</strong>?<br><br>Sau khi xóa, mã này sẽ không thể khôi phục và khách hàng sẽ không dùng được nữa.`,
                type: 'danger',
                confirmText: 'Xóa voucher',
                confirmIcon: 'fa-trash-can',
                cancelText: 'Giữ lại'
            });
            if (ok) document.getElementById(`delete-voucher-form-${id}`).submit();
        }

        function openAddModal() {
            document.getElementById('modal-title-text').textContent = 'Thêm Voucher mới';
            document.getElementById('form-id').value = '0';
            document.getElementById('form-code').value = '';
            document.getElementById('form-code').readOnly = false;
            document.getElementById('form-active').value = '1';
            document.getElementById('form-desc').value = '';
            document.getElementById('form-type').value = 'pct';
            document.getElementById('form-discount').value = '';
            document.getElementById('form-min-order').value = '0';
            document.getElementById('form-max').value = '100';
            document.getElementById('form-expire').value = '';
            
            toggleDiscountPlaceholder();
            document.getElementById('voucherModal').classList.add('active');
        }

        function openEditModal(v) {
            document.getElementById('modal-title-text').textContent = 'Chỉnh sửa Voucher #' + v.code;
            document.getElementById('form-id').value = v.id;
            document.getElementById('form-code').value = v.code;
            document.getElementById('form-code').readOnly = true;
            document.getElementById('form-active').value = v.is_active;
            document.getElementById('form-desc').value = v.description;
            
            if (parseInt(v.discount_pct) > 0) {
                document.getElementById('form-type').value = 'pct';
                document.getElementById('form-discount').value = v.discount_pct;
            } else {
                document.getElementById('form-type').value = 'amt';
                document.getElementById('form-discount').value = v.discount_amt;
            }
            
            document.getElementById('form-min-order').value = v.min_order;
            document.getElementById('form-max').value = v.max_uses;
            document.getElementById('form-expire').value = v.expire_date || '';
            
            toggleDiscountPlaceholder();
            document.getElementById('voucherModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('voucherModal').classList.remove('active');
        }

        function toggleDiscountPlaceholder() {
            const type = document.getElementById('form-type').value;
            const label = document.getElementById('discount-label');
            const input = document.getElementById('form-discount');
            
            if (type === 'pct') {
                label.textContent = 'Mức giảm (%) *';
                input.placeholder = 'Ví dụ: 20';
                input.max = '100';
            } else {
                label.textContent = 'Mức giảm (₫) *';
                input.placeholder = 'Ví dụ: 30000';
                input.removeAttribute('max');
            }
        }

        function filterTable() {
            const query = document.getElementById('search-input').value.toLowerCase().trim();
            const typeFilter = document.getElementById('filter-type').value;
            const statusFilter = document.getElementById('filter-status').value;
            
            const container = document.getElementById('vouchersBody');
            
            // Remove existing empty-state element if any
            const existingEmpty = document.getElementById('filter-empty-row');
            if (existingEmpty) existingEmpty.remove();
            
            const cards = container.querySelectorAll('.admin-voucher-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const code = card.dataset.code.toLowerCase();
                const desc = card.dataset.desc.toLowerCase();
                const type = card.dataset.type;
                const status = card.dataset.status;
                
                const matchesQuery = code.includes(query) || desc.includes(query);
                const matchesType = !typeFilter || type === typeFilter;
                const matchesStatus = !statusFilter || status === statusFilter;
                
                if (matchesQuery && matchesType && matchesStatus) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show empty state if nothing matches
            if (visibleCount === 0 && cards.length > 0) {
                const emptyDiv = document.createElement('div');
                emptyDiv.id = 'filter-empty-row';
                emptyDiv.style.gridColumn = '1 / -1';
                emptyDiv.style.textAlign = 'center';
                emptyDiv.style.padding = '48px 20px';
                emptyDiv.innerHTML = `
                    <div style="display:flex; flex-direction:column; align-items:center; gap:12px; color:var(--text-muted);">
                        <div style="width:56px;height:56px;border-radius:16px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-size:22px;">
                            <i class="fa-solid fa-ticket-simple" style="opacity:.4; transform: rotate(-45deg);"></i>
                        </div>
                        <div>
                            <div style="font-size:15px;font-weight:700;color:var(--text-main);margin-bottom:4px;">Không tìm thấy mã giảm giá nào</div>
                            <div style="font-size:13px;">Thử thay đổi từ khoá hoặc bộ lọc để xem kết quả khác.</div>
                        </div>
                    </div>`;
                container.appendChild(emptyDiv);
            }
        }
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>

