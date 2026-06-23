<?php
session_start();
require_once __DIR__ . '/../../be/config/db.php';
$active_page = 'movies';
$id = (int)($_GET['id'] ?? 1);

// Tự động cập nhật phim coming_soon → now_showing nếu đã đến ngày khởi chiếu
$pdo->exec("UPDATE movies SET status='now_showing' WHERE status='coming_soon' AND release_date IS NOT NULL AND release_date <= CURDATE()");

$movie = $pdo->prepare("SELECT * FROM movies WHERE id=? LIMIT 1");
$movie->execute([$id]);
$movie = $movie->fetch();
if (!$movie) { header('Location: home.php'); exit; }

$showtimes = $pdo->prepare("
  SELECT s.*, c.name as cinema_name, c.address, c.city
  FROM showtimes s JOIN cinemas c ON s.cinema_id=c.id
  WHERE s.movie_id=? AND s.is_cancelled = 0
    AND (
      s.show_date > CURDATE()
      OR (s.show_date = CURDATE() AND ADDTIME(s.start_time, '00:20:00') >= CURTIME())
    )
  ORDER BY s.show_date ASC, s.start_time ASC
");
$showtimes->execute([$id]);
$showtimes = $showtimes->fetchAll();

// Group by date → cinema
$grouped = [];
foreach ($showtimes as $s) {
    $grouped[$s['show_date']][$s['cinema_name']][] = $s;
}
$dates = array_keys($grouped);
$today = date('Y-m-d');

// Fetch movie reviews
$reviews = $pdo->prepare("
  SELECT r.*, u.full_name 
  FROM movie_reviews r
  JOIN users u ON r.user_id = u.id
  WHERE r.movie_id = ?
  ORDER BY r.created_at DESC
");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($movie['title']) ?> - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* mfConfirm dialog */
#mf-dialog-overlay{position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px);z-index:999999;display:none;align-items:center;justify-content:center;padding:16px}
#mf-dialog-overlay.active{display:flex}
.mf-dialog{background:#fff;border-radius:20px;box-shadow:0 24px 48px -12px rgba(0,0,0,.25);width:100%;max-width:400px;overflow:hidden;animation:mfSlide .25s cubic-bezier(.34,1.56,.64,1)}
@keyframes mfSlide{from{transform:scale(.9) translateY(20px);opacity:0}to{transform:scale(1) translateY(0);opacity:1}}
.mf-dialog-icon-wrap{display:flex;justify-content:center;padding:28px 24px 0}
.mf-dialog-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px}
.mf-dialog-icon.info{background:#EEF2FF;color:#4F46E5}
.mf-dialog-icon.danger{background:#FEE2E2;color:#DC2626}
.mf-dialog-icon.warning{background:#FEF3C7;color:#D97706}
.mf-dialog-body{padding:20px 28px 24px;text-align:center}
.mf-dialog-title{font-size:17px;font-weight:800;color:#0F172A;margin-bottom:8px}
.mf-dialog-desc{font-size:13.5px;color:#64748B;line-height:1.6}
.mf-dialog-footer{padding:0 24px 24px;display:flex;gap:10px}
.mf-btn-cancel{flex:1;height:42px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;font-family:inherit}
.mf-btn-confirm{flex:1;height:42px;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;font-family:inherit;color:#fff;display:flex;align-items:center;justify-content:center;gap:6px}
.mf-btn-confirm.info{background:#4F46E5}
.mf-btn-confirm.danger{background:#EF4444}
.mf-btn-confirm.warning{background:#F59E0B}

*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--blue:#2563EB;--blue-h:#1D4ED8;--sb:#0F172A;--sbw:240px;--bg:#F1F5F9;--card:#fff;--text:#0F172A;--muted:#64748B;--light:#94A3B8;--border:#E2E8F0;--r:14px;--sh:0 2px 16px rgba(15,23,42,.08)}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}

/* MAIN */
.main{margin-left:var(--sbw);flex:1}
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 26px;height:60px;display:flex;align-items:center;gap:14px;position:sticky;top:0;z-index:50}
.back-btn{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:14px;font-weight:600;text-decoration:none;transition:color .2s}
.back-btn:hover{color:var(--text)}
.tb-title{font-size:15px;font-weight:700;margin-left:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tb-right{margin-left:auto;display:flex;align-items:center;gap:10px}
.tb-av{width:34px;height:34px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;cursor:pointer}
/* HERO */
.hero{position:relative;height:380px;background:#0f172a;overflow:hidden}
.hero-bg{width:100%;height:100%;object-fit:cover;opacity:.45;position:absolute;inset:0}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(15,23,42,.95) 0%,rgba(15,23,42,.5) 55%,transparent 100%)}
.hero-content{position:absolute;inset:0;display:flex;align-items:flex-end;padding:36px 40px;gap:28px}
.hero-poster{width:140px;height:200px;border-radius:12px;object-fit:cover;box-shadow:0 8px 32px rgba(0,0,0,.6);flex-shrink:0}
.hero-poster-ph{width:140px;height:200px;border-radius:12px;background:linear-gradient(135deg,#334155,#1e293b);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.2);font-size:40px}
.hero-info{color:#fff;flex:1}
.hero-badges{display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap}
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.3px}
.badge-red{background:rgba(239,68,68,.85);color:#fff}
.badge-blue{background:rgba(37,99,235,.8);color:#fff}
.badge-gray{background:rgba(255,255,255,.15);color:rgba(255,255,255,.85);backdrop-filter:blur(4px)}
.hero-title{font-size:28px;font-weight:800;line-height:1.2;margin-bottom:10px}
.hero-meta{display:flex;align-items:center;gap:18px;font-size:13px;color:rgba(255,255,255,.7);margin-bottom:12px;flex-wrap:wrap}
.hero-meta span{display:flex;align-items:center;gap:5px}
.hero-rating{color:#F59E0B;font-weight:700}
.hero-btns{display:flex;gap:10px}
.btn{display:inline-flex;align-items:center;gap:7px;padding:0 20px;height:40px;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;border:none;font-family:inherit;text-decoration:none;transition:all .2s}
.btn-blue{background:var(--blue);color:#fff}
.btn-blue:hover{background:var(--blue-h, #1D4ED8)}
.btn-outline{background:rgba(255,255,255,.12);color:#fff;border:1.5px solid rgba(255,255,255,.25);backdrop-filter:blur(4px)}
.btn-outline:hover{background:rgba(255,255,255,.22)}
/* CONTENT */
.content{padding:28px 36px;display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}
/* INFO CARD */
.card{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);padding:24px;margin-bottom:20px}
.card-title{font-size:15px;font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.info-item label{display:block;font-size:11.5px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.info-item span{font-size:14px;font-weight:600;color:var(--text)}
.desc-text{font-size:14px;line-height:1.7;color:var(--muted)}
/* SHOWTIME PANEL */
.panel{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden;position:sticky;top:70px}
.panel-head{padding:18px 20px;border-bottom:1px solid var(--border)}
.panel-head h3{font-size:15px;font-weight:700}
/* DATE TABS */
.date-tabs{display:flex;gap:0;overflow-x:auto;padding:12px 16px;border-bottom:1px solid var(--border)}
.date-tab{flex-shrink:0;padding:8px 16px;border-radius:10px;cursor:pointer;text-align:center;transition:all .2s;border:1.5px solid transparent}
.date-tab:hover{background:var(--bg)}
.date-tab.active{background:var(--blue);color:#fff}
.date-tab .dt-day{font-size:11px;font-weight:600;opacity:.8}
.date-tab .dt-date{font-size:15px;font-weight:800;margin:2px 0}
.date-tab .dt-label{font-size:10px;opacity:.7}
/* SHOWTIME LIST */
.st-body{padding:16px;max-height:480px;overflow-y:auto}
.cinema-group{margin-bottom:18px}
.cinema-name{font-size:12.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.cinema-name i{color:var(--blue)}
.time-slots{display:flex;flex-wrap:wrap;gap:8px}
.time-slot{padding:7px 12px;border-radius:8px;border:1.5px solid var(--border);cursor:pointer;transition:all .2s;text-align:center}
.time-slot:hover{border-color:var(--blue);color:var(--blue)}
.time-slot.selected{background:var(--blue);color:#fff;border-color:var(--blue)}
.time-slot.full{opacity:.4;cursor:not-allowed}
.ts-time{font-size:14px;font-weight:700;display:block}
.ts-fmt{font-size:10.5px;color:inherit;opacity:.75}
.ts-price{font-size:10.5px;font-weight:600;color:var(--blue);margin-top:2px;display:block}
.time-slot.selected .ts-price{color:rgba(255,255,255,.85)}
/* STICKY FOOTER */
.st-footer{padding:14px 16px;border-top:1px solid var(--border);background:var(--card)}
.st-footer-info{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:13px}
.st-footer-price{font-size:18px;font-weight:800;color:var(--blue)}
.btn-book{width:100%;height:44px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:7px}
.btn-book:hover{background:#1D4ED8}
.btn-book:disabled{opacity:.5;cursor:not-allowed}
.no-st{text-align:center;padding:32px 16px;color:var(--muted);font-size:14px}
/* FORMAT badges */
.fmt-2d{color:#2563EB}.fmt-3d{color:#7C3AED}.fmt-imax{color:#059669}.fmt-premium{color:#D97706}.fmt-4dx{color:#DC2626}
</style>
</head>
<body>
<?php include __DIR__ . '/../components/sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <a href="home.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
    <span class="tb-title"><?= htmlspecialchars($movie['title']) ?></span>
    <div class="tb-right">
      <?php if(!empty($_SESSION['user_id'])): ?>
      <div class="tb-av"><?= mb_strtoupper(mb_substr($_SESSION['user_name']??'U',0,1)) ?></div>
      <?php else: ?>
      <a href="login.php" class="btn btn-blue" style="height:34px;font-size:13px">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- HERO -->
  <div class="hero">
    <?php if($movie['backdrop_url']): ?>
    <img class="hero-bg" src="<?= htmlspecialchars($movie['backdrop_url']) ?>" alt="">
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <?php if($movie['poster_url']): ?>
      <img class="hero-poster" src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="">
      <?php else: ?>
      <div class="hero-poster-ph"><i class="fa-solid fa-film"></i></div>
      <?php endif; ?>
      <div class="hero-info">
        <div class="hero-badges">
          <?php if($movie['status']==='now_showing'): ?>
          <span class="badge badge-red"><i class="fa-solid fa-circle-play"></i> Đang chiếu</span>
          <?php else: ?>
          <span class="badge badge-blue"><i class="fa-regular fa-clock"></i> Sắp chiếu</span>
          <?php endif; ?>
          <span class="badge badge-gray"><?= $movie['age_rating'] ?></span>
        </div>
        <h1 class="hero-title"><?= htmlspecialchars($movie['title']) ?></h1>
        <div class="hero-meta">
          <span><i class="fa-regular fa-clock"></i> <?= $movie['duration_min'] ?> phút</span>
          <span><i class="fa-solid fa-star hero-rating"></i> <b class="hero-rating"><?= number_format($movie['rating'],1) ?></b>/10</span>
          <?php if($movie['genre']): ?><span><i class="fa-solid fa-masks-theater"></i> <?= htmlspecialchars($movie['genre']) ?></span><?php endif; ?>
          <?php if($movie['director']): ?><span><i class="fa-solid fa-video"></i> <?= htmlspecialchars($movie['director']) ?></span><?php endif; ?>
        </div>
        <div class="hero-btns">
          <a href="#showtimes" class="btn btn-blue"><i class="fa-solid fa-ticket"></i> Đặt vé ngay</a>
          <?php if($movie['trailer_url']): ?>
          <a href="<?= htmlspecialchars($movie['trailer_url']) ?>" target="_blank" class="btn btn-outline"><i class="fa-solid fa-play"></i> Xem trailer</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTENT GRID -->
  <div class="content">
    <div>
      <!-- MÔ TẢ -->
      <div class="card">
        <div class="card-title">Nội dung phim</div>
        <p class="desc-text"><?= nl2br(htmlspecialchars($movie['description']??'')) ?></p>
      </div>
      <!-- THÔNG TIN -->
      <div class="card">
        <div class="card-title">Thông tin chi tiết</div>
        <div class="info-grid">
          <div class="info-item"><label>Đạo diễn</label><span><?= htmlspecialchars($movie['director']??'—') ?></span></div>
          <div class="info-item"><label>Diễn viên</label><span><?= htmlspecialchars($movie['cast_list']??'—') ?></span></div>
          <div class="info-item"><label>Thể loại</label><span><?= htmlspecialchars($movie['genre']??'—') ?></span></div>
          <div class="info-item"><label>Thời lượng</label><span><?= $movie['duration_min'] ?> phút</span></div>
          <div class="info-item"><label>Khởi chiếu</label><span><?= $movie['release_date'] ? date('d/m/Y',strtotime($movie['release_date'])) : '—' ?></span></div>
          <div class="info-item"><label>Giới hạn tuổi</label><span><?= $movie['age_rating'] ?></span></div>
          <div class="info-item"><label>Đánh giá</label><span style="color:#F59E0B">⭐ <?= number_format($movie['rating'],1) ?>/10</span></div>
          <div class="info-item"><label>Trạng thái</label><span><?= $movie['status']==='now_showing'?'🟢 Đang chiếu':'🔵 Sắp chiếu' ?></span></div>
        </div>
      </div>

      <!-- ĐÁNH GIÁ TỪ KHÁN GIẢ -->
      <div class="card">
        <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
          <span>Đánh giá từ khán giả (<?= count($reviews) ?>)</span>
          <span style="font-size:13.5px; color:#F59E0B; font-weight:800;">⭐ <?= number_format($movie['rating'],1) ?>/10</span>
        </div>
        
        <?php if(empty($reviews)): ?>
          <div style="text-align:center; padding:32px 16px; color:var(--muted); font-size:13.5px;">
            <i class="fa-regular fa-star" style="font-size:32px; margin-bottom:10px; display:block; opacity:.3;"></i>
            Chưa có đánh giá nào cho phim này. Hãy là người đầu tiên đánh giá sau khi xem phim!
          </div>
        <?php else: ?>
          <div style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach($reviews as $r):
              $init = mb_strtoupper(mb_substr($r['full_name'],0,1)) ?: 'K';
            ?>
              <div style="border-bottom: 1px solid #E2E8F0; padding-bottom:14px; display:flex; gap:12px; align-items:flex-start;">
                <div style="width:36px; height:36px; border-radius:50%; background:#EFF6FF; color:var(--blue); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; flex-shrink:0;">
                  <?= $init ?>
                </div>
                <div style="flex:1;">
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <strong style="font-size:14px; color:#0F172A;"><?= htmlspecialchars($r['full_name']) ?></strong>
                    <span style="font-size:12.5px; color:#F59E0B; font-weight:800;">⭐ <?= $r['rating'] ?>/10</span>
                  </div>
                  <div style="font-size:11px; color:var(--muted); margin-bottom:6px;">📅 <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></div>
                  <p style="font-size:13.5px; color:#334155; line-height:1.5;"><?= nl2br(htmlspecialchars($r['comment'] ?? '')) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- SHOWTIME PANEL -->
    <div class="panel" id="showtimes">
      <div class="panel-head">
        <h3><i class="fa-regular fa-calendar" style="color:var(--blue);margin-right:6px"></i>Chọn suất chiếu</h3>
      </div>
      <?php if(empty($dates)): ?>
        <?php if($movie['status'] === 'coming_soon'): ?>
        <div class="no-st">
          <i class="fa-regular fa-clock" style="font-size:36px;margin-bottom:12px;display:block;color:#3B82F6;opacity:.7"></i>
          <div style="font-size:14px;font-weight:700;color:#0F172A;margin-bottom:6px">Phim sắp ra mắt</div>
          <?php if($movie['release_date']): ?>
          <div style="font-size:13px;color:#64748B">Dự kiến khởi chiếu: <strong style="color:#2563EB"><?= date('d/m/Y', strtotime($movie['release_date'])) ?></strong></div>
          <?php else: ?>
          <div style="font-size:13px;color:#64748B">Lịch chiếu sẽ được cập nhật sớm.</div>
          <?php endif; ?>
          <div style="margin-top:14px;font-size:12px;color:#94A3B8">⏰ Chưa có suất chiếu khả dụng</div>
        </div>
        <?php else: ?>
        <div class="no-st"><i class="fa-solid fa-calendar-xmark" style="font-size:32px;margin-bottom:10px;display:block;opacity:.3"></i>Chưa có suất chiếu</div>
        <?php endif; ?>
      <?php else: ?>
      <div class="date-tabs" id="date-tabs">
        <?php foreach($dates as $i=>$d): 
          $ts = strtotime($d);
          $isToday = ($d===$today);
          $isTomorrow = ($d===date('Y-m-d',strtotime('+1 day')));
        ?>
        <div class="date-tab <?= $i===0?'active':'' ?>" data-date="<?= $d ?>" onclick="selectDate('<?= $d ?>')">
          <div class="dt-day"><?= strtoupper(substr(['CN','T2','T3','T4','T5','T6','T7'][date('w',$ts)],0,3)) ?></div>
          <div class="dt-date"><?= date('d/m',$ts) ?></div>
          <div class="dt-label"><?= $isToday?'Hôm nay':($isTomorrow?'Ngày mai':'') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php foreach($dates as $i=>$d): ?>
      <div class="st-body date-panel" id="panel-<?= $d ?>" style="<?= $i>0?'display:none':'' ?>">
        <?php foreach($grouped[$d] as $cinema=>$slots): ?>
        <div class="cinema-group">
          <div class="cinema-name"><i class="fa-solid fa-location-dot"></i><?= htmlspecialchars($cinema) ?></div>
          <div class="time-slots">
            <?php foreach($slots as $s):
              $full = ($s['available_seats']===0);
              $fmtCls = 'fmt-'.strtolower($s['format']);
            ?>
            <div class="time-slot <?= $full?'full':'' ?>"
              data-id="<?= $s['id'] ?>"
              data-time="<?= $s['start_time'] ?>"
              data-cinema="<?= htmlspecialchars($cinema) ?>"
              data-hall="<?= htmlspecialchars($s['hall_name'] ?? 'Phòng chiếu 1') ?>"
              data-format="<?= $s['format'] ?>"
              data-price="<?= $s['price'] ?>"
              data-seats="<?= $s['available_seats'] ?>"
              onclick="<?= $full ? '' : 'selectSlot(this)' ?>">
              <span class="ts-time"><?= substr($s['start_time'],0,5) ?></span>
              <span class="ts-fmt <?= $fmtCls ?>"><?= $s['format'] ?> · <?= $s['subtitle_type'] ?> · <?= htmlspecialchars($s['hall_name'] ?? 'Phòng chiếu 1') ?></span>
              <span class="ts-price"><?= number_format($s['price'],0,',','.') ?>₫</span>
              <?php if($full): ?><span style="font-size:10px;color:#ef4444">Hết ghế</span><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
      <div class="st-footer">
        <div class="st-footer-info">
          <span id="sel-info" style="color:var(--muted)">Chưa chọn suất chiếu</span>
          <span class="st-footer-price" id="sel-price"></span>
        </div>
        <button class="btn-book" id="btn-book" disabled onclick="goBook()">
          <i class="fa-solid fa-ticket"></i> Tiếp tục chọn ghế
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
var selShowtime = null;

function selectDate(d) {
  document.querySelectorAll('.date-tab').forEach(t=>t.classList.remove('active'));
  document.querySelector('[data-date="'+d+'"]').classList.add('active');
  document.querySelectorAll('.date-panel').forEach(p=>p.style.display='none');
  var panel = document.getElementById('panel-'+d);
  if(panel) panel.style.display='block';
  // reset selection
  selShowtime = null;
  document.querySelectorAll('.time-slot').forEach(t=>t.classList.remove('selected'));
  document.getElementById('sel-info').textContent='Chưa chọn suất chiếu';
  document.getElementById('sel-price').textContent='';
  document.getElementById('btn-book').disabled=true;
}

function selectSlot(el) {
  document.querySelectorAll('.time-slot:not(.full)').forEach(t=>t.classList.remove('selected'));
  el.classList.add('selected');
  selShowtime = {
    id: el.dataset.id,
    time: el.dataset.time,
    cinema: el.dataset.cinema,
    format: el.dataset.format,
    price: el.dataset.price,
    seats: el.dataset.seats,
    hall: el.dataset.hall
  };
  document.getElementById('sel-info').textContent = el.dataset.cinema+' · '+el.dataset.hall+' · '+el.dataset.time.substr(0,5)+' · '+el.dataset.format;
  document.getElementById('sel-price').textContent = Number(el.dataset.price).toLocaleString('vi-VN')+'₫/vé';
  document.getElementById('btn-book').disabled = false;
}

function goBook() {
  if(!selShowtime) return;
  <?php if(empty($_SESSION['user_id'])): ?>
  mfConfirm({
    title: 'Đăng nhập để đặt vé',
    desc: 'Bạn cần đăng nhập để tiến hành đặt vé. Đến trang đăng nhập ngay?',
    type: 'info',
    confirmText: 'Đến trang đăng nhập',
    confirmIcon: 'fa-arrow-right-to-bracket',
    cancelText: 'Ở lại'
  }).then(ok => { if(ok) window.location.href='login.php'; });
  <?php else: ?>
  window.location.href='seat-select.php?showtime_id='+selShowtime.id;
  <?php endif; ?>
}

async function logout(){
  const fd=new FormData();fd.append('action','logout');
  const r=await fetch('../../be/api.php',{method:'POST',body:fd});
  const d=await r.json();
  location.href=d.redirect||'login.php';
}
</script>
<script src="../assets/js/script.js"></script>
</body>
</html>

