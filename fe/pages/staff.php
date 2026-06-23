<?php
session_start();
require_once __DIR__ . '/../../be/config/db.php';

// Check staff authentication
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'staff' && $_SESSION['user_role'] !== 'admin')) {
  header('Location: login.php');
  exit;
}

$working_cinema_id = $_SESSION['staff_cinema_id'] ?? 0;
$working_cinema_name = $_SESSION['staff_cinema_name'] ?? '';

// Fetch all cinemas for selection modal
$cinemas = $pdo->query("SELECT id, name, address FROM cinemas ORDER BY id ASC")->fetchAll();
// Fetch snacks for the concession POS tab
$snacks = $pdo->query("SELECT * FROM snacks ORDER BY category, price")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MovieFlex - Bàn làm việc Nhân viên</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --blue:#4F46E5;
  --blue-hover:#4338CA;
  --bg:#F8FAFC;
  --card:#ffffff;
  --text:#0F172A;
  --muted:#64748B;
  --border:#E2E8F0;
  --r:14px;
  --green:#10B981;
  --red:#EF4444;
  --orange:#F59E0B;
  --shadow:0 4px 20px -2px rgba(79,70,229,0.08), 0 2px 8px -1px rgba(79,70,229,0.04);
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column}

/* TOPBAR */
.topbar{background:var(--card);border-bottom:1px solid var(--border);height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:999;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.logo{display:flex;align-items:center;gap:9px;text-decoration:none}
.logo-icon{width:32px;height:32px;background:var(--blue);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px}
.logo-name{font-size:17px;font-weight:800;color:var(--blue)}
.logo-badge{background:#EEF2FF;color:var(--blue);font-size:11px;font-weight:700;padding:2px 8px;border-radius:12px;border:1px solid #C7D2FE}
.topbar-right{display:flex;align-items:center;gap:16px}
.cinema-display{display:flex;align-items:center;gap:8px;background:#F1F5F9;border:1px solid var(--border);padding:6px 14px;border-radius:10px;font-size:13.5px;font-weight:600}
.cinema-display i{color:var(--blue)}
.btn-switch-cinema{background:var(--blue);color:#fff;border:none;border-radius:8px;padding:6px 12px;font-size:12.5px;font-weight:700;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-switch-cinema:hover{background:var(--blue-hover)}
.btn-logout{background:#FEF2F2;color:var(--red);border:1px solid #FEE2E2;border-radius:8px;padding:6px 12px;font-size:12.5px;font-weight:700;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-logout:hover{background:#FEE2E2}

/* DASHBOARD LAYOUT */
.dashboard-container{max-width:1400px;width:100%;margin:0 auto;padding:24px;flex:1;display:flex;flex-direction:column;gap:24px}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.stat-card{background:var(--card);border-radius:var(--r);padding:20px;box-shadow:var(--shadow);border:1px solid var(--border);display:flex;align-items:center;gap:16px;transition:transform .2s}
.stat-card:hover{transform:translateY(-2px)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}
.stat-icon.blue{background:#EEF2FF;color:var(--blue)}
.stat-icon.green{background:#ECFDF5;color:var(--green)}
.stat-icon.orange{background:#FFFBEB;color:var(--orange)}
.stat-info{display:flex;flex-direction:column;gap:2px}
.stat-label{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.stat-value{font-size:22px;font-weight:800;color:var(--text)}

/* NAVIGATION TABS */
.nav-tabs{background:var(--card);border-radius:12px;padding:5px;box-shadow:var(--shadow);border:1px solid var(--border);display:flex;gap:4px;width:fit-content;margin:0 auto}
.tab-btn{padding:10px 24px;border-radius:8px;font-size:14px;font-weight:700;color:var(--muted);background:none;border:none;cursor:pointer;transition:all .2s;font-family:inherit;display:flex;align-items:center;gap:8px}
.tab-btn.active{background:var(--blue);color:#fff}
.tab-btn:hover:not(.active){background:#F1F5F9;color:var(--text)}

/* TAB CONTENT */
.tab-content{display:none}
.tab-content.active{display:block;animation:fadeIn .3s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* ==========================================================================
   TAB 1: SOÁT VÉ
   ========================================================================== */
.checkin-layout{max-width:700px;margin:20px auto 0;display:flex;flex-direction:column;gap:20px}
.search-box{background:var(--card);border-radius:var(--r);padding:24px;box-shadow:var(--shadow);border:1px solid var(--border);display:flex;flex-direction:column;gap:14px}
.search-box label{font-size:13.5px;font-weight:700;color:var(--text)}
.search-input-wrap{display:flex;gap:10px}
.search-input{flex:1;height:48px;border:1.5px solid var(--border);border-radius:10px;padding:0 16px;font-size:15px;font-family:inherit;outline:none;transition:border-color .2s}
.search-input:focus{border-color:var(--blue)}
.btn-search{height:48px;background:var(--blue);color:#fff;border:none;border-radius:10px;padding:0 24px;font-size:14.5px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s;display:flex;align-items:center;gap:8px}
.btn-search:hover{background:var(--blue-hover)}

/* Ticket Display Card */
.ticket-detail{background:var(--card);border-radius:20px;box-shadow:var(--shadow);border:1px solid var(--border);overflow:hidden;display:none}
.td-header{padding:20px 24px;border-bottom:1px dashed var(--border);position:relative;display:flex;gap:20px;align-items:center}
.td-poster{width:64px;height:90px;border-radius:8px;object-fit:cover;background:#EFF6FF}
.td-info h2{font-size:17px;font-weight:800;margin-bottom:6px}
.td-badge{display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;text-transform:uppercase}
.td-badge.confirmed{background:#ECFDF5;color:var(--green)}
.td-badge.checked_in{background:#EEF2FF;color:var(--blue)}
.td-badge.cancelled{background:#FEF2F2;color:var(--red)}

.td-body{padding:24px;display:grid;grid-template-columns:1fr 1fr;gap:14px 24px}
.td-field{display:flex;flex-direction:column;gap:2px}
.td-field label{font-size:10.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.3px}
.td-field span{font-size:13.5px;font-weight:600}
.td-field.full{grid-column:1/-1}

.td-seats{display:flex;flex-wrap:wrap;gap:5px;margin-top:4px}
.td-seat-chip{background:#EFF6FF;color:var(--blue);font-size:12px;font-weight:700;padding:3px 9px;border-radius:6px;border:1px solid #C7D2FE}

.td-warning{grid-column:1/-1;background:#FEF2F2;border:1px solid #FEE2E2;border-radius:10px;padding:12px 16px;color:#991B1B;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px}

.td-actions{padding:20px 24px;background:#F8FAFC;border-top:1px solid var(--border);display:flex;justify-content:flex-end}
.btn-approve-checkin{width:100%;height:46px;background:var(--green);color:#fff;border:none;border-radius:10px;font-size:14.5px;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .2s;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-approve-checkin:hover{opacity:.9}
.btn-approve-checkin:disabled{background:var(--muted);opacity:.45;cursor:not-allowed}

/* ==========================================================================
   TAB 2: BÁN VÉ TẠI QUẦY
   ========================================================================== */
.counter-layout{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}
.counter-main{background:var(--card);border-radius:var(--r);box-shadow:var(--shadow);border:1px solid var(--border);padding:24px;min-height:500px}
.section-title{font-size:16px;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:8px;color:var(--text)}
.section-title span{background:var(--blue);color:#fff;width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px}

/* Movies List Grid */
.c-movies-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:16px}
.c-movie-card{border:1.5px solid var(--border);border-radius:12px;overflow:hidden;cursor:pointer;transition:all .2s}
.c-movie-card:hover{border-color:var(--blue);transform:translateY(-2px)}
.c-movie-card.selected{border-color:var(--blue);box-shadow:0 0 0 2px var(--blue)}
.c-movie-poster{width:100%;height:180px;object-fit:cover;background:#F1F5F9}
.c-movie-title{padding:8px 10px;font-size:12.5px;font-weight:700;text-align:center;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* Showtimes List */
.c-showtimes-list{display:flex;flex-wrap:wrap;gap:8px}
.c-showtime-item{border:1.5px solid var(--border);border-radius:10px;padding:10px 16px;cursor:pointer;text-align:center;transition:all .2s}
.c-showtime-item:hover{border-color:var(--blue);background:#F8FAFC}
.c-showtime-item.selected{border-color:var(--blue);background:#EEF2FF;color:var(--blue)}
.c-showtime-time{font-size:15px;font-weight:800;display:block}
.c-showtime-date{font-size:10px;color:var(--muted);font-weight:600}
.c-showtime-hall{font-size:10px;font-weight:700;display:block;margin-top:2px}

/* Seat Selection */
.c-seats-wrap{display:flex;flex-direction:column;gap:12px;align-items:center}
.c-screen{height:6px;background:linear-gradient(to bottom,#94A3B8,#CBD5E1);border-radius:3px;width:70%;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,.10)}
.c-seat-grid{display:flex;flex-direction:column;gap:6px}
.c-seat-row{display:flex;align-items:center;gap:5px}
.c-row-label{width:18px;font-size:11px;font-weight:700;color:var(--muted);text-align:center}
.c-seat{width:28px;height:28px;border-radius:6px;border:1.5px solid #CBD5E1;background:#F8FAFC;cursor:pointer;font-size:9.5px;font-weight:700;color:var(--muted);display:flex;align-items:center;justify-content:center;transition:all .15s}
.c-seat:hover:not(.taken){transform:scale(1.08);border-color:var(--blue);color:var(--blue)}
.c-seat.vip{background:#FEF9C3;border-color:#FBBF24;color:#92400E}
.c-seat.sweet{background:#FCE7F3;border-color:#F9A8D4;color:#9D174D;width:61px;border-radius:8px}
.c-seat.selected{background:var(--blue)!important;border-color:var(--blue)!important;color:#fff!important}
.c-seat.taken{background:#E2E8F0;border-color:#E2E8F0;color:#CBD5E1;cursor:not-allowed;opacity:.5}
.c-aisle{width:12px}

/* RIGHT SUMMARY PANEL */
.sum-panel{background:var(--card);border-radius:var(--r);box-shadow:var(--shadow);border:1px solid var(--border);position:sticky;top:90px;overflow:hidden}
.sum-head{background:var(--blue);color:#fff;padding:16px 20px}
.sum-head h3{font-size:15px;font-weight:700}
.sum-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.sum-meta-row{display:flex;justify-content:space-between;font-size:13px}
.sum-meta-val{font-weight:700;text-align:right}

.sum-divider{height:1px;background:var(--border);margin:4px 0}
.sum-chosen-seats{display:flex;flex-wrap:wrap;gap:4px}
.sum-seat-tag{background:#EEF2FF;color:var(--blue);font-size:11px;font-weight:700;padding:2px 8px;border-radius:5px;border:1px solid #C7D2FE}

/* Member lookup */
.member-lookup{display:flex;flex-direction:column;gap:6px}
.member-lookup label{font-size:12px;font-weight:700;color:var(--text)}
.ml-input-row{display:flex;gap:6px}
.ml-input{flex:1;height:36px;border:1.5px solid var(--border);border-radius:8px;padding:0 10px;font-size:13px;font-family:inherit;outline:none}
.ml-input:focus{border-color:var(--blue)}
.btn-ml-search{height:36px;background:var(--blue);color:#fff;border:none;border-radius:8px;padding:0 12px;font-size:12px;font-weight:700;cursor:pointer}
.member-info-badge{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:8px;padding:8px 12px;font-size:12px;display:flex;flex-direction:column;gap:2px}

/* Concessions list inside ticket panel */
.concessions-mini{display:flex;flex-direction:column;gap:8px}
.mini-snack-item{display:flex;align-items:center;justify-content:space-between;font-size:12.5px}
.mini-snack-info{flex:1}
.mini-snack-name{font-weight:600}
.mini-snack-price{font-size:11.5px;color:var(--muted)}
.mini-snack-ctrl{display:flex;align-items:center;gap:8px}
.mini-snack-btn{width:22px;height:22px;border-radius:5px;border:1px solid var(--border);background:#fff;cursor:pointer;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center}
.mini-snack-qty{font-weight:800;width:14px;text-align:center}

.sum-payment{display:flex;flex-direction:column;gap:6px}
.sum-payment label{font-size:12px;font-weight:700}
.pay-options-row{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.pay-opt-btn{height:36px;border:1.5px solid var(--border);border-radius:8px;background:none;font-size:12.5px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px}
.pay-opt-btn.selected{border-color:var(--blue);background:#EEF2FF;color:var(--blue)}

.sum-total-row{display:flex;justify-content:space-between;align-items:center;font-size:15px;font-weight:800;margin-top:6px}
.sum-total-price{color:var(--blue);font-size:20px}

.btn-submit-booking{width:100%;height:46px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:14.5px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-submit-booking:hover{background:var(--blue-hover)}
.btn-submit-booking:disabled{background:var(--muted);opacity:.45;cursor:not-allowed}

/* ==========================================================================
   TAB 3: BÁN BẮP NƯỚC (POS)
   ========================================================================== */
.pos-layout{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}
.pos-main{background:var(--card);border-radius:var(--r);box-shadow:var(--shadow);border:1px solid var(--border);padding:24px}
.pos-grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));gap:16px}
.pos-card{border:1.5px solid var(--border);border-radius:12px;padding:16px;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;gap:8px;position:relative}
.pos-card:hover{border-color:var(--blue);transform:translateY(-2px)}
.pos-card.in-cart{border-color:var(--blue);background:#EEF2FF}
.pos-name{font-size:13.5px;font-weight:700;line-height:1.4}
.pos-category{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--muted)}
.pos-price-row{display:flex;justify-content:space-between;align-items:center;margin-top:auto}
.pos-price{font-size:14px;font-weight:800;color:var(--blue)}
.pos-badge{position:absolute;top:8px;right:8px;background:var(--blue);color:#fff;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800}

/* ==========================================================================
   CINEMA SELECTION MODAL
   ========================================================================== */
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.7);backdrop-filter:blur(4px);z-index:99999;display:none;align-items:center;justify-content:center;padding:16px}
.modal-overlay.active{display:flex}
.modal-card{background:var(--card);width:100%;max-width:540px;border-radius:20px;box-shadow:0 20px 48px -10px rgba(0,0,0,.3);overflow:hidden}
.modal-head{background:linear-gradient(135deg,var(--blue),var(--blue-hover));padding:24px;text-align:center;color:#fff}
.modal-head h2{font-size:19px;font-weight:800;margin-bottom:4px}
.modal-head p{font-size:12.5px;opacity:.9}
.modal-body{padding:24px;display:flex;flex-direction:column;gap:16px}
.cinema-options-list{display:flex;flex-direction:column;gap:10px;max-height:300px;overflow-y:auto;padding-right:4px}
.cinema-opt-card{border:1.5px solid var(--border);border-radius:12px;padding:14px 16px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:12px}
.cinema-opt-card:hover{border-color:var(--blue);background:#EEF2FF}
.cinema-opt-icon{width:36px;height:36px;border-radius:8px;background:#F1F5F9;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:16px}
.cinema-opt-card:hover .cinema-opt-icon{background:#fff}
.cinema-opt-info{flex:1}
.cinema-opt-name{font-size:13.5px;font-weight:700}
.cinema-opt-addr{font-size:11px;color:var(--muted);margin-top:2px}

/* TOAST */
.toast-container { position: fixed; top: 24px; right: 24px; z-index: 999999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
.toast { background: #fff; border-left: 4px solid var(--blue); box-shadow: 0 10px 25px -5px rgba(0,0,0,.1), 0 8px 10px -6px rgba(0,0,0,.1); border-radius: 8px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; transform: translateX(120%); opacity: 0; transition: all 0.4s cubic-bezier(0.21, 1.02, 0.73, 1); min-width: 300px; }
.toast.show { transform: translateX(0); opacity: 1; }
.toast-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.toast-icon.success { background: #ECFDF5; color: var(--green); }
.toast-icon.error { background: #FEF2F2; color: var(--red); }
.toast-icon.warning { background: #FFFBEB; color: var(--orange); }
.toast-content { flex: 1; }
.toast-title { font-size: 13.5px; font-weight: 700; color: #0F172A; margin-bottom: 2px; }
.toast-desc { font-size: 12px; color: var(--muted); line-height: 1.4; }

/* ─── mfConfirm Custom Dialog ─── */
#mf-dialog-overlay {
  position: fixed; inset: 0;
  background: rgba(15,23,42,.65);
  backdrop-filter: blur(4px);
  z-index: 9999999;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
}
#mf-dialog-overlay.active { display: flex; }
.mf-dialog {
  background: #fff;
  border-radius: 20px;
  box-shadow: 0 24px 48px -12px rgba(0,0,0,.28), 0 0 0 1px rgba(0,0,0,.04);
  width: 100%; max-width: 420px;
  overflow: hidden;
  animation: mfDialogIn .25s cubic-bezier(.34,1.56,.64,1);
}
@keyframes mfDialogIn {
  from { transform: scale(.88) translateY(24px); opacity: 0; }
  to   { transform: scale(1) translateY(0); opacity: 1; }
}
.mf-dialog-icon-wrap { display: flex; justify-content: center; padding: 28px 24px 0; }
.mf-dialog-icon {
  width: 64px; height: 64px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-size: 26px;
}
.mf-dialog-icon.danger  { background: #FEE2E2; color: #DC2626; }
.mf-dialog-icon.warning { background: #FEF3C7; color: #D97706; }
.mf-dialog-icon.info    { background: #EEF2FF; color: #4F46E5; }
.mf-dialog-icon.success { background: #D1FAE5; color: #059669; }
.mf-dialog-body { padding: 20px 28px 24px; text-align: center; }
.mf-dialog-title { font-size: 17px; font-weight: 800; color: #0F172A; margin-bottom: 8px; line-height: 1.3; }
.mf-dialog-desc  { font-size: 13.5px; color: #64748B; line-height: 1.6; }
.mf-dialog-footer { padding: 0 24px 24px; display: flex; gap: 10px; }
.mf-btn-cancel {
  flex: 1; height: 42px;
  background: #F1F5F9; color: #475569;
  border: 1px solid #E2E8F0; border-radius: 10px;
  font-size: 13.5px; font-weight: 700; cursor: pointer; font-family: inherit;
  transition: background .15s;
}
.mf-btn-cancel:hover { background: #E2E8F0; }
.mf-btn-confirm {
  flex: 1; height: 42px;
  border: none; border-radius: 10px;
  font-size: 13.5px; font-weight: 700; cursor: pointer; font-family: inherit;
  color: #fff;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: opacity .15s;
}
.mf-btn-confirm:hover { opacity: .88; }
.mf-btn-confirm.danger  { background: #EF4444; }
.mf-btn-confirm.warning { background: #F59E0B; }
.mf-btn-confirm.info    { background: #4F46E5; }
.mf-btn-confirm.success { background: #10B981; }
</style>

</head>
<body>

<div class="toast-container" id="toast-container"></div>

<!-- Topbar -->
<header class="topbar">
  <a href="#" class="logo">
    <div class="logo-icon"><i class="fa-solid fa-clapperboard"></i></div>
    <span class="logo-name">MovieFlex</span>
    <span class="logo-badge">Quầy Nhân viên</span>
  </a>
  <div class="topbar-right">
    <?php if ($working_cinema_id): ?>
      <div class="cinema-display" id="topbar-cinema-display">
        <i class="fa-solid fa-location-dot"></i>
        <span><?= htmlspecialchars($working_cinema_name) ?></span>
      </div>
      <button class="btn-switch-cinema" onclick="openCinemaSelectModal()"><i class="fa-solid fa-arrow-rotate-left"></i> Đổi chi nhánh</button>
    <?php else: ?>
      <button class="btn-switch-cinema" onclick="openCinemaSelectModal()"><i class="fa-solid fa-location-dot"></i> Chọn chi nhánh rạp</button>
    <?php endif; ?>
    <button class="btn-logout" onclick="doLogout()"><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất</button>
  </div>
</header>

<!-- Main Container -->
<div class="dashboard-container">

  <!-- KPIs Stats Row -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fa-solid fa-coins"></i></div>
      <div class="stat-info">
        <span class="stat-label">Doanh thu tại quầy (Hôm nay)</span>
        <span class="stat-value" id="stat-revenue">0 ₫</span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fa-solid fa-ticket"></i></div>
      <div class="stat-info">
        <span class="stat-label">Số vé bán ra (Hôm nay)</span>
        <span class="stat-value" id="stat-tickets">0 vé</span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="fa-solid fa-user-check"></i></div>
      <div class="stat-info">
        <span class="stat-label">Lượt đã check-in (Hôm nay)</span>
        <span class="stat-value" id="stat-checkins">0 lượt</span>
      </div>
    </div>
  </div>

  <!-- Tabs Navigation -->
  <div class="nav-tabs">
    <button class="tab-btn active" onclick="switchTab('checkin')"><i class="fa-solid fa-user-check"></i> Soát Vé</button>
    <button class="tab-btn" onclick="switchTab('booking')"><i class="fa-solid fa-ticket"></i> Bán Vé Tại Quầy</button>
    <button class="tab-btn" onclick="switchTab('snacks')"><i class="fa-solid fa-popcorn"></i> Bán Bắp Nước</button>
  </div>

  <!-- ========================================================================
       TAB 1: SOÁT VÉ
       ======================================================================== -->
  <div class="tab-content active" id="content-checkin">
    <div class="checkin-layout">
      <div class="search-box">
        <label for="ticket-search">Nhập mã đặt vé khách hàng (Mã có dạng MF...):</label>
        <div class="search-input-wrap">
          <input type="text" class="search-input" id="ticket-search" placeholder="Nhập mã vé..." autocomplete="off" onkeydown="if(event.key==='Enter') searchTicket()">
          <button class="btn-search" onclick="searchTicket()"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu</button>
        </div>
      </div>

      <!-- Ticket display -->
      <div class="ticket-detail" id="ticket-card">
        <div class="td-header">
          <img class="td-poster" id="ticket-poster" src="" alt="">
          <div class="td-info">
            <h2 id="ticket-movie-title">Tên Phim Ở Đây</h2>
            <span class="td-badge" id="ticket-status-badge">Confirmed</span>
          </div>
        </div>
        <div class="td-body">
          <div class="td-field"><label>Mã Đơn</label><strong id="ticket-code-val">MF...</strong></div>
          <div class="td-field"><label>Khách Hàng</label><span id="ticket-customer">Nguyễn Văn A</span></div>
          <div class="td-field"><label>Ngày Chiếu</label><span id="ticket-date">25/05/2026</span></div>
          <div class="td-field"><label>Giờ Chiếu</label><span id="ticket-time">19:30</span></div>
          <div class="td-field"><label>Phòng Chiếu</label><span id="ticket-hall">Phòng chiếu 1</span></div>
          <div class="td-field"><label>Rạp Chiếu</label><span id="ticket-cinema">CGV Vincom</span></div>
          <div class="td-field full">
            <label>Ghế Ngồi</label>
            <div class="td-seats" id="ticket-seats-row">
              <!-- seat chips -->
            </div>
          </div>
          <div class="td-warning" id="ticket-warning" style="display:none">
            <i class="fa-solid fa-triangle-exclamation"></i> <span id="ticket-warning-text">Cảnh báo rạp khác</span>
          </div>
        </div>
        <div class="td-actions" id="ticket-actions-row">
          <button class="btn-approve-checkin" id="btn-do-checkin" onclick="confirmCheckin()"><i class="fa-solid fa-circle-check"></i> Xác nhận vào phòng chiếu</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       TAB 2: BÁN VÉ TẠI QUẦY
       ======================================================================== -->
  <div class="tab-content" id="content-booking">
    <div class="counter-layout">
      
      <!-- Selection main panel -->
      <div class="counter-main" id="counter-booking-main">
        
        <!-- STEP 1: CHỌN PHIM -->
        <div class="booking-step" id="c-step-1">
          <div class="section-title"><span>1</span> Chọn phim đang chiếu tại chi nhánh</div>
          <div class="c-movies-grid" id="c-movies-list">
            <!-- Movie cards will load dynamically -->
          </div>
        </div>

        <!-- STEP 2: CHỌN SUẤT CHIẾU (Initially hidden) -->
        <div class="booking-step" id="c-step-2" style="display:none; margin-top:28px">
          <div class="section-title"><span>2</span> Chọn suất chiếu</div>
          <div class="c-showtimes-list" id="c-showtimes-list">
            <!-- showtimes load dynamically -->
          </div>
        </div>

        <!-- STEP 3: CHỌN GHẾ (Initially hidden) -->
        <div class="booking-step" id="c-step-3" style="display:none; margin-top:28px">
          <div class="section-title"><span>3</span> Chọn ghế phòng chiếu</div>
          <div class="c-seats-wrap">
            <div class="c-screen"></div>
            <div class="c-seat-grid" id="c-seat-map">
              <!-- Seat map loaded dynamically -->
            </div>
          </div>
        </div>

      </div>

      <!-- Right Summary Cart Panel -->
      <div class="sum-panel">
        <div class="sum-head">
          <h3>Hóa Đơn Vé & Bắp Nước</h3>
        </div>
        <div class="sum-body">
          <div class="sum-meta-row"><span class="sum-label">Chi Nhánh</span><span class="sum-meta-val" id="sum-cinema-name"><?= htmlspecialchars($working_cinema_name) ?></span></div>
          <div class="sum-meta-row"><span class="sum-label">Phim Chọn</span><span class="sum-meta-val" id="sum-movie-name">—</span></div>
          <div class="sum-meta-row"><span class="sum-label">Suất Chiếu</span><span class="sum-meta-val" id="sum-showtime">—</span></div>
          <div class="sum-divider"></div>
          
          <div class="sum-meta-row" style="flex-direction:column; gap:4px">
            <span class="sum-label">Ghế đã chọn:</span>
            <div class="sum-chosen-seats" id="sum-chosen-seats">
              <span style="color:var(--muted); font-size:12px">Chưa chọn ghế</span>
            </div>
          </div>
          <div class="sum-divider"></div>

          <!-- Add Concession mini list -->
          <div class="concessions-mini">
            <span class="sum-label" style="font-size:12px; display:block; margin-bottom:4px">Thêm bắp nước tại quầy:</span>
            <?php foreach($snacks as $sn): ?>
              <div class="mini-snack-item" data-id="<?= $sn['id'] ?>" data-price="<?= $sn['price'] ?>" data-name="<?= htmlspecialchars($sn['name']) ?>">
                <div class="mini-snack-info">
                  <div class="mini-snack-name"><?= htmlspecialchars($sn['name']) ?></div>
                  <div class="mini-snack-price"><?= number_format($sn['price'],0,',','.') ?>₫</div>
                </div>
                <div class="mini-snack-ctrl">
                  <button class="mini-snack-btn" onclick="changeMiniSnackQty(<?= $sn['id'] ?>, -1)">−</button>
                  <span class="mini-snack-qty" id="ms-qty-<?= $sn['id'] ?>">0</span>
                  <button class="mini-snack-btn" onclick="changeMiniSnackQty(<?= $sn['id'] ?>, 1)">+</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="sum-divider"></div>

          <!-- Member lookup info -->
          <div class="member-lookup">
            <label>Thành viên tích điểm (SĐT / Email):</label>
            <div class="ml-input-row">
              <input type="text" class="ml-input" id="c-member-search" placeholder="Nhập SĐT hoặc email...">
              <button class="btn-ml-search" onclick="searchMember()"><i class="fa-solid fa-search"></i></button>
            </div>
            <div class="member-info-badge" id="c-member-badge" style="display:none">
              <!-- member status badge -->
            </div>
          </div>
          <div class="sum-divider"></div>

          <!-- Payment method -->
          <div class="sum-payment">
            <label>Phương thức thanh toán:</label>
            <div class="pay-options-row">
              <button class="pay-opt-btn selected" id="p-btn-cash" onclick="setPaymentMethod('cash')"><i class="fa-solid fa-wallet"></i> Tiền mặt</button>
              <button class="pay-opt-btn" id="p-btn-momo" onclick="setPaymentMethod('momo')"><i class="fa-solid fa-credit-card"></i> Ví điện tử</button>
            </div>
          </div>
          <div class="sum-divider"></div>

          <div class="sum-total-row">
            <span>TỔNG CỘNG:</span>
            <span class="sum-total-price" id="c-total-amount">0 ₫</span>
          </div>

          <button class="btn-submit-booking" id="btn-submit-booking" onclick="showCounterInvoicePreview()" disabled><i class="fa-solid fa-file-invoice-dollar"></i> Xem trước hóa đơn</button>
        </div>
      </div>

    </div>
  </div>

  <!-- ========================================================================
       TAB 3: BÁN BẮP NƯỚC (POS)
       ======================================================================== -->
  <div class="tab-content" id="content-snacks">
    <div class="pos-layout">
      
      <!-- Concessions POS Grid -->
      <div class="pos-main">
        <div class="section-title">Danh sách bắp nước & Dịch vụ</div>
        <div class="pos-grid">
          <?php foreach ($snacks as $sn): ?>
            <div class="pos-card" id="pos-card-<?= $sn['id'] ?>" onclick="addPosSnack(<?= $sn['id'] ?>, '<?= htmlspecialchars($sn['name']) ?>', <?= $sn['price'] ?>)">
              <span class="pos-category"><?= htmlspecialchars($sn['category']) ?></span>
              <div class="pos-name"><?= htmlspecialchars($sn['name']) ?></div>
              <div class="pos-price-row">
                <span class="pos-price"><?= number_format($sn['price'], 0, ',', '.') ?>₫</span>
              </div>
              <span class="pos-badge" id="pos-badge-<?= $sn['id'] ?>" style="display:none">0</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Snack checkout panel -->
      <div class="sum-panel">
        <div class="sum-head">
          <h3>Hóa Đơn Bắp Nước Lẻ</h3>
        </div>
        <div class="sum-body" style="gap:16px">
          <div class="sum-meta-row" style="flex-direction:column; gap:8px">
            <span class="sum-label">Sản phẩm đã chọn:</span>
            <div id="pos-cart-list" style="display:flex; flex-direction:column; gap:10px; width:100%">
              <span style="color:var(--muted); font-size:12.5px; text-align:center; display:block; padding:20px 0">Giỏ hàng trống</span>
            </div>
          </div>
          <div class="sum-divider"></div>

          <div class="member-lookup">
            <label>Thành viên tích điểm (SĐT / Email - Tùy chọn):</label>
            <div class="ml-input-row">
              <input type="text" class="ml-input" id="pos-member-search" placeholder="Nhập SĐT hoặc email...">
              <button class="btn-ml-search" onclick="searchPosMember()"><i class="fa-solid fa-search"></i></button>
            </div>
            <div class="member-info-badge" id="pos-member-badge" style="display:none">
              <!-- member status -->
            </div>
          </div>
          <div class="sum-divider"></div>

          <div class="sum-payment">
            <label>Phương thức thanh toán:</label>
            <div class="pay-options-row">
              <button class="pay-opt-btn selected" id="pos-btn-cash" onclick="setPosPaymentMethod('cash')"><i class="fa-solid fa-wallet"></i> Tiền mặt</button>
              <button class="pay-opt-btn" id="pos-btn-momo" onclick="setPosPaymentMethod('momo')"><i class="fa-solid fa-credit-card"></i> Ví điện tử</button>
            </div>
          </div>
          <div class="sum-divider"></div>

          <div class="sum-total-row">
            <span>TỔNG CỘNG:</span>
            <span class="sum-total-price" id="pos-total-amount">0 ₫</span>
          </div>

          <button class="btn-submit-booking" id="btn-submit-pos" onclick="showPosInvoicePreview()" disabled><i class="fa-solid fa-file-invoice-dollar"></i> Xem trước hóa đơn</button>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- Cinema Selection Modal Overlay -->
<div class="modal-overlay <?= !$working_cinema_id ? 'active' : '' ?>" id="cinemaSelectModal">
  <div class="modal-card">
    <div class="modal-head">
      <h2><i class="fa-solid fa-shop"></i> CHỌN CHI NHÁNH LÀM VIỆC</h2>
      <p>Vui lòng chọn rạp bạn đang trực để bắt đầu thao tác bán vé & soát vé</p>
    </div>
    <div class="modal-body">
      <div class="cinema-options-list">
        <?php foreach ($cinemas as $c): ?>
          <div class="cinema-opt-card" onclick="selectWorkingCinema(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
            <div class="cinema-opt-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div class="cinema-opt-info">
              <div class="cinema-opt-name"><?= htmlspecialchars($c['name']) ?></div>
              <div class="cinema-opt-addr"><?= htmlspecialchars($c['address']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Invoice Preview Modal Overlay -->
<div class="modal-overlay" id="invoicePreviewModal">
  <div class="modal-card" style="max-width: 500px;">
    <div class="modal-head" style="background: linear-gradient(135deg, var(--blue), var(--blue-hover));">
      <h2><i class="fa-solid fa-file-invoice-dollar"></i> XÁC NHẬN HÓA ĐƠN</h2>
      <p>Kiểm tra kỹ các sản phẩm trước khi thanh toán & in</p>
    </div>
    <div class="modal-body" style="padding: 20px; font-size: 13.5px;">
      <div id="preview-invoice-content"></div>
    </div>
    <div class="modal-foot" style="padding: 16px 20px; background: #F8FAFC; border-top: 1px solid var(--border); display: flex; gap: 10px; justify-content: flex-end;">
      <button class="btn-logout" style="margin: 0; padding: 10px 20px;" onclick="closeInvoicePreview()">✕ Quay lại</button>
      <button class="btn-submit-booking" id="btn-confirm-invoice-submit" style="margin: 0; padding: 10px 20px; width: auto; flex: 1;" onclick="executeInvoiceSubmit()"><i class="fa-solid fa-print"></i> Xác nhận & In vé</button>
    </div>
  </div>
</div>

<script>
// Session & Working Cinema States
let workingCinemaId = <?= (int)$working_cinema_id ?>;
let workingCinemaName = <?= json_encode($working_cinema_name) ?>;

// Counter Tickets tab variables
let activeMovieId = null;
let activeShowtimeId = null;
let activeBasePrice = 0;
let counterSelectedSeats = [];
let counterSelectedSnacks = {}; // snackId => qty
let counterMemberId = null;
let counterPaymentMethod = 'cash';

// POS Concessions tab variables
let posSnacksInCart = {}; // snackId => {name, price, qty}
let posMemberId = null;
let posPaymentMethod = 'cash';

document.addEventListener('DOMContentLoaded', () => {
  if (workingCinemaId) {
    fetchCinemaStats();
    loadCounterMovies();
  }
});

function generateTicketCode() {
  const now = new Date();
  const yyyy = now.getFullYear();
  const mm = String(now.getMonth() + 1).padStart(2, '0');
  const dd = String(now.getDate()).padStart(2, '0');
  const dateStr = `${yyyy}${mm}${dd}`;
  
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let randStr = '';
  for (let i = 0; i < 6; i++) {
    randStr += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return 'MF' + dateStr + randStr;
}

// Toast notification helper
function showToast(title, desc, type='success') {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = 'toast';
  
  let iconHtml = '';
  if(type==='success') iconHtml = '<div class="toast-icon success"><i class="fa-solid fa-circle-check"></i></div>';
  else if(type==='error') iconHtml = '<div class="toast-icon error"><i class="fa-solid fa-circle-exclamation"></i></div>';
  else iconHtml = '<div class="toast-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></div>';

  toast.innerHTML = `
    ${iconHtml}
    <div class="toast-content">
      <div class="toast-title">${title}</div>
      <div class="toast-desc">${desc}</div>
    </div>
  `;
  container.appendChild(toast);
  
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      toast.classList.add('show');
    });
  });
  
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 400);
  }, 3500);
}

// ─── INVOICE PREVIEW MODAL FUNCTIONS ──────────────────────────────────────
let currentPreviewType = ''; // 'counter' or 'pos'

function closeInvoicePreview() {
  document.getElementById('invoicePreviewModal').classList.remove('active');
}

function executeInvoiceSubmit() {
  closeInvoicePreview();
  if (currentPreviewType === 'counter') {
    submitCounterBooking();
  } else if (currentPreviewType === 'pos') {
    submitPosSnackOrder();
  }
}

function showCounterInvoicePreview() {
  currentPreviewType = 'counter';
  const movieTitle = document.getElementById('sum-movie-name').textContent;
  const showtimeStr = document.getElementById('sum-showtime').textContent;
  
  let seatsHtml = '';
  if (counterSelectedSeats.length > 0) {
    let seatsRowsHtml = '';
    counterSelectedSeats.forEach(s => {
      const seatName = s.seat2 ? `${s.seat}+${s.seat2}` : s.seat;
      const row = s.seat.substring(0, 1);
      let seatType = 'Thường';
      if (s.seat2) seatType = 'Sweetbox';
      else if (['E', 'F', 'G'].includes(row)) seatType = 'VIP';
      
      seatsRowsHtml += `
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 6px; padding: 6px 0; border-bottom: 1px dashed var(--border);">
          <div>
            <div>Ghế <strong style="color: var(--blue);">${seatName}</strong> (${seatType})</div>
            <div style="font-family: monospace; font-size: 11.5px; color: var(--muted); margin-top: 2px;">Mã vé: <strong style="color: #10B981; font-size: 12.5px; letter-spacing: 0.5px;">${s.booking_code}</strong></div>
          </div>
          <span style="font-weight: 600;">${s.price.toLocaleString('vi-VN')}₫</span>
        </div>
      `;
    });

    seatsHtml = `
      <div style="margin-bottom: 12px; border-bottom: 1px dashed var(--border); padding-bottom: 8px;">
        <div style="font-weight: 700; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Vé xem phim (${counterSelectedSeats.length} vé)</div>
        <div style="margin-top: 4px; font-size: 13px; color: var(--text);">
          Phim: <strong>${movieTitle}</strong> | Suất: ${showtimeStr}
        </div>
        <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 3px;">
          ${seatsRowsHtml}
        </div>
      </div>
    `;
  }

  // Snacks List
  let snacksHtml = '';
  let snacksTotal = 0;
  let snacksList = [];
  document.querySelectorAll('.mini-snack-item').forEach(item => {
    const price = parseInt(item.dataset.price);
    const id = parseInt(item.dataset.id);
    const name = item.dataset.name;
    const qty = counterSelectedSnacks[id] || 0;
    if (qty > 0) {
      snacksTotal += price * qty;
      snacksList.push(`
        <div style="display: flex; justify-content: space-between; margin-top: 3px;">
          <span>${name} <strong style="color: var(--muted);">x${qty}</strong></span>
          <span>${(price * qty).toLocaleString('vi-VN')}₫</span>
        </div>
      `);
    }
  });

  if (snacksList.length > 0) {
    snacksHtml = `
      <div style="margin-bottom: 12px; border-bottom: 1px dashed var(--border); padding-bottom: 8px;">
        <div style="font-weight: 700; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Bắp nước & dịch vụ</div>
        ${snacksList.join('')}
      </div>
    `;
  }

  // Member info
  let memberHtml = '';
  const memberBadge = document.getElementById('c-member-badge');
  if (counterMemberId && memberBadge.style.display !== 'none') {
    memberHtml = `
      <div style="margin-bottom: 12px; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 10px; color: #065F46; line-height: 1.4;">
        <i class="fa-solid fa-user-tag"></i> <strong>Thành viên:</strong> ${memberBadge.querySelector('strong').textContent.replace('✓', '').trim()}<br>
        <span style="font-size: 11.5px; opacity: .9;">${memberBadge.querySelector('span').textContent}</span>
      </div>
    `;
  }

  // Payment method
  const payMethodText = counterPaymentMethod === 'cash' ? '💵 Tiền mặt' : '💳 Ví MoMo';
  const totalAmountText = document.getElementById('c-total-amount').textContent;

  const finalHtml = `
    ${memberHtml}
    ${seatsHtml}
    ${snacksHtml}
    <div style="margin-bottom: 12px;">
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--muted); font-weight: 600;">Phương thức TT:</span>
        <span style="font-weight: 700;">${payMethodText}</span>
      </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; background: #EEF2FF; padding: 12px; border-radius: 8px; border: 1px solid #C7D2FE;">
      <span style="font-weight: 800; color: var(--blue); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Tổng cộng thanh toán:</span>
      <span style="font-weight: 800; color: var(--blue); font-size: 18px;">${totalAmountText}</span>
    </div>
  `;

  document.getElementById('preview-invoice-content').innerHTML = finalHtml;
  document.getElementById('invoicePreviewModal').classList.add('active');
}

function showPosInvoicePreview() {
  currentPreviewType = 'pos';
  
  // Snacks List
  let snacksHtml = '';
  let snacksList = [];
  const ids = Object.keys(posSnacksInCart);
  let sumTotal = 0;
  
  ids.forEach(sid => {
    const item = posSnacksInCart[sid];
    const itemTotal = item.price * item.qty;
    sumTotal += itemTotal;
    snacksList.push(`
      <div style="display: flex; justify-content: space-between; margin-top: 3px;">
        <span>${item.name} <strong style="color: var(--muted);">x${item.qty}</strong></span>
        <span>${itemTotal.toLocaleString('vi-VN')}₫</span>
      </div>
    `);
  });

  if (snacksList.length > 0) {
    snacksHtml = `
      <div style="margin-bottom: 12px; border-bottom: 1px dashed var(--border); padding-bottom: 8px;">
        <div style="font-weight: 700; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Bắp nước lẻ</div>
        ${snacksList.join('')}
      </div>
    `;
  }

  // Member info
  let memberHtml = '';
  const memberBadge = document.getElementById('pos-member-badge');
  if (posMemberId && memberBadge.style.display !== 'none') {
    memberHtml = `
      <div style="margin-bottom: 12px; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 10px; color: #065F46; line-height: 1.4;">
        <i class="fa-solid fa-user-tag"></i> <strong>Thành viên:</strong> ${memberBadge.querySelector('strong').textContent.replace('✓', '').trim()}<br>
        <span style="font-size: 11.5px; opacity: .9;">${memberBadge.querySelector('span').textContent}</span>
      </div>
    `;
  }

  // Payment method
  const payMethodText = posPaymentMethod === 'cash' ? '💵 Tiền mặt' : '💳 Ví MoMo';
  const totalAmountText = document.getElementById('pos-total-amount').textContent;

  const finalHtml = `
    ${memberHtml}
    ${snacksHtml}
    <div style="margin-bottom: 12px;">
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--muted); font-weight: 600;">Phương thức TT:</span>
        <span style="font-weight: 700;">${payMethodText}</span>
      </div>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; background: #EEF2FF; padding: 12px; border-radius: 8px; border: 1px solid #C7D2FE;">
      <span style="font-weight: 800; color: var(--blue); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Tổng cộng thanh toán:</span>
      <span style="font-weight: 800; color: var(--blue); font-size: 18px;">${totalAmountText}</span>
    </div>
  `;

  document.getElementById('preview-invoice-content').innerHTML = finalHtml;
  document.getElementById('invoicePreviewModal').classList.add('active');
}

// ─── WORKING CINEMA SELECTION ─────────────────────────────────────────────
function openCinemaSelectModal() {
  const modal = document.getElementById('cinemaSelectModal');
  // Allow closing only if already selected a cinema
  if (workingCinemaId) {
    modal.classList.add('active');
  } else {
    modal.classList.add('active');
  }
}

async function selectWorkingCinema(id, name) {
  try {
    const res = await fetch('../../be/controllers/StaffController.php?action=set_working_cinema', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({cinema_id: id})
    });
    const json = await res.json();
    
    if (json.success) {
      workingCinemaId = id;
      workingCinemaName = name;
      
      // Update topbar & summary panel
      const topbarDisp = document.getElementById('topbar-cinema-display');
      if (topbarDisp) {
        topbarDisp.querySelector('span').textContent = name;
      } else {
        // reload
        window.location.reload();
        return;
      }
      
      document.getElementById('sum-cinema-name').textContent = name;
      document.getElementById('cinemaSelectModal').classList.remove('active');
      
      showToast('Đổi rạp thành công', `Chào mừng bạn tới làm việc tại ${name}!`);
      
      // Fetch stats and load ticket movies
      fetchCinemaStats();
      resetCounterForm();
      loadCounterMovies();
    } else {
      showToast('Thao tác thất bại', json.message, 'error');
    }
  } catch(e) {
    showToast('Lỗi kết nối', 'Không thể kết nối tới máy chủ.', 'error');
  }
}

// ─── TAB NAVIGATION ────────────────────────────────────────────────────────
function switchTab(tabId) {
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
  
  // Find event target button
  const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(btn => btn.textContent.toLowerCase().includes(tabId === 'checkin' ? 'soát' : (tabId === 'booking' ? 'bán vé' : 'bắp nước')));
  if (activeBtn) activeBtn.classList.add('active');
  
  const targetContent = document.getElementById(`content-${tabId}`);
  if (targetContent) targetContent.classList.add('active');
}

// ─── FETCH STATS ──────────────────────────────────────────────────────────
async function fetchCinemaStats() {
  if (!workingCinemaId) return;
  try {
    const res = await fetch('../../be/controllers/StaffController.php?action=get_cinema_stats');
    const json = await res.json();
    
    if (json.success) {
      document.getElementById('stat-revenue').textContent = Number(json.stats.revenue).toLocaleString('vi-VN') + ' ₫';
      document.getElementById('stat-tickets').textContent = json.stats.tickets + ' vé';
      document.getElementById('stat-checkins').textContent = json.stats.checkins + ' lượt';
    }
  } catch(e) { console.error('Error fetching stats:', e); }
}

// ─── TICKET CHECK-IN (TAB 1) ──────────────────────────────────────────────
let activeTicketCode = null;
let activeTicketObj = null;

function printCheckinTicket() {
  if (!activeTicketObj) return;
  const ticket = activeTicketObj;
  
  const showDateFormatted = new Date(ticket.show_date).toLocaleDateString('vi-VN');
  const showTimeFormatted = ticket.start_time.substring(0, 5);
  const seatsStr = ticket.seats.join(', ');
  
  const printWindow = window.open('', '_blank', 'width=350,height=600');
  let html = '<html><head><title>In vé - ' + ticket.booking_code + '</title>';
  html += '<style>';
  html += '@page { size: auto; margin: 0mm; }';
  html += 'body { margin: 0; padding: 15px; font-family: "Courier New", Courier, monospace; }';
  html += '.ticket-stub { width: 260px; font-size: 12px; line-height: 1.4; color: #000; text-align: center; }';
  html += '.dotted-line { border-top: 1px dashed #000; margin: 10px 0; }';
  html += '.movie-title { font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 6px 0; text-align: left; }';
  html += '.detail-row { text-align: left; margin: 4px 0; }';
  html += '.seats-large { font-size: 18px; font-weight: bold; margin: 8px 0; text-align: left; }';
  html += '</style></head><body>';
  html += '<div class="ticket-stub">';
  html += '<div style="font-size: 15px; font-weight: bold; letter-spacing: 1px;">*** MOVIEFLEX ***</div>';
  html += '<div style="font-size: 10px; margin-top: 2px;">VÉ VÀO CỬA PHÒNG CHIẾU</div>';
  html += '<div class="dotted-line"></div>';
  html += '<div class="detail-row"><strong>RẠP:</strong> ' + ticket.cinema_name + '</div>';
  html += '<div class="detail-row"><strong>PHÒNG:</strong> ' + (ticket.hall_name || 'Phòng chiếu 1') + '</div>';
  html += '<div class="dotted-line"></div>';
  html += '<div class="movie-title">' + ticket.title + '</div>';
  html += '<div class="detail-row"><strong>NGÀY:</strong> ' + showDateFormatted + '</div>';
  html += '<div class="detail-row"><strong>GIỜ CHIẾU:</strong> ' + showTimeFormatted + '</div>';
  html += '<div class="seats-large">GHẾ: ' + seatsStr + '</div>';
  html += '<div class="dotted-line"></div>';
  html += '<div class="detail-row"><strong>MÃ VÉ:</strong> ' + ticket.booking_code + '</div>';
  html += '<div class="detail-row"><strong>KHÁCH HÀNG:</strong> ' + ticket.user_name + '</div>';
  html += '<div class="detail-row"><strong>TRẠNG THÁI:</strong> ĐÃ SOÁT VÉ (Đủ đk)</div>';
  html += '<div class="detail-row" style="font-size: 10px; color: #444; margin-top: 4px;">Thời gian soát: ' + new Date().toLocaleTimeString('vi-VN') + ' ' + new Date().toLocaleDateString('vi-VN') + '</div>';
  html += '<div class="dotted-line"></div>';
  html += '<div style="font-size: 9px; margin-top: 10px;">Chúc quý khách xem phim vui vẻ!</div>';
  html += '<div style="font-size: 8px; margin-top: 4px;">MovieFlex - Đồng hành cùng đam mê điện ảnh</div>';
  html += '</div>';
  html += '<script>';
  html += 'window.onload = function() {';
  html += '  window.print();';
  html += '  setTimeout(function() { window.close(); }, 500);';
  html += '};';
  html += '<\/script></body></html>';
  
  printWindow.document.write(html);
  printWindow.document.close();
}

async function searchTicket() {
  const input = document.getElementById('ticket-search');
  const code = input.value.trim().toUpperCase();
  const card = document.getElementById('ticket-card');
  const warn = document.getElementById('ticket-warning');

  if (!code) {
    showToast('Thiếu mã vé', 'Vui lòng điền mã đặt vé xem phim.', 'warning');
    return;
  }

  card.style.display = 'none';
  warn.style.display = 'none';
  activeTicketCode = null;
  activeTicketObj = null;

  try {
    const res = await fetch(`../../be/controllers/StaffController.php?action=check_ticket&code=${encodeURIComponent(code)}`);
    const json = await res.json();

    if (json.success) {
      const ticket = json.ticket;
      activeTicketCode = ticket.booking_code;
      activeTicketObj = ticket;
      
      // Update UI fields
      document.getElementById('ticket-poster').src = ticket.poster_url || '';
      document.getElementById('ticket-movie-title').textContent = ticket.title;
      document.getElementById('ticket-code-val').textContent = ticket.booking_code;
      document.getElementById('ticket-customer').textContent = `${ticket.user_name} — ĐT: ${ticket.phone || 'N/A'}`;
      document.getElementById('ticket-date').textContent = new Date(ticket.show_date).toLocaleDateString('vi-VN');
      document.getElementById('ticket-time').textContent = ticket.start_time.substring(0, 5);
      document.getElementById('ticket-hall').textContent = ticket.hall_name || 'Phòng chiếu 1';
      document.getElementById('ticket-cinema').textContent = ticket.cinema_name;

      // Badges
      const badge = document.getElementById('ticket-status-badge');
      badge.textContent = ticket.status === 'confirmed' ? 'Đang Chờ' : (ticket.status === 'checked_in' ? 'Đã Soát Vé' : 'Đã Hủy');
      badge.className = `td-badge ${ticket.status}`;

      // Seat chips
      const seatsRow = document.getElementById('ticket-seats-row');
      seatsRow.innerHTML = '';
      ticket.seats.forEach(seat => {
        const span = document.createElement('span');
        span.className = 'td-seat-chip';
        span.textContent = seat;
        seatsRow.appendChild(span);
      });

      card.style.display = 'block';

      // Verify locations and generate action buttons
      const actionsRow = document.getElementById('ticket-actions-row');
      actionsRow.innerHTML = '';
      
      if (!json.is_valid) {
        warn.style.display = 'flex';
        document.getElementById('ticket-warning-text').textContent = json.message;
        
        const disabledBtn = document.createElement('button');
        disabledBtn.className = 'btn-approve-checkin';
        disabledBtn.disabled = true;
        disabledBtn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Thao tác bị chặn';
        actionsRow.appendChild(disabledBtn);
      } else if (ticket.status === 'confirmed') {
        const approveBtn = document.createElement('button');
        approveBtn.className = 'btn-approve-checkin';
        approveBtn.id = 'btn-do-checkin';
        approveBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Xác nhận vào phòng chiếu';
        approveBtn.onclick = confirmCheckin;
        actionsRow.appendChild(approveBtn);
      } else if (ticket.status === 'checked_in') {
        const printBtn = document.createElement('button');
        printBtn.className = 'btn-print-checkin-ticket';
        printBtn.style.cssText = 'width:100%; height:46px; background:linear-gradient(135deg, #10B981, #059669); color:#fff; border:none; border-radius:10px; font-size:14.5px; font-weight:700; cursor:pointer; font-family:inherit; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 12px rgba(16, 185, 129, 0.25); transition: opacity .15s;';
        printBtn.innerHTML = '<i class="fa-solid fa-print"></i> In vé xem phim cho khách';
        printBtn.onclick = printCheckinTicket;
        actionsRow.appendChild(printBtn);
      } else {
        const disabledBtn = document.createElement('button');
        disabledBtn.className = 'btn-approve-checkin';
        disabledBtn.disabled = true;
        disabledBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Vé đã bị hủy';
        actionsRow.appendChild(disabledBtn);
      }
      
    } else {
      showToast('Không tìm thấy', json.message, 'error');
    }
  } catch(e) {
    showToast('Lỗi hệ thống', 'Không thể tra cứu thông tin vé.', 'error');
  }
}

async function confirmCheckin() {
  if (!activeTicketCode) return;
  const btn = document.getElementById('btn-do-checkin');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';
  }

  try {
    const res = await fetch('../../be/controllers/StaffController.php?action=do_checkin', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({code: activeTicketCode})
    });
    const json = await res.json();

    if (json.success) {
      showToast('Soát vé thành công', json.message);
      // Refresh ticket card
      await searchTicket();
      // Refresh Stats
      fetchCinemaStats();
    } else {
      showToast('Lỗi soát vé', json.message, 'error');
      if (btn) btn.disabled = false;
    }
  } catch(e) {
    showToast('Lỗi kết nối', 'Không thể hoàn thành soát vé.', 'error');
    if (btn) btn.disabled = false;
  } finally {
    if (btn && activeTicketObj && activeTicketObj.status === 'confirmed') {
      btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Xác nhận vào phòng chiếu';
    }
  }
}

// ─── DIRECT COUNTER TICKET SALES (TAB 2) ──────────────────────────────────
async function loadCounterMovies() {
  const list = document.getElementById('c-movies-list');
  list.innerHTML = '<div style="color:var(--muted); padding:20px 0; grid-column:1/-1; text-align:center">Đang tải phim chi nhánh...</div>';

  try {
    const res = await fetch('../../be/controllers/StaffController.php?action=get_counter_movies');
    const json = await res.json();

    if (json.success) {
      list.innerHTML = '';
      if (json.data.length === 0) {
        list.innerHTML = '<div style="color:var(--muted); padding:20px 0; grid-column:1/-1; text-align:center">Rạp hiện chưa xếp suất chiếu nào hôm nay hoặc tương lai.</div>';
        return;
      }
      json.data.forEach(m => {
        const card = document.createElement('div');
        card.className = 'c-movie-card';
        card.id = `c-movie-card-${m.id}`;
        card.onclick = () => selectCounterMovie(m.id, m.title);
        card.innerHTML = `
          <img class="c-movie-poster" src="${m.poster_url || ''}" alt="">
          <div class="c-movie-title" title="${m.title}">${m.title}</div>
        `;
        list.appendChild(card);
      });
    }
  } catch(e) { console.error('Error loading counter movies:', e); }
}

async function selectCounterMovie(id, title) {
  // Toggle selection
  document.querySelectorAll('.c-movie-card').forEach(c => c.classList.remove('selected'));
  const card = document.getElementById(`c-movie-card-${id}`);
  if (card) card.classList.add('selected');

  activeMovieId = id;
  document.getElementById('sum-movie-name').textContent = title;
  
  // Hide steps
  document.getElementById('c-step-2').style.display = 'none';
  document.getElementById('c-step-3').style.display = 'none';
  
  // Reset selected seats & showtimes
  activeShowtimeId = null;
  activeBasePrice = 0;
  counterSelectedSeats = [];
  document.getElementById('sum-showtime').textContent = '—';
  updateCounterSummary();

  const list = document.getElementById('c-showtimes-list');
  list.innerHTML = '<div style="color:var(--muted)">Đang tải suất chiếu...</div>';
  document.getElementById('c-step-2').style.display = 'block';

  try {
    const res = await fetch(`../../be/controllers/StaffController.php?action=get_movie_showtimes&movie_id=${id}`);
    const json = await res.json();

    if (json.success) {
      list.innerHTML = '';
      if (json.data.length === 0) {
        list.innerHTML = '<div style="color:var(--red); font-weight:600">Phim này hiện không có suất chiếu nào hoạt động ở rạp của bạn.</div>';
        return;
      }
      json.data.forEach(s => {
        const item = document.createElement('div');
        item.className = 'c-showtime-item';
        item.id = `c-showtime-item-${s.id}`;
        item.onclick = () => selectCounterShowtime(s.id, s.show_date, s.start_time, s.hall_name);
        
        const dateStr = new Date(s.show_date).toLocaleDateString('vi-VN', {day:'2-digit', month:'2-digit'});
        item.innerHTML = `
          <span class="c-showtime-time">${s.start_time.substring(0,5)}</span>
          <span class="c-showtime-date">${dateStr}</span>
          <span class="c-showtime-hall">${s.hall_name || 'Phòng 1'} (${s.format})</span>
        `;
        list.appendChild(item);
      });
    }
  } catch(e) { console.error('Error loading movie showtimes:', e); }
}

async function selectCounterShowtime(id, date, start_time, hall_name) {
  document.querySelectorAll('.c-showtime-item').forEach(i => i.classList.remove('selected'));
  const item = document.getElementById(`c-showtime-item-${id}`);
  if (item) item.classList.add('selected');

  activeShowtimeId = id;
  const dateStr = new Date(date).toLocaleDateString('vi-VN');
  document.getElementById('sum-showtime').textContent = `${start_time.substring(0,5)} - ${dateStr} (${hall_name || 'Phòng 1'})`;

  document.getElementById('c-step-3').style.display = 'none';
  counterSelectedSeats = [];
  updateCounterSummary();

  const map = document.getElementById('c-seat-map');
  map.innerHTML = '<div style="color:var(--muted)">Đang tải sơ đồ phòng chiếu...</div>';
  document.getElementById('c-step-3').style.display = 'block';

  try {
    const res = await fetch(`../../be/controllers/StaffController.php?action=get_showtime_seats&showtime_id=${id}`);
    const json = await res.json();

    if (json.success) {
      activeBasePrice = parseInt(json.price);
      renderSeatMap(json.booked_seats);
    }
  } catch(e) { console.error('Error loading showtime seats:', e); }
}

function renderSeatMap(bookedSeats) {
  const map = document.getElementById('c-seat-map');
  map.innerHTML = '';

  const rows = ['A','B','C','D','E','F','G','H','I','J'];
  const cols = 10;
  const vipRows = ['E','F','G'];

  rows.forEach(rowLetter => {
    const isVip = vipRows.includes(rowLetter);
    const isLastRow = (rowLetter === 'J');
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'c-seat-row';
    
    const leftLabel = document.createElement('div');
    leftLabel.className = 'c-row-label';
    leftLabel.textContent = rowLetter;
    rowDiv.appendChild(leftLabel);

    let col = 1;
    while (col <= cols) {
      const seatKey = `${rowLetter}-${String(col).padStart(2, '0')}`;
      const isTaken = bookedSeats.includes(seatKey);

      // Sweetbox couple seats (Row J, 9-10)
      if (isLastRow && col === 9) {
        const sk2 = `J-10`;
        const takenSweet = isTaken || bookedSeats.includes(sk2);
        
        const seatDiv = document.createElement('div');
        seatDiv.className = `c-seat sweet ${takenSweet ? 'taken' : ''}`;
        seatDiv.id = `c-seat-${seatKey}`;
        seatDiv.textContent = '🛋 J9-10';
        
        if (!takenSweet) {
          seatDiv.onclick = () => toggleCounterSeat(seatDiv, seatKey, sk2, activeBasePrice * 2);
        }
        rowDiv.appendChild(seatDiv);
        col += 2;
        continue;
      }

      if (col === 5) {
        const aisle = document.createElement('div');
        aisle.className = 'c-aisle';
        rowDiv.appendChild(aisle);
      }

      const seatPrice = isVip ? Math.round(activeBasePrice * 1.3) : activeBasePrice;
      const seatDiv = document.createElement('div');
      seatDiv.className = `c-seat ${isVip ? 'vip' : ''} ${isTaken ? 'taken' : ''}`;
      seatDiv.id = `c-seat-${seatKey}`;
      seatDiv.textContent = col;
      
      if (!isTaken) {
        seatDiv.onclick = () => toggleCounterSeat(seatDiv, seatKey, null, seatPrice);
      }
      rowDiv.appendChild(seatDiv);
      col++;
    }

    const rightLabel = document.createElement('div');
    rightLabel.className = 'c-row-label';
    rightLabel.textContent = rowLetter;
    rowDiv.appendChild(rightLabel);

    map.appendChild(rowDiv);
  });
}

function toggleCounterSeat(el, seat, seat2, price) {
  const idx = counterSelectedSeats.findIndex(s => s.seat === seat);
  if (idx > -1) {
    counterSelectedSeats.splice(idx, 1);
    el.classList.remove('selected');
  } else {
    if (counterSelectedSeats.length >= 8) {
      showToast('Giới hạn đặt vé', 'Tối đa bán 8 ghế cho mỗi lần đặt tại quầy.', 'warning');
      return;
    }
    counterSelectedSeats.push({seat, seat2, price, booking_code: generateTicketCode()});
    el.classList.add('selected');
  }
  updateCounterSummary();
}

function changeMiniSnackQty(snackId, delta) {
  const current = counterSelectedSnacks[snackId] || 0;
  let next = current + delta;
  next = Math.max(0, next);
  
  counterSelectedSnacks[snackId] = next;
  document.getElementById(`ms-qty-${snackId}`).textContent = next;
  updateCounterSummary();
}

function setPaymentMethod(method) {
  counterPaymentMethod = method;
  document.getElementById('p-btn-cash').classList.toggle('selected', method === 'cash');
  document.getElementById('p-btn-momo').classList.toggle('selected', method === 'momo');
}

function updateCounterSummary() {
  const chosenRow = document.getElementById('sum-chosen-seats');
  
  if (counterSelectedSeats.length === 0) {
    chosenRow.innerHTML = '<span style="color:var(--muted); font-size:12px">Chưa chọn ghế</span>';
  } else {
    chosenRow.innerHTML = '';
    counterSelectedSeats.forEach(s => {
      const tag = document.createElement('span');
      tag.className = 'sum-seat-tag';
      tag.textContent = s.seat2 ? `${s.seat}+${s.seat2}` : s.seat;
      chosenRow.appendChild(tag);
    });
  }

  // Calculate sum totals
  let ticketsTotal = counterSelectedSeats.reduce((a, s) => a + s.price, 0);
  
  // Calculate snacks total
  let snacksTotal = 0;
  document.querySelectorAll('.mini-snack-item').forEach(item => {
    const price = parseInt(item.dataset.price);
    const id = parseInt(item.dataset.id);
    const qty = counterSelectedSnacks[id] || 0;
    snacksTotal += price * qty;
  });

  const total = ticketsTotal + snacksTotal;
  document.getElementById('c-total-amount').textContent = total.toLocaleString('vi-VN') + ' ₫';
  
  // Enable submit button
  const submitBtn = document.getElementById('btn-submit-booking');
  submitBtn.disabled = (counterSelectedSeats.length === 0 && snacksTotal === 0) || !activeShowtimeId;
}

async function searchMember() {
  const val = document.getElementById('c-member-search').value.trim();
  const badge = document.getElementById('c-member-badge');
  counterMemberId = null;
  badge.style.display = 'none';

  if (!val) {
    showToast('Thiếu thông tin', 'Nhập SĐT hoặc Email thành viên.', 'warning');
    return;
  }

  try {
    const res = await fetch(`../../be/controllers/StaffController.php?action=search_customer&query=${encodeURIComponent(val)}`);
    const json = await res.json();

    if (json.success) {
      counterMemberId = json.data.id;
      badge.innerHTML = `
        <strong><i class="fa-solid fa-circle-check"></i> ${json.data.full_name}</strong>
        <span>Hạng: ${json.data.member_tier} | Điểm tích lũy: ${json.data.loyalty_points}</span>
      `;
      badge.style.display = 'flex';
      showToast('Đã nhận diện thành viên', `Đã áp dụng tài khoản tích điểm cho ${json.data.full_name}`);
    } else {
      showToast('Không tìm thấy', json.message, 'error');
    }
  } catch(e) { console.error(e); }
}

async function submitCounterBooking() {
  const btn = document.getElementById('btn-submit-booking');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';

  // Pack snacks
  const snacksArr = [];
  document.querySelectorAll('.mini-snack-item').forEach(item => {
    const id = parseInt(item.dataset.id);
    const qty = counterSelectedSnacks[id] || 0;
    if (qty > 0) {
      snacksArr.push({id, qty});
    }
  });

  // Pack seats with pre-generated codes
  const seatsArr = counterSelectedSeats.map(s => ({
    seat: s.seat2 ? `${s.seat},${s.seat2}` : s.seat,
    booking_code: s.booking_code
  }));

  const payload = {
    showtime_id: activeShowtimeId,
    seats: seatsArr,
    member_id: counterMemberId,
    payment_method: counterPaymentMethod,
    snacks: snacksArr
  };

  try {
    const res = await fetch('../../be/controllers/StaffController.php?action=create_counter_booking', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
    const json = await res.json();

    if (json.success) {
      showToast('Lập đơn thành công', `Đơn hàng ${json.booking_code} đã thanh toán thành công!`);
      setTimeout(() => {
        window.location.href = `booking-confirm.php?code=${json.booking_code}`;
      }, 1500);
    } else {
      showToast('Lập đơn thất bại', json.message, 'error');
      btn.disabled = false;
    }
  } catch(e) {
    showToast('Lỗi kết nối', 'Không thể gửi đơn đặt vé về máy chủ.', 'error');
    btn.disabled = false;
  } finally {
    btn.innerHTML = '<i class="fa-solid fa-print"></i> Xác nhận & In vé';
  }
}

function resetCounterForm() {
  activeMovieId = null;
  activeShowtimeId = null;
  activeBasePrice = 0;
  counterSelectedSeats = [];
  counterSelectedSnacks = {};
  counterMemberId = null;

  document.getElementById('sum-movie-name').textContent = '—';
  document.getElementById('sum-showtime').textContent = '—';
  document.getElementById('c-member-search').value = '';
  document.getElementById('c-member-badge').style.display = 'none';
  
  document.querySelectorAll('.mini-snack-qty').forEach(q => q.textContent = '0');
  document.querySelectorAll('.c-movie-card').forEach(c => c.classList.remove('selected'));
  
  document.getElementById('c-step-2').style.display = 'none';
  document.getElementById('c-step-3').style.display = 'none';
  
  updateCounterSummary();
  setPaymentMethod('cash');
}


// ─── CONCESSION SALES / POS TAB (TAB 3) ───────────────────────────────────
function addPosSnack(id, name, price) {
  const item = posSnacksInCart[id] || {name, price, qty: 0};
  item.qty += 1;
  posSnacksInCart[id] = item;
  
  // Show badges
  const badge = document.getElementById(`pos-badge-${id}`);
  badge.textContent = item.qty;
  badge.style.display = 'flex';
  
  document.getElementById(`pos-card-${id}`).classList.add('in-cart');
  updatePosSummary();
}

function changePosQty(id, delta) {
  if (!posSnacksInCart[id]) return;
  posSnacksInCart[id].qty += delta;
  
  const card = document.getElementById(`pos-card-${id}`);
  const badge = document.getElementById(`pos-badge-${id}`);

  if (posSnacksInCart[id].qty <= 0) {
    delete posSnacksInCart[id];
    if (card) card.classList.remove('in-cart');
    if (badge) badge.style.display = 'none';
  } else {
    if (badge) badge.textContent = posSnacksInCart[id].qty;
  }
  updatePosSummary();
}

function setPosPaymentMethod(method) {
  posPaymentMethod = method;
  document.getElementById('pos-btn-cash').classList.toggle('selected', method === 'cash');
  document.getElementById('pos-btn-momo').classList.toggle('selected', method === 'momo');
}

function updatePosSummary() {
  const cartList = document.getElementById('pos-cart-list');
  const ids = Object.keys(posSnacksInCart);
  
  if (ids.length === 0) {
    cartList.innerHTML = '<span style="color:var(--muted); font-size:12.5px; text-align:center; display:block; padding:20px 0">Giỏ hàng trống</span>';
    document.getElementById('pos-total-amount').textContent = '0 ₫';
    document.getElementById('btn-submit-pos').disabled = true;
    return;
  }

  cartList.innerHTML = '';
  let sumTotal = 0;
  
  ids.forEach(sid => {
    const item = posSnacksInCart[sid];
    const itemTotal = item.price * item.qty;
    sumTotal += itemTotal;

    const row = document.createElement('div');
    row.className = 'mini-snack-item';
    row.style.margin = '4px 0';
    row.innerHTML = `
      <div class="mini-snack-info">
        <div class="mini-snack-name">${item.name}</div>
        <div class="mini-snack-price">${item.price.toLocaleString('vi-VN')}₫ x ${item.qty}</div>
      </div>
      <div class="mini-snack-ctrl">
        <button class="mini-snack-btn" onclick="changePosQty(${sid}, -1)">−</button>
        <span class="mini-snack-qty">${item.qty}</span>
        <button class="mini-snack-btn" onclick="changePosQty(${sid}, 1)">+</button>
      </div>
    `;
    cartList.appendChild(row);
  });

  document.getElementById('pos-total-amount').textContent = sumTotal.toLocaleString('vi-VN') + ' ₫';
  document.getElementById('btn-submit-pos').disabled = false;
}

async function searchPosMember() {
  const val = document.getElementById('pos-member-search').value.trim();
  const badge = document.getElementById('pos-member-badge');
  posMemberId = null;
  badge.style.display = 'none';

  if (!val) {
    showToast('Thiếu thông tin', 'Nhập SĐT hoặc Email thành viên.', 'warning');
    return;
  }

  try {
    const res = await fetch(`../../be/controllers/StaffController.php?action=search_customer&query=${encodeURIComponent(val)}`);
    const json = await res.json();

    if (json.success) {
      posMemberId = json.data.id;
      badge.innerHTML = `
        <strong><i class="fa-solid fa-circle-check"></i> ${json.data.full_name}</strong>
        <span>Hạng: ${json.data.member_tier} | Điểm: ${json.data.loyalty_points}</span>
      `;
      badge.style.display = 'flex';
      showToast('Đã nhận diện thành viên', `Đã áp dụng tài khoản tích điểm cho ${json.data.full_name}`);
    } else {
      showToast('Không tìm thấy', json.message, 'error');
    }
  } catch(e) { console.error(e); }
}

async function submitPosSnackOrder() {
  const btn = document.getElementById('btn-submit-pos');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';

  // Format snacks list
  const snacksArr = Object.keys(posSnacksInCart).map(sid => {
    return {
      id: parseInt(sid),
      qty: posSnacksInCart[sid].qty
    };
  });

  // To fulfill DB schema constraints, concession-only orders will be mapped 
  // to a dummy counter booking with 0 tickets but containing snacks total
  // associated with the first active showtime at the cinema.
  // Let's find any showtime in this cinema to hook it up.
  try {
    // 1. Fetch cinema active showtimes to get a valid showtime_id
    const showRes = await fetch('../../be/controllers/StaffController.php?action=get_counter_movies');
    const showJson = await showRes.json();
    let showtime_id = 0;
    
    if (showJson.success && showJson.data.length > 0) {
      // Find showtimes for first movie
      const stRes = await fetch(`../../be/controllers/StaffController.php?action=get_movie_showtimes&movie_id=${showJson.data[0].id}`);
      const stJson = await stRes.json();
      if (stJson.success && stJson.data.length > 0) {
        showtime_id = stJson.data[0].id;
      }
    }

    if (!showtime_id) {
      showToast('Không tìm thấy suất chiếu', 'Cần có ít nhất 1 suất chiếu đang hoạt động tại rạp để thực hiện bán bắp nước lẻ.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-receipt"></i> Thanh toán & In hóa đơn';
      return;
    }

    const payload = {
      showtime_id: showtime_id,
      seats: [],
      member_id: posMemberId,
      payment_method: posPaymentMethod,
      snacks: snacksArr
    };

    const res = await fetch('../../be/controllers/StaffController.php?action=create_counter_booking', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
    const json = await res.json();

    if (json.success) {
      showToast('Bán bắp nước thành công', `Mã đơn hàng lẻ ${json.booking_code} đã hoàn tất thanh toán ${Number(json.total_amount).toLocaleString('vi-VN')}₫!`);
      resetPosForm();
      fetchCinemaStats();
    } else {
      showToast('Thao tác thất bại', json.message, 'error');
      btn.disabled = false;
    }
  } catch(e) {
    showToast('Lỗi kết nối', 'Không thể hoàn tất bán hàng lẻ bắp nước.', 'error');
    btn.disabled = false;
  } finally {
    btn.innerHTML = '<i class="fa-solid fa-receipt"></i> Thanh toán & In hóa đơn';
  }
}

function resetPosForm() {
  posSnacksInCart = {};
  posMemberId = null;

  document.querySelectorAll('.pos-badge').forEach(b => b.style.display = 'none');
  document.querySelectorAll('.pos-card').forEach(c => c.classList.remove('in-cart'));
  document.getElementById('pos-member-search').value = '';
  document.getElementById('pos-member-badge').style.display = 'none';

  updatePosSummary();
  setPosPaymentMethod('cash');
}

// ─── AUTHENTICATION LOGOUT ────────────────────────────────────────────────
async function doLogout() {
  const ok = await mfConfirm({
    title: 'Kết thúc ca trực',
    desc: 'Bạn có chắc chắn muốn đăng xuất và kết thúc ca làm việc không? Hãy đảm bảo đã hoàn thành các giao dịch đang xử lý.',
    type: 'warning',
    confirmText: 'Kết thúc ca',
    confirmIcon: 'fa-arrow-right-from-bracket',
    cancelText: 'Tiếp tục làm việc'
  });
  if (!ok) return;
  const fd = new FormData();
  fd.append('action', 'logout');
  try {
    const res = await fetch('../../be/api.php', {method: 'POST', body: fd});
    const json = await res.json();
    window.location.href = json.redirect || 'login.php';
  } catch(e) { window.location.href = 'login.php'; }
}

</script>
<script src="../assets/js/script.js"></script>
</body>
</html>

