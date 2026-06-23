<?php

class Logger
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(string $actionType, string $actionDesc, string $userName = 'System', string $role = 'system'): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc)
                 VALUES (NOW(), :user_name, :role, :action_type, :action_desc)'
            );
            $stmt->execute([
                ':user_name'   => $userName,
                ':role'        => $role,
                ':action_type' => $actionType,
                ':action_desc' => $actionDesc,
            ]);
        } catch (Exception $e) {
            // Log failure must never crash the application
            error_log('[Logger] Failed to write system log: ' . $e->getMessage());
        }
    }

    public function auth(string $actionType, string $actionDesc): void
    {
        $userName = $_SESSION['user_name'] ?? 'Anonymous';
        $role     = $_SESSION['user_role'] ?? 'guest';
        $this->log($actionType, $actionDesc, $userName, $role);
    }
}