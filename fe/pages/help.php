<?php
session_start();
$active_page = 'help';

// Pre-fill form nếu đã đăng nhập (fix bug $_userName/$_userEmail chưa định nghĩa)
$_userName  = $_SESSION['user_name']  ?? '';
$_userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Trợ giúp & Hỗ trợ - MovieFlex</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --blue:#2563EB;
  --bg:#F1F5F9;
  --card:#fff;
  --text:#0F172A;
  --muted:#64748B;
  --light:#94A3B8;
  --border:#E2E8F0;
  --r:14px;
  --sh:0 2px 16px rgba(15,23,42,.08);
  --sbw:240px;
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}

/* MAIN */
.main{margin-left:var(--sbw);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:all .3s}
.topbar{background:var(--card);border-bottom:1px solid var(--border);padding:0 28px;height:64px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50}
.topbar h1{font-size:18px;font-weight:800}

/* CONTENT */
.content{padding:24px 28px;flex:1;display:grid;grid-template-columns:1fr 340px;gap:24px;max-width:1400px;margin:0 auto;width:100%}

/* HERO SEARCH */
.search-hero{background:linear-gradient(135deg, #EFF6FF, #DBEAFE);border-radius:var(--r);padding:32px;margin-bottom:24px;text-align:center;box-shadow:var(--sh);grid-column:1 / -1}
.search-hero h2{font-size:22px;font-weight:800;color:var(--blue);margin-bottom:8px}
.search-hero p{font-size:14px;color:var(--muted);margin-bottom:20px}
.search-box-wrap{max-width:500px;margin:0 auto;position:relative}
.search-box-wrap i{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--light);font-size:16px}
.search-input{width:100%;height:46px;background:var(--card);border:1.5px solid var(--border);border-radius:24px;padding:0 20px 0 46px;font-size:14.5px;font-family:inherit;outline:none;transition:all .2s;box-shadow:0 4px 12px rgba(37,99,235,.05)}
.search-input:focus{border-color:var(--blue);box-shadow:0 4px 20px rgba(37,99,235,.15)}

/* TOPIC CARDS */
.topic-grid{display:grid;grid-template-columns:repeat(4, 1fr);gap:16px;margin-bottom:24px;grid-column:1 / -1}
.topic-card{background:var(--card);border-radius:var(--r);padding:20px;box-shadow:var(--sh);border:1.5px solid transparent;cursor:pointer;transition:all .2s;text-align:center}
.topic-card:hover{transform:translateY(-3px);border-color:var(--blue);box-shadow:0 8px 24px rgba(37,99,235,.08)}
.topic-icon{width:46px;height:46px;border-radius:12px;background:#EFF6FF;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:18px;margin:0 auto 12px}
.topic-card h3{font-size:13.5px;font-weight:700;margin-bottom:4px}
.topic-card p{font-size:11.5px;color:var(--muted);line-height:1.4}

/* FAQ SECTION */
.panel{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);padding:24px;margin-bottom:20px}
.panel-head{display:flex;align-items:center;gap:10px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:14px}
.panel-head h2{font-size:16px;font-weight:800}
.panel-head i{color:var(--blue);font-size:18px}

/* ACCORDION */
.faq-list{display:flex;flex-direction:column;gap:12px}
.faq-item{border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:all .2s}
.faq-item:hover{border-color:var(--blue)}
.faq-header{padding:16px 20px;background:#F8FAFC;cursor:pointer;display:flex;justify-content:between;align-items:center;gap:12px;user-select:none}
.faq-title{font-size:13.5px;font-weight:600;flex:1;line-height:1.4}
.faq-arrow{font-size:12px;color:var(--muted);transition:transform .2s}
.faq-body{max-height:0;overflow:hidden;transition:max-height .25s ease-out;background:var(--card)}
.faq-content{padding:16px 20px;font-size:13px;color:var(--muted);line-height:1.6;border-top:1px solid var(--border)}
.faq-item.active .faq-arrow{transform:rotate(180deg)}
.faq-item.active{border-color:var(--blue);box-shadow:0 4px 12px rgba(37,99,235,.05)}

/* FORM SECTION */
.fg{margin-bottom:14px}
.fg label{display:block;font-size:12.5px;font-weight:600;color:var(--muted);margin-bottom:6px}
.fg label span{color:#EF4444}
.form-control{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13.5px;font-family:inherit;outline:none;transition:border-color .2s}
.form-control:focus{border-color:var(--blue)}
textarea.form-control{resize:vertical;min-height:100px}
.btn-submit{width:100%;height:42px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .2s}
.btn-submit:hover:not(:disabled){background:#1D4ED8}
.btn-submit:disabled{opacity:.6;cursor:not-allowed}

/* CONTACT CARD */
.contact-card{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);padding:20px;position:sticky;top:88px}
.contact-head{font-size:14.5px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.contact-head i{color:var(--blue)}
.contact-item{display:flex;gap:12px;margin-bottom:14px}
.contact-item-icon{width:32px;height:32px;border-radius:8px;background:#EFF6FF;color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.contact-item-info h4{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px}
.contact-item-info p{font-size:13px;font-weight:600}
.social-list{display:flex;gap:8px;margin-top:16px;border-top:1px solid var(--border);padding-top:16px}
.social-btn{width:36px;height:36px;border-radius:50%;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none}
.social-btn:hover{border-color:var(--blue);color:var(--blue);background:#EFF6FF}

/* ALERTS */
.alert{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:16px;display:none;align-items:center;gap:8px;line-height:1.4}
.alert.show{display:flex}
.alert-success{background:#F0FDF4;color:#166534;border:1px solid #BBF7D0}
.alert-error{background:#FEF2F2;color:#991B1B;border:1px solid #FEE2E2}

@media(max-width:992px){
  .content{grid-template-columns:1fr}
  .contact-card{position:static}
}
@media(max-width:768px){
  .main{margin-left:0}
  .topic-grid{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<?php include __DIR__ . '/../components/sidebar.php'; ?>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <h1><i class="fa-regular fa-circle-question" style="color:var(--blue);margin-right:8px"></i>Trợ giúp & Hỗ trợ</h1>
  </div>

  <div class="content">
    
    <!-- SEARCH HERO -->
    <div class="search-hero">
      <h2>Xin chào, chúng tôi có thể giúp gì cho bạn?</h2>
      <p>Nhập từ khóa hoặc câu hỏi của bạn để tìm kiếm câu trả lời nhanh chóng</p>
      <div class="search-box-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="search-input" id="search-faq" placeholder="Tìm kiếm câu hỏi thường gặp (ví dụ: đặt vé, hủy vé)..." oninput="filterFaqs()">
      </div>
    </div>

    <!-- QUICK TOPICS -->
    <div class="topic-grid">
      <div class="topic-card" onclick="setSearch('đặt vé')">
        <div class="topic-icon"><i class="fa-solid fa-ticket"></i></div>
        <h3>Đặt vé phim</h3>
        <p>Chọn phim, chọn ghế & thanh toán vé</p>
      </div>
      <div class="topic-card" onclick="setSearch('hủy vé')">
        <div class="topic-icon"><i class="fa-solid fa-arrow-rotate-left"></i></div>
        <h3>Hoàn/Hủy vé</h3>
        <p>Quy chế hủy và hoàn tiền giao dịch</p>
      </div>
      <div class="topic-card" onclick="setSearch('thành viên')">
        <div class="topic-icon"><i class="fa-solid fa-star"></i></div>
        <h3>Thành viên</h3>
        <p>Tích lũy điểm & đổi quà tặng</p>
      </div>
      <div class="topic-card" onclick="setSearch('thanh toán')">
        <div class="topic-icon"><i class="fa-solid fa-credit-card"></i></div>
        <h3>Thanh toán</h3>
        <p>MoMo, VNPay, ZaloPay & thẻ napas</p>
      </div>
    </div>

    <!-- LEFT FAQ & SUPPORT FORM -->
    <div>
      
      <!-- FAQS -->
      <div class="panel">
        <div class="panel-head">
          <i class="fa-solid fa-circle-info"></i>
          <h2>Câu hỏi thường gặp</h2>
        </div>
        <div class="faq-list" id="faq-list">
          
          <div class="faq-item" data-tags="đặt vé lich chieu phim">
            <div class="faq-header" onclick="toggleFaq(this)">
              <span class="faq-title">Làm thế nào để tôi đặt vé xem phim trực tuyến?</span>
              <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </div>
            <div class="faq-body">
              <div class="faq-content">
                Bạn chỉ cần truy cập vào mục <b>"Trang chủ"</b> hoặc <b>"Phim"</b>, chọn phim muốn xem, bấm <b>"Đặt vé"</b>, sau đó chọn Rạp chiếu, ngày chiếu và suất chiếu mong muốn. Cuối cùng, chọn sơ đồ ghế ngồi và thực hiện thanh toán trực tuyến qua các ví điện tử hoặc tài khoản ngân hàng.
              </div>
            </div>
          </div>

          <div class="faq-item" data-tags="hủy vé hoan tien doi ve">
            <div class="faq-header" onclick="toggleFaq(this)">
              <span class="faq-title">Tôi có thể hủy vé hoặc đổi suất chiếu sau khi đã thanh toán thành công không?</span>
              <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </div>
            <div class="faq-body">
              <div class="faq-content">
                Theo quy định của MovieFlex, vé phim đã mua thành công trực tuyến <b>không thể hủy hoặc đổi trả</b> để đảm bảo quyền lợi đặt chỗ cho các khách hàng khác. Vui lòng kiểm tra kỹ thông tin Rạp, Phim, Suất chiếu và Ghế ngồi trước khi xác nhận thanh toán.
              </div>
            </div>
          </div>

          <div class="faq-item" data-tags="thành viên diem thuong doi qua loyalty">
            <div class="faq-header" onclick="toggleFaq(this)">
              <span class="faq-title">Quy chế tích lũy điểm thưởng thành viên hoạt động thế nào?</span>
              <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </div>
            <div class="faq-body">
              <div class="faq-content">
                Mỗi giao dịch đặt vé thành công khi đã đăng nhập tài khoản sẽ được tích lũy điểm thưởng theo tỷ lệ: <b>10.000đ chi tiêu = 1 điểm tích lũy</b>. Bạn có thể sử dụng điểm tích lũy để tăng hạng thành viên (Silver, Gold, Platinum) và nhận các voucher ưu đãi hấp dẫn trong mục hồ sơ.
              </div>
            </div>
          </div>

          <div class="faq-item" data-tags="thanh toán vi dien tu momo vnpay zalopay loi">
            <div class="faq-header" onclick="toggleFaq(this)">
              <span class="faq-title">Tôi đã bị trừ tiền ngân hàng nhưng hệ thống báo đặt vé thất bại?</span>
              <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </div>
            <div class="faq-body">
              <div class="faq-content">
                Trong một số trường hợp nghẽn mạng từ ví điện tử/ngân hàng, giao dịch có thể bị chậm trễ cập nhật. Nếu bạn đã bị trừ tiền nhưng không nhận được vé, vui lòng liên hệ ngay với Hotline <b>1900 1234</b> hoặc gửi yêu cầu hỗ trợ kèm ảnh chụp biên lai giao dịch. Chúng tôi sẽ đối soát hệ thống và hoàn trả tiền hoặc cấp lại vé cho bạn trong vòng 30 phút.
              </div>
            </div>
          </div>

          <div class="faq-item" data-tags="do an thuc an nuoc uong ngo bap mang vao">
            <div class="faq-header" onclick="toggleFaq(this)">
              <span class="faq-title">Tôi có được mang thức ăn, nước uống từ bên ngoài vào rạp không?</span>
              <i class="fa-solid fa-chevron-down faq-arrow"></i>
            </div>
            <div class="faq-body">
              <div class="faq-content">
                Để giữ gìn vệ sinh chung và đảm bảo trải nghiệm thưởng thức tốt nhất cho mọi người, rạp chiếu phim MovieFlex <b>không cho phép mang đồ ăn và nước uống từ bên ngoài vào</b>. Bạn có thể mua các loại bắp nước thơm ngon và đa dạng trực tiếp tại quầy bắp nước của rạp hoặc đặt online trước khi thanh toán vé.
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- SUPPORT TICKET FORM -->
      <div class="panel">
        <div class="panel-head">
          <i class="fa-regular fa-envelope"></i>
          <h2>Gửi yêu cầu hỗ trợ trực tuyến</h2>
        </div>
        
        <div id="alert-box"></div>

        <form id="support-form">
          <input type="hidden" name="action" value="submit_support_ticket">
          <div class="fg">
            <label>Họ và tên <span>*</span></label>
            <input type="text" name="fullname" class="form-control" placeholder="Nhập đầy đủ họ tên..." required value="<?= htmlspecialchars($_userName) ?>">
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="fg">
              <label>Email <span>*</span></label>
              <input type="email" name="email" class="form-control" placeholder="Nhập địa chỉ email..." required value="<?= htmlspecialchars($_userEmail) ?>">
            </div>
            <div class="fg">
              <label>Số điện thoại</label>
              <input type="tel" name="phone" class="form-control" placeholder="Nhập số điện thoại liên hệ...">
            </div>
          </div>
          <div class="fg">
            <label>Chủ đề yêu cầu <span>*</span></label>
            <input type="text" name="subject" class="form-control" placeholder="Ví dụ: Lỗi thanh toán, sự cố đặt ghế..." required>
          </div>
          <div class="fg">
            <label>Nội dung chi tiết <span>*</span></label>
            <textarea name="content" class="form-control" placeholder="Mô tả cụ thể vấn đề hoặc câu hỏi của bạn để chúng tôi hỗ trợ tốt nhất..." required></textarea>
          </div>
          <button type="submit" class="btn-submit" id="btn-submit">
            <i class="fa-regular fa-paper-plane"></i> Gửi yêu cầu hỗ trợ
          </button>
        </form>
      </div>

    </div>

    <!-- RIGHT CONTACT INFO -->
    <div>
      <div class="contact-card">
        <div class="contact-head">
          <i class="fa-solid fa-circle-nodes"></i>
          <span>Kênh hỗ trợ trực tiếp</span>
        </div>
        
        <div class="contact-item">
          <div class="contact-item-icon"><i class="fa-solid fa-phone"></i></div>
          <div class="contact-item-info">
            <h4>Hotline chăm sóc</h4>
            <p>1900 1234 (7:00 - 23:00)</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-item-icon"><i class="fa-solid fa-envelope"></i></div>
          <div class="contact-item-info">
            <h4>Email hỗ trợ</h4>
            <p>support@movieflex.com</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-item-icon"><i class="fa-solid fa-location-dot"></i></div>
          <div class="contact-item-info">
            <h4>Trụ sở chính</h4>
            <p>191 Bà Triệu, Hai Bà Trưng, Hà Nội</p>
          </div>
        </div>

        <div class="social-list">
          <a href="#" class="social-btn" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" class="social-btn" title="Messenger"><i class="fa-brands fa-facebook-messenger"></i></a>
          <a href="#" class="social-btn" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="social-btn" title="Zalo"><i class="fa-solid fa-comment-dots"></i></a>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
const USER_ENDPOINT = '../../be/api.php';

// ── Support Ticket Form ──
document.getElementById('support-form').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('btn-submit');
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang gửi...';

  const fd = new FormData(this);

  try {
    const r = await fetch(USER_ENDPOINT, { method: 'POST', body: fd });
    const d = await r.json();
    showAlert(d.success ? 'success' : 'error', d.message);
    if (d.success) this.reset();
  } catch {
    showAlert('error', 'Lỗi kết nối. Vui lòng thử lại.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
});

function showAlert(type, message) {
  const box = document.getElementById('alert-box');
  const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
  box.innerHTML = `<div class="alert alert-${type} show"><i class="fa-solid ${icon}"></i>${message}</div>`;
  box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── FAQ Accordion ──
function toggleFaq(header) {
  const item = header.parentElement;
  const body = header.nextElementSibling;
  
  document.querySelectorAll('.faq-item').forEach(x => {
    if (x !== item && x.classList.contains('active')) {
      x.classList.remove('active');
      x.querySelector('.faq-body').style.maxHeight = null;
    }
  });

  item.classList.toggle('active');
  body.style.maxHeight = item.classList.contains('active') ? body.scrollHeight + 'px' : null;
}

function filterFaqs() {
  const query = document.getElementById('search-faq').value.toLowerCase().trim();
  document.querySelectorAll('.faq-item').forEach(item => {
    const title   = item.querySelector('.faq-title').textContent.toLowerCase();
    const content = item.querySelector('.faq-content').textContent.toLowerCase();
    const tags    = item.dataset.tags.toLowerCase();
    const match   = title.includes(query) || content.includes(query) || tags.includes(query);
    item.style.display = match ? 'block' : 'none';
    if (!match && item.classList.contains('active')) {
      item.classList.remove('active');
      item.querySelector('.faq-body').style.maxHeight = null;
    }
  });
}

function setSearch(term) {
  document.getElementById('search-faq').value = term;
  filterFaqs();
  setTimeout(() => {
    const visible = Array.from(document.querySelectorAll('.faq-item')).filter(x => x.style.display !== 'none');
    if (visible.length > 0 && !visible[0].classList.contains('active')) {
      toggleFaq(visible[0].querySelector('.faq-header'));
    }
  }, 100);
}
</script>
</body>
</html>
