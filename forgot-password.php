<?php
session_start();
require_once 'db.php';

// Auto create table if not exists
$pdo->exec("
  CREATE TABLE IF NOT EXISTS password_resets (
      id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(150) NOT NULL,
      token VARCHAR(10) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      expires_at DATETIME NOT NULL
  ) ENGINE=InnoDB;
");

$step = $_SESSION['pwd_step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // STEP 1: Send OTP to Email
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Delete old OTPs for this email
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            
            // Insert new OTP
            $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")->execute([$email, $otp, $expires]);

            // ========================================================
            // CHẾ ĐỘ LOCAL (KHÔNG CÓ MẠNG / KHÔNG CẦN CẤU HÌNH SMTP)
            // ========================================================
            // Thay vì gửi email thật (cần mạng và cấu hình phức tạp), 
            // ta sẽ hiển thị trực tiếp mã OTP lên màn hình để dễ test.
            
            $_SESSION['pwd_step'] = 2;
            $_SESSION['pwd_email'] = $email;
            
            // Hiển thị mã OTP thẳng lên giao diện (Dành cho báo cáo/demo local)
            $success = "Đã giả lập gửi Email! (DEV MODE) Mã OTP của bạn là: " . $otp;
            $step = 2;
        } else {
            $error = "Không tìm thấy tài khoản nào với địa chỉ email này.";
        }
    }
    
    // STEP 2: Verify OTP
    elseif ($action === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        $email = $_SESSION['pwd_email'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$email, $otp]);
        
        if ($stmt->fetch()) {
            $_SESSION['pwd_step'] = 3;
            $_SESSION['pwd_otp'] = $otp; // verified
            $step = 3;
        } else {
            $error = "Mã OTP không chính xác hoặc đã hết hạn.";
        }
    }
    
    // STEP 3: Reset Password
    elseif ($action === 'reset_pwd') {
        $pwd1 = $_POST['new_pwd'] ?? '';
        $pwd2 = $_POST['confirm_pwd'] ?? '';
        $email = $_SESSION['pwd_email'] ?? '';
        
        if (strlen($pwd1) < 6) {
            $error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
        } elseif ($pwd1 !== $pwd2) {
            $error = "Mật khẩu xác nhận không khớp.";
        } else {
            $hash = password_hash($pwd1, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?")->execute([$hash, $email]);
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            
            // Clear session
            unset($_SESSION['pwd_step'], $_SESSION['pwd_email'], $_SESSION['pwd_otp']);
            
            $_SESSION['login_success'] = "Đổi mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.";
            header("Location: login.php");
            exit;
        }
    }
}
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
.btn-submit:hover{background:#1D4ED8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.2)}
.alert{padding:12px 16px;border-radius:8px;font-size:13.5px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-err{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}
.alert-succ{background:#F0FDF4;color:#16A34A;border:1px solid #BBF7D0}
.auth-footer{margin-top:24px;text-align:center;font-size:14px;color:var(--muted)}
.auth-footer a{color:var(--blue);text-decoration:none;font-weight:600;transition:color .2s}
.auth-footer a:hover{color:#1D4ED8}
</style>
</head>
<body>

<div class="auth-box">
  <a href="home.php" class="logo">
    <div class="logo-icon"><i class="fa-solid fa-clapperboard"></i></div>
    <div class="logo-name">MovieFlex</div>
  </a>

  <?php if($error): ?>
    <div class="alert alert-err"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if($success): ?>
    <div class="alert alert-succ"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if($step === 1): ?>
    <h1 class="auth-title">Quên mật khẩu</h1>
    <p class="auth-desc">Nhập địa chỉ email đăng ký tài khoản của bạn. Chúng tôi sẽ gửi một mã OTP gồm 6 chữ số để đặt lại mật khẩu.</p>
    <form method="POST">
      <input type="hidden" name="action" value="send_otp">
      <div class="frm-grp">
        <label class="frm-lbl">Địa chỉ Email</label>
        <input type="email" name="email" class="frm-inp" placeholder="VD: nguyenvanan@gmail.com" required>
      </div>
      <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Gửi mã xác nhận</button>
    </form>
  
  <?php elseif($step === 2): ?>
    <h1 class="auth-title">Nhập mã OTP</h1>
    <p class="auth-desc">Mã OTP gồm 6 chữ số đã được "gửi giả lập" tới <b><?= htmlspecialchars($_SESSION['pwd_email']) ?></b>.<br><i>(Hãy nhìn thông báo màu xanh ở trên để lấy mã OTP nhé)</i></p>
    <form method="POST">
      <input type="hidden" name="action" value="verify_otp">
      <div class="frm-grp">
        <label class="frm-lbl">Mã OTP (6 chữ số)</label>
        <input type="text" name="otp" class="frm-inp" placeholder="VD: 123456" maxlength="6" required style="letter-spacing:4px; font-size:18px; font-weight:700; text-align:center">
      </div>
      <button type="submit" class="btn-submit"><i class="fa-solid fa-check-circle"></i> Xác thực mã</button>
    </form>
    <div class="auth-footer" style="margin-top:16px">
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="send_otp">
        <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['pwd_email']) ?>">
        Chưa nhận được mã? <button type="submit" style="background:none;border:none;color:var(--blue);font-weight:600;cursor:pointer;font-family:inherit;font-size:14px">Gửi lại</button>
      </form>
    </div>

  <?php elseif($step === 3): ?>
    <h1 class="auth-title">Đặt mật khẩu mới</h1>
    <p class="auth-desc">Vui lòng tạo một mật khẩu mới cho tài khoản của bạn.</p>
    <form method="POST">
      <input type="hidden" name="action" value="reset_pwd">
      <div class="frm-grp">
        <label class="frm-lbl">Mật khẩu mới</label>
        <input type="password" name="new_pwd" class="frm-inp" placeholder="Ít nhất 6 ký tự" required minlength="6">
      </div>
      <div class="frm-grp">
        <label class="frm-lbl">Xác nhận mật khẩu</label>
        <input type="password" name="confirm_pwd" class="frm-inp" placeholder="Nhập lại mật khẩu mới" required minlength="6">
      </div>
      <button type="submit" class="btn-submit"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</button>
    </form>
  <?php endif; ?>

  <div class="auth-footer">
    <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Quay lại đăng nhập</a>
  </div>
</div>

</body>
</html>
