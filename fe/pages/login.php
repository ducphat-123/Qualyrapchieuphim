<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng nhập - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --blue:#3B82F6;--blue-h:#2563EB;--blue-dark:#1D4ED8;
  --text:#0F172A;--muted:#64748B;--light:#94A3B8;--border:#E2E8F0;
  --red:#EF4444;--green:#10B981;
}
html{margin:0;padding:0;height:100%;overflow:hidden}
body{font-family:'Inter',sans-serif;margin:0;padding:0;display:flex;height:100vh;width:100vw;max-width:100vw;overflow:hidden;position:fixed;top:0;left:0;right:0;bottom:0}
.left{position:relative;flex:1 1 0%;overflow:hidden;background:#050D1A;min-width:0;max-width:100%}
.bg-slides{position:absolute;inset:0;z-index:0}
.bg-slide{position:absolute;inset:0;background-size:cover;background-position:center;opacity:0;transition:opacity 1.5s ease}
.bg-slide.active{opacity:1}
.bg-overlay{position:absolute;inset:0;z-index:1;background:linear-gradient(135deg,rgba(5,13,26,.92) 0%,rgba(5,13,26,.6) 50%,rgba(5,13,26,.82) 100%)}
.particles{position:absolute;inset:0;z-index:2;overflow:hidden}
.dot{position:absolute;width:3px;height:3px;background:rgba(255,255,255,.4);border-radius:50%;animation:float linear infinite}
@keyframes float{0%{transform:translateY(100vh) scale(0);opacity:0}10%{opacity:1}90%{opacity:.6}100%{transform:translateY(-100px) scale(1.5);opacity:0}}
.left-content{position:relative;z-index:3;height:100%;display:flex;flex-direction:column;justify-content:space-between;padding:36px 40px;overflow:hidden}
.brand{display:flex;align-items:center;gap:14px}
.brand-icon{width:48px;height:48px;background:linear-gradient(135deg,#3B82F6,#8B5CF6);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;box-shadow:0 8px 24px rgba(59,130,246,.5)}
.brand-name{font-size:24px;font-weight:800;color:#fff;letter-spacing:-.5px}
.brand-name span{color:#60A5FA}
.hero-copy{margin-bottom:auto;padding-top:40px}
.hero-label{display:inline-flex;align-items:center;gap:8px;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);color:#93C5FD;font-size:12px;font-weight:700;padding:6px 14px;border-radius:20px;letter-spacing:.6px;margin-bottom:24px;backdrop-filter:blur(8px)}
.hero-title{font-size:40px;font-weight:800;color:#fff;line-height:1.1;margin-bottom:14px;letter-spacing:-1.5px}
.hero-title span{background:linear-gradient(90deg,#60A5FA,#A78BFA,#F472B6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-desc{font-size:14px;color:rgba(255,255,255,.6);line-height:1.6;max-width:340px}
.movie-strip{display:flex;gap:10px;margin-bottom:0;overflow:hidden}
.strip-card{border-radius:10px;overflow:hidden;flex-shrink:0;position:relative;box-shadow:0 6px 18px rgba(0,0,0,.5);transition:transform .3s;cursor:pointer}
.strip-card:hover{transform:translateY(-3px) scale(1.02)}
.strip-card img{width:62px;height:90px;object-fit:cover;display:block}
.strip-card .rating{position:absolute;bottom:6px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.75);backdrop-filter:blur(4px);color:#FBBF24;font-size:11px;font-weight:700;padding:2px 7px;border-radius:6px;white-space:nowrap}
.stats-bar{display:flex;gap:0;border-top:1px solid rgba(255,255,255,.1);padding-top:32px}
.stat{flex:1;text-align:center}
.stat:not(:last-child){border-right:1px solid rgba(255,255,255,.1)}
.stat-val{font-size:28px;font-weight:800;color:#fff;letter-spacing:-1px}
.stat-val span{font-size:14px;background:linear-gradient(90deg,#60A5FA,#A78BFA);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.stat-lbl{font-size:12px;color:rgba(255,255,255,.45);margin-top:3px;font-weight:500}
.right{flex:0 0 400px;width:400px;min-width:0;background:linear-gradient(160deg,#FFFFFF 0%,#F0F4FF 100%);display:flex;flex-direction:column;justify-content:center;padding:40px 36px;overflow-y:auto;overflow-x:hidden;height:100vh;position:relative}
.right::before{content:'';position:absolute;bottom:-60px;right:-60px;width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(99,102,241,.08) 0%,transparent 70%);pointer-events:none}
.right::after{content:'';position:absolute;top:-40px;left:-40px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(59,130,246,.07) 0%,transparent 70%);pointer-events:none}
.form-header{margin-bottom:28px;position:relative;z-index:1}
.form-header h2{font-size:26px;font-weight:800;color:#0F172A;letter-spacing:-.5px;margin-bottom:6px}
.form-header p{font-size:13px;color:#64748B;line-height:1.6}
.alert{display:none;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:16px;animation:slideIn .3s ease;position:relative;z-index:1}
.alert.show{display:flex}
.alert-error{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA}
.alert-success{background:#F0FDF4;color:#166534;border:1px solid #BBF7D0}
@keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.fg{margin-bottom:14px;position:relative;z-index:1}
.fg label{display:block;font-size:12px;font-weight:700;color:#64748B;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
.iw{position:relative}
.iw .il,.iw .ir{position:absolute;top:50%;transform:translateY(-50%);font-size:15px}
.iw .il{left:14px;color:#94A3B8}
.iw .ir{right:14px;color:#94A3B8;cursor:pointer;transition:color .2s}
.iw .ir:hover{color:#3B82F6}
.iw input{width:100%;height:46px;border:1.5px solid #E2E8F0;border-radius:10px;padding:0 40px;font-size:14px;font-family:inherit;color:#0F172A;background:#FAFBFE;outline:none;transition:all .25s;font-weight:500}
.iw input::placeholder{color:#CBD5E1;font-weight:400}
.iw input:focus{border-color:#3B82F6;background:#fff;box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.iw input.err{border-color:#EF4444;background:#FFF8F8}
.fe{display:none;font-size:12px;color:#EF4444;margin-top:4px;font-weight:500}
.fe.show{display:block}
.opts{display:flex;justify-content:space-between;align-items:center;margin:4px 0 20px;font-size:13px;position:relative;z-index:1}
.chk{display:flex;align-items:center;gap:8px;cursor:pointer;color:#64748B;font-weight:500}
.chk input{width:15px;height:15px;accent-color:#3B82F6;cursor:pointer}
.lnk{color:#3B82F6;font-weight:700;text-decoration:none;font-size:13px;transition:opacity .2s}
.lnk:hover{opacity:.75}
.btn{width:100%;height:48px;border:none;border-radius:10px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:10px;background:linear-gradient(135deg,#3B82F6 0%,#6366F1 100%);color:#fff;box-shadow:0 6px 20px rgba(59,130,246,.3);position:relative;overflow:hidden;margin-bottom:20px;z-index:1}
.btn::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.12),transparent)}
.btn:hover{transform:translateY(-1px);box-shadow:0 10px 28px rgba(59,130,246,.4)}
.btn:active{transform:translateY(0)}
.btn>*{position:relative;z-index:1}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.spin{width:18px;height:18px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:sp .65s linear infinite;display:none}
@keyframes sp{to{transform:rotate(360deg)}}
.btn.loading .spin{display:block}
.btn.loading .bt-text{display:none}
.tog{text-align:center;font-size:13px;color:#64748B;font-weight:500;position:relative;z-index:1}
.tog a{cursor:pointer}
#vr{display:none}
@media(max-width:900px){
  .left{display:none}
  .right{width:100%;padding:36px 24px;background:linear-gradient(160deg,#fff,#F0F4FF)}
}
</style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="left">
  <div class="bg-slides">
    <div class="bg-slide active" style="background-image:url('https://wsrv.nl/?url=image.tmdb.org/t/p/original/xOMo8BRK7PfcJv9JCnx7s5hj0PX.jpg')"></div>
    <div class="bg-slide" style="background-image:url('https://wsrv.nl/?url=image.tmdb.org/t/p/original/rLb2cwF3Pazuxaj0sRXQ037tGI1.jpg')"></div>
    <div class="bg-slide" style="background-image:url('https://wsrv.nl/?url=image.tmdb.org/t/p/original/s3TBrRGB1iav7gFOCNx3H31MoES.jpg')"></div>
    <div class="bg-slide" style="background-image:url('https://wsrv.nl/?url=image.tmdb.org/t/p/original/xJHokMbljvjADYdit5fK5VQsXEG.jpg')"></div>
  </div>
  <div class="bg-overlay"></div>
  <div class="particles" id="particles"></div>
  <div class="left-content">
    <a href="guest.php" class="brand" style="text-decoration:none;cursor:pointer" title="Xem trang khách">
      <div class="brand-icon"><i class="fa-solid fa-clapperboard"></i></div>
      <div class="brand-name">Movie<span>Flex</span></div>
    </a>
    <div class="hero-copy">
      <div class="hero-label"><i class="fa-solid fa-ticket"></i> NỀN TẢNG ĐẶT VÉ SỐ 1</div>
      <h1 class="hero-title">Trải nghiệm<br><span>điện ảnh</span><br>đỉnh cao</h1>
      <p class="hero-desc">Hàng trăm bộ phim, hàng nghìn suất chiếu — đặt vé chỉ trong 60 giây. Ưu đãi thành viên độc quyền mỗi ngày.</p>
    </div>
    <div class="movie-strip">
      <div class="strip-card"><img src="https://wsrv.nl/?url=image.tmdb.org/t/p/w200/8b8R8l88Qje9dn9OE8PY05Nxl1X.jpg" alt=""><div class="rating">9.2</div></div>
      <div class="strip-card"><img src="https://upload.wikimedia.org/wikipedia/en/4/4a/Oppenheimer_%28film%29.jpg" alt=""><div class="rating">9.0</div></div>
      <div class="strip-card"><img src="https://upload.wikimedia.org/wikipedia/en/2/2e/Inception_%282010%29_theatrical_poster.jpg" alt=""><div class="rating">9.3</div></div>
      <div class="strip-card"><img src="https://upload.wikimedia.org/wikipedia/en/b/bc/Interstellar_film_poster.jpg" alt=""><div class="rating">9.4</div></div>
      <div class="strip-card"><img src="https://wsrv.nl/?url=image.tmdb.org/t/p/w200/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg" alt=""><div class="rating">9.1</div></div>
    </div>
    <div class="stats-bar">
      <div class="stat"><div class="stat-val">500<span>+</span></div><div class="stat-lbl">Bộ phim</div></div>
      <div class="stat"><div class="stat-val">50<span>k</span></div><div class="stat-lbl">Thành viên</div></div>
      <div class="stat"><div class="stat-val">30<span>+</span></div><div class="stat-lbl">Rạp chiếu</div></div>
    </div>
  </div>
</div>

<!-- RIGHT PANEL -->
<div class="right">

  <!-- LOGIN VIEW -->
  <div id="vl">
    <div class="form-header">
      <h2>Chào mừng trở lại</h2>
      <p>Đăng nhập để tiếp tục trải nghiệm điện ảnh cao cấp của bạn.</p>
    </div>
    <div id="la" class="alert"></div>
    <form id="fl" novalidate>
      <div class="fg">
        <label>Email hoặc Tên đăng nhập</label>
        <div class="iw">
          <i class="fa-regular fa-envelope il"></i>
          <input type="text" id="li" name="identifier" placeholder="Nhập email của bạn" autocomplete="username">
        </div>
        <span class="fe" id="ei"></span>
      </div>
      <div class="fg">
        <label>Mật khẩu</label>
        <div class="iw">
          <i class="fa-solid fa-lock il"></i>
          <input type="password" id="lp" name="password" placeholder="••••••••" autocomplete="current-password">
          <i class="fa-regular fa-eye ir" onclick="tp('lp',this)"></i>
        </div>
        <span class="fe" id="ep"></span>
      </div>
      <div class="opts">
        <label class="chk"><input type="checkbox" name="remember"><span>Ghi nhớ đăng nhập</span></label>
        <a href="forgot-password.php" class="lnk">Quên mật khẩu?</a>
      </div>
      <button class="btn" id="bl" type="submit">
        <div class="spin"></div>
        <span class="bt-text"><i class="fa-solid fa-right-to-bracket"></i>&nbsp; Đăng nhập</span>
      </button>
    </form>
    <p class="tog">Bạn chưa có tài khoản? <a class="lnk" onclick="sw('r')">Đăng ký miễn phí</a></p>
  </div>

  <!-- REGISTER VIEW -->
  <div id="vr">
    <div class="form-header">
      <h2>Tạo tài khoản mới</h2>
      <p>Tham gia MovieFlex để nhận ưu đãi thành viên và đặt vé dễ dàng hơn.</p>
    </div>
    <div id="ra" class="alert"></div>
    <form id="fr" novalidate>
      <div class="fg">
        <label>Họ và tên</label>
        <div class="iw">
          <i class="fa-regular fa-id-card il"></i>
          <input type="text" id="rn" name="full_name" placeholder="Nguyễn Văn A" autocomplete="name">
        </div>
        <span class="fe" id="en"></span>
      </div>
      <div class="fg">
        <label>Email</label>
        <div class="iw">
          <i class="fa-regular fa-envelope il"></i>
          <input type="email" id="re" name="email" placeholder="example@gmail.com" autocomplete="email">
        </div>
        <span class="fe" id="ee"></span>
      </div>
      <div class="fg">
        <label>Số điện thoại <small style="color:var(--light);font-weight:400">(tuỳ chọn)</small></label>
        <div class="iw">
          <i class="fa-solid fa-phone il"></i>
          <input type="tel" id="rph" name="phone" placeholder="0912 345 678" autocomplete="tel">
        </div>
      </div>
      <div class="fg">
        <label>Mật khẩu</label>
        <div class="iw">
          <i class="fa-solid fa-lock il"></i>
          <input type="password" id="rpw" name="password" placeholder="Tối thiểu 6 ký tự" autocomplete="new-password">
          <i class="fa-regular fa-eye ir" onclick="tp('rpw',this)"></i>
        </div>
        <span class="fe" id="epw"></span>
      </div>
      <div class="fg">
        <label>Xác nhận mật khẩu</label>
        <div class="iw">
          <i class="fa-solid fa-key il"></i>
          <input type="password" id="rcf" name="confirm_password" placeholder="Nhập lại mật khẩu" autocomplete="new-password">
          <i class="fa-regular fa-eye ir" onclick="tp('rcf',this)"></i>
        </div>
        <span class="fe" id="ecf"></span>
      </div>
      <button class="btn" id="br" type="submit" style="margin-top:8px">
        <div class="spin"></div>
        <span class="bt-text"><i class="fa-solid fa-user-plus"></i>&nbsp; Đăng ký ngay</span>
      </button>
    </form>
    <p class="tog">Đã có tài khoản? <a class="lnk" onclick="sw('l')">Đăng nhập ngay</a></p>
  </div>

</div>

<script>
const AUTH_ENDPOINT = '../../be/api.php';

if (sessionStorage.getItem('mf_show_register') === '1') {
  sessionStorage.removeItem('mf_show_register');
  document.getElementById('vl').style.display = 'none';
  document.getElementById('vr').style.display = 'block';
}

let slideIdx = 0;
const slides = document.querySelectorAll('.bg-slide');
setInterval(() => {
  slides[slideIdx].classList.remove('active');
  slideIdx = (slideIdx + 1) % slides.length;
  slides[slideIdx].classList.add('active');
}, 4000);

const pc = document.getElementById('particles');
for (let i = 0; i < 30; i++) {
  const d = document.createElement('div');
  d.className = 'dot';
  d.style.cssText = `left:${Math.random()*100}%;width:${Math.random()*3+1}px;height:${Math.random()*3+1}px;animation-duration:${Math.random()*12+8}s;animation-delay:${Math.random()*8}s;opacity:${Math.random()*.5+.2}`;
  pc.appendChild(d);
}

function sw(v) {
  document.querySelectorAll('.alert').forEach(e => e.className = 'alert');
  document.querySelectorAll('.fe').forEach(e => e.className = 'fe');
  document.querySelectorAll('input.err').forEach(e => e.classList.remove('err'));
  document.getElementById('vl').style.display = v === 'r' ? 'none' : 'block';
  document.getElementById('vr').style.display = v === 'r' ? 'block' : 'none';
}

function tp(id, icon) {
  const i = document.getElementById(id);
  i.type = i.type === 'password' ? 'text' : 'password';
  icon.classList.toggle('fa-eye');
  icon.classList.toggle('fa-eye-slash');
}

function alert_(id, type, msg) {
  const e = document.getElementById(id);
  e.className = `alert alert-${type} show`;
  e.innerHTML = `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i> ${msg}`;
}

function fe_(inp, fe, msg) {
  document.getElementById(inp)?.classList.add('err');
  const e = document.getElementById(fe);
  if (e) { e.textContent = msg; e.classList.add('show'); }
}

function setLoad(id, on) {
  const b = document.getElementById(id);
  b.classList.toggle('loading', on);
  b.disabled = on;
}

document.getElementById('fl').addEventListener('submit', async function(e) {
  e.preventDefault();
  document.querySelectorAll('.fe').forEach(x => x.className = 'fe');
  document.querySelectorAll('input.err').forEach(x => x.classList.remove('err'));

  const id = document.getElementById('li').value.trim();
  const pw = document.getElementById('lp').value.trim();
  let ok = true;
  if (!id) { fe_('li', 'ei', 'Vui lòng nhập email.'); ok = false; }
  if (!pw) { fe_('lp', 'ep', 'Vui lòng nhập mật khẩu.'); ok = false; }
  if (!ok) return;

  setLoad('bl', true);
  const fd = new FormData(this);
  fd.append('action', 'login');

  try {
    const r = await fetch(AUTH_ENDPOINT, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) {
      alert_('la', 'success', d.message || 'Đăng nhập thành công!');
      setTimeout(() => location.href = d.redirect || 'home.php', 800);
    } else {
      alert_('la', 'error', d.message);
      setLoad('bl', false);
    }
  } catch {
    alert_('la', 'error', 'Lỗi kết nối. Vui lòng thử lại.');
    setLoad('bl', false);
  }
});

document.getElementById('fr').addEventListener('submit', async function(e) {
  e.preventDefault();
  document.querySelectorAll('.fe').forEach(x => x.className = 'fe');
  document.querySelectorAll('input.err').forEach(x => x.classList.remove('err'));

  const nm = document.getElementById('rn').value.trim();
  const em = document.getElementById('re').value.trim();
  const pw = document.getElementById('rpw').value.trim();
  const cf = document.getElementById('rcf').value.trim();
  let ok = true;
  if (!nm) { fe_('rn', 'en', 'Vui lòng nhập họ và tên.'); ok = false; }
  if (!em) { fe_('re', 'ee', 'Vui lòng nhập email.'); ok = false; }
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { fe_('re', 'ee', 'Email không hợp lệ.'); ok = false; }
  if (!pw || pw.length < 6) { fe_('rpw', 'epw', 'Mật khẩu tối thiểu 6 ký tự.'); ok = false; }
  if (pw !== cf) { fe_('rcf', 'ecf', 'Mật khẩu xác nhận không khớp.'); ok = false; }
  if (!ok) return;

  setLoad('br', true);
  const fd = new FormData(this);
  fd.append('action', 'register');

  try {
    const r = await fetch(AUTH_ENDPOINT, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) {
      alert_('ra', 'success', d.message);
      this.reset();
      setTimeout(() => sw('l'), 1800);
    } else {
      alert_('ra', 'error', d.message);
    }
  } catch {
    alert_('ra', 'error', 'Lỗi kết nối. Vui lòng thử lại.');
  } finally {
    setLoad('br', false);
  }
});
</script>
</body>
</html>
