<?php
$active_page = 'cinemas';
require_once 'db.php';
session_start();

$cinema_id = (int)($_GET['id'] ?? 0);
$cinemas = $pdo->query("SELECT * FROM cinemas ORDER BY name ASC")->fetchAll();

if (!$cinema_id && count($cinemas) > 0) {
    $cinema_id = $cinemas[0]['id'];
}

$selected_cinema = null;
foreach ($cinemas as $c) {
    if ($c['id'] == $cinema_id) {
        $selected_cinema = $c;
        break;
    }
}

// Fetch movies and showtimes for the selected cinema
$grouped_showtimes = [];
if ($cinema_id) {
    $stQuery = $pdo->prepare("
        SELECT s.*, m.title, m.poster_url, m.age_rating, m.genre
        FROM showtimes s
        JOIN movies m ON s.movie_id = m.id
        WHERE s.cinema_id = ? AND s.is_cancelled = 0
          AND (
            s.show_date > CURDATE()
            OR (s.show_date = CURDATE() AND ADDTIME(s.start_time, '00:20:00') >= CURTIME())
          )
        ORDER BY s.show_date ASC, m.id ASC, s.start_time ASC
    ");
    $stQuery->execute([$cinema_id]);
    $stData = $stQuery->fetchAll();
    
    // Group by Date -> Movie -> Showtimes
    foreach ($stData as $row) {
        $grouped_showtimes[$row['show_date']][$row['movie_id']]['movie'] = [
            'id' => $row['movie_id'],
            'title' => $row['title'],
            'poster_url' => $row['poster_url'],
            'age_rating' => $row['age_rating'],
            'genre' => $row['genre'],
        ];
        $grouped_showtimes[$row['show_date']][$row['movie_id']]['slots'][] = $row;
    }
}
$dates = array_keys($grouped_showtimes);
$selected_date = $_GET['date'] ?? ($dates[0] ?? date('Y-m-d'));
if (!in_array($selected_date, $dates) && count($dates) > 0) {
    $selected_date = $dates[0];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Hệ thống rạp chiếu - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
html,body{margin:0;padding:0;height:100%;overflow:hidden}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --blue:#2563EB;--blue-h:#1D4ED8;
  --sb:#0F172A;--sbw:240px;
  --bg:#F1F5F9;--card:#fff;
  --text:#0F172A;--muted:#64748B;--light:#94A3B8;--border:#E2E8F0;
  --radius:14px;--sh:0 2px 16px rgba(15,23,42,.08);
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;height:100vh;width:100vw;overflow:hidden;position:fixed;inset:0}

.main{margin-left:var(--sbw, 240px);flex:1;display:flex;flex-direction:column;height:100vh;overflow:hidden}
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 28px;height:64px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50}
.topbar h1{font-size:18px;font-weight:800}

/* LAYOUT */
.content{flex:1;display:flex;height:calc(100vh - 64px);overflow:hidden}

