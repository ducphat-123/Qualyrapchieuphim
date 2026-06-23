<?php
/**
 * guest.php — Trang công khai dành cho khách (không cần đăng nhập)
 * MovieFlex Cinema — Giao diện khách hàng
 */
require_once __DIR__ . '/../../be/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Nếu đã đăng nhập → chuyển về home
if (!empty($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

// Auto-promote coming_soon → now_showing
$pdo->exec("UPDATE movies SET status='now_showing' WHERE status='coming_soon' AND release_date IS NOT NULL AND release_date <= CURDATE()");

// Fetch movies đang chiếu (kèm formats)
$movies_showing = $pdo->query("
  SELECT m.*, GROUP_CONCAT(DISTINCT s.format ORDER BY s.format SEPARATOR ',') as formats
  FROM movies m
  LEFT JOIN showtimes s ON m.id = s.movie_id AND s.show_date >= CURDATE()
  WHERE m.status = 'now_showing'
  GROUP BY m.id
  ORDER BY m.rating DESC
  LIMIT 20
")->fetchAll();

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

// Fetch phim sắp chiếu
$movies_coming = $pdo->query("
  SELECT * FROM movies WHERE status='coming_soon'
  ORDER BY release_date ASC
  LIMIT 8
")->fetchAll();

// Fetch cinemas
$cinemas = $pdo->query("SELECT * FROM cinemas ORDER BY name ASC LIMIT 6")->fetchAll();

// Fetch vouchers nổi bật
$promos = $pdo->query("
  SELECT * FROM vouchers WHERE is_active = 1
  ORDER BY id DESC LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MovieFlex — Đặt vé xem phim dễ dàng, nhanh chóng</title>
<meta name="description" content="MovieFlex — Nền tảng đặt vé xem phim trực tuyến hàng đầu Việt Nam. Hàng trăm bộ phim, hàng nghìn suất chiếu, ưu đãi thành viên hấp dẫn.">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ═══════════════════════════════════════════════════════
   RESET & ROOT
═══════════════════════════════════════════════════════ */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
:root {
  --blue:    #3B82F6;
  --blue-h:  #2563EB;
  --blue-dk: #1D4ED8;
  --violet:  #7C3AED;
  --pink:    #EC4899;
  --gold:    #F59E0B;
  --green:   #10B981;
  --red:     #EF4444;

  /* Dark base */
  --bg:      #050D1A;
  --bg2:     #0A1628;
  --bg3:     #0F1E35;
  --card:    #0D1B2E;
  --card2:   #111D30;
  --border:  rgba(255,255,255,.08);
  --border2: rgba(255,255,255,.12);

  --text:    #F0F6FF;
  --muted:   rgba(255,255,255,.55);
  --light:   rgba(255,255,255,.35);

  --radius:  16px;
  --shadow:  0 8px 32px rgba(0,0,0,.4);
  --glow-blue: 0 0 30px rgba(59,130,246,.3);
}

html { scroll-behavior: smooth; }
body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}

/* ═══════════════════════════════════════════════════════
   SCROLLBAR
═══════════════════════════════════════════════════════ */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg); }
::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.25); }

/* ═══════════════════════════════════════════════════════
   NAVBAR
═══════════════════════════════════════════════════════ */
.navbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
  height: 70px;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 40px;
  gap: 16px;
  background: rgba(5,13,26,.8);
  backdrop-filter: blur(16px) saturate(160%);
  -webkit-backdrop-filter: blur(16px) saturate(160%);
  border-bottom: 1px solid var(--border);
  transition: all .3s;
}
.navbar.scrolled {
  background: rgba(5,13,26,.97);
  border-bottom-color: var(--border2);
}

/* ── SEARCH BAR ── */
.nav-search {
  flex: 1; max-width: 320px;
}
.nav-search-wrap {
  position: relative; display: flex; align-items: center;
}
.nav-search-icon {
  position: absolute; left: 13px;
  color: var(--light); font-size: 13px;
  pointer-events: none;
  transition: color .2s;
}
.nav-search-input {
  width: 100%; height: 40px;
  background: rgba(255,255,255,.07);
  border: 1.5px solid var(--border);
  border-radius: 10px;
  padding: 0 36px 0 38px;
  font-size: 14px; font-family: 'Inter', sans-serif;
  color: var(--text);
  outline: none; transition: all .25s;
}
.nav-search-input::placeholder { color: var(--light); }
.nav-search-input:focus {
  background: rgba(255,255,255,.11);
  border-color: rgba(59,130,246,.5);
  box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}
.nav-search-input:focus + .nav-search-icon,
.nav-search-wrap:focus-within .nav-search-icon { color: var(--blue); }
.nav-search-clear {
  position: absolute; right: 10px;
  background: none; border: none;
  color: var(--light); font-size: 13px;
  cursor: pointer; padding: 4px;
  border-radius: 50%; transition: all .2s;
  display: flex; align-items: center; justify-content: center;
}
.nav-search-clear:hover { background: rgba(255,255,255,.1); color: var(--text); }

/* Search highlight */
.search-highlight {
  outline: 2px solid rgba(59,130,246,.4);
  outline-offset: 2px;
}

/* No results state */
#guest-no-results {
  display: none;
  text-align: center; padding: 64px 24px;
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  grid-column: 1 / -1;
}
#guest-no-results i { font-size: 48px; color: var(--muted); display: block; margin-bottom: 16px; }
#guest-no-results h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
#guest-no-results p { font-size: 14px; color: var(--muted); }
.nav-brand {
  display: flex; align-items: center; gap: 12px;
  text-decoration: none;
}
.nav-brand-icon {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, var(--blue), var(--violet));
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; color: #fff;
  box-shadow: 0 4px 16px rgba(59,130,246,.4);
  transition: transform .25s;
}
.nav-brand:hover .nav-brand-icon { transform: scale(1.08) rotate(-3deg); }
.nav-brand-name {
  font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -.5px;
}
.nav-brand-name span { color: var(--blue); }

.nav-links {
  display: flex; align-items: center; gap: 4px;
  list-style: none;
}
.nav-links a {
  display: flex; align-items: center; gap: 7px;
  padding: 8px 16px; border-radius: 10px;
  color: var(--muted); font-size: 14px; font-weight: 600;
  text-decoration: none; transition: all .2s;
}
.nav-links a:hover, .nav-links a.active {
  background: rgba(255,255,255,.07);
  color: #fff;
}
.nav-links a i { font-size: 13px; }

.nav-actions { display: flex; align-items: center; gap: 10px; }
.btn-nav-outline {
  height: 40px; padding: 0 20px; border-radius: 10px;
  background: transparent; border: 1.5px solid var(--border2);
  color: var(--text); font-size: 14px; font-weight: 700;
  font-family: inherit; cursor: pointer; text-decoration: none;
  display: flex; align-items: center; gap: 8px;
  transition: all .2s;
}
.btn-nav-outline:hover { border-color: var(--blue); color: var(--blue); }
.btn-nav-primary {
  height: 40px; padding: 0 22px; border-radius: 10px;
  background: linear-gradient(135deg, var(--blue), var(--violet));
  border: none; color: #fff; font-size: 14px; font-weight: 700;
  font-family: inherit; cursor: pointer; text-decoration: none;
  display: flex; align-items: center; gap: 8px;
  box-shadow: 0 4px 16px rgba(59,130,246,.4);
  transition: all .25s; white-space: nowrap;
}
.btn-nav-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(59,130,246,.5);
}

/* Hamburger */
.nav-toggle { display: none; }

/* ═══════════════════════════════════════════════════════
   HERO SECTION
═══════════════════════════════════════════════════════ */
.hero {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
}
.hero-slides-wrap {
  position: absolute; inset: 0;
  z-index: 1;
}
.hero-slide {
  position: absolute; inset: 0;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.8s;
  display: flex;
  align-items: flex-end;
  padding-bottom: 80px;
}
.hero-slide.active {
  opacity: 1;
  visibility: visible;
  z-index: 2;
}
.hero-bg {
  position: absolute; inset: 0;
  background-size: cover; background-position: center 20%;
  transform-origin: center;
}
.hero-slide.active .hero-bg {
  animation: heroZoom 20s ease-in-out infinite alternate;
}
@keyframes heroZoom {
  from { transform: scale(1); }
  to   { transform: scale(1.06); }
}
.hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(5,13,26,.3) 0%,
    rgba(5,13,26,.15) 30%,
    rgba(5,13,26,.7) 65%,
    rgba(5,13,26,1) 100%
  );
  z-index: 3;
}
.hero-overlay2 {
  position: absolute; inset: 0;
  background: linear-gradient(
    to right,
    rgba(5,13,26,.9) 0%,
    rgba(5,13,26,.5) 50%,
    transparent 100%
  );
  z-index: 3;
}

/* Particle orbs */
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: .18;
  animation: orbFloat 12s ease-in-out infinite alternate;
  pointer-events: none;
  z-index: 3;
}
.orb1 { width: 500px; height: 500px; background: var(--blue); top: -100px; right: -80px; animation-duration: 14s; }
.orb2 { width: 300px; height: 300px; background: var(--violet); bottom: 80px; left: 30%; animation-duration: 10s; animation-delay: -5s; }
@keyframes orbFloat {
  from { transform: translate(0, 0) scale(1); }
  to   { transform: translate(30px, -30px) scale(1.1); }
}

.hero-content {
  position: relative; z-index: 5;
  padding: 0 64px;
  max-width: 700px;
}

