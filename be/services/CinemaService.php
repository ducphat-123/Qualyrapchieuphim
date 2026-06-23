<?php

require_once __DIR__ . '/../models/Cinema.php';

class CinemaService
{
    private PDO    $pdo;
    private Cinema $cinemaModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo         = $pdo;
        $this->cinemaModel = new Cinema($pdo);
    }

    // -------------------------------------------------------------------------
    // DANH SÁCH RẠP (dùng cho guest.php, cinemas.php)
    // -------------------------------------------------------------------------

    public function getAll(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM cinemas ORDER BY name ASC LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // SHOWTIMES THEO RẠP — group theo ngày → phim (dùng cho cinemas.php)
    // -------------------------------------------------------------------------

    public function getShowtimesGroupedByDateAndMovie(int $cinemaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, m.title, m.poster_url, m.age_rating, m.genre
            FROM showtimes s
            JOIN movies m ON s.movie_id = m.id
            WHERE s.cinema_id = :cinema_id
              AND s.is_cancelled = 0
              AND (
                s.show_date > CURDATE()
                OR (s.show_date = CURDATE() AND ADDTIME(s.start_time, '00:20:00') >= CURTIME())
              )
            ORDER BY s.show_date ASC, m.id ASC, s.start_time ASC
        ");
        $stmt->execute([':cinema_id' => $cinemaId]);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['show_date']][$row['movie_id']]['movie'] = [
                'id'         => $row['movie_id'],
                'title'      => $row['title'],
                'poster_url' => $row['poster_url'],
                'age_rating' => $row['age_rating'],
                'genre'      => $row['genre'],
            ];
            $grouped[$row['show_date']][$row['movie_id']]['slots'][] = $row;
        }

        return $grouped;
    }
}