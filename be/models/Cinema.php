<?php

class Cinema
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(string $city = null): array
    {
        if ($city !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM cinemas WHERE city = :city ORDER BY name ASC'
            );
            $stmt->execute([':city' => $city]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT * FROM cinemas ORDER BY city ASC, name ASC'
            );
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM cinemas WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cinemas (name, address, city, phone, logo_url)
             VALUES (:name, :address, :city, :phone, :logo_url)'
        );
        $stmt->execute([
            ':name'     => $data['name'],
            ':address'  => $data['address'],
            ':city'     => $data['city'] ?? 'Hà Nội',
            ':phone'    => $data['phone'] ?? null,
            ':logo_url' => $data['logo_url'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['name', 'address', 'city', 'phone', 'logo_url'];
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

        $sql  = 'UPDATE cinemas SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM cinemas WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function getShowtimes(int $cinemaId, string $date = null, int $movieId = null): array
    {
        $where  = ['s.cinema_id = :cinema_id', 's.is_cancelled = 0'];
        $params = [':cinema_id' => $cinemaId];

        if ($date !== null) {
            $where[]          = 's.show_date = :show_date';
            $params[':show_date'] = $date;
        }

        if ($movieId !== null) {
            $where[]            = 's.movie_id = :movie_id';
            $params[':movie_id'] = $movieId;
        }

        $sql  = 'SELECT s.*, m.title AS movie_title, m.poster_url, m.duration_min, m.age_rating
                 FROM showtimes s
                 JOIN movies m ON m.id = s.movie_id
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY s.show_date ASC, s.start_time ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHalls(int $cinemaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM cinema_halls WHERE cinema_id = :cinema_id ORDER BY name ASC'
        );
        $stmt->execute([':cinema_id' => $cinemaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addHall(int $cinemaId, string $name, int $totalSeats = 100): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cinema_halls (cinema_id, name, total_seats)
             VALUES (:cinema_id, :name, :total_seats)'
        );
        $stmt->execute([
            ':cinema_id'   => $cinemaId,
            ':name'        => $name,
            ':total_seats' => $totalSeats,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function deleteHall(int $hallId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM cinema_halls WHERE id = :id');
        return $stmt->execute([':id' => $hallId]);
    }

    public function getCities(): array
    {
        $stmt = $this->pdo->query(
            'SELECT DISTINCT city FROM cinemas ORDER BY city ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM cinemas')->fetchColumn();
    }
}