/* Premium text transitions on active slide */
.hero-slide .hero-badge,
.hero-slide .hero-title,
.hero-slide .hero-meta,
.hero-slide .hero-desc,
.hero-slide .hero-btns {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.hero-slide.active .hero-badge,
.hero-slide.active .hero-title,
.hero-slide.active .hero-meta,
.hero-slide.active .hero-desc,
.hero-slide.active .hero-btns {
  opacity: 1;
  transform: translateY(0);
}
.hero-slide.active .hero-badge { transition-delay: 0.1s; }
.hero-slide.active .hero-title { transition-delay: 0.2s; }
.hero-slide.active .hero-meta  { transition-delay: 0.3s; }
.hero-slide.active .hero-desc  { transition-delay: 0.4s; }
.hero-slide.active .hero-btns  { transition-delay: 0.5s; }

.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(59,130,246,.15);
  border: 1px solid rgba(59,130,246,.35);
  color: #93C5FD;
  font-size: 11.5px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase;
  padding: 6px 14px; border-radius: 20px;
  margin-bottom: 20px;
  backdrop-filter: blur(8px);
}
.hero-badge i { color: var(--gold); }

.hero-title {
  font-size: clamp(32px, 5vw, 58px);
  font-weight: 900; line-height: 1.05;
  letter-spacing: -2px;
  margin-bottom: 16px;
  color: #fff;
}
.hero-title .gradient-text {
  background: linear-gradient(90deg, #60A5FA, #A78BFA, #F472B6);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-meta {
  display: flex; align-items: center; gap: 20px;
  font-size: 14px; color: var(--muted);
  margin-bottom: 14px;
}
.hero-meta span { display: flex; align-items: center; gap: 6px; }
.hero-meta .star { color: var(--gold); }

.hero-desc {
  font-size: 15px; color: rgba(255,255,255,.65);
  line-height: 1.7; margin-bottom: 32px;
  max-width: 520px;
  display: -webkit-box; -webkit-line-clamp: 3;
  -webkit-box-orient: vertical; overflow: hidden;
}

.hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }
.btn-hero-primary {
  height: 52px; padding: 0 32px;
  background: linear-gradient(135deg, var(--blue) 0%, var(--violet) 100%);
  border: none; border-radius: 14px;
  color: #fff; font-size: 15px; font-weight: 800;
  font-family: inherit; cursor: pointer;
  display: flex; align-items: center; gap: 10px;
  text-decoration: none;
  box-shadow: 0 8px 24px rgba(59,130,246,.45);
  transition: all .25s; position: relative; overflow: hidden;
}
.btn-hero-primary::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg,rgba(255,255,255,.15),transparent);
}
.btn-hero-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 32px rgba(59,130,246,.55);
}
.btn-hero-outline {
  height: 52px; padding: 0 28px;
  background: rgba(255,255,255,.08);
  border: 1.5px solid rgba(255,255,255,.2);
  border-radius: 14px;
  color: #fff; font-size: 15px; font-weight: 700;
  font-family: inherit; cursor: pointer;
  display: flex; align-items: center; gap: 10px;
  text-decoration: none;
  backdrop-filter: blur(8px);
  transition: all .25s;
}
.btn-hero-outline:hover {
  background: rgba(255,255,255,.15);
  border-color: rgba(255,255,255,.35);
}

/* Glassmorphism Slider Buttons */
.hero-ctrl {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fff;
  font-size: 18px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.hero-ctrl:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: var(--blue);
  box-shadow: 0 0 15px rgba(59,130,246,0.4);
  color: var(--blue);
}
.hero-prev { left: 40px; }
.hero-next { right: 40px; }

/* Carousel indicators */
.hero-dots {
  position: absolute;
  bottom: 30px;
  left: 64px;
  display: flex;
  gap: 8px;
  z-index: 10;
}
.hero-dot {
  width: 32px;
  height: 4px;
  border-radius: 2px;
  background: rgba(255, 255, 255, 0.25);
  cursor: pointer;
  transition: all 0.3s ease;
}
.hero-dot:hover {
  background: rgba(255, 255, 255, 0.45);
}
.hero-dot.active {
  background: linear-gradient(90deg, var(--blue), var(--violet));
  width: 48px;
}
@media (max-width: 768px) {
  .hero-dots {
    left: 50%;
    transform: translateX(-50%);
    bottom: 24px;
  }
  .hero-ctrl {
    display: none;
  }
}

/* Hero stats */
.hero-stats {
  position: absolute; bottom: 80px; right: 64px; z-index: 5;
  display: flex; gap: 1px;
  background: rgba(255,255,255,.08);
  border: 1px solid var(--border2);
  border-radius: 18px;
  overflow: hidden;
  backdrop-filter: blur(12px);
}
.hstat {
  padding: 20px 28px; text-align: center;
  border-right: 1px solid var(--border);
}
.hstat:last-child { border-right: none; }
.hstat-val {
  font-size: 26px; font-weight: 900; color: #fff;
  letter-spacing: -1px;
}
.hstat-val span {
  font-size: 14px;
  background: linear-gradient(90deg, var(--blue), var(--violet));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hstat-lbl { font-size: 11px; color: var(--light); margin-top: 3px; font-weight: 500; }

/* Scroll indicator */
.scroll-hint {
  position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%);
  z-index: 5; display: flex; flex-direction: column; align-items: center; gap: 6px;
  color: var(--light); font-size: 11px; font-weight: 600; letter-spacing: .5px;
  animation: bounce 2s ease-in-out infinite;
}
.scroll-hint i { font-size: 16px; }
@keyframes bounce {
  0%, 100% { transform: translateX(-50%) translateY(0); }
  50%       { transform: translateX(-50%) translateY(6px); }
}

/* ═══════════════════════════════════════════════════════
   SECTION COMMON
═══════════════════════════════════════════════════════ */
section { padding: 80px 0; }
.container { max-width: 1280px; margin: 0 auto; padding: 0 48px; }

.sec-head {
  display: flex; align-items: flex-end; justify-content: space-between;
  margin-bottom: 36px; gap: 16px;
}
.sec-eyebrow {
  display: flex; align-items: center; gap: 8px;
  font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;
  color: var(--blue); margin-bottom: 8px;
}
.sec-eyebrow::before {
  content: '';
  width: 20px; height: 3px;
  background: linear-gradient(90deg, var(--blue), var(--violet));
  border-radius: 2px;
}
.sec-title {
  font-size: 28px; font-weight: 800; color: var(--text);
  letter-spacing: -.5px; line-height: 1.2;
}
.sec-link {
  display: flex; align-items: center; gap: 7px;
  color: var(--blue); font-size: 14px; font-weight: 700;
  text-decoration: none; white-space: nowrap;
  padding: 8px 18px; border-radius: 10px;
  border: 1.5px solid rgba(59,130,246,.3);
  transition: all .2s; flex-shrink: 0;
}
.sec-link:hover { background: rgba(59,130,246,.1); border-color: var(--blue); }

/* Filter pills */
.filter-bar {
  display: flex; gap: 8px; flex-wrap: wrap;
  margin-bottom: 28px;
}
.fpill {
  padding: 8px 18px; border-radius: 20px;
  background: var(--card); border: 1.5px solid var(--border2);
  color: var(--muted); font-size: 13px; font-weight: 600;
  cursor: pointer; transition: all .2s;
}
.fpill:hover { border-color: rgba(59,130,246,.5); color: var(--text); }
.fpill.active {
  background: var(--blue); color: #fff;
  border-color: var(--blue);
  box-shadow: 0 4px 14px rgba(59,130,246,.4);
}

/* ═══════════════════════════════════════════════════════
   MOVIE GRID
═══════════════════════════════════════════════════════ */
#sec-showing { background: var(--bg); }

.movie-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
  gap: 22px;
}
.movie-card {
  background: var(--card);
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--border);
  text-decoration: none;
  display: block;
  transition: all .3s;
  position: relative;
  cursor: pointer;
}
.movie-card:hover {
  transform: translateY(-6px);
  border-color: rgba(59,130,246,.4);
  box-shadow: 0 16px 40px rgba(0,0,0,.5), var(--glow-blue);
}
.mc-poster-wrap { position: relative; aspect-ratio: 2/3; overflow: hidden; }
.mc-poster {
  width: 100%; height: 100%;
  object-fit: cover; display: block;
  transition: transform .5s;
}
.movie-card:hover .mc-poster { transform: scale(1.05); }
.mc-poster-ph {
  width: 100%; height: 100%;
  background: linear-gradient(135deg, var(--bg3), var(--card2));
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,.1); font-size: 48px;
}

/* Hover overlay */
.mc-overlay {
  position: absolute; inset: 0;
  background: rgba(5,13,26,.8);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 16px;
  gap: 10px;
  z-index: 5;
}
.movie-card:hover .mc-overlay { opacity: 1; }
.mc-book-btn {
  width: 100%;
  background: linear-gradient(135deg, var(--blue), var(--violet));
  color: #fff; border: none; border-radius: 10px;
  height: 38px; font-size: 12.5px; font-weight: 800;
  font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  text-decoration: none; transition: all .2s;
  box-shadow: 0 4px 12px rgba(59,130,246,0.3);
}
.mc-book-btn:hover { filter: brightness(1.1); transform: translateY(-1px); }
.mc-detail-btn {
  width: 100%;
  background: rgba(255, 255, 255, 0.1);
  border: 1.5px solid rgba(255, 255, 255, 0.25);
  color: #fff; border-radius: 10px;
  height: 38px; font-size: 12.5px; font-weight: 800;
  font-family: inherit; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  text-decoration: none; transition: all .2s;
  backdrop-filter: blur(4px);
}
.mc-detail-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
  transform: translateY(-1px);
}

/* Format badge */
.mc-format-badge {
  position: absolute; top: 10px; left: 10px;
  background: rgba(59,130,246,.9); color: #fff;
  font-size: 9px; font-weight: 800; padding: 3px 8px;
  border-radius: 6px; letter-spacing: .3px;
  backdrop-filter: blur(4px);
}

