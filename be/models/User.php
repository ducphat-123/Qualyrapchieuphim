<?php

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, phone, avatar_url, role, status,
                    loyalty_points, member_tier, created_at, last_login
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, phone, role, status,
                    loyalty_points, member_tier, created_at, last_login
             FROM users
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, avatar_url, role, status)
             VALUES (:full_name, :email, :phone, :password_hash, :avatar_url, :role, :status)'
        );
        $stmt->execute([
            ':full_name'     => $data['full_name'],
            ':email'         => $data['email'],
            ':phone'         => $data['phone'] ?? null,
            ':password_hash' => $data['password_hash'],
            ':avatar_url'    => $data['avatar_url'] ?? null,
            ':role'          => $data['role'] ?? 'user',
            ':status'        => $data['status'] ?? 'active',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['full_name', 'phone', 'avatar_url'];
        $fields  = [];
        $params  = [':id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[]         = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql  = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = :hash WHERE id = :id'
        );
        return $stmt->execute([':hash' => $passwordHash, ':id' => $id]);
    }

    public function updateLastLogin(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET last_login = NOW() WHERE id = :id'
        );
        return $stmt->execute([':id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET status = :status WHERE id = :id'
        );
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function updateRole(int $id, string $role): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET role = :role WHERE id = :id'
        );
        return $stmt->execute([':role' => $role, ':id' => $id]);
    }

    public function addLoyaltyPoints(int $id, int $points): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET loyalty_points = loyalty_points + :points WHERE id = :id'
        );
        return $stmt->execute([':points' => $points, ':id' => $id]);
    }

    public function updateMemberTier(int $id, string $tier): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET member_tier = :tier WHERE id = :id'
        );
        return $stmt->execute([':tier' => $tier, ':id' => $id]);
    }

    public function lock(int $id): bool
    {
        return $this->updateStatus($id, 'locked');
    }

    public function unlock(int $id): bool
    {
        return $this->updateStatus($id, 'active');
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM users WHERE email = :email'
        );
        $stmt->execute([':email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function savePasswordResetToken(string $email, string $token, string $expiresAt): bool
    {
        $this->pdo->prepare(
            'DELETE FROM password_resets WHERE email = :email'
        )->execute([':email' => $email]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (email, token, expires_at)
             VALUES (:email, :token, :expires_at)'
        );
        return $stmt->execute([
            ':email'      => $email,
            ':token'      => $token,
            ':expires_at' => $expiresAt,
        ]);
    }

    public function findPasswordResetToken(string $email, string $token): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_resets
             WHERE email = :email AND token = :token AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':email' => $email, ':token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deletePasswordResetToken(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM password_resets WHERE email = :email'
        );
        return $stmt->execute([':email' => $email]);
    }

    public function search(string $keyword, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, phone, role, status, created_at
             FROM users
             WHERE full_name LIKE :kw OR email LIKE :kw OR phone LIKE :kw
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':kw', '%' . $keyword . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}