# MoviePlex - Platform

Ứng dụng đặt vé xem phim nhóm xây dựng bằng PHP thuần, tổ chức theo mô hình MVC, chạy trên môi trường Docker (Apache + MySQL). 

---

## Tính năng

- Đăng ký, đăng nhập, quên mật khẩu (gửi email qua SMTP)
- Xem danh sách phim, chi tiết phim, lịch chiếu theo rạp
- Chọn ghế, đặt vé, thanh toán, áp mã giảm giá (voucher)
- Quản lý vé cá nhân và hồ sơ người dùng
- Trang quản trị (Admin): quản lý phim, rạp, lịch chiếu, voucher, tài khoản, doanh thu, nhật ký hoạt động
- Giao diện nhân viên rạp

---

## Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.2 (thuần) |
| Frontend | HTML / CSS / JavaScript thuần |
| Database | MySQL 8 |
| Web Server | Apache 2 (mod_rewrite) |
| Môi trường | Docker + Docker Compose |
| Gửi email | PHPMailer (Composer) |
| DB GUI | phpMyAdmin |

---

## Cấu trúc thư mục

```
MoviePlex-Platform/
├── docker/             # Cấu hình Docker: Dockerfile, Apache, PHP, init SQL
├── fe/                 # Frontend: pages, admin panel, components, assets
├── be/                 # Backend: config, routes, middleware, controllers, services, models, core
├── docker-compose.yml
├── composer.json
├── .env.example
└── README.md
```

Xem chi tiết từng thành phần tại [PROJECT_STRUCTURE.md](./PROJECT_STRUCTURE.md).

---

## Yêu cầu môi trường

- Docker Desktop (Windows / macOS) hoặc Docker Engine + Docker Compose (Linux)
- Composer
- Git

---

## Cài đặt và chạy

### 1. Clone project

```bash
git clone https://github.com/DuyNam169/MoviePlex-Platform
cd MoviePlex-Platform
```

### 2. Tạo file `.env`

```bash
cp .env.example .env
```

Mở `.env` và điền các giá trị phù hợp:

```env
DB_HOST=mysql
DB_NAME=movieflex_db
DB_USER=root
DB_PASSWORD=your_password

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM=your_email@gmail.com
```

### 3. Cài đặt PHPMailer

```bash
composer install
```

### 4. Khởi động Docker

```bash
docker compose up -d --build
```

Lần đầu chạy, Docker sẽ tự động build image và khởi tạo database từ `docker/mysql/init.sql` (bao gồm schema và dữ liệu mẫu).

### 5. Truy cập

| Dịch vụ | URL |
|---|---|
| Ứng dụng | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | localhost:3306 |

---

## Reset database

Khi cần xóa toàn bộ dữ liệu và khởi tạo lại từ đầu:

```bash
docker compose down -v
docker compose up -d --build
```

> Lệnh này xóa toàn bộ dữ liệu trong database. Backup trước nếu cần.

---

## Dừng ứng dụng

```bash
docker compose down
```

---

## Thành viên nhóm

| Tên | Vai trò | GitHub |
|---|---|---|
| | | |

---

## Ghi chú

Dự án phục vụ mục đích học tập — nhóm môn học tại Trường Đại học Công nghệ Giao thông Vận tải (UTT).