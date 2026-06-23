<?php

class CorsMiddleware
{
    /**
     * Apply CORS headers.
     * In development, allow all origins.
     * In production, restrict to APP_ORIGIN env variable.
     */
    public static function apply(): void
    {
        $appEnv    = getenv('APP_ENV') ?: 'development';
        $appOrigin = getenv('APP_ORIGIN') ?: '*';

        if ($appEnv === 'production' && $appOrigin !== '*') {
            header('Access-Control-Allow-Origin: ' . $appOrigin);
        } else {
            header('Access-Control-Allow-Origin: *');
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');

        // Handle preflight request
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}