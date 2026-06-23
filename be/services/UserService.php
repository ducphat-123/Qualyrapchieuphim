<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../core/Logger.php';

class UserService
{
    private PDO     $pdo;
    private User    $userModel;
    private Booking $bookingModel;
    private Logger  $logger;

    public function __construct(PDO $pdo)
    {
        $this->pdo          = $pdo;
        $this->userModel    = new User($pdo);
        $this->bookingModel = new Booking($pdo);
        $this->logger       = new Logger($pdo);
    }

    // -------------------------------------------------------------------------
    // PROFILE
    // -------------------------------------------------------------------------

    public function getProfile(int $userId): array
    {
        $user = $this->userModel->findById($userId);

        if (!$user) {
            return $this->fail('Không tìm thấy người dùng.');
        }

        $stats = $this->getBookingStats($userId);

        return [
            'success' => true,
            'user'    => $user,
            'stats'   => $stats,
        ];
    }

    public function updateProfile(int $userId, array $data): array
    {
        $fullName = trim($data['full_name'] ?? '');
        $phone    = trim($data['phone'] ?? '');

        if (empty($fullName)) {
            return $this->fail('Họ tên không được để trống.');
        }

        if (!empty($phone) && !preg_match('/^(0[35789])[0-9]{8}$/', $phone)) {
            return $this->fail('Số điện thoại không hợp lệ. Vui lòng nhập số gồm 10 chữ số (bắt đầu bằng 03, 05, 07, 08, 09).');
        }

        $this->userModel->update($userId, [
            'full_name' => $fullName,
            'phone'     => $phone ?: null,
        ]);

        $this->logger->log('Cập nhật hồ sơ', "User cập nhật thông tin cá nhân.", $fullName, 'user');

        return [
            'success'   => true,
            'message'   => 'Cập nhật hồ sơ thành công!',
            'full_name' => $fullName,
            'phone'     => $phone,
        ];
    }

    // -------------------------------------------------------------------------
    // CHANGE PASSWORD
    // -------------------------------------------------------------------------

