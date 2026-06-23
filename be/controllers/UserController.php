<?php

require_once BASE_PATH . '/be/core/Response.php';
require_once BASE_PATH . '/be/core/Request.php';
require_once BASE_PATH . '/be/services/UserService.php';
require_once BASE_PATH . '/be/middleware/AuthMiddleware.php';

class UserController
{
    private UserService $service;
    private Request $request;

    public function __construct(PDO $pdo)
    {
        $this->service = new UserService($pdo);
        $this->request = new Request();
    }

    public function profile(): void
    {
        $result = $this->service->getProfile(AuthMiddleware::userId());
        Response::json($result);
    }

    public function updateProfile(): void
    {
        $userId = AuthMiddleware::userId();
        $result = $this->service->updateProfile($userId, [
            'full_name' => $_POST['full_name'] ?? '',
            'phone'     => $_POST['phone']     ?? '',
        ]);

        if ($result['success']) {
            $_SESSION['user_name'] = $result['full_name'];
        }

        Response::json($result);
    }

    public function changePassword(): void
    {
        $result = $this->service->changePassword(
            AuthMiddleware::userId(),
            $_POST['old_password']     ?? '',
            $_POST['new_password']     ?? '',
            $_POST['confirm_password'] ?? ''
        );
        Response::json($result);
    }

    public function myTickets(): void
    {
        $result = $this->service->getMyTickets(AuthMiddleware::userId());
        Response::json($result);
    }

    public function cancelBooking(): void
    {
        $bookingCode = trim($_POST['booking_code'] ?? '');
        $result      = $this->service->cancelBooking(AuthMiddleware::userId(), $bookingCode);
        Response::json($result);
    }

    public function submitReview(): void
    {
        $result = $this->service->submitReview(
            AuthMiddleware::userId(),
            trim($_POST['booking_code'] ?? ''),
            (int) ($_POST['rating']  ?? 10),
            trim($_POST['comment']   ?? '')
        );
        Response::json($result);
    }

    public function myVouchers(): void
    {
        $result = $this->service->getMyVouchers(AuthMiddleware::userId());
        Response::json($result);
    }

    public function submitSupportTicket(): void
    {
        $fullName = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $subject  = trim($_POST['subject']  ?? '');
        $content  = trim($_POST['content']  ?? '');

        if (!$fullName || !$email || !$subject || !$content) {
            Response::error('Vui lòng điền đầy đủ các thông tin bắt buộc.');
            return;
        }

        try {
            $this->service->submitSupportTicket($fullName, $email, $phone, $subject, $content);
            Response::json([
                'success' => true,
                'message' => 'Yêu cầu hỗ trợ của bạn đã được gửi đi! Chúng tôi sẽ phản hồi qua email trong vòng 24h.',
            ]);
        } catch (Exception $e) {
            Response::error('Đã có lỗi xảy ra. Vui lòng thử lại sau.');
        }
    }
}