.mc-body { padding: 14px; }
.mc-title {
  font-size: 14px; font-weight: 700; color: var(--text);
  margin-bottom: 6px; line-height: 1.35;
  display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden;
}
.mc-genre {
  font-size: 11.5px; color: var(--muted);
  margin-bottom: 10px; white-space: nowrap;
  overflow: hidden; text-overflow: ellipsis;
}
.mc-footer { display: flex; align-items: center; justify-content: space-between; }
.mc-rating {
  display: flex; align-items: center; gap: 4px;
  font-size: 13px; font-weight: 800; color: var(--gold);
}
.mc-age {
  font-size: 10px; font-weight: 800;
  padding: 3px 7px; border-radius: 5px;
}
.mc-age.p { background: rgba(16,185,129,.15); color: #6EE7B7; }
.mc-age.t13 { background: rgba(16,185,129,.15); color: #6EE7B7; }
.mc-age.t16 { background: rgba(245,158,11,.15); color: #FCD34D; }
.mc-age.t18 { background: rgba(239,68,68,.15); color: #FCA5A5; }

/* No results */
#no-results {
  display: none; text-align: center; padding: 64px 24px;
  background: var(--card); border-radius: var(--radius);
  border: 1px solid var(--border);
}
#no-results i { font-size: 48px; color: var(--muted); margin-bottom: 16px; display: block; }
#no-results h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
#no-results p { font-size: 14px; color: var(--muted); }

/* ═══════════════════════════════════════════════════════
   PROMO BANNER (CTA đăng ký)
═══════════════════════════════════════════════════════ */
.promo-section {
  padding: 0;
  position: relative; overflow: hidden;
}
.promo-inner {
  background: linear-gradient(135deg, #0F172A 0%, #1E1045 50%, #0F172A 100%);
  border-radius: 28px;
  padding: 64px 72px;
  display: flex; align-items: center; justify-content: space-between;
  gap: 48px;
  position: relative; overflow: hidden;
  border: 1px solid rgba(139,92,246,.25);
}
.promo-inner::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse at 0% 0%, rgba(59,130,246,.15) 0%, transparent 55%),
    radial-gradient(ellipse at 100% 100%, rgba(139,92,246,.15) 0%, transparent 55%);
  pointer-events: none;
}
.promo-glow {
  position: absolute; width: 400px; height: 400px; border-radius: 50%;
  background: radial-gradient(circle, rgba(139,92,246,.2), transparent 70%);
  top: -150px; right: -100px; pointer-events: none;
  animation: orbFloat 8s ease-in-out infinite alternate;
}
.promo-text { position: relative; z-index: 1; max-width: 560px; }
.promo-eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(139,92,246,.15); border: 1px solid rgba(139,92,246,.3);
  color: #C4B5FD; font-size: 11px; font-weight: 700; letter-spacing: .7px;
  padding: 5px 14px; border-radius: 20px; margin-bottom: 18px; text-transform: uppercase;
}
.promo-title {
  font-size: 36px; font-weight: 900; color: #fff;
  letter-spacing: -1.5px; line-height: 1.1; margin-bottom: 14px;
}
.promo-title span {
  background: linear-gradient(90deg, #60A5FA, #A78BFA, #F472B6);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.promo-desc { font-size: 15px; color: rgba(255,255,255,.65); line-height: 1.7; margin-bottom: 28px; }
.promo-benefits { display: flex; flex-direction: column; gap: 10px; margin-bottom: 32px; }
.pb-item {
  display: flex; align-items: center; gap: 12px;
  font-size: 14px; color: rgba(255,255,255,.8); font-weight: 500;
}
.pb-icon {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 13px;
}
.pb-icon.blue { background: rgba(59,130,246,.2); color: #60A5FA; }
.pb-icon.violet { background: rgba(139,92,246,.2); color: #A78BFA; }
.pb-icon.pink { background: rgba(236,72,153,.2); color: #F9A8D4; }
.pb-icon.gold { background: rgba(245,158,11,.2); color: #FCD34D; }
.promo-btns { display: flex; gap: 14px; flex-wrap: wrap; }
.btn-promo-primary {
  height: 52px; padding: 0 32px; border-radius: 14px;
  background: linear-gradient(135deg, var(--blue), var(--violet));
  border: none; color: #fff; font-size: 15px; font-weight: 800;
  font-family: inherit; cursor: pointer; text-decoration: none;
  display: flex; align-items: center; gap: 10px;
  box-shadow: 0 8px 24px rgba(59,130,246,.4);
  transition: all .25s;
}
.btn-promo-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(59,130,246,.5); }
.btn-promo-outline {
  height: 52px; padding: 0 28px; border-radius: 14px;
  background: transparent; border: 1.5px solid rgba(255,255,255,.2);
  color: #fff; font-size: 15px; font-weight: 700;
  font-family: inherit; cursor: pointer; text-decoration: none;
  display: flex; align-items: center; gap: 10px;
  transition: all .25s;
}
.btn-promo-outline:hover { border-color: rgba(255,255,255,.4); background: rgba(255,255,255,.07); }

/* Promo right — movie showcase */
.promo-showcase {
  position: relative; z-index: 1; flex-shrink: 0;
  display: grid; grid-template-columns: 1fr 1fr 1fr;
  gap: 12px; align-items: end;
}
.showcase-card {
  border-radius: 14px; overflow: hidden;
  box-shadow: 0 8px 24px rgba(0,0,0,.6);
  transition: transform .3s;
}
.showcase-card:hover { transform: translateY(-6px) rotate(-1deg); }
.showcase-card:nth-child(2) { transform: translateY(-24px); }
.showcase-card:nth-child(2):hover { transform: translateY(-30px) rotate(1deg); }
.showcase-card img { width: 90px; height: 130px; object-fit: cover; display: block; }

/* ═══════════════════════════════════════════════════════
   COMING SOON
═══════════════════════════════════════════════════════ */
#sec-coming { background: var(--bg2); }

.coming-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 18px;
}
.coming-card {
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  display: flex; gap: 16px; padding: 18px;
  align-items: flex-start;
  text-decoration: none;
  transition: all .25s;
  cursor: pointer;
}
.coming-card:hover {
  border-color: rgba(59,130,246,.3);
  background: var(--card2);
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(0,0,0,.4);
}
.cc-poster {
  width: 56px; height: 80px;
  border-radius: 10px; object-fit: cover;
  flex-shrink: 0; background: var(--bg3);
}
.cc-poster-ph {
  width: 56px; height: 80px; border-radius: 10px;
  background: var(--bg3); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,.1); font-size: 22px;
}
.cc-info { flex: 1; min-width: 0; }
.cc-title {
  font-size: 14px; font-weight: 700; color: var(--text);
  margin-bottom: 4px; line-height: 1.35;
  display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden;
}
.cc-genre { font-size: 11.5px; color: var(--muted); margin-bottom: 10px; }
.cc-release {
  display: flex; align-items: center; gap: 6px;
  font-size: 11.5px; color: var(--blue); font-weight: 700;
}
.cc-remind {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--muted); font-weight: 600;
  margin-top: 6px; cursor: pointer;
  transition: color .2s;
}
.cc-remind:hover { color: var(--gold); }
.cc-remind:hover i { color: var(--gold); }

/* ═══════════════════════════════════════════════════════
   CINEMAS
═══════════════════════════════════════════════════════ */
#sec-cinemas { background: var(--bg); }

.cinema-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}
.cinema-card {
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 24px;
  transition: all .25s;
  text-decoration: none;
  display: block;
}
.cinema-card:hover {
  border-color: rgba(59,130,246,.3);
  background: var(--card2);
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(0,0,0,.4);
}
.cin-logo-wrap {
  width: 100%; height: 90px;
  border-radius: 14px; overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 18px;
  position: relative;
  transition: transform .3s;
}
.cinema-card:hover .cin-logo-wrap { transform: scale(1.03); }
.cin-logo-img {
  max-width: 80%; max-height: 60px;
  object-fit: contain; display: block;
  filter: brightness(0) invert(1);
}
.cin-logo-fallback {
  display: none;
  align-items: center; justify-content: center;
  width: 100%; height: 100%;
}

/* CSS Text Logo */
.cin-brand-text {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 2px; width: 100%; padding: 0 12px;
  position: relative;
}
.cin-brand-dot {
  width: 8px; height: 8px; border-radius: 50%;
  margin-bottom: 4px;
  box-shadow: 0 0 8px currentColor;
}
.cin-brand-main {
  font-weight: 900; color: #fff; line-height: 1;
  letter-spacing: 2px; text-shadow: 0 2px 8px rgba(0,0,0,.4);
  font-family: 'Inter', sans-serif;
}
.cin-brand-sub {
  font-size: 9px; font-weight: 800; letter-spacing: 3px;
  color: rgba(255,255,255,.65); text-transform: uppercase;
  margin-top: 3px;
}
.cin-name {
  font-size: 16px; font-weight: 800; color: var(--text);
  margin-bottom: 6px;
}
.cin-addr {
  font-size: 13px; color: var(--muted);
  line-height: 1.5; margin-bottom: 14px;
}
.cin-tags { display: flex; gap: 6px; flex-wrap: wrap; }
.cin-tag {
  font-size: 11px; font-weight: 700; padding: 4px 10px;
  border-radius: 6px; background: rgba(59,130,246,.1);
  color: var(--blue); border: 1px solid rgba(59,130,246,.2);
}

/* ═══════════════════════════════════════════════════════
   VOUCHERS / DEALS
═══════════════════════════════════════════════════════ */
#sec-deals { background: var(--bg2); }

.deals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}
.deal-card {
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 24px 28px;
  display: flex; align-items: center; gap: 20px;
  position: relative; overflow: hidden;
  transition: all .25s;
}
.deal-card:hover {
  border-color: rgba(139,92,246,.3);
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(0,0,0,.4);
}
.deal-card::before {
  content: '';
  position: absolute; left: 0; top: 0; bottom: 0;
  width: 5px;
  background: linear-gradient(to bottom, var(--blue), var(--violet));
  border-radius: 4px 0 0 4px;
}
.deal-icon {
  width: 56px; height: 56px; flex-shrink: 0;
  background: linear-gradient(135deg, rgba(59,130,246,.15), rgba(139,92,246,.15));
  border-radius: 14px; border: 1px solid rgba(139,92,246,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 24px;
}
.deal-info { flex: 1; }
.deal-title { font-size: 15px; font-weight: 800; color: var(--text); margin-bottom: 5px; }
.deal-desc { font-size: 13px; color: var(--muted); line-height: 1.5; }
.deal-badge {
  position: absolute; top: 12px; right: 14px;
  font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
  padding: 3px 10px; border-radius: 20px;
  background: rgba(16,185,129,.15); color: #6EE7B7;
  border: 1px solid rgba(16,185,129,.25);
}

/* ═══════════════════════════════════════════════════════
   FEATURES
═══════════════════════════════════════════════════════ */
#sec-features { background: var(--bg); }
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 24px;
}
.feat-card {
  background: var(--card);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 32px 28px;
  transition: all .3s;
}
.feat-card:hover {
  border-color: rgba(59,130,246,.3);
  transform: translateY(-4px);
  box-shadow: var(--glow-blue), 0 12px 32px rgba(0,0,0,.4);
}
.feat-icon {
  width: 56px; height: 56px;
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 24px; margin-bottom: 20px;
}
.feat-icon.blue { background: rgba(59,130,246,.15); color: var(--blue); border: 1px solid rgba(59,130,246,.2); }
.feat-icon.violet { background: rgba(139,92,246,.15); color: var(--violet); border: 1px solid rgba(139,92,246,.2); }
.feat-icon.gold { background: rgba(245,158,11,.15); color: var(--gold); border: 1px solid rgba(245,158,11,.2); }
.feat-icon.green { background: rgba(16,185,129,.15); color: var(--green); border: 1px solid rgba(16,185,129,.2); }
.feat-icon.pink { background: rgba(236,72,153,.15); color: var(--pink); border: 1px solid rgba(236,72,153,.2); }
.feat-icon.red { background: rgba(239,68,68,.15); color: var(--red); border: 1px solid rgba(239,68,68,.2); }
.feat-title {
  font-size: 16px; font-weight: 800; color: var(--text);
  margin-bottom: 8px;
}
.feat-desc { font-size: 13.5px; color: var(--muted); line-height: 1.6; }

/* ═══════════════════════════════════════════════════════
   FINAL CTA
═══════════════════════════════════════════════════════ */
#sec-cta {
  background: var(--bg);
  padding-bottom: 100px;
}
.cta-card {
  background: linear-gradient(135deg, #0F172A 0%, #1E1045 100%);
  border-radius: 28px;
  padding: 72px;
  text-align: center;
  position: relative; overflow: hidden;
  border: 1px solid rgba(139,92,246,.2);
}
.cta-card::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse at 20% 50%, rgba(59,130,246,.12) 0%, transparent 60%),
    radial-gradient(ellipse at 80% 50%, rgba(139,92,246,.12) 0%, transparent 60%);
  pointer-events: none;
}
.cta-title {
  font-size: 44px; font-weight: 900; color: #fff;
  letter-spacing: -2px; line-height: 1.1; margin-bottom: 16px;
  position: relative;
}
.cta-title span {
  background: linear-gradient(90deg, #60A5FA, #A78BFA, #F472B6);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.cta-sub {
  font-size: 16px; color: rgba(255,255,255,.65);
  margin-bottom: 40px; max-width: 500px; margin-left: auto; margin-right: auto;
  line-height: 1.6; position: relative;
}
.cta-btns {
  display: flex; align-items: center; justify-content: center;
  gap: 16px; flex-wrap: wrap; position: relative;
}
.btn-cta-primary {
  height: 56px; padding: 0 40px; border-radius: 16px;
  background: linear-gradient(135deg, var(--blue), var(--violet));
  border: none; color: #fff; font-size: 16px; font-weight: 800;
  font-family: inherit; cursor: pointer; text-decoration: none;
  display: flex; align-items: center; gap: 10px;
  box-shadow: 0 8px 28px rgba(59,130,246,.45);
  transition: all .25s;
}
.btn-cta-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 36px rgba(59,130,246,.55); }
.btn-cta-ghost {
  height: 56px; padding: 0 32px; border-radius: 16px;
  background: transparent; border: 1.5px solid rgba(255,255,255,.2);
  color: #fff; font-size: 16px; font-weight: 700;
  font-family: inherit; cursor: pointer; text-decoration: none;
  display: flex; align-items: center; gap: 10px;
  transition: all .25s;
}
.btn-cta-ghost:hover { border-color: rgba(255,255,255,.4); background: rgba(255,255,255,.07); }

