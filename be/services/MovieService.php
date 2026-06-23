<?php

require_once __DIR__ . '/../models/Movie.php';

class MovieService
{
    private PDO   $pdo;
    private Movie $movieModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo        = $pdo;
        $this->movieModel = new Movie($pdo);
    }

    // -------------------------------------------------------------------------
    // AUTO-UPDATE: coming_soon → now_showing nếu đã đến ngày khởi chiếu
    // Gọi 1 lần duy nhất ở Service thay vì rải rác ở 3 file FE
    // -------------------------------------------------------------------------

    public function autoPromoteMovies(): void
    {
        $this->pdo->exec("
            UPDATE movies
            SET status = 'now_showing'
            WHERE status = 'coming_soon'
              AND release_date IS NOT NULL
              AND release_date <= CURDATE()
        ");
    }

    // -------------------------------------------------------------------------
    // PHIM ĐANG CHIẾU (kèm formats GROUP_CONCAT — dùng cho home.php, guest.php)
    // -------------------------------------------------------------------------

    public function getNowShowingWithFormats(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.*, GROUP_CONCAT(DISTINCT s.format ORDER BY s.format SEPARATOR ',') as formats
            FROM movies m
            LEFT JOIN showtimes s ON m.id = s.movie_id AND s.show_date >= CURDATE()
            WHERE m.status = 'now_showing'
            GROUP BY m.id
            ORDER BY m.rating DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // PHIM ĐANG CHIẾU theo tab (dùng cho movies.php — không cần formats)
    // -------------------------------------------------------------------------

    public function getByStatus(string $status, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM movies
            WHERE status = :status
            ORDER BY release_date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // PHIM SẮP CHIẾU
    // -------------------------------------------------------------------------

    public function getComingSoon(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM movies
            WHERE status = 'coming_soon'
            ORDER BY release_date ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // CHI TIẾT PHIM (dùng cho movie-detail.php)
    // -------------------------------------------------------------------------

    public function getMovieDetail(int $id): array|false
    {
        return $this->movieModel->getById($id);
    }

    // -------------------------------------------------------------------------
    // REVIEWS CỦA PHIM (kèm full_name user)
    // -------------------------------------------------------------------------

    public function getReviews(int $movieId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, u.full_name
            FROM movie_reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.movie_id = :movie_id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([':movie_id' => $movieId]);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------------------
    // SHOWTIMES THEO PHIM (lọc giờ đã qua hôm nay — dùng movie-detail.php)
    // Trả về mảng đã group theo ngày → rạp
    // -------------------------------------------------------------------------

    public function getShowtimesGrouped(int $movieId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, c.name as cinema_name, c.address, c.city
            FROM showtimes s
            JOIN cinemas c ON s.cinema_id = c.id
            WHERE s.movie_id = :movie_id
              AND s.is_cancelled = 0
              AND (
                s.show_date > CURDATE()
                OR (s.show_date = CURDATE() AND ADDTIME(s.start_time, '00:20:00') >= CURTIME())
              )
            ORDER BY s.show_date ASC, s.start_time ASC
        ");
        $stmt->execute([':movie_id' => $movieId]);
        $showtimes = $stmt->fetchAll();

        $grouped = [];
        foreach ($showtimes as $s) {
            $grouped[$s['show_date']][$s['cinema_name']][] = $s;
        }

        return $grouped;
    }

    // -------------------------------------------------------------------------
    // HERO MOVIES (top 5 có backdrop — dùng cho carousel)
    // -------------------------------------------------------------------------

    public function getHeroMovies(array $nowShowing, int $max = 5): array
    {
        $heroes = [];
        foreach ($nowShowing as $m) {
            if (!empty($m['backdrop_url'])) {
                $heroes[] = $m;
                if (count($heroes) >= $max) break;
            }
        }
        if (empty($heroes) && !empty($nowShowing)) {
            $heroes = array_slice($nowShowing, 0, $max);
        }
        return $heroes;
    }
}