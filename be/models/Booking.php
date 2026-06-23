<?php

class Booking
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*,
                    u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone,
                    m.title AS movie_title, m.poster_url, m.duration_min,
                    s.show_date, s.start_time, s.end_time, s.format, s.subtitle_type,
                    s.hall_name, c.name AS cinema_name, c.address AS cinema_address
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             JOIN showtimes s ON s.id = b.showtime_id
             JOIN movies m ON m.id = s.movie_id
             JOIN cinemas c ON c.id = s.cinema_id
             WHERE b.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByCode(string $bookingCode): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*,
                    u.full_name AS user_name, u.email AS user_email,
                    m.title AS movie_title, m.poster_url, m.duration_min,
                    s.show_date, s.start_time, s.end_time, s.format, s.subtitle_type,
                    s.hall_name, c.name AS cinema_name, c.address AS cinema_address
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             JOIN showtimes s ON s.id = b.showtime_id
             JOIN movies m ON m.id = s.movie_id
             JOIN cinemas c ON c.id = s.cinema_id
             WHERE b.booking_code = :code
             LIMIT 1'
        );
        $stmt->execute([':code' => $bookingCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*,
                    m.title AS movie_title, m.poster_url,
                    s.show_date, s.start_time, s.format,
                    c.name AS cinema_name
             FROM bookings b
             JOIN showtimes s ON s.id = b.showtime_id
             JOIN movies m ON m.id = s.movie_id
             JOIN cinemas c ON c.id = s.cinema_id
             WHERE b.user_id = :user_id
             ORDER BY b.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByShowtime(int $showtimeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.full_name AS user_name, u.email AS user_email
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             WHERE b.showtime_id = :showtime_id
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([':showtime_id' => $showtimeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSeatsByShowtime(int $showtimeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT seats_json FROM bookings
             WHERE showtime_id = :showtime_id
             AND status IN ("confirmed", "checked_in")'
        );
        $stmt->execute([':showtime_id' => $showtimeId]);
        $rows   = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $booked = [];

        foreach ($rows as $json) {
            $seats = json_decode($json, true);
            if (is_array($seats)) {
                $booked = array_merge($booked, $seats);
            }
        }

        return $booked;
    }

    public function getAll(int $limit = 50, int $offset = 0, string $status = null): array
    {
        $where  = [];
        $params = [];

        if ($status !== null) {
            $where[]           = 'b.status = :status';
            $params[':status'] = $status;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql         = "SELECT b.*,
                               u.full_name AS user_name, u.email AS user_email,
                               m.title AS movie_title,
                               s.show_date, s.start_time,
                               c.name AS cinema_name
                        FROM bookings b
                        JOIN users u ON u.id = b.user_id
                        JOIN showtimes s ON s.id = b.showtime_id
                        JOIN movies m ON m.id = s.movie_id
                        JOIN cinemas c ON c.id = s.cinema_id
                        $whereClause
                        ORDER BY b.created_at DESC
                        LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO bookings
                (booking_code, user_id, showtime_id, seats_json, num_tickets,
                 subtotal, discount, total_amount, payment_method, payment_status,
                 status, voucher_code)
             VALUES
                (:booking_code, :user_id, :showtime_id, :seats_json, :num_tickets,
                 :subtotal, :discount, :total_amount, :payment_method, :payment_status,
                 :status, :voucher_code)'
        );
        $stmt->execute([
            ':booking_code'    => $data['booking_code'],
            ':user_id'         => $data['user_id'],
            ':showtime_id'     => $data['showtime_id'],
            ':seats_json'      => $data['seats_json'] ?? null,
            ':num_tickets'     => $data['num_tickets'],
            ':subtotal'        => $data['subtotal'],
            ':discount'        => $data['discount'] ?? 0,
            ':total_amount'    => $data['total_amount'],
            ':payment_method'  => $data['payment_method'] ?? 'momo',
            ':payment_status'  => $data['payment_status'] ?? 'pending',
            ':status'          => $data['status'] ?? 'confirmed',
            ':voucher_code'    => $data['voucher_code'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function markPaid(int $id, string $transactionId = null): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE bookings
             SET payment_status = "paid", transaction_id = :txn_id
             WHERE id = :id'
        );
        return $stmt->execute([':txn_id' => $transactionId, ':id' => $id]);
    }

    public function markCheckedIn(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE bookings SET status = "checked_in" WHERE id = :id AND status = "confirmed"'
        );
        return $stmt->execute([':id' => $id]);
    }

    public function cancel(int $id, string $reason = null): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE bookings
             SET status = "cancelled", cancel_reason = :reason
             WHERE id = :id AND status = "confirmed"'
        );
        return $stmt->execute([':reason' => $reason, ':id' => $id]);
    }

    public function refund(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE bookings SET payment_status = "refunded" WHERE id = :id'
        );
        return $stmt->execute([':id' => $id]);
    }

    public function generateCode(): string
    {
        do {
            $code = 'MF' . strtoupper(bin2hex(random_bytes(8)));
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM bookings WHERE booking_code = :code'
            );
            $stmt->execute([':code' => $code]);
        } while ((int) $stmt->fetchColumn() > 0);

        return $code;
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM bookings WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function getTotalRevenueByPeriod(string $from, string $to): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(total_amount), 0)
             FROM bookings
             WHERE payment_status = "paid"
               AND DATE(created_at) BETWEEN :from AND :to'
        );
        $stmt->execute([':from' => $from, ':to' => $to]);
        return (float) $stmt->fetchColumn();
    }

    public function getRevenueGroupedByDay(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DATE(created_at) AS day, SUM(total_amount) AS revenue, COUNT(*) AS tickets
             FROM bookings
             WHERE payment_status = "paid"
               AND DATE(created_at) BETWEEN :from AND :to
             GROUP BY DATE(created_at)
             ORDER BY day ASC'
        );
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRevenueGroupedByMovie(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.title, m.poster_url,
                    SUM(b.total_amount) AS revenue, COUNT(b.id) AS bookings
             FROM bookings b
             JOIN showtimes s ON s.id = b.showtime_id
             JOIN movies m ON m.id = s.movie_id
             WHERE b.payment_status = "paid"
               AND DATE(b.created_at) BETWEEN :from AND :to
             GROUP BY m.id, m.title, m.poster_url
             ORDER BY revenue DESC'
        );
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}