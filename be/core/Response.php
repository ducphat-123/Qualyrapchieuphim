<?php

class Response
{
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(array $payload = [], string $message = 'OK'): void
    {
        self::json(array_merge(['success' => true, 'message' => $message], $payload));
    }

    public static function error(string $message, int $statusCode = 200, array $extra = []): void
    {
        self::json(array_merge(['success' => false, 'message' => $message], $extra), $statusCode);
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function unauthorized(string $message = 'Yêu cầu đăng nhập.'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Bạn không có quyền thực hiện thao tác này.'): void
    {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'Không tìm thấy.'): void
    {
        self::error($message, 404);
    }

    public static function serverError(string $message = 'Lỗi máy chủ nội bộ.'): void
    {
        self::error($message, 500);
    }
}