/* ═══════════════════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════════════════ */
footer {
  background: var(--bg2);
  border-top: 1px solid var(--border);
  padding: 48px 0 28px;
}
.footer-inner {
  max-width: 1280px; margin: 0 auto; padding: 0 48px;
}
.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 40px; margin-bottom: 40px;
}
.footer-brand { }
.footer-brand-name {
  font-size: 22px; font-weight: 800; color: var(--text);
  letter-spacing: -.5px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px;
}
.footer-brand-name .fi {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--blue), var(--violet));
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
  font-size: 16px; color: #fff; flex-shrink: 0;
}
.footer-brand-desc { font-size: 13.5px; color: var(--muted); line-height: 1.6; margin-bottom: 20px; max-width: 260px; }
.footer-socials { display: flex; gap: 10px; }
.fsoc {
  width: 38px; height: 38px; border-radius: 10px;
  background: rgba(255,255,255,.06); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--muted); font-size: 16px;
  transition: all .2s; text-decoration: none;
}
.fsoc:hover { background: var(--blue); border-color: var(--blue); color: #fff; }
.footer-col h4 {
  font-size: 13px; font-weight: 800; color: var(--text);
  letter-spacing: .5px; text-transform: uppercase;
  margin-bottom: 16px;
}
.footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
.footer-col ul a {
  font-size: 13.5px; color: var(--muted);
  text-decoration: none; transition: color .2s;
}
.footer-col ul a:hover { color: var(--blue); }
.footer-bottom {
  border-top: 1px solid var(--border);
  padding-top: 24px;
  display: flex; align-items: center; justify-content: space-between;
  gap: 16px; flex-wrap: wrap;
}
.footer-copy { font-size: 13px; color: var(--light); }
.footer-tags { display: flex; gap: 8px; }
.ftag {
  font-size: 11px; font-weight: 700; padding: 4px 12px;
  border-radius: 6px; background: rgba(255,255,255,.05);
  border: 1px solid var(--border); color: var(--light);
}

/* ═══════════════════════════════════════════════════════
   MOBILE NAV
═══════════════════════════════════════════════════════ */
.mobile-menu {
  display: none;
  position: fixed; inset: 0; z-index: 999;
  background: rgba(5,13,26,.97); backdrop-filter: blur(20px);
  flex-direction: column; align-items: center; justify-content: center; gap: 24px;
  padding: 80px 32px;
}
.mobile-menu.open { display: flex; }
.mobile-menu a {
  font-size: 22px; font-weight: 700; color: var(--text);
  text-decoration: none; transition: color .2s;
}
.mobile-menu a:hover { color: var(--blue); }
.mobile-menu-btns { display: flex; flex-direction: column; gap: 12px; width: 100%; margin-top: 16px; }

/* ═══════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════ */
.toast-wrap {
  position: fixed; bottom: 28px; right: 28px; z-index: 9999;
  display: flex; flex-direction: column; gap: 10px;
}
.toast {
  background: var(--card2); border: 1px solid var(--border2);
  border-radius: 14px; padding: 14px 20px;
  display: flex; align-items: center; gap: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,.5);
  animation: toastIn .35s ease; min-width: 280px;
}
.toast.hide { animation: toastOut .35s ease forwards; }
@keyframes toastIn  { from { opacity:0; transform: translateY(12px); } to { opacity:1; transform: translateY(0); } }
@keyframes toastOut { to   { opacity:0; transform: translateY(12px); } }
.toast-icon {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px;
}
.toast-icon.success { background: rgba(16,185,129,.15); color: var(--green); }
.toast-icon.info    { background: rgba(59,130,246,.15); color: var(--blue); }
.toast-text h4 { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 2px; }
.toast-text p  { font-size: 12px; color: var(--muted); }

/* ═══════════════════════════════════════════════════════
   ANIMATIONS
═══════════════════════════════════════════════════════ */
.fade-up {
  opacity: 0; transform: translateY(30px);
  transition: opacity .6s ease, transform .6s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }
.fade-up-delay-1 { transition-delay: .1s; }
.fade-up-delay-2 { transition-delay: .2s; }
.fade-up-delay-3 { transition-delay: .3s; }

/* ═══════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .hero-stats { display: none; }
  .promo-inner { padding: 48px; flex-direction: column; }
  .promo-showcase { display: none; }
  .footer-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
  .navbar { padding: 0 24px; }
  .nav-links, .nav-actions { display: none; }
  .nav-toggle {
    display: flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,.08); border: none; color: var(--text);
    font-size: 18px; cursor: pointer;
  }
  .container { padding: 0 24px; }
  section { padding: 56px 0; }
  .hero-content { padding: 0 24px; }
  .hero-title { font-size: 32px; }
  .hero-btns { flex-direction: column; }
  .btn-hero-primary, .btn-hero-outline { width: 100%; justify-content: center; }
  .promo-inner { padding: 36px 28px; }
  .promo-title { font-size: 26px; }
  .promo-btns { flex-direction: column; }
  .cta-card { padding: 40px 28px; }
  .cta-title { font-size: 30px; }
  .cta-btns { flex-direction: column; }
  .btn-cta-primary, .btn-cta-ghost { width: 100%; justify-content: center; }
  .footer-grid { grid-template-columns: 1fr; gap: 28px; }
  .footer-bottom { flex-direction: column; text-align: center; }
  .sec-head { flex-direction: column; align-items: flex-start; gap: 12px; }
}

/* ═══════════════════════════════════════════════════════
   QUICK-VIEW MODAL
═══════════════════════════════════════════════════════ */
#quick-view-modal {
  position: fixed; inset: 0; z-index: 9000;
  display: flex; align-items: center; justify-content: center;
  pointer-events: none; opacity: 0;
  transition: opacity .25s ease;
}
#quick-view-modal.open {
  pointer-events: all; opacity: 1;
}

