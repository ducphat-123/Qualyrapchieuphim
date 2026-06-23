<?php

class Router
{
    private array $routes = [];

    /**
     * Register a route.
     *
     * @param string          $method   HTTP method: GET, POST, or ANY
     * @param string          $action   Value of $_POST['action'] or $_GET['action']
     * @param callable|array  $handler  Callable or [ClassName, 'method']
     * @param callable[]      $middlewares  Array of middleware callables to run before handler
     */
    public function register(string $method, string $action, callable|array $handler, array $middlewares = []): void
    {
        $this->routes[$method][$action] = [
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function post(string $action, callable|array $handler, array $middlewares = []): void
    {
        $this->register('POST', $action, $handler, $middlewares);
    }

    public function get(string $action, callable|array $handler, array $middlewares = []): void
    {
        $this->register('GET', $action, $handler, $middlewares);
    }

    public function any(string $action, callable|array $handler, array $middlewares = []): void
    {
        $this->register('ANY', $action, $handler, $middlewares);
    }

    /**
     * Resolve and dispatch the current request.
     * Action is read from $_POST['action'] (POST) or $_GET['action'] (GET).
     */
    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = trim($_POST['action'] ?? $_GET['action'] ?? '');

        $route = $this->routes[$method][$action]
              ?? $this->routes['ANY'][$action]
              ?? null;

        if ($route === null) {
            $this->notFound($action);
            return;
        }

        foreach ($route['middlewares'] as $middleware) {
            call_user_func($middleware);
        }

        call_user_func($route['handler']);
    }

    private function notFound(string $action): void
    {
        if (!headers_sent()) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => "Action '{$action}' không tồn tại.",
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}