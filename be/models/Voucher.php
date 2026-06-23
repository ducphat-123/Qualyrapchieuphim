<?php

class Voucher
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByCode(string $code): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vouchers WHERE code = :code LIMIT 1'
        );
        $stmt->execute([':code' => $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vouchers WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll(bool $activeOnly = false, int $limit = 50, int $offset = 0): array
    {
        if ($activeOnly) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM vouchers
                 WHERE is_active = 1
                   AND (expire_date IS NULL OR expire_date >= CURDATE())
                   AND (max_uses IS NULL OR used_count < max_uses)
                 ORDER BY expire_date ASC
                 LIMIT :limit OFFSET :offset'
            );
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM vouchers
                 ORDER BY id DESC
                 LIMIT :limit OFFSET :offset'
            );
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vouchers
             WHERE user_id = :user_id
               AND is_active = 1
               AND (expire_date IS NULL OR expire_date >= CURDATE())
             ORDER BY expire_date ASC'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validate(string $code, float $orderAmount): array
    {
        $voucher = $this->findByCode($code);

        if (!$voucher) {
            return ['valid' => false, 'message' => 'Mã giảm giá không tồn tại.'];
        }

        if (!$voucher['is_active']) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã bị vô hiệu hóa.'];
        }

        if ($voucher['expire_date'] && $voucher['expire_date'] < date('Y-m-d')) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết hạn.'];
        }

        if ($voucher['max_uses'] !== null && $voucher['used_count'] >= $voucher['max_uses']) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'];
        }

        if ($orderAmount < $voucher['min_order']) {
            return [
                'valid'   => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($voucher['min_order']) . '₫.',
            ];
        }

        $discount = 0;
        if ($voucher['discount_pct'] > 0) {
            $discount = $orderAmount * $voucher['discount_pct'] / 100;
        } elseif ($voucher['discount_amt'] > 0) {
            $discount = min((float) $voucher['discount_amt'], $orderAmount);
        }

        return [
            'valid'    => true,
            'discount' => $discount,
            'voucher'  => $voucher,
            'message'  => 'Áp dụng thành công.',
        ];
    }

    public function markUsed(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE vouchers SET used_count = used_count + 1 WHERE code = :code'
        );
        return $stmt->execute([':code' => $code]);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vouchers
                (code, description, discount_pct, discount_amt, min_order,
                 max_uses, expire_date, is_active, user_id)
             VALUES
                (:code, :description, :discount_pct, :discount_amt, :min_order,
                 :max_uses, :expire_date, :is_active, :user_id)'
        );
        $stmt->execute([
            ':code'         => strtoupper(trim($data['code'])),
            ':description'  => $data['description'] ?? null,
            ':discount_pct' => $data['discount_pct'] ?? 0,
            ':discount_amt' => $data['discount_amt'] ?? 0,
            ':min_order'    => $data['min_order'] ?? 0,
            ':max_uses'     => $data['max_uses'] ?? 100,
            ':expire_date'  => $data['expire_date'] ?? null,
            ':is_active'    => $data['is_active'] ?? 1,
            ':user_id'      => $data['user_id'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'description', 'discount_pct', 'discount_amt',
            'min_order', 'max_uses', 'expire_date', 'is_active', 'user_id',
        ];
        $fields = [];
        $params = [':id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[]         = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql  = 'UPDATE vouchers SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function toggleActive(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE vouchers SET is_active = NOT is_active WHERE id = :id'
        );
        return $stmt->execute([':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM vouchers WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function codeExists(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM vouchers WHERE code = :code'
        );
        $stmt->execute([':code' => strtoupper(trim($code))]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function countAll(bool $activeOnly = false): int
    {
        if ($activeOnly) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM vouchers WHERE is_active = 1'
            );
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM vouchers');
        }
        return (int) $stmt->fetchColumn();
    }
}