#qv-backdrop {
  position: absolute; inset: 0;
  background: rgba(5,13,26,.75);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

#qv-panel {
  position: relative; z-index: 1;
  background: linear-gradient(145deg, rgba(13,27,46,.98) 0%, rgba(17,29,48,.98) 100%);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 20px;
  box-shadow: 0 24px 80px rgba(0,0,0,.7), 0 0 0 1px rgba(59,130,246,.08);
  width: min(560px, 94vw);
  max-height: 85vh;
  overflow: hidden;
  transform: translateY(20px) scale(.96);
  transition: transform .3s cubic-bezier(.34,1.56,.64,1);
}
#quick-view-modal.open #qv-panel {
  transform: translateY(0) scale(1);
}

#qv-close {
  position: absolute; top: 14px; right: 14px; z-index: 2;
  width: 34px; height: 34px; border-radius: 50%;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.15);
  color: var(--muted); font-size: 15px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all .2s;
}
#qv-close:hover { background: rgba(255,255,255,.18); color: #fff; }

#qv-inner {
  display: flex; gap: 0;
}

/* ── Poster side ── */
#qv-poster-wrap {
  flex-shrink: 0;
  width: 145px; min-height: 215px;
  background: linear-gradient(135deg, var(--bg3), var(--card2));
  border-radius: 20px 0 0 20px;
  overflow: hidden; position: relative;
}
#qv-poster {
  width: 100%; height: 100%; object-fit: cover; display: block;
}
#qv-poster-ph {
  width: 100%; height: 100%; min-height: 215px;
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,.1); font-size: 44px;
}

/* ── Info side ── */
#qv-info {
  flex: 1; padding: 24px 22px 22px 20px;
  display: flex; flex-direction: column; gap: 12px;
  overflow-y: auto; max-height: 80vh;
}

#qv-title {
  font-size: 17px; font-weight: 800; line-height: 1.3;
  color: var(--text); letter-spacing: -.3px;
  padding-right: 28px; /* space for close btn */
}

#qv-badges {
  display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
}
#qv-rating-wrap {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 13px; font-weight: 800; color: var(--gold);
}
#qv-duration-wrap {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; color: var(--muted);
}
.qv-age-badge {
  font-size: 10px; font-weight: 800;
  padding: 3px 7px; border-radius: 5px;
}

#qv-genre-row {
  font-size: 12px; color: var(--muted); font-weight: 500;
  display: flex; align-items: center; gap: 6px;
}
#qv-genre-row i { color: var(--violet); }

#qv-desc {
  font-size: 13px; color: rgba(255,255,255,.6);
  line-height: 1.65; flex: 1;
}

#qv-cta {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  height: 42px; border-radius: 11px;
  background: linear-gradient(135deg, var(--blue), var(--violet));
  color: #fff; font-size: 13px; font-weight: 800;
  text-decoration: none; border: none; cursor: pointer;
  font-family: inherit;
  box-shadow: 0 6px 18px rgba(59,130,246,.4);
  transition: all .22s; margin-top: auto;
}
#qv-cta:hover {
  filter: brightness(1.1);
  transform: translateY(-1px);
  box-shadow: 0 10px 26px rgba(59,130,246,.55);
}

@media (max-width: 500px) {
  #qv-poster-wrap { width: 110px; }
  #qv-info { padding: 18px 16px 18px 14px; }
  #qv-title { font-size: 15px; }
}
</style>

</head>
<body>

<!-- ══════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════ -->
<nav class="navbar" id="navbar">
  <a href="guest.php" class="nav-brand">
    <div class="nav-brand-icon"><i class="fa-solid fa-clapperboard"></i></div>
    <div class="nav-brand-name">Movie<span>Flex</span></div>
  </a>

  <ul class="nav-links" id="nav-links">
    <li><a href="#sec-showing" class="active"><i class="fa-solid fa-circle-play"></i> Đang chiếu</a></li>
    <li><a href="#sec-coming"><i class="fa-solid fa-clock-rotate-left"></i> Sắp chiếu</a></li>
    <li><a href="#sec-cinemas"><i class="fa-solid fa-location-dot"></i> Rạp chiếu</a></li>
    <li><a href="#sec-deals"><i class="fa-solid fa-tag"></i> Ưu đãi</a></li>
  </ul>

  <!-- SEARCH BAR -->
  <div class="nav-search" id="nav-search">
    <div class="nav-search-wrap">
      <i class="fa-solid fa-magnifying-glass nav-search-icon"></i>
      <input
        type="text"
        id="guest-search"
        class="nav-search-input"
        placeholder="Tìm phim, thể loại..."
        autocomplete="off"
        maxlength="80"
      >
      <button class="nav-search-clear" id="search-clear" onclick="clearSearch()" style="display:none">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  </div>

  <div class="nav-actions">
    <a href="login.php" class="btn-nav-outline">
      <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
    </a>
    <a href="login.php" class="btn-nav-primary" onclick="showRegister(event)">
      <i class="fa-solid fa-user-plus"></i> Đăng ký miễn phí
    </a>
  </div>

  <button class="nav-toggle" id="nav-toggle" aria-label="Menu">
    <i class="fa-solid fa-bars"></i>
  </button>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobile-menu">
  <button id="menu-close" style="position:absolute;top:20px;right:20px;background:rgba(255,255,255,.08);border:none;color:#fff;width:44px;height:44px;border-radius:12px;font-size:20px;cursor:pointer;">
    <i class="fa-solid fa-xmark"></i>
  </button>
  <a href="#sec-showing" onclick="closeMobileMenu()">Đang chiếu</a>
  <a href="#sec-coming" onclick="closeMobileMenu()">Sắp chiếu</a>
  <a href="#sec-cinemas" onclick="closeMobileMenu()">Rạp chiếu</a>
  <a href="#sec-deals" onclick="closeMobileMenu()">Ưu đãi</a>
  <div class="mobile-menu-btns">
    <a href="login.php" style="display:flex;align-items:center;justify-content:center;height:52px;border:1.5px solid rgba(255,255,255,.2);border-radius:14px;color:#fff;text-decoration:none;font-size:15px;font-weight:700;gap:10px;">
      <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
    </a>
    <a href="login.php" onclick="showRegister(event)" style="display:flex;align-items:center;justify-content:center;height:52px;background:linear-gradient(135deg,var(--blue),var(--violet));border:none;border-radius:14px;color:#fff;text-decoration:none;font-size:15px;font-weight:700;gap:10px;box-shadow:0 8px 24px rgba(59,130,246,.4);">
      <i class="fa-solid fa-user-plus"></i> Đăng ký miễn phí
    </a>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════ -->
<section class="hero" id="hero" onmouseenter="pauseHeroAutoplay()" onmouseleave="resumeHeroAutoplay()">
  <div class="hero-overlay"></div>
  <div class="hero-overlay2"></div>
  <div class="orb orb1"></div>
  <div class="orb orb2"></div>

  <div class="hero-slides-wrap">
    <?php if (!empty($hero_movies)): ?>
      <?php foreach ($hero_movies as $index => $m): ?>
        <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>" data-slide-index="<?= $index ?>">
          <div class="hero-bg" style="background-image:url('<?= htmlspecialchars($m['backdrop_url'] ?: $m['poster_url'] ?: '') ?>')"></div>
          
          <div class="hero-content">
            <div class="hero-badge">
              <i class="fa-solid fa-ticket"></i>
              NỀN TẢNG ĐẶT VÉ SỐ 1 VIỆT NAM
            </div>
            
            <h1 class="hero-title">
              <span class="gradient-text"><?= htmlspecialchars($m['title']) ?></span>
            </h1>
            
            <div class="hero-meta">
              <span><i class="fa-regular fa-clock"></i> <?= $m['duration_min'] ?> phút</span>
              <span class="star"><i class="fa-solid fa-star"></i> <?= number_format($m['rating'],1) ?></span>
              <span><i class="fa-solid fa-shield-halved"></i> <?= htmlspecialchars($m['age_rating']) ?></span>
              <?php if (!empty($m['genre'])): ?>
                <span><i class="fa-solid fa-masks-theater"></i> <?= htmlspecialchars(explode(',', $m['genre'])[0]) ?></span>
              <?php endif; ?>
            </div>
            
            <p class="hero-desc"><?= htmlspecialchars($m['description'] ?? '') ?></p>
            
            <div class="hero-btns">
              <a href="login.php" class="btn-hero-primary">
                <i class="fa-solid fa-ticket"></i> Đặt vé ngay
              </a>
              <a href="#sec-showing" class="btn-hero-outline">
                <i class="fa-solid fa-film"></i> Xem tất cả phim
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="hero-slide active" data-slide-index="0">
        <div class="hero-bg" style="background:linear-gradient(135deg,#0F172A,#1E1045)"></div>
        <div class="hero-content">
          <div class="hero-badge">
            <i class="fa-solid fa-ticket"></i>
            NỀN TẢNG ĐẶT VÉ SỐ 1 VIỆT NAM
          </div>
          <h1 class="hero-title">Trải nghiệm điện ảnh<br><span class="gradient-text">đỉnh cao</span></h1>
          <p class="hero-desc">Hàng trăm bộ phim, hàng nghìn suất chiếu — đặt vé chỉ trong 60 giây.</p>
          <div class="hero-btns">
            <a href="#sec-showing" class="btn-hero-outline">
              <i class="fa-solid fa-film"></i> Xem tất cả phim
            </a>
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

  <!-- Stats -->
  <div class="hero-stats">
    <div class="hstat">
      <div class="hstat-val"><?= count($movies_showing) ?><span>+</span></div>
      <div class="hstat-lbl">Phim đang chiếu</div>
    </div>
    <div class="hstat">
      <div class="hstat-val"><?= count($cinemas) ?><span>+</span></div>
      <div class="hstat-lbl">Rạp chiếu</div>
    </div>
    <div class="hstat">
      <div class="hstat-val">50<span>k</span></div>
      <div class="hstat-lbl">Thành viên</div>
    </div>
  </div>

  <div class="scroll-hint">
    <span>CUỘN XUỐNG</span>
    <i class="fa-solid fa-angles-down"></i>
  </div>