/* LEFT: CINEMA LIST */
.cinema-list{width:320px;background:var(--card);border-right:1px solid var(--border);overflow-y:auto;display:flex;flex-direction:column}
.cl-header{padding:16px 20px;font-size:14px;font-weight:700;border-bottom:1px solid var(--border);color:var(--muted);text-transform:uppercase;letter-spacing:1px;position:sticky;top:0;background:var(--card);z-index:10}
.cl-item{padding:16px 20px;border-bottom:1px solid var(--border);cursor:pointer;transition:all .2s;display:block;text-decoration:none}
.cl-item:hover{background:var(--bg)}
.cl-item.active{background:var(--blue);border-color:var(--blue)}
.cl-name{font-size:15px;font-weight:700;color:var(--text);margin-bottom:6px;display:flex;align-items:center;gap:8px}
.cl-item.active .cl-name{color:#fff}
.cl-addr{font-size:12.5px;color:var(--muted);line-height:1.4}
.cl-item.active .cl-addr{color:rgba(255,255,255,.8)}

/* RIGHT: SHOWTIMES */
.st-view{flex:1;overflow-y:auto;padding:24px 32px;background:var(--bg)}
.st-header{margin-bottom:24px}
.st-title{font-size:24px;font-weight:800;margin-bottom:8px;display:flex;align-items:center;gap:10px}
.st-desc{font-size:14px;color:var(--muted);display:flex;align-items:center;gap:16px}
.st-desc span{display:flex;align-items:center;gap:6px}

/* DATE TABS */
.date-tabs{display:flex;gap:8px;margin-bottom:24px;overflow-x:auto;padding-bottom:4px}
.date-tab{padding:10px 20px;background:var(--card);border-radius:12px;cursor:pointer;text-align:center;border:1px solid var(--border);color:var(--muted);text-decoration:none;transition:all .2s;flex-shrink:0}
.date-tab:hover{border-color:var(--blue);color:var(--text)}
.date-tab.active{background:var(--blue);color:#fff;border-color:var(--blue)}
.dt-day{font-size:11px;font-weight:700;text-transform:uppercase}
.dt-date{font-size:16px;font-weight:800;margin-top:2px}

/* MOVIE SHOWTIMES */
.ms-card{background:var(--card);border-radius:var(--radius);padding:20px;margin-bottom:20px;display:flex;gap:20px;box-shadow:var(--sh)}
.ms-poster{width:110px;height:155px;border-radius:10px;object-fit:cover;background:#e2e8f0;flex-shrink:0}
.ms-info{flex:1}
.ms-title{font-size:18px;font-weight:800;margin-bottom:8px;color:var(--text)}
.ms-meta{font-size:13px;color:var(--muted);margin-bottom:16px;display:flex;align-items:center;gap:12px}
.ms-age{padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;background:#FEF3C7;color:#92400E}
.ms-age.t18{background:#FEE2E2;color:#991B1B}
.ms-age.t13{background:#DCFCE7;color:#166534}

.ts-grid{display:flex;flex-wrap:wrap;gap:10px}
.ts-btn{display:inline-flex;flex-direction:column;align-items:center;padding:8px 16px;background:#fff;border:1.5px solid var(--border);border-radius:8px;text-decoration:none;color:var(--text);transition:all .2s;min-width:80px}
.ts-btn:hover{background:var(--blue);border-color:var(--blue);color:#fff}
.ts-time{font-size:15px;font-weight:800}
.ts-fmt{font-size:11px;color:var(--muted);margin-top:2px}
.ts-btn:hover .ts-fmt{color:rgba(255,255,255,.8)}

.empty-state{text-align:center;padding:60px 20px;color:var(--muted)}
.empty-state i{font-size:48px;opacity:.2;margin-bottom:16px}
.empty-state h3{font-size:18px;font-weight:700}

/* Scrollbar */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <h1><i class="fa-solid fa-location-dot" style="color:var(--blue);margin-right:8px"></i>Hệ thống rạp chiếu</h1>
  </div>

  <div class="content">
    <!-- LEFT: CINEMAS -->
    <div class="cinema-list">
      <div class="cl-header">Danh sách rạp</div>
      <?php foreach($cinemas as $c): ?>
      <a href="?id=<?= $c['id'] ?>" class="cl-item <?= $c['id']==$cinema_id ? 'active':'' ?>">
        <div class="cl-name"><i class="fa-solid fa-camera-movie"></i> <?= htmlspecialchars($c['name']) ?></div>
        <div class="cl-addr"><?= htmlspecialchars($c['address']) ?></div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- RIGHT: SHOWTIMES -->
    <div class="st-view">
      <?php if($selected_cinema): ?>
      <div class="st-header">
        <h2 class="st-title"><i class="fa-solid fa-camera-movie" style="color:var(--blue)"></i> <?= htmlspecialchars($selected_cinema['name']) ?></h2>
        <div class="st-desc">
          <span><i class="fa-solid fa-map-location-dot"></i> <?= htmlspecialchars($selected_cinema['address'].', '.$selected_cinema['city']) ?></span>
          <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($selected_cinema['phone'] ?? '—') ?></span>
        </div>
      </div>

      <?php if(empty($dates)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-calendar-xmark"></i>
          <h3>Chưa có lịch chiếu</h3>
          <p>Rạp này hiện chưa có suất chiếu nào trong những ngày tới.</p>
        </div>
      <?php else: ?>
        <div class="date-tabs">
          <?php foreach($dates as $d): $ts=strtotime($d); ?>
          <a href="?id=<?= $cinema_id ?>&date=<?= $d ?>" class="date-tab <?= $d===$selected_date?'active':'' ?>">
            <div class="dt-day"><?= date('w',$ts)==0?'CN':'T'.(date('w',$ts)+1) ?></div>
            <div class="dt-date"><?= date('d/m',$ts) ?></div>
          </a>
          <?php endforeach; ?>
        </div>

        <?php if(!empty($grouped_showtimes[$selected_date])): ?>
          <?php foreach($grouped_showtimes[$selected_date] as $m_id => $data): $m = $data['movie']; ?>
          <div class="ms-card">
            <?php if($m['poster_url']): ?>
              <img src="<?= htmlspecialchars($m['poster_url']) ?>" class="ms-poster" alt="">
            <?php else: ?>
              <div class="ms-poster" style="display:flex;align-items:center;justify-content:center;font-size:32px;color:rgba(255,255,255,.2)"><i class="fa-solid fa-film"></i></div>
            <?php endif; ?>
            
            <div class="ms-info">
              <h3 class="ms-title"><?= htmlspecialchars($m['title']) ?></h3>
              <div class="ms-meta">
                <span class="ms-age <?= strtolower($m['age_rating']) ?>"><?= $m['age_rating'] ?></span>
                <span><?= htmlspecialchars($m['genre']) ?></span>
              </div>
              
              <div class="ts-grid">
                <?php foreach($data['slots'] as $s): ?>
                <a href="seat-select.php?id=<?= $s['id'] ?>" class="ts-btn">
                  <span class="ts-time"><?= substr($s['start_time'],0,5) ?></span>
                  <span class="ts-fmt"><?= $s['format'] ?> <?= $s['subtitle_type'] ?></span>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <i class="fa-solid fa-film"></i>
            <h3>Không có phim nào chiếu ngày này</h3>
          </div>
        <?php endif; ?>

      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
