<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../../be/config/db.php';
$active_page = 'home';

// Tự động chuyển coming_soon → now_showing nếu release_date đã qua
$pdo->exec("UPDATE movies SET status='now_showing' WHERE status='coming_soon' AND release_date IS NOT NULL AND release_date <= CURDATE()");

$user = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

// Prepare dynamic notifications array
$notifications = [];
$user_pts = (int)($user['loyalty_points'] ?? 0);

// 1. Emergency Cancelled Bookings Notification (if any)
$cancelledStmt = $pdo->prepare("
  SELECT b.*, m.title, s.show_date, s.start_time
  FROM bookings b
  JOIN showtimes s ON b.showtime_id = s.id
  JOIN movies m ON s.movie_id = m.id
  WHERE b.user_id = ? 
    AND b.status = 'cancelled' 
    AND b.cancel_reason IS NOT NULL
  ORDER BY b.id DESC LIMIT 2
");
$cancelledStmt->execute([$_SESSION['user_id']]);
$cancelled_list = $cancelledStmt->fetchAll();
foreach ($cancelled_list as $cb) {
    $notifications[] = [
        'icon' => 'fa-solid fa-circle-exclamation',
        'color' => '#EF4444',
        'title' => 'Vé phim đã bị hủy',
        'desc' => "Suất chiếu phim {$cb['title']} ngày " . date('d/m/Y', strtotime($cb['show_date'])) . " bị hủy khẩn cấp. Bấm để xem chi tiết thông tin hoàn trả tiền vé.",
        'time' => 'Hôm nay',
        'link' => 'my-tickets.php?tab=cancelled'
    ];
}

// 2. Point Reward Promo
if ($user_pts >= 15) {
    $notifications[] = [
        'icon' => 'fa-solid fa-gem',
        'color' => '#10B981',
        'title' => 'Tích điểm đổi quà',
        'desc' => "Bạn hiện có $user_pts điểm thưởng. Hãy vào Shop đổi ngay các phần quà/voucher giảm giá cực hấp dẫn!",
        'time' => 'Vừa xong',
        'link' => 'vouchers.php'
    ];
} else {
    $notifications[] = [
        'icon' => 'fa-solid fa-gem',
        'color' => '#10B981',
        'title' => 'Tích điểm đổi quà',
        'desc' => "Tích lũy điểm khi đặt vé xem phim để quy đổi các phần quà và voucher giảm giá hấp dẫn!",
        'time' => 'Vừa xong',
        'link' => 'vouchers.php'
    ];
}

// 3. Upcoming showtime reminder
$bkStmt = $pdo->prepare("
  SELECT b.id, s.show_date, s.start_time, m.title, c.name as cinema_name
  FROM bookings b
  JOIN showtimes s ON b.showtime_id = s.id
  JOIN movies m ON s.movie_id = m.id
  JOIN cinemas c ON s.cinema_id = c.id
  WHERE b.user_id = ? AND b.status = 'confirmed'
  ORDER BY b.id DESC LIMIT 1
");
$bkStmt->execute([$_SESSION['user_id']]);
$latest_bk = $bkStmt->fetch();
if ($latest_bk) {
    $show_ts = strtotime($latest_bk['show_date'] . ' ' . $latest_bk['start_time']);
    if ($show_ts >= time()) {
        $formatted_time = date('H:i d/m', $show_ts);
        $notifications[] = [
            'icon' => 'fa-solid fa-receipt',
            'color' => '#2563EB',
            'title' => 'Suất chiếu sắp diễn ra',
            'desc' => "Vé xem phim {$latest_bk['title']} tại {$latest_bk['cinema_name']} sẽ bắt đầu lúc $formatted_time.",
            'time' => '1 giờ trước',
            'link' => 'my-tickets.php'
        ];
    }
}

// 4. Latest active voucher promotion (public and master campaigns only)
$vStmt = $pdo->prepare("
  SELECT description FROM vouchers 
  WHERE is_active = 1 
    AND user_id IS NULL 
    AND code NOT IN ('REDM30K', 'REDM50K', 'REDM100K', 'GIFTPOP') 
  ORDER BY id DESC LIMIT 1
");
$vStmt->execute();
$latest_v = $vStmt->fetch();
if ($latest_v) {
    $notifications[] = [
        'icon' => 'fa-solid fa-gift',
        'color' => '#7C3AED',
        'title' => 'Voucher mới dành cho bạn',
        'desc' => "Ưu đãi mới: {$latest_v['description']}. Áp dụng ngay khi đặt vé!",
        'time' => 'Hôm nay',
        'link' => 'vouchers.php'
    ];
}

// 5. Static generic promotions
$notifications[] = [
    'icon' => 'fa-solid fa-fire',
    'color' => '#EF4444',
    'title' => 'Đại tiệc đồng giá 79k',
    'desc' => 'Trải nghiệm phòng chiếu đôi Sweetbox siêu lãng mạn chỉ 79k vào thứ Hai hàng tuần.',
    'time' => '1 ngày trước',
    'link' => 'movies.php'
];

$movies_showing = $pdo->query("
  SELECT m.*, GROUP_CONCAT(DISTINCT s.format) as formats 
  FROM movies m
  LEFT JOIN showtimes s ON m.id = s.movie_id
  WHERE m.status='now_showing' 
  GROUP BY m.id
  ORDER BY m.rating DESC
")->fetchAll();
$movies_coming  = $pdo->query("SELECT * FROM movies WHERE status='coming_soon'  ORDER BY id   ASC")->fetchAll();
// Hero movies = top 5 phim đang chiếu có backdrop để làm carousel
$hero_movies = [];
foreach ($movies_showing as $m) {
    if (!empty($m['backdrop_url'])) {
        $hero_movies[] = $m;
        if (count($hero_movies) >= 5) break;
    }
}
if (empty($hero_movies) && !empty($movies_showing)) {
    $hero_movies = array_slice($movies_showing, 0, 5);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>MovieFlex - Trang chủ</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --blue:#2563EB;--blue-h:#1D4ED8;--blue-light:#EFF6FF;
  --sidebar:#0F172A;--sidebar-w:240px;
  --bg:#F1F5F9;--card:#fff;
  --text:#0F172A;--muted:#64748B;--light:#94A3B8;--border:#E2E8F0;
  --radius:14px;--shadow:0 2px 16px rgba(15,23,42,.08);
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}



/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}

/* ── TOPBAR ── */
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 28px;height:64px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50}
.search-wrap{flex:1;max-width:440px;position:relative}
.search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--light);font-size:14px}
.search-wrap input{width:100%;height:40px;background:var(--bg);border:1.5px solid transparent;border-radius:10px;padding:0 14px 0 40px;font-size:14px;font-family:inherit;color:var(--text);outline:none;transition:all .2s}
.search-wrap input:focus{border-color:var(--blue);background:var(--card)}
.search-wrap input::placeholder{color:var(--light)}
.topbar-right{display:flex;align-items:center;gap:12px;margin-left:auto}
.tb-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--muted);cursor:pointer;transition:all .2s;background:none;border:none;position:relative}
.tb-icon:hover{background:var(--bg);color:var(--text)}
.tb-badge{position:absolute;top:6px;right:6px;width:8px;height:8px;background:#EF4444;border-radius:50%;border:2px solid #fff}
.tb-avatar{width:36px;height:36px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;cursor:pointer;overflow:hidden;flex-shrink:0}
.tb-avatar img{width:100%;height:100%;object-fit:cover}

/* ── CONTENT ── */
.content{padding:24px 28px;flex:1}

/* ── HERO BANNER ── */
.hero{border-radius:20px;overflow:hidden;position:relative;height:320px;background:#0f172a;margin-bottom:28px}
.hero-slides-wrap{position:absolute;inset:0;z-index:1}
.hero-slide{position:absolute;inset:0;opacity:0;visibility:hidden;transition:opacity 0.8s ease, visibility 0.8s;display:flex;align-items:center}
.hero-slide.active{opacity:1;visibility:visible;z-index:2}
.hero-bg{width:100%;height:100%;object-fit:cover;opacity:.55;position:absolute;inset:0;transform-origin:center}
.hero-slide.active .hero-bg{animation:heroZoom 20s ease-in-out infinite alternate}
@keyframes heroZoom{from{transform:scale(1)}to{transform:scale(1.06)}}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(15,23,42,.92) 0%,rgba(15,23,42,.4) 60%,transparent 100%);z-index:3}
.hero-content{position:absolute;inset:0;display:flex;align-items:center;padding:40px 44px;gap:32px;z-index:4}
.hero-poster{width:120px;height:170px;border-radius:12px;object-fit:cover;box-shadow:0 8px 32px rgba(0,0,0,.6);flex-shrink:0}
.hero-poster-ph{width:120px;height:170px;border-radius:12px;background:linear-gradient(135deg,#334155,#1e293b);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:36px}
.hero-info{color:#fff;max-width:420px}

/* Premium text transitions on active slide */
.hero-slide .hero-poster,
.hero-slide .hero-poster-ph,
.hero-slide .hero-badge,
.hero-slide .hero-title,
.hero-slide .hero-meta,
.hero-slide .hero-desc,
.hero-slide .hero-btns {
  opacity: 0;
  transform: translateY(16px);
  transition: opacity 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.hero-slide.active .hero-poster,
.hero-slide.active .hero-poster-ph,
.hero-slide.active .hero-badge,
.hero-slide.active .hero-title,
.hero-slide.active .hero-meta,
.hero-slide.active .hero-desc,
.hero-slide.active .hero-btns {
  opacity: 1;
  transform: translateY(0);
}
.hero-slide.active .hero-poster { transition-delay: 0.1s; }
.hero-slide.active .hero-poster-ph { transition-delay: 0.1s; }
.hero-slide.active .hero-badge { transition-delay: 0.2s; }
.hero-slide.active .hero-title { transition-delay: 0.3s; }
.hero-slide.active .hero-meta  { transition-delay: 0.4s; }
.hero-slide.active .hero-desc  { transition-delay: 0.5s; }
.hero-slide.active .hero-btns  { transition-delay: 0.6s; }

.hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(239,68,68,.9);color:#fff;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;margin-bottom:12px;letter-spacing:.4px}
.hero-title{font-size:30px;font-weight:800;line-height:1.2;margin-bottom:10px}
.hero-meta{display:flex;align-items:center;gap:16px;font-size:13px;color:rgba(255,255,255,.7);margin-bottom:8px}
.hero-meta span{display:flex;align-items:center;gap:5px}
.hero-desc{font-size:14px;color:rgba(255,255,255,.65);line-height:1.6;margin-bottom:20px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.hero-btns{display:flex;gap:12px}
.btn-hero{height:42px;padding:0 22px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;border:none;display:flex;align-items:center;gap:8px;text-decoration:none;transition:all .2s}
.btn-hero-primary{background:var(--blue);color:#fff}
.btn-hero-primary:hover{background:var(--blue-h)}
.btn-hero-outline{background:rgba(255,255,255,.1);color:#fff;border:1.5px solid rgba(255,255,255,.25);backdrop-filter:blur(4px)}
.btn-hero-outline:hover{background:rgba(255,255,255,.2)}

/* Controls & Indicators */
.hero-ctrl {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fff;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.hero-ctrl:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: var(--blue);
  box-shadow: 0 0 12px rgba(37,99,235,0.4);
  color: #fff;
}
.hero-prev { left: 16px; }
.hero-next { right: 16px; }

.hero-dots {
  position: absolute;
  bottom: 20px;
  right: 44px;
  display: flex;
  gap: 6px;
  z-index: 10;
}
.hero-dot {
  width: 20px;
  height: 3px;
  border-radius: 1.5px;
  background: rgba(255, 255, 255, 0.25);
  cursor: pointer;
  transition: all 0.25s ease;
}
.hero-dot:hover {
  background: rgba(255, 255, 255, 0.45);
}
.hero-dot.active {
  background: var(--blue);
  width: 36px;
}
@media (max-width: 768px) {
  .hero-ctrl { display: none; }
  .hero-dots {
    left: 50%;
    transform: translateX(-50%);
    right: auto;
    bottom: 12px;
  }
}

/* ── SECTION HEADER ── */
.sec-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.sec-title{font-size:18px;font-weight:700;color:var(--text)}
.sec-link{font-size:13px;color:var(--blue);font-weight:600;text-decoration:none}
.sec-link:hover{opacity:.75}

/* ── FILTERS ── */
.filters{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.filter-btn{padding:7px 16px;border-radius:20px;border:1.5px solid var(--border);background:var(--card);font-size:13px;font-weight:500;color:var(--muted);cursor:pointer;transition:all .2s}
.filter-btn:hover,.filter-btn.active{background:var(--blue);color:#fff;border-color:var(--blue)}

/* ── MOVIE GRID ── */
.movie-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:18px;margin-bottom:32px}
.movie-card{background:var(--card);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);cursor:pointer;transition:transform .2s,box-shadow .2s;text-decoration:none;display:block}
.movie-card:hover{transform:translateY(-4px);box-shadow:0 8px 32px rgba(15,23,42,.14)}
.mc-poster{width:100%;aspect-ratio:2/3;object-fit:cover;background:#e2e8f0;display:block}
.mc-poster-ph{width:100%;aspect-ratio:2/3;background:linear-gradient(135deg,#334155,#1e293b);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:32px}
.mc-body{padding:12px}
.mc-title{font-size:13.5px;font-weight:700;color:var(--text);margin-bottom:5px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.mc-genre{font-size:11.5px;color:var(--muted);margin-bottom:7px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mc-footer{display:flex;align-items:center;justify-content:space-between}
.mc-rating{display:flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#F59E0B}
.mc-age{font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;background:#FEF3C7;color:#92400E}
.mc-age.t18{background:#FEE2E2;color:#991B1B}
.mc-age.t13{background:#DCFCE7;color:#166534}
.mc-badge{position:absolute;top:8px;left:8px;background:#2563EB;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px}
.mc-wrap{position:relative;overflow:hidden;border-radius:var(--radius) var(--radius) 0 0}
/* Hover overlay for movie-card */
.mc-overlay {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  opacity: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 5;
}
.movie-card:hover .mc-overlay {
  opacity: 1;
}
.mc-book-btn {
  width: 100%;
  background: linear-gradient(135deg, var(--blue), #7C3AED);
  color: #fff;
  border: none;
  border-radius: 10px;
  height: 38px;
  font-size: 12.5px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}
.mc-book-btn:hover {
  filter: brightness(1.1);
  transform: translateY(-1px);
}
.mc-detail-btn {
  width: 100%;
  background: rgba(255, 255, 255, 0.1);
  border: 1.5px solid rgba(255, 255, 255, 0.25);
  color: #fff;
  border-radius: 10px;
  height: 38px;
  font-size: 12.5px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
  backdrop-filter: blur(4px);
}
.mc-detail-btn:hover {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.4);
  transform: translateY(-1px);
}

/* ── PROMO BANNER ── */
.promo{border-radius:18px;background:linear-gradient(135deg,#2563EB 0%,#7C3AED 100%);padding:28px 32px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:32px}
.promo-text h3{font-size:20px;font-weight:800;color:#fff;margin-bottom:6px}
.promo-text p{font-size:14px;color:rgba(255,255,255,.8);line-height:1.5}
.btn-promo{height:40px;padding:0 20px;background:#fff;color:#2563EB;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap;text-decoration:none;display:flex;align-items:center;transition:opacity .2s;flex-shrink:0}
.btn-promo:hover{opacity:.9}

/* ── UPCOMING CARD ── */
.upcoming-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:32px}
.uc{background:var(--card);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);display:flex;gap:14px;padding:14px;align-items:center;cursor:pointer;transition:box-shadow .2s}
.uc:hover{box-shadow:0 4px 24px rgba(15,23,42,.12)}
.uc-poster{width:50px;height:70px;border-radius:8px;object-fit:cover;flex-shrink:0;background:#e2e8f0}
.uc-poster-ph{width:50px;height:70px;border-radius:8px;flex-shrink:0;background:linear-gradient(135deg,#334155,#1e293b);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:18px}
.uc-info{flex:1;min-width:0}
.uc-title{font-size:13px;font-weight:700;margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.uc-genre{font-size:11px;color:var(--muted);margin-bottom:8px}
.uc-remind{font-size:11.5px;color:var(--blue);font-weight:600;cursor:pointer}

/* ── UPCOMING ASIDE ── */
.upcoming-aside{background:var(--card);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow);margin-bottom:32px}
.upcoming-aside h4{font-size:14px;font-weight:700;margin-bottom:4px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.upcoming-aside h3{font-size:18px;font-weight:800;margin-bottom:6px}
.upcoming-aside p{font-size:13px;color:var(--muted);margin-bottom:16px}

@media(max-width:768px){
  .sidebar{display:none}
  .main{margin-left:0}
  .hero{height:240px}
  .hero-title{font-size:20px}
  .hero-poster,.hero-poster-ph{width:80px;height:114px}
}
</style>
</head>
<body>

<?php include __DIR__ . '/../components/sidebar.php'; ?>

<!-- MAIN -->
<div class="main">
  <!-- TOPBAR -->
  <div class="topbar">
    <div class="search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="search-input" placeholder="Tìm kiếm phim, rạp chiếu..." autocomplete="off">
    </div>
    <div class="topbar-right">
      <div style="position: relative;" class="noti-container">
        <button class="tb-icon" id="noti-bell-btn">
          <i class="fa-regular fa-bell"></i>
          <span class="tb-badge"></span>
        </button>
        
        <!-- Notification Dropdown -->
        <div class="noti-dropdown" id="noti-dropdown" style="display:none; position:absolute; right:0; top:46px; width:340px; background:#fff; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,.15); border:1px solid #E2E8F0; z-index:1000; overflow:hidden;">
          <div style="padding:14px 16px; border-bottom:1px solid #F1F5F9; display:flex; justify-content:space-between; align-items:center; background:#FAFBFD;">
            <span style="font-weight:800; font-size:14px; color:#0F172A;"><i class="fa-solid fa-bell" style="color:var(--blue); margin-right:6px;"></i>Thông báo</span>
            <span onclick="markAllRead()" style="font-size:12px; color:var(--blue); cursor:pointer; font-weight:600;">Đánh dấu đã đọc</span>
          </div>
          
          <div style="max-height:300px; overflow-y:auto;" id="noti-list">
            <?php foreach($notifications as $n): ?>
              <a href="<?= $n['link'] ?>" style="display:flex; gap:12px; padding:14px 16px; border-bottom:1px solid #F1F5F9; text-decoration:none; color:inherit; transition:background .2s; text-align:left;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='none'">
                <div style="width:36px; height:36px; border-radius:50%; background:<?= $n['color'] ?>15; color:<?= $n['color'] ?>; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; margin-top:2px;">
                  <i class="<?= $n['icon'] ?>"></i>
                </div>
                <div style="flex:1;">
                  <h4 style="font-size:13px; font-weight:700; color:#0F172A; margin-bottom:2px;"><?= htmlspecialchars($n['title']) ?></h4>
                  <p style="font-size:12px; color:var(--muted); line-height:1.4; margin-bottom:4px;"><?= htmlspecialchars($n['desc']) ?></p>
                  <span style="font-size:10px; color:var(--light); font-weight:500;"><?= htmlspecialchars($n['time']) ?></span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="tb-avatar">
        <?php if(!empty($user['avatar_url'])): ?>
          <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="">
        <?php else: ?>
          <?= mb_strtoupper(mb_substr($user['full_name'],0,1)) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- HERO -->
    <!-- HERO -->
    <div class="hero" onmouseenter="pauseHeroAutoplay()" onmouseleave="resumeHeroAutoplay()">
      <div class="hero-slides-wrap">
        <?php if (!empty($hero_movies)): ?>
          <?php foreach ($hero_movies as $index => $m): ?>
            <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>" data-slide-index="<?= $index ?>">
              <?php if ($m['backdrop_url']): ?>
                <img class="hero-bg" src="<?= htmlspecialchars($m['backdrop_url']) ?>" alt="">
              <?php endif; ?>
              <div class="hero-overlay"></div>
              
              <div class="hero-content">
                <?php if ($m['poster_url']): ?>
                  <img class="hero-poster" src="<?= htmlspecialchars($m['poster_url']) ?>" alt="">
                <?php else: ?>
                  <div class="hero-poster-ph"><i class="fa-solid fa-film"></i></div>
                <?php endif; ?>
                
                <div class="hero-info">
                  <div class="hero-badge"><i class="fa-solid fa-circle-play"></i> ĐANG CHIẾU</div>
                  <h1 class="hero-title"><?= htmlspecialchars($m['title']) ?></h1>
                  <div class="hero-meta">
                    <span><i class="fa-regular fa-clock"></i> <?= $m['duration_min'] ?> phút</span>
                    <span><i class="fa-solid fa-shield-halved"></i> <?= $m['age_rating'] ?></span>
                    <span><i class="fa-solid fa-star" style="color:#F59E0B"></i> <?= number_format($m['rating'],1) ?></span>
                  </div>
                  <p class="hero-desc"><?= htmlspecialchars($m['description'] ?? '') ?></p>
                  <div class="hero-btns">
                    <a href="movie-detail.php?id=<?= $m['id'] ?>" class="btn-hero btn-hero-primary"><i class="fa-solid fa-ticket"></i> Đặt vé ngay</a>
                    <a href="movie-detail.php?id=<?= $m['id'] ?>" class="btn-hero btn-hero-outline"><i class="fa-solid fa-play"></i> Xem trailer</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="hero-slide active" data-slide-index="0">
            <div class="hero-bg" style="background:linear-gradient(135deg,#0F172A,#1E1045)"></div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
              <div class="hero-poster-ph"><i class="fa-solid fa-film"></i></div>
              <div class="hero-info">
                <h1 class="hero-title">Trải nghiệm điện ảnh đỉnh cao</h1>
                <p class="hero-desc">Hàng trăm bộ phim đang chiếu, đặt vé chỉ trong 60 giây.</p>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Carousel Controls -->
      <?php if (count($hero_movies) > 1): ?>
        <button class="hero-ctrl hero-prev" onclick="prevHeroSlide(event)" aria-label="Previous Slide">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="hero-ctrl hero-next" onclick="nextHeroSlide(event)" aria-label="Next Slide">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
        
        <div class="hero-dots">
          <?php foreach ($hero_movies as $index => $m): ?>
            <span class="hero-dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToHeroSlide(<?= $index ?>)"></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- PHIM ĐANG CHIẾU -->
    <div class="sec-head">
      <h2 class="sec-title">🎬 Phim Đang Chiếu</h2>
      <a href="movies.php" class="sec-link">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
    </div>

    <!-- FILTERS -->
    <div class="filters" id="filter-bar">
      <button class="filter-btn active" data-f="all">Tất cả</button>
      <button class="filter-btn" data-f="2D">2D Digital</button>
      <button class="filter-btn" data-f="3D">3D Digital</button>
      <button class="filter-btn" data-f="IMAX">IMAX</button>
      <button class="filter-btn" data-f="PREMIUM">Premium</button>
      <button class="filter-btn" data-f="4DX">4DX</button>
    </div>

    <div class="movie-grid" id="movie-grid">
      <?php foreach($movies_showing as $m): ?>
      <a class="movie-card" href="movie-detail.php?id=<?= $m['id'] ?>" data-formats="<?= htmlspecialchars($m['formats'] ?? '') ?>">
        <div class="mc-wrap">
          <?php if($m['poster_url']): ?>
            <img class="mc-poster" src="<?= htmlspecialchars($m['poster_url']) ?>" alt="<?= htmlspecialchars($m['title']) ?>">
          <?php else: ?>
            <div class="mc-poster-ph"><i class="fa-solid fa-film"></i></div>
          <?php endif; ?>
          <div class="mc-overlay">
            <span class="mc-book-btn">
              <i class="fa-solid fa-ticket"></i> Đặt vé ngay
            </span>
            <span class="mc-detail-btn">
              <i class="fa-solid fa-circle-info"></i> Xem chi tiết
            </span>
          </div>
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

    <!-- NO RESULTS MESSAGE -->
    <div id="no-results" style="display:none;text-align:center;padding:48px 24px;background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:32px">
      <div style="font-size:52px;margin-bottom:16px">🎬</div>
      <h3 style="font-size:17px;font-weight:700;color:var(--text);margin-bottom:8px">Không tìm thấy phim nào</h3>
      <p style="font-size:14px;color:var(--muted);line-height:1.6">Không có phim nào khớp với từ khóa <strong id="no-results-kw" style="color:var(--blue)"></strong>.<br>Thử tìm với từ khóa khác hoặc xem toàn bộ danh sách phim.</p>
      <button onclick="document.getElementById('search-input').value=''; document.querySelectorAll('.filter-btn').forEach(b=>b.classList.toggle('active', b.getAttribute('data-f')==='all')); applyFilters();" style="margin-top:18px;padding:10px 24px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">Xem tất cả phim</button>
    </div>

    <!-- PROMO BANNER -->
    <div class="promo">
      <div class="promo-text">
        <h3>🎁 Ưu Đãi Thành Viên</h3>
        <p>Giảm 20% cho tất cả các suất chiếu IMAX vào mỗi thứ Tư hàng tuần.<br>Đăng ký ngay để không bỏ lỡ!</p>
      </div>
      <a href="register.php" class="btn-promo"><i class="fa-solid fa-star"></i>&nbsp; Đăng ký ngay</a>
    </div>

    <!-- SẮP CHIẾU -->
    <?php if(!empty($movies_coming)): ?>
    <div class="sec-head">
      <h2 class="sec-title">🔜 Sắp Chiếu</h2>
      <a href="movies.php?tab=coming" class="sec-link">Xem tất cả <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="upcoming-grid">
      <?php foreach($movies_coming as $m): ?>
      <div class="uc" onclick="location.href='movie-detail.php?id=<?= $m['id'] ?>'">
        <?php if($m['poster_url']): ?>
          <img class="uc-poster" src="<?= htmlspecialchars($m['poster_url']) ?>" alt="">
        <?php else: ?>
          <div class="uc-poster-ph"><i class="fa-solid fa-film"></i></div>
        <?php endif; ?>
        <div class="uc-info">
          <div class="uc-title"><?= htmlspecialchars($m['title']) ?></div>
          <div class="uc-genre"><?= htmlspecialchars($m['genre'] ?? '') ?></div>
          <div class="uc-remind"><i class="fa-regular fa-bell"></i> Nhắc tôi</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div><!-- /content -->
</div><!-- /main -->

<script>
// Filter tabs and search combined logic
function applyFilters() {
  const activeBtn = document.querySelector('.filter-btn.active');
  const filter = activeBtn ? activeBtn.getAttribute('data-f') : 'all';
  
  const searchInput = document.getElementById('search-input');
  const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
  
  const cards = document.querySelectorAll('#movie-grid .movie-card');
  let visible = 0;
  
  cards.forEach(card => {
    // 1. Check format match (case-insensitive check)
    const formatsStr = card.getAttribute('data-formats') || '';
    const formats = formatsStr.split(',').map(f => f.trim().toUpperCase());
    const formatMatch = (filter === 'all' || formats.includes(filter.toUpperCase()));
    
    // 2. Check search match
    const title = card.querySelector('.mc-title')?.textContent.toLowerCase() || '';
    const genre = card.querySelector('.mc-genre')?.textContent.toLowerCase() || '';
    const searchMatch = !q || title.includes(q) || genre.includes(q);
    
    const match = formatMatch && searchMatch;
    card.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  
  const noRes = document.getElementById('no-results');
  const kw    = document.getElementById('no-results-kw');
  if (noRes) {
    noRes.style.display = (visible === 0) ? 'block' : 'none';
    if (kw) kw.textContent = q ? '"' + q + '"' : 'bộ lọc';
  }
}

document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
    this.classList.add('active');
    applyFilters();
  });
});

document.getElementById('search-input').addEventListener('input', function(){
  applyFilters();
});

// Notification Dropdown Toggle
const bellBtn = document.getElementById('noti-bell-btn');
const notiDropdown = document.getElementById('noti-dropdown');

if (bellBtn && notiDropdown) {
  bellBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    notiDropdown.style.display = notiDropdown.style.display === 'none' ? 'block' : 'none';
  });

  document.addEventListener('click', (e) => {
    if (!notiDropdown.contains(e.target) && e.target !== bellBtn) {
      notiDropdown.style.display = 'none';
    }
  });
}

function markAllRead() {
  const badge = document.querySelector('#noti-bell-btn .tb-badge');
  if (badge) {
    badge.style.display = 'none';
  }
}

// Logout
async function logout(){
  const fd = new FormData(); fd.append('action','logout');
  const r = await fetch('../../be/api.php',{method:'POST',body:fd});
  const d = await r.json();
  location.href = d.redirect || 'login.php';
}

// ── Hero Carousel Slider Logic ──
let currentHeroIndex = 0;
const heroSlides = document.querySelectorAll('.hero .hero-slide');
const heroDots = document.querySelectorAll('.hero .hero-dot');
let heroAutoplayTimer = null;
const heroSlideDelay = 6000; // 6 seconds

function showHeroSlide(index) {
  if (heroSlides.length <= 1) return;
  
  if (index >= heroSlides.length) index = 0;
  if (index < 0) index = heroSlides.length - 1;
  
  currentHeroIndex = index;
  
  heroSlides.forEach((slide, i) => {
    slide.classList.toggle('active', i === index);
  });
  
  heroDots.forEach((dot, i) => {
    dot.classList.toggle('active', i === index);
  });
}

function nextHeroSlide(e) {
  if (e) { e.preventDefault(); e.stopPropagation(); }
  showHeroSlide(currentHeroIndex + 1);
  resetHeroAutoplay();
}

function prevHeroSlide(e) {
  if (e) { e.preventDefault(); e.stopPropagation(); }
  showHeroSlide(currentHeroIndex - 1);
  resetHeroAutoplay();
}

function goToHeroSlide(index) {
  showHeroSlide(index);
  resetHeroAutoplay();
}

function startHeroAutoplay() {
  if (heroSlides.length <= 1) return;
  stopHeroAutoplay();
  heroAutoplayTimer = setInterval(() => {
    showHeroSlide(currentHeroIndex + 1);
  }, heroSlideDelay);
}

function stopHeroAutoplay() {
  if (heroAutoplayTimer) {
    clearInterval(heroAutoplayTimer);
    heroAutoplayTimer = null;
  }
}

function pauseHeroAutoplay() {
  stopHeroAutoplay();
}

function resumeHeroAutoplay() {
  startHeroAutoplay();
}

function resetHeroAutoplay() {
  stopHeroAutoplay();
  startHeroAutoplay();
}

document.addEventListener('DOMContentLoaded', () => {
  if (heroSlides.length > 0) {
    setTimeout(() => {
      heroSlides[0].classList.add('active');
    }, 100);
  }
  startHeroAutoplay();
});
</script>
</body>
</html>