</section>

<!-- ══════════════════════════════════════════════════
     PHIM ĐANG CHIẾU
══════════════════════════════════════════════════ -->
<section id="sec-showing">
  <div class="container">
    <div class="sec-head fade-up">
      <div>
        <div class="sec-eyebrow"><i class="fa-solid fa-circle-play"></i> Phim mới nhất</div>
        <h2 class="sec-title">🎬 Đang Chiếu</h2>
      </div>
      <a href="login.php" class="sec-link">
        Xem tất cả <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar fade-up" id="filter-bar">
      <button class="fpill active" data-f="all">Tất cả</button>
      <button class="fpill" data-f="2D">2D Digital</button>
      <button class="fpill" data-f="3D">3D Digital</button>
      <button class="fpill" data-f="IMAX">IMAX</button>
      <button class="fpill" data-f="PREMIUM">Premium</button>
      <button class="fpill" data-f="4DX">4DX</button>
    </div>

    <div class="movie-grid" id="movie-grid">
      <?php foreach($movies_showing as $m): ?>
      <?php
        $formats = array_filter(array_map('trim', explode(',', $m['formats'] ?? '')));
        $topFormat = $formats[0] ?? '';
      ?>
      <a class="movie-card fade-up"
         href="login.php"
         data-formats="<?= htmlspecialchars($m['formats'] ?? '') ?>"
         data-qv-title="<?= htmlspecialchars($m['title']) ?>"
         data-qv-poster="<?= htmlspecialchars($m['poster_url'] ?? '') ?>"
         data-qv-rating="<?= number_format($m['rating'] ?? 0, 1) ?>"
         data-qv-duration="<?= (int)($m['duration'] ?? 0) ?>"
         data-qv-age="<?= htmlspecialchars($m['age_rating'] ?? '') ?>"
         data-qv-genre="<?= htmlspecialchars($m['genre'] ?? 'Chưa phân loại') ?>"
         data-qv-desc="<?= htmlspecialchars(mb_substr($m['description'] ?? '', 0, 220)) ?>"
         title="<?= htmlspecialchars($m['title']) ?>">
        <div class="mc-poster-wrap">
          <?php if ($m['poster_url']): ?>
            <img class="mc-poster" src="<?= htmlspecialchars($m['poster_url']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" loading="lazy">
          <?php else: ?>
            <div class="mc-poster-ph"><i class="fa-solid fa-film"></i></div>
          <?php endif; ?>
          <?php if ($topFormat): ?>
            <div class="mc-format-badge"><?= htmlspecialchars($topFormat) ?></div>
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
          <div class="mc-genre"><?= htmlspecialchars($m['genre'] ?? 'Chưa phân loại') ?></div>
          <div class="mc-footer">
            <span class="mc-rating">
              <i class="fa-solid fa-star"></i>
              <?= number_format($m['rating'], 1) ?>
            </span>
            <span class="mc-age <?= strtolower($m['age_rating']) ?>">
              <?= htmlspecialchars($m['age_rating']) ?>
            </span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>

      <?php if (empty($movies_showing)): ?>
      <div id="no-results" style="display:block; grid-column:1/-1">
        <i class="fa-solid fa-film"></i>
        <h3>Chưa có phim đang chiếu</h3>
        <p>Hệ thống đang cập nhật lịch chiếu mới nhất.</p>
      </div>
      <?php endif; ?>
    </div>
    <div id="guest-no-results">
      <i class="fa-solid fa-magnifying-glass"></i>
      <h3>Không tìm thấy phim</h3>
      <p>Không có phim nào phù hợp với bộ lọc hiện tại.</p>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════════
     PROMO — CTA đăng ký
══════════════════════════════════════════════════ -->
<section style="padding:0 0 80px; background:var(--bg)">
  <div class="container">
    <div class="promo-inner fade-up">
      <div class="promo-glow"></div>
      <div class="promo-text">
        <div class="promo-eyebrow"><i class="fa-solid fa-crown"></i> THÀNH VIÊN ĐẶC QUYỀN</div>
        <h2 class="promo-title">Đăng ký ngay,<br>nhận <span>ưu đãi độc quyền</span></h2>
        <p class="promo-desc">Tham gia cộng đồng MovieFlex để tận hưởng những đặc quyền thành viên hàng đầu, tích điểm đổi quà và không bao giờ bỏ lỡ suất chiếu yêu thích.</p>
        <div class="promo-benefits">
          <div class="pb-item">
            <div class="pb-icon blue"><i class="fa-solid fa-bolt"></i></div>
            Đặt vé nhanh trong vòng 60 giây, không cần xếp hàng
          </div>
          <div class="pb-item">
            <div class="pb-icon violet"><i class="fa-solid fa-gem"></i></div>
            Tích điểm thưởng mỗi lần mua vé, đổi quà hấp dẫn
          </div>
          <div class="pb-item">
            <div class="pb-icon pink"><i class="fa-solid fa-bell"></i></div>
            Nhận thông báo sớm về phim mới và suất chiếu đặc biệt
          </div>
          <div class="pb-item">
            <div class="pb-icon gold"><i class="fa-solid fa-tag"></i></div>
            Voucher giảm giá độc quyền cho thành viên mỗi tuần
          </div>
        </div>
        <div class="promo-btns">
          <a href="login.php" onclick="showRegister(event)" class="btn-promo-primary">
            <i class="fa-solid fa-user-plus"></i> Đăng ký miễn phí
          </a>
          <a href="login.php" class="btn-promo-outline">
            <i class="fa-solid fa-right-to-bracket"></i> Đã có tài khoản?
          </a>
        </div>
      </div>

      <!-- Movie showcase -->
      <div class="promo-showcase">
        <?php
          $showcase = array_slice($movies_showing, 0, 3);
          foreach($showcase as $sc):
        ?>
        <div class="showcase-card">
          <?php if($sc['poster_url']): ?>
            <img src="<?= htmlspecialchars($sc['poster_url']) ?>" alt="<?= htmlspecialchars($sc['title']) ?>">
          <?php else: ?>
            <div style="width:90px;height:130px;background:var(--bg3);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.1);font-size:24px"><i class="fa-solid fa-film"></i></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════════
     SẮP CHIẾU
