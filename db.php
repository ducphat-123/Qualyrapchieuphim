<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'movieflex_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Kết nối cơ sở dữ liệu thất bại.']));
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Database migration: Ensure 'cancel_reason' column exists in 'bookings' table
try {
    $pdo->query("SELECT cancel_reason FROM bookings LIMIT 1");
} catch (Exception $e) {
    $pdo->query("ALTER TABLE bookings ADD COLUMN cancel_reason VARCHAR(255) DEFAULT NULL");
}

// Database migration: Merge admin_monitor role into admin
try {
    $pdo->exec("UPDATE users SET role = 'admin' WHERE role = 'admin_monitor'");
} catch (Exception $e) {
    // Ignore migration error
}

// Tự động chuyển vé confirmed -> checked_in nếu đã qua giờ chiếu
// ĐỒNG THỜI CỘNG ĐIỂM cho user dựa trên total_amount của vé đó
$pdo->exec("
  UPDATE bookings b
  JOIN showtimes s ON b.showtime_id = s.id
  JOIN users u ON b.user_id = u.id
  SET 
    b.status = 'checked_in',
    u.loyalty_points = u.loyalty_points + FLOOR(b.total_amount / 10000)
  WHERE b.status = 'confirmed' 
    AND (s.show_date < CURDATE() OR (s.show_date = CURDATE() AND s.start_time <= CURTIME()))
");

// Cập nhật lại member_tier cho user dựa trên tổng tiền vé đã xem (checked_in)
$pdo->exec("
  UPDATE users u
  JOIN (
    SELECT user_id, SUM(total_amount) as spent
    FROM bookings
    WHERE status = 'checked_in'
    GROUP BY user_id
  ) b ON u.id = b.user_id
  SET u.member_tier = CASE 
    WHEN b.spent >= 100000000 THEN 'PLATINUM'
    WHEN b.spent >= 50000000 THEN 'GOLD'
    WHEN b.spent >= 10000000 THEN 'SILVER'
    ELSE 'STANDARD'
  END
");

// Auto-create global templates if they don't exist yet
$templatesCount = $pdo->query("SELECT COUNT(*) FROM vouchers WHERE user_id IS NULL AND code IN ('REDM30K', 'REDM50K', 'REDM100K', 'GIFTPOP', 'SUMMER30', 'NEWUSER50', 'MOVIE20')")->fetchColumn();
if ($templatesCount < 7) {
    $defaults = [
        'REDM30K'   => ['desc' => '[Chương trình đổi thưởng] Voucher giảm giá 30.000₫', 'pct' => 0, 'amt' => 30000, 'min' => 0],
        'REDM50K'   => ['desc' => '[Chương trình đổi thưởng] Voucher giảm giá 50.000₫', 'pct' => 0, 'amt' => 50000, 'min' => 0],
        'REDM100K'  => ['desc' => '[Chương trình đổi thưởng] Voucher giảm giá 100.000₫', 'pct' => 0, 'amt' => 100000, 'min' => 0],
        'GIFTPOP'   => ['desc' => '[Chương trình đổi thưởng] Combo Bắp + Nước miễn phí', 'pct' => 100, 'amt' => 0, 'min' => 0],
        'SUMMER30'  => ['desc' => 'Ưu đãi mùa hè giảm 30k', 'pct' => 0, 'amt' => 30000, 'min' => 90000],
        'NEWUSER50' => ['desc' => 'Giảm 50k cho thành viên mới', 'pct' => 0, 'amt' => 50000, 'min' => 150000],
        'MOVIE20'   => ['desc' => 'Giảm 20% cho đơn từ 100k', 'pct' => 20, 'amt' => 0, 'min' => 100000]
    ];
    foreach ($defaults as $code => $d) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE code = ? AND user_id IS NULL");
        $check->execute([$code]);
        if ($check->fetchColumn() == 0) {
            $pdo->prepare("
                INSERT INTO vouchers (code, description, discount_pct, discount_amt, min_order, max_uses, used_count, expire_date, is_active, user_id)
                VALUES (?, ?, ?, ?, ?, 9999, 0, NULL, 1, NULL)
            ")->execute([$code, $d['desc'], $d['pct'], $d['amt'], $d['min']]);
        }
    }
}