    public function changePassword(int $userId, string $oldPassword, string $newPassword, string $confirmPassword): array
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return $this->fail('Không tìm thấy người dùng.');
        }

        // findById() strips password_hash, so re-fetch full record for verification
        $fullUser = $this->getFullUserRecord($userId);

        if (!$fullUser || !password_verify($oldPassword, $fullUser['password_hash'])) {
            return $this->fail('Mật khẩu hiện tại không đúng.');
        }

        if (strlen($newPassword) < 6) {
            return $this->fail('Mật khẩu mới tối thiểu 6 ký tự.');
        }

        if ($newPassword !== $confirmPassword) {
            return $this->fail('Mật khẩu xác nhận không khớp.');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $this->userModel->updatePassword($userId, $hash);

        $this->logger->log('Đổi mật khẩu', "User tự đổi mật khẩu.", $fullUser['full_name'], 'user');

        return ['success' => true, 'message' => 'Đổi mật khẩu thành công!'];
    }

    // -------------------------------------------------------------------------
    // CANCEL BOOKING
    // -------------------------------------------------------------------------

    public function cancelBooking(int $userId, string $bookingCode): array
    {
        if (empty($bookingCode)) {
            return $this->fail('Thiếu mã đặt vé.');
        }

        $stmt = $this->pdo->prepare("
            SELECT b.*, s.show_date, s.start_time
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.id
            WHERE b.booking_code = ? AND b.user_id = ? AND b.status = 'confirmed'
            LIMIT 1
        ");
        $stmt->execute([$bookingCode, $userId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return $this->fail('Không tìm thấy vé hợp lệ để hủy.');
        }

        $showtimeTs  = strtotime($booking['show_date'] . ' ' . $booking['start_time']);
        $minutesLeft = ($showtimeTs - time()) / 60;

        if ($minutesLeft < 60) {
            return $this->fail('Không thể hủy vé này do đã quá hạn thời gian cho phép (tối thiểu trước giờ chiếu 60 phút).');
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("
                UPDATE bookings
                SET status = 'cancelled', cancel_reason = NULL, payment_status = 'refunded'
                WHERE booking_code = ?
            ")->execute([$bookingCode]);

            $this->pdo->prepare("
                UPDATE showtimes SET available_seats = available_seats + ? WHERE id = ?
            ")->execute([$booking['num_tickets'], $booking['showtime_id']]);

            if (!empty($booking['voucher_code'])) {
                $this->pdo->prepare("
                    UPDATE vouchers SET used_count = GREATEST(0, used_count - 1) WHERE code = ?
                ")->execute([$booking['voucher_code']]);
            }

            $this->pdo->commit();

            $this->logger->auth('Hủy vé', "Hủy vé {$bookingCode}, hoàn {$booking['total_amount']}đ qua {$booking['payment_method']}.");

            return [
                'success' => true,
                'message' => "Đã hủy vé {$bookingCode} thành công! Số tiền " . number_format($booking['total_amount']) . "₫ đã được hoàn trả về tài khoản nguồn (" . strtoupper($booking['payment_method']) . ").",
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->fail('Có lỗi xảy ra khi hoàn vé. Vui lòng thử lại.');
        }
    }

    // -------------------------------------------------------------------------
    // MOVIE REVIEW
    // -------------------------------------------------------------------------

    public function submitReview(int $userId, string $bookingCode, int $rating, string $comment): array
    {
        if (empty($bookingCode) || $rating < 1 || $rating > 10) {
            return $this->fail('Dữ liệu đánh giá không hợp lệ.');
        }

        $stmt = $this->pdo->prepare("
            SELECT b.showtime_id, s.movie_id
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.id
            WHERE b.booking_code = ? AND b.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$bookingCode, $userId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return $this->fail('Không tìm thấy vé tương ứng để đánh giá.');
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("
                INSERT INTO movie_reviews (user_id, movie_id, booking_code, rating, comment)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)
            ")->execute([$userId, $booking['movie_id'], $bookingCode, $rating, $comment]);

            $avgStmt = $this->pdo->prepare("SELECT AVG(rating) FROM movie_reviews WHERE movie_id = ?");
            $avgStmt->execute([$booking['movie_id']]);
            $newAvg = round((float) $avgStmt->fetchColumn(), 1);

            $this->pdo->prepare("UPDATE movies SET rating = ? WHERE id = ?")
                ->execute([$newAvg ?: 10.0, $booking['movie_id']]);

            $this->pdo->commit();

            return ['success' => true, 'message' => 'Gửi đánh giá cho phim thành công! Cảm ơn đóng góp của bạn.'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->fail('Có lỗi xảy ra khi lưu đánh giá. Vui lòng thử lại.');
        }
    }

    // -------------------------------------------------------------------------
    // MY TICKETS (list, grouped by tab)
    // -------------------------------------------------------------------------

    public function getMyTickets(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, m.title, m.poster_url, m.duration_min, m.id as movie_id,
                   s.show_date, s.start_time, s.format, s.subtitle_type, s.hall_name,
                   c.name as cinema_name,
                   r.rating as user_rating, r.comment as user_comment
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN cinemas c ON s.cinema_id = c.id
            LEFT JOIN movie_reviews r ON b.booking_code = r.booking_code AND b.user_id = r.user_id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$userId]);
        $all = $stmt->fetchAll();

        $now       = time();
        $upcoming  = array_values(array_filter($all, fn($b) => $b['status'] !== 'cancelled' && strtotime($b['show_date'] . ' ' . $b['start_time']) >= $now));
        $past      = array_values(array_filter($all, fn($b) => $b['status'] !== 'cancelled' && strtotime($b['show_date'] . ' ' . $b['start_time']) < $now));
        $cancelled = array_values(array_filter($all, fn($b) => $b['status'] === 'cancelled'));

        return [
            'success'   => true,
            'upcoming'  => $upcoming,
            'past'      => $past,
            'cancelled' => $cancelled,
        ];
    }

    // -------------------------------------------------------------------------
    // VOUCHERS OF USER
    // -------------------------------------------------------------------------

    public function getMyVouchers(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT v.*
            FROM vouchers v
            WHERE v.is_active = 1
              AND (v.expire_date IS NULL OR v.expire_date >= CURDATE())
              AND v.user_id = ?
              AND v.used_count < v.max_uses
            ORDER BY v.id DESC
        ");
        $stmt->execute([$userId]);

        return ['success' => true, 'data' => $stmt->fetchAll()];
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    private function getBookingStats(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total, SUM(total_amount) as spent
            FROM bookings
            WHERE user_id = ? AND status != 'cancelled'
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'spent' => (float) ($row['spent'] ?? 0),
        ];
    }

    private function getFullUserRecord(int $userId): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}