══════════════════════════════════════════════════ -->
<?php if (!empty($movies_coming)): ?>
<section id="sec-coming">
  <div class="container">
    <div class="sec-head fade-up">
      <div>
        <div class="sec-eyebrow"><i class="fa-solid fa-hourglass-half"></i> Chuẩn bị ra mắt</div>
        <h2 class="sec-title">🔜 Sắp Chiếu</h2>
      </div>
      <a href="login.php" class="sec-link">
        Xem tất cả <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <div class="coming-grid">
      <?php foreach($movies_coming as $m): ?>
      <a class="coming-card fade-up" href="login.php" title="<?= htmlspecialchars($m['title']) ?>">
        <?php if($m['poster_url']): ?>
          <img class="cc-poster" src="<?= htmlspecialchars($m['poster_url']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" loading="lazy">
        <?php else: ?>
          <div class="cc-poster-ph"><i class="fa-solid fa-film"></i></div>
        <?php endif; ?>
        <div class="cc-info">
          <div class="cc-title"><?= htmlspecialchars($m['title']) ?></div>
          <div class="cc-genre"><?= htmlspecialchars($m['genre'] ?? '') ?></div>
          <?php if ($m['release_date']): ?>
          <div class="cc-release">
            <i class="fa-regular fa-calendar"></i>
            <?= date('d/m/Y', strtotime($m['release_date'])) ?>
          </div>
          <?php endif; ?>
          <div class="cc-remind" onclick="setReminder(event, '<?= htmlspecialchars($m['title']) ?>')">
            <i class="fa-regular fa-bell"></i> Nhắc tôi khi ra mắt
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     RẠP CHIẾU PHIM
══════════════════════════════════════════════════ -->
<?php if (!empty($cinemas)): ?>
<section id="sec-cinemas">
  <div class="container">
    <div class="sec-head fade-up">
      <div>
        <div class="sec-eyebrow"><i class="fa-solid fa-location-dot"></i> Hệ thống rạp</div>
        <h2 class="sec-title">🎭 Rạp Chiếu Phim</h2>
      </div>
      <a href="login.php" class="sec-link">
        Xem lịch chiếu <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    <div class="cinema-grid">
      <?php
      // Brand identity map — CSS text logos, không cần ảnh ngoài
      function getCinemaBrand(string $name, ?string $logo_url): array {
        // Nếu có logo_url từ DB → hiển thị ảnh
        if (!empty($logo_url)) return [
          'type' => 'img', 'src' => $logo_url,
          'bg'   => 'linear-gradient(135deg,#0D1B2E,#111D30)',
        ];

        $n = strtolower($name);

        // CGV
        if (str_contains($n, 'cgv')) return [
          'type'    => 'text',
          'bg'      => 'linear-gradient(135deg, #C00000 0%, #8B0000 100%)',
          'label'   => 'CGV',
          'sub'     => 'CINEMAS',
          'dot'     => '#FF6B6B',
          'lsize'   => '34px',
        ];
        // Lotte Cinema
        if (str_contains($n, 'lotte')) return [
          'type'    => 'text',
          'bg'      => 'linear-gradient(135deg, #E50012 0%, #B00010 100%)',
          'label'   => 'LOTTE',
          'sub'     => 'CINEMA',
          'dot'     => '#FF8080',
          'lsize'   => '26px',
        ];
        // Galaxy Cinema
        if (str_contains($n, 'galaxy')) return [
          'type'    => 'text',
          'bg'      => 'linear-gradient(135deg, #1A237E 0%, #283593 100%)',
          'label'   => 'GALAXY',
          'sub'     => 'CINEMA',
          'dot'     => '#7986CB',
          'lsize'   => '22px',
        ];
        // BHD Star
        if (str_contains($n, 'bhd')) return [
          'type'    => 'text',
          'bg'      => 'linear-gradient(135deg, #0F2942 0%, #1B3A5C 100%)',
          'label'   => 'BHD',
          'sub'     => 'STAR ★',
          'dot'     => '#F59E0B',
          'lsize'   => '32px',
        ];
        // Beta Cinemas
        if (str_contains($n, 'beta')) return [
          'type'    => 'text',
          'bg'      => 'linear-gradient(135deg, #4C1D95 0%, #6D28D9 100%)',
          'label'   => 'BETA',
          'sub'     => 'CINEMAS',
          'dot'     => '#A78BFA',
          'lsize'   => '28px',
        ];
        // CineStar
        if (str_contains($n, 'cinestar')) return [
          'type'    => 'text',
          'bg'      => 'linear-gradient(135deg, #1D4ED8 0%, #3B82F6 100%)',
          'label'   => 'CINE',
          'sub'     => 'STAR ⭐',
          'dot'     => '#93C5FD',
          'lsize'   => '24px',
        ];

        // Generic fallback — gradient xanh + icon
        return [
          'type'  => 'icon',
          'bg'    => 'linear-gradient(135deg, #0D1B2E 0%, #1E3A5F 100%)',
          'icon'  => 'fa-solid fa-camera-movie',
          'color' => '#60A5FA',
        ];
      }
      ?>
      <?php foreach($cinemas as $c): ?>
      <?php $brand = getCinemaBrand($c['name'], $c['logo_url'] ?? null); ?>
      <a class="cinema-card fade-up" href="login.php" title="<?= htmlspecialchars($c['name']) ?>">

        <div class="cin-logo-wrap" style="background:<?= $brand['bg'] ?>">
          <?php if ($brand['type'] === 'img'): ?>
            <!-- logo từ DB -->
            <img src="<?= htmlspecialchars($brand['src']) ?>"
                 alt="<?= htmlspecialchars($c['name']) ?>"
                 class="cin-logo-img"
                 onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-camera-movie\' style=\'font-size:28px;color:rgba(255,255,255,.7)\'></i>'">

          <?php elseif ($brand['type'] === 'text'): ?>
            <!-- CSS text logo -->
            <div class="cin-brand-text">
              <div class="cin-brand-dot" style="background:<?= $brand['dot'] ?>"></div>
              <div class="cin-brand-main" style="font-size:<?= $brand['lsize'] ?>"><?= $brand['label'] ?></div>
              <div class="cin-brand-sub"><?= $brand['sub'] ?></div>
            </div>

          <?php else: ?>
            <!-- Icon fallback -->
            <i class="<?= $brand['icon'] ?>" style="font-size:28px;color:<?= $brand['color'] ?>"></i>
          <?php endif; ?>
        </div>

        <div class="cin-name"><?= htmlspecialchars($c['name']) ?></div>
        <div class="cin-addr"><?= htmlspecialchars($c['address'] . (isset($c['city']) ? ', '.$c['city'] : '')) ?></div>
        <div class="cin-tags">
          <span class="cin-tag">2D</span>
          <span class="cin-tag">3D</span>
          <span class="cin-tag">IMAX</span>
          <?php if (!empty($c['phone'])): ?>
          <span class="cin-tag"><i class="fa-solid fa-phone" style="font-size:10px"></i> <?= htmlspecialchars($c['phone']) ?></span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     ƯU ĐÃI / DEALS
══════════════════════════════════════════════════ -->
<section id="sec-deals">
  <div class="container">
    <div class="sec-head fade-up">
      <div>
        <div class="sec-eyebrow"><i class="fa-solid fa-gift"></i> Khuyến mãi</div>
        <h2 class="sec-title">🎁 Ưu Đãi Hôm Nay</h2>
      </div>
    </div>

    <div class="deals-grid">
      <?php if (!empty($promos)): foreach($promos as $v): ?>
      <div class="deal-card fade-up">
        <div class="deal-icon">🎟️</div>
        <div class="deal-info">
          <div class="deal-title"><?= htmlspecialchars($v['code'] ?? 'ƯU ĐÃI ĐẶC BIỆT') ?></div>
          <div class="deal-desc"><?= htmlspecialchars($v['description'] ?? '') ?></div>
        </div>
        <div class="deal-badge">Còn hiệu lực</div>
      </div>
      <?php endforeach; else: ?>
      <!-- Static deals -->
      <div class="deal-card fade-up">
        <div class="deal-icon">🌙</div>
        <div class="deal-info">
          <div class="deal-title">Thứ Hai vui vẻ — Đồng giá 79k</div>
          <div class="deal-desc">Tất cả suất chiếu 2D vào mỗi thứ Hai hàng tuần chỉ 79.000đ/vé cho mọi thành viên.</div>
        </div>
        <div class="deal-badge">Còn hiệu lực</div>
      </div>
      <div class="deal-card fade-up fade-up-delay-1">
        <div class="deal-icon">💳</div>
        <div class="deal-info">
          <div class="deal-title">IMAX Thứ Tư — Giảm 20%</div>
          <div class="deal-desc">Thành viên đăng ký được giảm ngay 20% cho toàn bộ suất chiếu IMAX vào thứ Tư.</div>
        </div>
        <div class="deal-badge">Còn hiệu lực</div>
      </div>
      <div class="deal-card fade-up fade-up-delay-2">
        <div class="deal-icon">🍿</div>
        <div class="deal-info">
          <div class="deal-title">Combo Bắp + Nước — Giảm 30%</div>
          <div class="deal-desc">Đặt vé kèm combo bắp nước trên app, tiết kiệm tới 30% so với mua tại quầy.</div>
        </div>
        <div class="deal-badge">Còn hiệu lực</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════════
     FEATURES
══════════════════════════════════════════════════ -->
<section id="sec-features">
  <div class="container">
    <div class="sec-head fade-up" style="justify-content:center;text-align:center;flex-direction:column;align-items:center">
      <div class="sec-eyebrow">Tại sao chọn chúng tôi</div>
      <h2 class="sec-title">Trải nghiệm đặt vé hiện đại</h2>
    </div>
    <div class="features-grid">
      <div class="feat-card fade-up">
        <div class="feat-icon blue"><i class="fa-solid fa-bolt"></i></div>
        <div class="feat-title">Đặt vé siêu tốc</div>
        <div class="feat-desc">Chỉ 3 bước đơn giản: chọn phim → chọn ghế → thanh toán. Hoàn tất trong vòng 60 giây.</div>
      </div>
      <div class="feat-card fade-up fade-up-delay-1">
        <div class="feat-icon violet"><i class="fa-solid fa-couch"></i></div>
        <div class="feat-title">Chọn ghế trực quan</div>
        <div class="feat-desc">Sơ đồ ghế ngồi 3D trực quan theo thời gian thực, xem ngay ghế nào còn trống.</div>
      </div>
      <div class="feat-card fade-up fade-up-delay-2">
        <div class="feat-icon gold"><i class="fa-solid fa-gem"></i></div>
        <div class="feat-title">Tích điểm thưởng</div>
        <div class="feat-desc">Mỗi đồng bạn chi tiêu được quy đổi thành điểm thưởng, đổi lấy vé xem phim miễn phí.</div>
      </div>
      <div class="feat-card fade-up">
        <div class="feat-icon green"><i class="fa-solid fa-qrcode"></i></div>
        <div class="feat-title">Vé điện tử QR</div>
        <div class="feat-desc">Nhận vé điện tử QR ngay sau khi đặt thành công. Quét tại cửa, không cần in giấy.</div>
      </div>
      <div class="feat-card fade-up fade-up-delay-1">
        <div class="feat-icon pink"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="feat-title">Thanh toán an toàn</div>
        <div class="feat-desc">Hỗ trợ nhiều cổng thanh toán: Ví điện tử, QR Pay, thẻ ngân hàng — bảo mật tuyệt đối.</div>
      </div>
      <div class="feat-card fade-up fade-up-delay-2">
        <div class="feat-icon red"><i class="fa-solid fa-headset"></i></div>
        <div class="feat-title">Hỗ trợ 24/7</div>
        <div class="feat-desc">Đội ngũ chăm sóc khách hàng luôn sẵn sàng hỗ trợ bạn mọi lúc, mọi nơi.</div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════════
     FINAL CTA
══════════════════════════════════════════════════ -->
<section id="sec-cta">
  <div class="container">
    <div class="cta-card fade-up">
      <div class="cta-title">Sẵn sàng<br><span>trải nghiệm chưa?</span></div>
      <p class="cta-sub">Tạo tài khoản miễn phí ngay hôm nay và bắt đầu hành trình điện ảnh của bạn với MovieFlex.</p>
      <div class="cta-btns">
        <a href="login.php" onclick="showRegister(event)" class="btn-cta-primary">
          <i class="fa-solid fa-user-plus"></i> Tạo tài khoản miễn phí
        </a>
        <a href="#sec-showing" class="btn-cta-ghost">
          <i class="fa-solid fa-film"></i> Xem phim trước
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════ -->
<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-brand-name">
          <div class="fi"><i class="fa-solid fa-clapperboard"></i></div>
          MovieFlex
        </div>
        <p class="footer-brand-desc">
          Nền tảng đặt vé xem phim trực tuyến hàng đầu Việt Nam. Hàng trăm bộ phim, hàng nghìn suất chiếu mỗi ngày.
        </p>
        <div class="footer-socials">
          <a href="#" class="fsoc"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" class="fsoc"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="fsoc"><i class="fa-brands fa-tiktok"></i></a>
          <a href="#" class="fsoc"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Dịch vụ</h4>
        <ul>
          <li><a href="#sec-showing">Phim đang chiếu</a></li>
          <li><a href="#sec-coming">Phim sắp chiếu</a></li>
          <li><a href="#sec-cinemas">Rạp chiếu phim</a></li>
          <li><a href="#sec-deals">Ưu đãi & Voucher</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Tài khoản</h4>
        <ul>
          <li><a href="login.php">Đăng nhập</a></li>
          <li><a href="login.php" onclick="showRegister(event)">Đăng ký</a></li>
          <li><a href="login.php">Vé của tôi</a></li>
          <li><a href="login.php">Tích điểm thưởng</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Hỗ trợ</h4>
        <ul>
          <li><a href="login.php">Trung tâm trợ giúp</a></li>
          <li><a href="#">Chính sách hoàn vé</a></li>
          <li><a href="#">Điều khoản dịch vụ</a></li>
          <li><a href="#">Liên hệ</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="footer-copy">© 2026 MovieFlex. Tất cả quyền được bảo lưu.</div>
      <div class="footer-tags">
        <span class="ftag">🔒 Bảo mật SSL</span>
        <span class="ftag">✅ Vé chính hãng</span>
        <span class="ftag">⚡ 24/7</span>
      </div>
    </div>
  </div>
