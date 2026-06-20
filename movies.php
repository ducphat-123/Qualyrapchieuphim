<?php
$active_page = 'movies';
require_once 'db.php';
session_start();

$tab = $_GET['tab'] ?? 'now_showing';
$status = $tab === 'coming_soon' ? 'coming_soon' : 'now_showing';

$movies = $pdo->prepare("SELECT * FROM movies WHERE status = ? ORDER BY release_date DESC");
$movies->execute([$status]);
$movieList = $movies->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Phim - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--blue:#2563EB;--bg:#F1F5F9;--card:#fff;--text:#0F172A;--muted:#64748B;--light:#94A3B8;--border:#E2E8F0;--r:14px;--sh:0 2px 16px rgba(15,23,42,.08)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}

/* MAIN */
.main{margin-left:var(--sbw, 240px);flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 28px;height:64px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50}
.topbar h1{font-size:18px;font-weight:800}

/* CONTENT */
.content{padding:24px 28px;flex:1}

/* TABS */
.tabs{display:flex;gap:4px;background:var(--card);border-radius:12px;padding:4px;box-shadow:var(--sh);width:fit-content;margin-bottom:24px}
.tab{padding:8px 22px;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;color:var(--muted);transition:all .2s;text-decoration:none}
.tab.active{background:var(--blue);color:#fff}

/* MOVIE GRID */
.movie-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:20px;margin-bottom:32px}
.movie-card{background:var(--card);border-radius:var(--r);overflow:hidden;box-shadow:var(--sh);cursor:pointer;transition:transform .2s,box-shadow .2s;text-decoration:none;display:block}
.movie-card:hover{transform:translateY(-4px);box-shadow:0 8px 32px rgba(15,23,42,.14)}
.mc-wrap{position:relative}
.mc-poster{width:100%;aspect-ratio:2/3;object-fit:cover;background:#e2e8f0;display:block}
.mc-poster-ph{width:100%;aspect-ratio:2/3;background:linear-gradient(135deg,#334155,#1e293b);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:32px}
.mc-body{padding:14px}
.mc-title{font-size:14px;font-weight:700;color:var(--text);margin-bottom:6px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.mc-genre{font-size:12px;color:var(--muted);margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mc-footer{display:flex;align-items:center;justify-content:space-between}
.mc-rating{display:flex;align-items:center;gap:4px;font-size:12.5px;font-weight:700;color:#F59E0B}
.mc-age{font-size:10.5px;font-weight:700;padding:2px 6px;border-radius:4px;background:#FEF3C7;color:#92400E}
.mc-age.t18{background:#FEE2E2;color:#991B1B}
.mc-age.t13{background:#DCFCE7;color:#166534}

.empty{text-align:center;padding:60px 20px;color:var(--muted)}
.empty i{font-size:48px;opacity:.2;display:block;margin-bottom:16px}
.empty h3{font-size:18px;font-weight:700;margin-bottom:8px}

@media(max-width:768px){
  .main{margin-left:0}
}
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<!-- MAIN -->
<div class="main">
  <!-- TOPBAR -->
  <div class="topbar">
    <h1><i class="fa-solid fa-film" style="color:var(--blue);margin-right:8px"></i>Phim</h1>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <div class="tabs">
      <a class="tab <?= $tab==='now_showing'?'active':'' ?>" href="?tab=now_showing">Đang chiếu</a>
      <a class="tab <?= $tab==='coming_soon'?'active':'' ?>" href="?tab=coming_soon">Sắp chiếu</a>
    </div>

    <?php if (empty($movieList)): ?>
      <div class="empty">
        <i class="fa-solid fa-film"></i>
        <h3>Chưa có phim nào</h3>
        <p>Danh sách phim đang được cập nhật.</p>
      </div>
    <?php else: ?>
      <div class="movie-grid">
        <?php foreach($movieList as $m): ?>
        <a class="movie-card" href="movie-detail.php?id=<?= $m['id'] ?>">
          <div class="mc-wrap">
            <?php if($m['poster_url']): ?>
              <img class="mc-poster" src="<?= htmlspecialchars($m['poster_url']) ?>" alt="<?= htmlspecialchars($m['title']) ?>">
            <?php else: ?>
              <div class="mc-poster-ph"><i class="fa-solid fa-film"></i></div>
            <?php endif; ?>
          </div>
          <div class="mc-body">
            <div class="mc-title"><?= htmlspecialchars($m['title']) ?></div>
            <div class="mc-genre"><?= htmlspecialchars($m['genre'] ?? '') ?></div>
            <div class="mc-footer">
              <span class="mc-rating"><i class="fa-solid fa-star"></i><?= number_format($m['rating'],1) ?></span>
              <span class="mc-age <?= strtolower($m['age_rating']) ?>"><?= $m['age_rating'] ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
