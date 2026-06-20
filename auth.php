<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$action = $_POST['action'] ?? '';

// ─── ĐĂNG NHẬP ──────────────────────────────────────────
if ($action === 'login') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = trim($_POST['password']   ?? '');
    $remember   = !empty($_POST['remember']);

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tài khoản và mật khẩu của bạn.']);
        exit;
    }

    // Tìm theo email hoặc tên đăng nhập (sử dụng email làm username)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu của bạn không đúng.']);
        exit;
    }

    // Lưu session
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_role']  = $user['role'];

    // Cập nhật last_login
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    // Redirect theo role
    if ($user['role'] === 'admin' || $user['role'] === 'admin_monitor') {
        $redirect = 'admin/index.php';
    } elseif ($user['role'] === 'staff') {
        $redirect = 'staff.php';
    } else {
        $redirect = 'home.php';
    }

    echo json_encode([
        'success'  => true,
        'message'  => 'Đăng nhập thành công!',
        'redirect' => $redirect,
        'user'     => [
            'id'        => $user['id'],
            'name'      => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
        ]
    ]);
    exit;
}

// ─── ĐĂNG KÝ ────────────────────────────────────────────
if ($action === 'register') {
    $full_name        = trim($_POST['full_name']        ?? '');
    $email            = trim($_POST['email']            ?? '');
    $phone            = trim($_POST['phone']            ?? '');
    $password         = trim($_POST['password']         ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Định dạng email không hợp lệ. Vui lòng kiểm tra lại.']);
        exit;
    }
    if (!empty($phone) && !preg_match('/^(0[35789])[0-9]{8}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam gồm 10 chữ số (bắt đầu bằng 03, 05, 07, 08, 09).']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.']);
        exit;
    }
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp.']);
        exit;
    }

    // Kiểm tra email đã tồn tại
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email này đã được đăng ký. Vui lòng dùng email khác.']);
        exit;
    }

    // Tạo tài khoản
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, 'user', 'active')");
    $stmt->execute([$full_name, $email, $phone ?: null, $hash]);
    $newId = $pdo->lastInsertId();

    // Auto-create active welcome vouchers for this new user based on active master templates
    $templates = $pdo->query("SELECT code, description, discount_pct, discount_amt, min_order, is_active FROM vouchers WHERE code IN ('SUMMER30', 'NEWUSER50', 'MOVIE20') AND user_id IS NULL")->fetchAll();
    foreach ($templates as $t) {
        if ($t['is_active'] == 1) {
            $uniq = strtoupper(substr(uniqid(), -6));
            $vcode = $t['code'] . '-' . $uniq;
            $ins = $pdo->prepare("
                INSERT INTO vouchers (code, description, discount_pct, discount_amt, min_order, max_uses, used_count, expire_date, is_active, user_id)
                VALUES (?, ?, ?, ?, ?, 1, 0, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, ?)
            ");
            $ins->execute([$vcode, $t['description'], $t['discount_pct'], $t['discount_amt'], $t['min_order'], $newId]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đăng ký thành công! Bạn có thể đăng nhập ngay.',
    ]);
    exit;
}

// ─── ĐĂNG XUẤT ──────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'redirect' => 'login.php']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