</footer>

<!-- ══ QUICK-VIEW MODAL ══ -->
<div id="quick-view-modal" role="dialog" aria-modal="true" aria-labelledby="qv-title">
  <div id="qv-backdrop"></div>
  <div id="qv-panel">
    <!-- Close -->
    <button id="qv-close" aria-label="Đóng"><i class="fa-solid fa-xmark"></i></button>

    <div id="qv-inner">
      <!-- Poster -->
      <div id="qv-poster-wrap">
        <img id="qv-poster" src="" alt="Poster" style="display:none">
        <div id="qv-poster-ph"><i class="fa-solid fa-film"></i></div>
      </div>

      <!-- Info -->
      <div id="qv-info">
        <h3 id="qv-title"></h3>

        <div id="qv-badges">
          <span id="qv-rating-wrap"><i class="fa-solid fa-star"></i> <span id="qv-rating"></span></span>
          <span id="qv-age" class="qv-age-badge"></span>
          <span id="qv-duration-wrap"><i class="fa-regular fa-clock"></i> <span id="qv-duration"></span></span>
        </div>

        <div id="qv-genre-row"><i class="fa-solid fa-masks-theater"></i> <span id="qv-genre"></span></div>

        <p id="qv-desc"></p>

        <a href="login.php" id="qv-cta">
          <i class="fa-solid fa-ticket"></i> Đăng nhập để đặt vé
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-wrap" id="toast-wrap"></div>

<script>
// ── Navbar scroll effect ──
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 60);
});

// ── Active nav link on scroll ──
const navSections = [
  { id: 'sec-showing', link: 0 },
  { id: 'sec-coming',  link: 1 },
  { id: 'sec-cinemas', link: 2 },
  { id: 'sec-deals',   link: 3 },
];
const navLinks = document.querySelectorAll('.nav-links a');
window.addEventListener('scroll', () => {
  let current = '';
  navSections.forEach(s => {
    const el = document.getElementById(s.id);
    if (el && window.scrollY >= el.offsetTop - 120) current = s.id;
  });
  navLinks.forEach(a => {
    a.classList.toggle('active', a.getAttribute('href') === '#'+current);
  });
});

// ── Mobile menu ──
document.getElementById('nav-toggle').addEventListener('click', () => {
  document.getElementById('mobile-menu').classList.add('open');
  document.body.style.overflow = 'hidden';
});
document.getElementById('menu-close').addEventListener('click', closeMobileMenu);
function closeMobileMenu() {
  document.getElementById('mobile-menu').classList.remove('open');
  document.body.style.overflow = '';
}

// ── Smooth anchor scroll ──
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const top = target.getBoundingClientRect().top + window.scrollY - 80;
      window.scrollTo({ top, behavior: 'smooth' });
      closeMobileMenu();
    }
  });
});

// ── Filter pills ──
const filterBtns = document.querySelectorAll('.fpill');
filterBtns.forEach(btn => {
  btn.addEventListener('click', function() {
    filterBtns.forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    applyFilter();
  });
});

// ── Search & Filter Logic ──
function applyFilter() {
  const f = document.querySelector('.fpill.active')?.dataset.f || 'all';
  const q = document.getElementById('guest-search')?.value.trim().toLowerCase() || '';

  // 1. Filter Now Showing Grid
  const cards = document.querySelectorAll('#movie-grid .movie-card');
  let showingVisible = 0;
  cards.forEach(card => {
    const formats = (card.dataset.formats || '').toUpperCase().split(',').map(x => x.trim());
    const formatMatch = f === 'all' || formats.includes(f.toUpperCase());
    
    const title = card.querySelector('.mc-title')?.textContent.toLowerCase() || '';
    const genre = card.querySelector('.mc-genre')?.textContent.toLowerCase() || '';
    const searchMatch = !q || title.includes(q) || genre.includes(q);

    const show = formatMatch && searchMatch;
    card.style.display = show ? '' : 'none';
    if (show) showingVisible++;
  });

  // Toggle guest-no-results empty state
  const noResults = document.getElementById('guest-no-results');
  if (noResults) {
    noResults.style.display = showingVisible === 0 ? 'block' : 'none';
  }

  // 2. Filter Coming Soon Grid
  const comingGrid = document.querySelector('.coming-grid');
  if (comingGrid) {
    const comingCards = comingGrid.querySelectorAll('.coming-card');
    comingCards.forEach(card => {
      const title = card.querySelector('.cc-title')?.textContent.toLowerCase() || '';
      const genre = card.querySelector('.cc-genre')?.textContent.toLowerCase() || '';
      const searchMatch = !q || title.includes(q) || genre.includes(q);

      card.style.display = searchMatch ? '' : 'none';
    });
  }

  // Toggle clear search button
  const clearBtn = document.getElementById('search-clear');
  if (clearBtn) {
    clearBtn.style.display = q ? 'flex' : 'none';
  }
}

// ── Search Input Handlers ──
const guestSearch = document.getElementById('guest-search');
if (guestSearch) {
  guestSearch.addEventListener('input', applyFilter);
}

function clearSearch() {
  const searchInput = document.getElementById('guest-search');
  if (searchInput) {
    searchInput.value = '';
    searchInput.focus();
  }
  applyFilter();
}

// ── Show register flag (pass to login.php) ──
function showRegister(e) {
  e.preventDefault();
  sessionStorage.setItem('mf_show_register', '1');
  window.location.href = 'login.php';
}

// ── Remind me button ──
function setReminder(e, title) {
  e.preventDefault(); e.stopPropagation();
  showToast('info', '🔔 Nhắc nhở đã đặt', `Bạn sẽ được thông báo khi "${title}" ra mắt.`);
}

// ── Toast ──
function showToast(type, title, desc) {
  const wrap = document.getElementById('toast-wrap');
  const t = document.createElement('div');
  t.className = 'toast';
  const icons = { success: 'fa-circle-check', info: 'fa-circle-info' };
  t.innerHTML = `
    <div class="toast-icon ${type}"><i class="fa-solid ${icons[type] || 'fa-circle-info'}"></i></div>
    <div class="toast-text"><h4>${title}</h4><p>${desc}</p></div>
  `;
  wrap.appendChild(t);
  setTimeout(() => {
    t.classList.add('hide');
    setTimeout(() => t.remove(), 350);
  }, 3500);
}

// ── Fade-up Intersection Observer ──
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

// ── Hero Carousel Slider Logic ──
let currentHeroIndex = 0;
const heroSlides = document.querySelectorAll('#hero .hero-slide');
const heroDots = document.querySelectorAll('#hero .hero-dot');
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

// Observe rest
document.querySelectorAll('section .fade-up').forEach(el => observer.observe(el));

// ── Quick-View Modal ──
const qvModal     = document.getElementById('quick-view-modal');
const qvBackdrop  = document.getElementById('qv-backdrop');
const qvClose     = document.getElementById('qv-close');

function openQuickView(card) {
  const d = card.dataset;
  document.getElementById('qv-title').textContent   = d.qvTitle  || '';
  document.getElementById('qv-rating').textContent  = d.qvRating || '—';
  document.getElementById('qv-genre').textContent   = d.qvGenre  || '—';
  document.getElementById('qv-desc').textContent    = d.qvDesc   || 'Chưa có mô tả.';

  // Duration
  const dur = parseInt(d.qvDuration) || 0;
  document.getElementById('qv-duration').textContent = dur ? dur + ' phút' : '—';

  // Age badge colour
  const ageEl  = document.getElementById('qv-age');
  ageEl.textContent = d.qvAge || '—';
  ageEl.className   = 'qv-age-badge mc-age ' + (d.qvAge || '').toLowerCase();

  // Poster
  const posterEl = document.getElementById('qv-poster');
  if (d.qvPoster) {
    posterEl.src   = d.qvPoster;
    posterEl.style.display = 'block';
    document.getElementById('qv-poster-ph').style.display = 'none';
  } else {
    posterEl.style.display = 'none';
    document.getElementById('qv-poster-ph').style.display = 'flex';
  }

  qvModal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeQuickView() {
  qvModal.classList.remove('open');
  document.body.style.overflow = '';
}

// Bind close events
if (qvClose)    qvClose.addEventListener('click', closeQuickView);
if (qvBackdrop) qvBackdrop.addEventListener('click', closeQuickView);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeQuickView(); });

// Delegate click events on movie cards overlay buttons to ensure rock-solid reliability
document.addEventListener('click', e => {
  const detailBtn = e.target.closest('.mc-detail-btn');
  if (detailBtn) {
    e.preventDefault();
    e.stopPropagation();
    const card = detailBtn.closest('.movie-card');
    if (card) {
      openQuickView(card);
    }
    return;
  }

  const bookBtn = e.target.closest('.mc-book-btn');
  if (bookBtn) {
    e.preventDefault();
    e.stopPropagation();
    window.location.href = 'login.php';
    return;
  }
});
</script>
</body>
</html>
