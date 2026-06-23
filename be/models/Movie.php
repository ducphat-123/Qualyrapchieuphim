<?php

class Movie
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(string $status = null, int $limit = 50, int $offset = 0): array
    {
        if ($status !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM movies
                 WHERE status = :status
                 ORDER BY created_at DESC
                 LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM movies
                 ORDER BY created_at DESC
                 LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM movies WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getNowShowing(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM movies
             WHERE status = "now_showing"
             ORDER BY rating DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getComingSoon(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM movies
             WHERE status = "coming_soon"
             ORDER BY release_date ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(string $keyword, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM movies
             WHERE title LIKE :kw OR genre LIKE :kw OR director LIKE :kw OR cast_list LIKE :kw
             ORDER BY rating DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':kw', '%' . $keyword . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO movies
                (title, description, genre, duration_min, release_date, rating,
                 poster_url, backdrop_url, trailer_url, director, cast_list, age_rating, status)
             VALUES
                (:title, :description, :genre, :duration_min, :release_date, :rating,
                 :poster_url, :backdrop_url, :trailer_url, :director, :cast_list, :age_rating, :status)'
        );
        $stmt->execute([
            ':title'        => $data['title'],
            ':description'  => $data['description'] ?? null,
            ':genre'        => $data['genre'] ?? null,
            ':duration_min' => $data['duration_min'] ?? null,
            ':release_date' => $data['release_date'] ?? null,
            ':rating'       => $data['rating'] ?? 0.0,
            ':poster_url'   => $data['poster_url'] ?? null,
            ':backdrop_url' => $data['backdrop_url'] ?? null,
            ':trailer_url'  => $data['trailer_url'] ?? null,
            ':director'     => $data['director'] ?? null,
            ':cast_list'    => $data['cast_list'] ?? null,
            ':age_rating'   => $data['age_rating'] ?? 'T16',
            ':status'       => $data['status'] ?? 'coming_soon',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'title', 'description', 'genre', 'duration_min', 'release_date',
            'rating', 'poster_url', 'backdrop_url', 'trailer_url',
            'director', 'cast_list', 'age_rating', 'status',
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

        $sql  = 'UPDATE movies SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE movies SET status = :status WHERE id = :id'
        );
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM movies WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function countAll(string $status = null): int
    {
        if ($status !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM movies WHERE status = :status'
            );
            $stmt->execute([':status' => $status]);
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM movies');
        }
        return (int) $stmt->fetchColumn();
    }

    public function getReviews(int $movieId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.full_name, u.avatar_url
             FROM movie_reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.movie_id = :movie_id
             ORDER BY r.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':movie_id', $movieId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addReview(int $userId, int $movieId, string $bookingCode, int $rating, string $comment = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO movie_reviews (user_id, movie_id, booking_code, rating, comment)
             VALUES (:user_id, :movie_id, :booking_code, :rating, :comment)'
        );
        $stmt->execute([
            ':user_id'      => $userId,
            ':movie_id'     => $movieId,
            ':booking_code' => $bookingCode,
            ':rating'       => $rating,
            ':comment'      => $comment,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function hasReviewed(int $userId, int $movieId, string $bookingCode): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM movie_reviews
             WHERE user_id = :user_id AND movie_id = :movie_id AND booking_code = :booking_code'
        );
        $stmt->execute([
            ':user_id'      => $userId,
            ':movie_id'     => $movieId,
            ':booking_code' => $bookingCode,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getAverageRating(int $movieId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT AVG(rating) FROM movie_reviews WHERE movie_id = :movie_id'
        );
        $stmt->execute([':movie_id' => $movieId]);
        return round((float) $stmt->fetchColumn(), 1);
    }
}