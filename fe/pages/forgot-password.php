<?php
/**
 * fe/pages/forgot-password.php
 *
 * Pure presentation layer. No DB access, no business logic.
 * All actions are sent via JS fetch() to /be/api.php.
 *
 * Step state is stored in $_SESSION by AuthController on the BE side.
 * This page reads $_SESSION['pwd_step'] only to decide which step UI to render.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step = $_SESSION['pwd_step'] ?? 1;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quên mật khẩu - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--blue:#2563EB;--bg:#F8FAFC;--card:#fff;--text:#0F172A;--muted:#64748B;--border:#E2E8F0;--r:16px}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.auth-box{width:100%;max-width:420px;background:var(--card);border-radius:var(--r);box-shadow:0 10px 40px -10px rgba(0,0,0,.08);padding:32px 40px;position:relative;overflow:hidden}
.auth-box::before{content:'';position:absolute;top:0;left:0;width:100%;height:4px;background:var(--blue)}
.logo{display:flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;margin-bottom:24px}
.logo-icon{width:36px;height:36px;background:var(--blue);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px}
.logo-name{font-size:20px;font-weight:800;color:var(--blue)}
.auth-title{font-size:22px;font-weight:800;text-align:center;margin-bottom:8px}
.auth-desc{font-size:14px;color:var(--muted);text-align:center;margin-bottom:28px;line-height:1.5}
.frm-grp{margin-bottom:18px}
.frm-lbl{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:#334155}
.frm-inp{width:100%;height:46px;border:1.5px solid var(--border);border-radius:10px;padding:0 14px;font-size:14px;font-family:inherit;transition:all .2s;background:#F8FAFC}
.frm-inp:focus{border-color:var(--blue);background:#fff;outline:none;box-shadow:0 0 0 4px rgba(37,99,235,.1)}
.btn-submit{width:100%;height:46px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-submit:hover:not(:disabled){background:#1D4ED8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.2)}
.btn-submit:disabled{opacity:.5;cursor:not-allowed}
.btn-submit .spin{width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:sp .65s linear infinite;display:none}
.btn-submit.loading .spin{display:block}
.btn-submit.loading .bt{display:none}
@keyframes sp{to{transform:rotate(360deg)}}
.alert{padding:12px 16px;border-radius:8px;font-size:13.5px;font-weight:500;margin-bottom:20px;display:none;align-items:center;gap:8px}
.alert.show{display:flex}
.alert-err{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.alert-succ{background:#F0FDF4;color:#16A34A;border:1px solid #BBF7D0}
.auth-footer{margin-top:24px;text-align:center;font-size:14px;color:var(--muted)}
.auth-footer a{color:var(--blue);text-decoration:none;font-weight:600;transition:color .2s}
.auth-footer a:hover{color:#1D4ED8}
.btn-resend{background:none;border:none;color:var(--blue);font-weight:600;cursor:pointer;font-family:inherit;font-size:14px;padding:0}
.btn-resend:hover{text-decoration:underline}
</style>
</head>
<body>

<div class="auth-box">
  <a href="home.php" class="logo">
    <div class="logo-icon"><i class="fa-solid fa-clapperboard"></i></div>
    <div class="logo-name">MovieFlex</div>
  </a>

  <div id="msg" class="alert"></div>

  <!-- STEP 1: Enter email -->
  <div id="s1" style="display:<?= $step === 1 ? 'block' : 'none' ?>">
    <h1 class="auth-title">Quên mật khẩu</h1>
    <p class="auth-desc">Nhập địa chỉ email đăng ký tài khoản. Chúng tôi sẽ gửi mã OTP gồm 6 chữ số để đặt lại mật khẩu.</p>
    <div class="frm-grp">
      <label class="frm-lbl">Địa chỉ Email</label>
      <input type="email" id="inp-email" class="frm-inp" placeholder="VD: nguyenvanan@gmail.com">
    </div>
    <button class="btn-submit" id="btn-s1">
      <div class="spin"></div>
      <span class="bt"><i class="fa-solid fa-paper-plane"></i> Gửi mã xác nhận</span>
    </button>
  </div>

  <!-- STEP 2: Enter OTP -->
  <div id="s2" style="display:<?= $step === 2 ? 'block' : 'none' ?>">
    <h1 class="auth-title">Nhập mã OTP</h1>
    <p class="auth-desc" id="otp-desc">Mã OTP gồm 6 chữ số đã được gửi tới email của bạn.</p>
    <div class="frm-grp">
      <label class="frm-lbl">Mã OTP (6 chữ số)</label>
      <input type="text" id="inp-otp" class="frm-inp" placeholder="123456" maxlength="6" style="letter-spacing:4px;font-size:18px;font-weight:700;text-align:center">
    </div>
    <button class="btn-submit" id="btn-s2">
      <div class="spin"></div>
      <span class="bt"><i class="fa-solid fa-check-circle"></i> Xác thực mã</span>
    </button>
    <div style="margin-top:16px;text-align:center;font-size:14px;color:var(--muted)">
      Chưa nhận được mã? <button class="btn-resend" id="btn-resend">Gửi lại</button>
    </div>
  </div>

  <!-- STEP 3: New password -->
  <div id="s3" style="display:<?= $step === 3 ? 'block' : 'none' ?>">
    <h1 class="auth-title">Đặt mật khẩu mới</h1>
    <p class="auth-desc">Vui lòng tạo một mật khẩu mới cho tài khoản của bạn.</p>
    <div class="frm-grp">
      <label class="frm-lbl">Mật khẩu mới</label>
      <input type="password" id="inp-pw1" class="frm-inp" placeholder="Ít nhất 6 ký tự">
    </div>
    <div class="frm-grp">
      <label class="frm-lbl">Xác nhận mật khẩu</label>
      <input type="password" id="inp-pw2" class="frm-inp" placeholder="Nhập lại mật khẩu mới">
    </div>
    <button class="btn-submit" id="btn-s3">
      <div class="spin"></div>
      <span class="bt"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</span>
    </button>
  </div>

  <div class="auth-footer">
    <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Quay lại đăng nhập</a>
  </div>
</div>

<script>
const ENDPOINT = '../../be/api.php';

function showMsg(type, text) {
  const el = document.getElementById('msg');
  el.className = `alert alert-${type} show`;
  el.innerHTML = `<i class="fa-solid ${type === 'err' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i> ${text}`;
}

function hideMsg() {
  document.getElementById('msg').className = 'alert';
}

function setLoad(btnId, on) {
  const b = document.getElementById(btnId);
  b.classList.toggle('loading', on);
  b.disabled = on;
}

function goStep(n) {
  document.getElementById('s1').style.display = n === 1 ? 'block' : 'none';
  document.getElementById('s2').style.display = n === 2 ? 'block' : 'none';
  document.getElementById('s3').style.display = n === 3 ? 'block' : 'none';
  hideMsg();
}

// STEP 1: Send OTP
document.getElementById('btn-s1').addEventListener('click', async () => {
  const email = document.getElementById('inp-email').value.trim();
  if (!email) { showMsg('err', 'Vui lòng nhập địa chỉ email.'); return; }

  setLoad('btn-s1', true);
  hideMsg();

  const fd = new FormData();
  fd.append('action', 'send_otp');
  fd.append('email', email);

  try {
    const r = await fetch(ENDPOINT, { method: 'POST', body: fd });
    const d = await r.json();

    if (d.success) {
      if (d.dev_otp) {
        showMsg('succ', `(DEV MODE) Mã OTP của bạn là: <strong>${d.dev_otp}</strong>`);
      } else {
        showMsg('succ', 'Mã OTP đã được gửi tới email của bạn.');
      }
      document.getElementById('otp-desc').textContent = `Mã OTP gồm 6 chữ số đã được gửi tới ${email}.`;
      setTimeout(() => goStep(2), 1500);
    } else {
      showMsg('err', d.message);
    }
  } catch {
    showMsg('err', 'Lỗi kết nối. Vui lòng thử lại.');
  } finally {
    setLoad('btn-s1', false);
  }
});

// STEP 2: Verify OTP
document.getElementById('btn-s2').addEventListener('click', async () => {
  const otp = document.getElementById('inp-otp').value.trim();
  if (!otp || otp.length !== 6) { showMsg('err', 'Vui lòng nhập đúng 6 chữ số.'); return; }

  setLoad('btn-s2', true);
  hideMsg();

  const fd = new FormData();
  fd.append('action', 'verify_otp');
  fd.append('otp', otp);

  try {
    const r = await fetch(ENDPOINT, { method: 'POST', body: fd });
    const d = await r.json();

    if (d.success) {
      showMsg('succ', 'Xác thực thành công!');
      setTimeout(() => goStep(3), 800);
    } else {
      showMsg('err', d.message);
    }
  } catch {
    showMsg('err', 'Lỗi kết nối. Vui lòng thử lại.');
  } finally {
    setLoad('btn-s2', false);
  }
});

// STEP 2: Resend OTP
document.getElementById('btn-resend').addEventListener('click', () => {
  goStep(1);
});

// STEP 3: Reset password
document.getElementById('btn-s3').addEventListener('click', async () => {
  const pw1 = document.getElementById('inp-pw1').value;
  const pw2 = document.getElementById('inp-pw2').value;

  if (pw1.length < 6) { showMsg('err', 'Mật khẩu mới phải có ít nhất 6 ký tự.'); return; }
  if (pw1 !== pw2)    { showMsg('err', 'Mật khẩu xác nhận không khớp.'); return; }

  setLoad('btn-s3', true);
  hideMsg();

  const fd = new FormData();
  fd.append('action', 'reset_pwd');
  fd.append('new_pwd', pw1);
  fd.append('confirm_pwd', pw2);

  try {
    const r = await fetch(ENDPOINT, { method: 'POST', body: fd });
    const d = await r.json();

    if (d.success) {
      showMsg('succ', d.message);
      setTimeout(() => location.href = 'login.php', 1500);
    } else {
      showMsg('err', d.message);
    }
  } catch {
    showMsg('err', 'Lỗi kết nối. Vui lòng thử lại.');
  } finally {
    setLoad('btn-s3', false);
  }
});
</script>
</body>
</html>
