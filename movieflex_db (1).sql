-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 20, 2026 lúc 12:21 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `movieflex_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_code` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `showtime_id` int(10) UNSIGNED NOT NULL,
  `seats_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seats_json`)),
  `num_tickets` tinyint(4) NOT NULL DEFAULT 1,
  `subtotal` decimal(12,0) NOT NULL,
  `discount` decimal(12,0) DEFAULT 0,
  `total_amount` decimal(12,0) NOT NULL,
  `payment_method` enum('momo','vnpay','zalopay','napas','cash') DEFAULT 'momo',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `status` enum('confirmed','cancelled','checked_in') DEFAULT 'confirmed',
  `voucher_code` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancel_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `user_id`, `showtime_id`, `seats_json`, `num_tickets`, `subtotal`, `discount`, `total_amount`, `payment_method`, `payment_status`, `status`, `voucher_code`, `transaction_id`, `created_at`, `cancel_reason`) VALUES
(3, 'MF20260519001', 2, 20, '[\"F-08\",\"F-09\"]', 2, 240000, 20000, 220000, 'momo', 'paid', 'checked_in', NULL, 'MOMO88392138', '2026-05-19 05:09:57', NULL),
(4, 'MF20260515002', 2, 16, '[\"C-12\"]', 1, 120000, 0, 120000, 'zalopay', 'paid', 'checked_in', NULL, 'ZALO7728318', '2026-05-17 05:09:57', NULL),
(5, 'MF2026051940CBDD', 2, 146, '[\"D-08\"]', 1, 200000, 40000, 200000, 'momo', 'paid', 'cancelled', 'MOVIE20', NULL, '2026-05-19 10:13:40', NULL),
(6, 'MF20260519033405', 2, 30, '[\"E-07\"]', 1, 260000, 50000, 245000, 'momo', 'paid', 'cancelled', 'NEWUSER50', NULL, '2026-05-19 10:19:28', NULL),
(7, 'MF20260519E16771', 2, 159, '[\"B-06\",\"B-07\",\"B-08\"]', 3, 600000, 120000, 480000, 'zalopay', 'paid', 'cancelled', 'MOVIE20', NULL, '2026-05-19 13:25:50', NULL),
(10, 'MF202605190894AB', 2, 159, '[\"D-07\",\"D-06\",\"D-08\",\"D-09\",\"D-10\"]', 5, 1000000, 100000, 935000, 'momo', 'paid', 'cancelled', 'REDM100K-CE9025', NULL, '2026-05-19 14:38:24', NULL),
(12, 'MF20260519AAAB59', 2, 159, '[\"C-06\",\"C-07\",\"C-09\",\"C-08\"]', 4, 800000, 0, 800000, 'momo', 'paid', 'cancelled', NULL, NULL, '2026-05-19 14:47:54', NULL),
(13, 'MF202605192DA7DE', 2, 24, '[\"F-08\",\"F-09\"]', 2, 312000, 0, 312000, 'momo', 'paid', 'checked_in', NULL, NULL, '2026-05-19 14:50:10', NULL),
(14, 'MF202605194C6764', 2, 157, '[\"E-08\",\"E-09\"]', 2, 520000, 100000, 550000, 'momo', 'paid', 'checked_in', 'REDM100K-CE9025', NULL, '2026-05-19 15:41:40', NULL),
(15, 'MF202605193C37F6', 2, 157, '[\"E-08\",\"E-09\"]', 2, 520000, 50000, 470000, 'momo', 'paid', 'cancelled', 'NEWUSER50', NULL, '2026-05-19 15:41:55', NULL),
(20, 'MF20260520D2B946', 2, 336, '[\"E-09\",\"E-08\",\"E-07\"]', 3, 780000, 156000, 624000, 'vnpay', 'paid', 'checked_in', 'MOVIE20', NULL, '2026-05-20 11:50:37', NULL),
(21, 'MF2026052091F30A', 2, 223, '[\"E-05\",\"E-07\",\"E-06\",\"E-08\",\"E-09\",\"E-10\",\"F-10\",\"F-09\"]', 8, 2080000, 0, 2080000, 'momo', 'paid', 'cancelled', NULL, NULL, '2026-05-20 14:04:41', NULL),
(22, 'MF2026052124CE32', 2, 198, '[\"E-07\",\"E-08\"]', 2, 520000, 50000, 545000, 'vnpay', 'paid', 'cancelled', 'NEWUSER50', NULL, '2026-05-21 05:49:38', NULL),
(24, 'MF20260524816EE9', 2, 499, '[\"E-07\",\"E-08\",\"E-09\"]', 3, 780000, 0, 855000, 'momo', 'refunded', 'cancelled', NULL, NULL, '2026-05-24 13:19:52', NULL),
(25, 'MF202605244ACB33', 2, 565, '[\"E-08\",\"E-07\",\"E-09\"]', 3, 780000, 0, 825000, 'momo', 'refunded', 'cancelled', NULL, NULL, '2026-05-24 13:27:48', 'Hệ thống tự động hủy và hoàn tiền do suất chiếu gặp sự cố kỹ thuật'),
(26, 'MF20260525B21D4F', 6, 566, '[\"E-04\",\"E-05\",\"E-06\",\"E-07\"]', 4, 1040000, 50000, 1065000, 'momo', 'paid', 'checked_in', 'NEWUSER50-47921C', NULL, '2026-05-25 07:49:47', NULL),
(27, 'MF202605255DAA32', 6, 501, '[\"E-07\"]', 1, 156000, 10000, 191000, 'momo', 'paid', 'checked_in', 'SUMMER30-47846C', 'TX-20260525C6626F', '2026-05-25 08:12:12', NULL),
(28, 'MF202605250ECA97', 6, 501, '[\"E-06\"]', 1, 156000, 10000, 146000, 'momo', 'paid', 'checked_in', 'SUMMER30-47846C', 'TX-20260525C6626F', '2026-05-25 08:12:12', NULL),
(29, 'MF202605251752DA', 6, 501, '[\"E-08\"]', 1, 156000, 10000, 146000, 'momo', 'paid', 'checked_in', 'SUMMER30-47846C', 'TX-20260525C6626F', '2026-05-25 08:12:12', NULL),
(30, 'MF202605252DF60B', 10, 504, '[\"F-07\"]', 1, 156000, 0, 156000, 'cash', 'paid', 'checked_in', NULL, 'COUNTER-2026052596DF3B', '2026-05-25 08:35:21', NULL),
(31, 'MF202605259F194C', 10, 504, '[\"F-06\"]', 1, 156000, 0, 156000, 'cash', 'paid', 'checked_in', NULL, 'COUNTER-2026052596DF3B', '2026-05-25 08:35:21', NULL),
(32, 'MF202605252831AF', 10, 504, '[\"F-08\"]', 1, 156000, 0, 156000, 'cash', 'paid', 'checked_in', NULL, 'COUNTER-2026052596DF3B', '2026-05-25 08:35:21', NULL),
(33, 'MF202605251E0254', 10, 503, '[\"G-08\"]', 1, 260000, 0, 260000, 'cash', 'paid', 'checked_in', NULL, 'COUNTER-202605256140E2', '2026-05-25 09:34:14', NULL),
(34, 'MF20260525A8D6D2', 10, 503, '[\"F-08\"]', 1, 260000, 0, 260000, 'cash', 'paid', 'checked_in', NULL, 'COUNTER-202605256140E2', '2026-05-25 09:34:14', NULL),
(35, 'MF20260525F1987F', 10, 503, '[\"D-08\"]', 1, 200000, 0, 200000, 'cash', 'paid', 'checked_in', NULL, 'COUNTER-202605256140E2', '2026-05-25 09:34:14', NULL),
(36, 'MF20260525NVSAGR', 6, 503, '[\"F-09\"]', 1, 260000, 0, 260000, 'cash', 'paid', 'checked_in', NULL, 'COUNTER-20260525E1E1F2', '2026-05-25 09:47:10', NULL),
(37, 'MF202605252LEA2C', 6, 503, '[\"G-07\"]', 1, 260000, 0, 260000, 'cash', 'paid', 'checked_in', NULL, 'COUNTER-20260525E1E1F2', '2026-05-25 09:47:10', NULL),
(38, 'MF202605259F77E5', 6, 520, '[\"F-08\"]', 1, 260000, 0, 260000, 'momo', 'paid', 'checked_in', NULL, 'TX-20260525B98B66', '2026-05-25 14:06:03', NULL),
(39, 'MF2026052559C39F', 6, 520, '[\"F-07\"]', 1, 260000, 0, 260000, 'momo', 'paid', 'checked_in', NULL, 'TX-20260525B98B66', '2026-05-25 14:06:03', NULL),
(40, 'MF202605258110E4', 6, 520, '[\"F-06\"]', 1, 260000, 0, 260000, 'momo', 'paid', 'checked_in', NULL, 'TX-20260525B98B66', '2026-05-25 14:06:03', NULL),
(41, 'MF2026052652E8F7', 6, 583, '[\"F-10\"]', 1, 208000, 0, 208000, 'momo', 'paid', 'checked_in', NULL, 'TX-202605260DBF84', '2026-05-26 08:23:12', NULL),
(42, 'MF202605269D8660', 6, 634, '[\"E-07\"]', 1, 182000, 0, 182000, 'momo', 'refunded', 'cancelled', NULL, 'TX-2026052657B903', '2026-05-26 15:02:13', 'Hệ thống tự động hủy và hoàn tiền do suất chiếu gặp sự cố kỹ thuật'),
(43, 'MF202605269827D6', 6, 634, '[\"E-08\"]', 1, 182000, 0, 182000, 'momo', 'refunded', 'cancelled', NULL, 'TX-2026052657B903', '2026-05-26 15:02:13', 'Hệ thống tự động hủy và hoàn tiền do suất chiếu gặp sự cố kỹ thuật'),
(44, 'MF20260528E050DF', 6, 932, '[\"G-05\"]', 1, 104000, 0, 104000, 'momo', 'paid', 'checked_in', NULL, 'TX-20260528D9C5A1', '2026-05-28 11:43:57', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `checkin_hourly`
--

CREATE TABLE `checkin_hourly` (
  `id` int(10) UNSIGNED NOT NULL,
  `hour_label` varchar(10) NOT NULL,
  `checkins` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `checkin_hourly`
--

INSERT INTO `checkin_hourly` (`id`, `hour_label`, `checkins`) VALUES
(1, '08:00', 80),
(2, '10:00', 120),
(3, '12:00', 210),
(4, '14:00', 180),
(5, '16:00', 320),
(6, '18:00', 580),
(7, '20:00', 850),
(8, '22:00', 420);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cinemas`
--

CREATE TABLE `cinemas` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT 'Hà Nội',
  `phone` varchar(20) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cinemas`
--

INSERT INTO `cinemas` (`id`, `name`, `address`, `city`, `phone`, `logo_url`) VALUES
(1, 'CGV Vincom Center', '191 Bà Triệu, Hai Bà Trưng, Hà Nội', 'Hà Nội', '1900 6017', NULL),
(2, 'Lotte Cinema Q7', '469 Nguyễn Hữu Thọ, Quận 7, TP.HCM', 'TP.HCM', '1900 6666', NULL),
(3, 'Galaxy Nguyễn Du', '116 Nguyễn Du, Quận 1, TP.HCM', 'TP.HCM', '1900 2224', NULL),
(4, 'CGV Vincom Metropolis', 'Vincom Metropolis, 29 Liễu Giai, Ba Đình, Hà Nội', 'Hà Nội', '1900 6017', NULL),
(5, 'BHD Star Phạm Ngọc Thạch', '499 Phạm Ngọc Thạch, Đống Đa, Hà Nội', 'Hà Nội', '1900 2345', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cinema_halls`
--

CREATE TABLE `cinema_halls` (
  `id` int(10) UNSIGNED NOT NULL,
  `cinema_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `total_seats` int(10) UNSIGNED DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cinema_halls`
--

INSERT INTO `cinema_halls` (`id`, `cinema_id`, `name`, `total_seats`, `created_at`) VALUES
(1, 1, 'Phòng 01', 100, '2026-05-25 10:11:20'),
(2, 1, 'Phòng 02', 100, '2026-05-25 10:11:20'),
(3, 1, 'Phòng 03', 100, '2026-05-25 10:11:20'),
(4, 1, 'Phòng 04', 100, '2026-05-25 10:11:20'),
(5, 1, 'Phòng 05', 100, '2026-05-25 10:11:20'),
(6, 1, 'Phòng 06', 100, '2026-05-25 10:11:20'),
(7, 2, 'Phòng 01', 100, '2026-05-25 10:11:20'),
(8, 2, 'Phòng 02', 100, '2026-05-25 10:11:20'),
(9, 2, 'Phòng 03', 100, '2026-05-25 10:11:20'),
(10, 2, 'Phòng 04', 100, '2026-05-25 10:11:20'),
(11, 2, 'Phòng 05', 100, '2026-05-25 10:11:20'),
(12, 2, 'Phòng 06', 100, '2026-05-25 10:11:20'),
(13, 3, 'Phòng 01', 100, '2026-05-25 10:11:20'),
(14, 3, 'Phòng 02', 100, '2026-05-25 10:11:20'),
(15, 3, 'Phòng 03', 100, '2026-05-25 10:11:20'),
(16, 3, 'Phòng 04', 100, '2026-05-25 10:11:20'),
(17, 3, 'Phòng 05', 100, '2026-05-25 10:11:20'),
(18, 3, 'Phòng 06', 100, '2026-05-25 10:11:20'),
(19, 4, 'Phòng 01', 100, '2026-05-25 10:11:20'),
(20, 4, 'Phòng 02', 100, '2026-05-25 10:11:20'),
(21, 4, 'Phòng 03', 100, '2026-05-25 10:11:20'),
(22, 4, 'Phòng 04', 100, '2026-05-25 10:11:20'),
(23, 4, 'Phòng 05', 100, '2026-05-25 10:11:20'),
(24, 4, 'Phòng 06', 100, '2026-05-25 10:11:20'),
(25, 5, 'Phòng 01', 100, '2026-05-25 10:11:20'),
(26, 5, 'Phòng 02', 100, '2026-05-25 10:11:20'),
(27, 5, 'Phòng 03', 100, '2026-05-25 10:11:20'),
(28, 5, 'Phòng 04', 100, '2026-05-25 10:11:20'),
(29, 5, 'Phòng 05', 100, '2026-05-25 10:11:20'),
(30, 5, 'Phòng 06', 100, '2026-05-25 10:11:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `emp_code` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `role` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `employees`
--

INSERT INTO `employees` (`id`, `emp_code`, `full_name`, `email`, `role`, `status`, `created_at`) VALUES
(1, 'NV001', 'Nguyễn Văn Đô', 'donv@movieflex.com', 'Admin Portal', 'active', '2026-05-24 05:19:39'),
(2, 'NV002', 'Trần Minh Anh', 'anhtm@movieflex.com', 'Kế toán trưởng', 'active', '2026-05-24 05:19:39'),
(3, 'NV003', 'Lê Thị Bình', 'binhlt@movieflex.com', 'Nhân viên CSKH', 'active', '2026-05-24 05:19:39'),
(4, 'NV004', 'Hoàng Minh Đức', 'duchm@movieflex.com', 'Kỹ thuật viên phòng máy', 'active', '2026-05-24 05:19:39'),
(5, 'NV005', 'Phạm Hồng Nhung', 'nhungph@movieflex.com', 'Marketing Executive', 'locked', '2026-05-24 05:19:39');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kpis`
--

CREATE TABLE `kpis` (
  `id` int(10) UNSIGNED NOT NULL,
  `total_tickets` int(11) NOT NULL DEFAULT 0,
  `total_checkins` int(11) NOT NULL DEFAULT 0,
  `reconciliation_errors` int(11) NOT NULL DEFAULT 0,
  `active_staff` int(11) NOT NULL DEFAULT 0,
  `locked_accounts` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `kpis`
--

INSERT INTO `kpis` (`id`, `total_tickets`, `total_checkins`, `reconciliation_errors`, `active_staff`, `locked_accounts`) VALUES
(1, 8420, 7150, 3, 14, 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `movies`
--

CREATE TABLE `movies` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `genre` varchar(150) DEFAULT NULL,
  `duration_min` int(11) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT 0.0,
  `poster_url` varchar(500) DEFAULT NULL,
  `backdrop_url` varchar(500) DEFAULT NULL,
  `trailer_url` varchar(500) DEFAULT NULL,
  `director` varchar(150) DEFAULT NULL,
  `cast_list` text DEFAULT NULL,
  `age_rating` varchar(10) DEFAULT 'T16',
  `status` enum('now_showing','coming_soon','ended') DEFAULT 'coming_soon',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `movies`
--

INSERT INTO `movies` (`id`, `title`, `description`, `genre`, `duration_min`, `release_date`, `rating`, `poster_url`, `backdrop_url`, `trailer_url`, `director`, `cast_list`, `age_rating`, `status`, `created_at`) VALUES
(1, 'Dune: Hành Tinh Cát Phần Hai', 'Hành trình của Paul Atreides trong việc đoàn kết với người Fremen trong khi phải đưa ra một lựa chọn khó khăn nhất.', 'Hành động, Phiêu lưu, Khoa học viễn tưởng', 167, '2024-03-01', 10.0, 'https://wsrv.nl/?url=image.tmdb.org/t/p/w500/8b8R8l88Qje9dn9OE8PY05Nxl1X.jpg', 'https://wsrv.nl/?url=image.tmdb.org/t/p/original/xOMo8BRK7PfcJv9JCnx7s5hj0PX.jpg', NULL, 'Denis Villeneuve', 'Timothée Chalamet, Zendaya, Rebecca Ferguson', 'T16', 'now_showing', '2026-05-19 07:40:55'),
(2, 'Avatar: Dòng Chảy Của Nước', 'Jake Sully và Ney\'tiri đã lập gia đình và đang cố gắng ở lại sống với nhau. Tuy nhiên, họ phải rời bỏ ngôi nhà của mình và khám phá các khu vực khác của Pandora.', 'Hành động, Phiêu lưu, Khoa học viễn tưởng', 192, '2022-12-16', 8.5, 'https://upload.wikimedia.org/wikipedia/en/5/54/Avatar_The_Way_of_Water_poster.jpg', 'https://images.unsplash.com/photo-1482862549707-f63cb32c5fd9?q=80&w=1600', NULL, 'James Cameron', 'Sam Worthington, Zoe Saldana, Sigourney Weaver', 'T13', 'now_showing', '2026-05-19 07:40:55'),
(3, 'Oppenheimer', 'Câu chuyện về cuộc đời của J. Robert Oppenheimer - cha đẻ của bom nguyên tử và người có vai trò quan trọng trong sự phát triển vũ khí đầu tiên trên thế giới.', 'Tiểu sử, Lịch sử, Chính kịch', 180, '2023-07-21', 9.0, 'https://upload.wikimedia.org/wikipedia/en/4/4a/Oppenheimer_%28film%29.jpg', 'https://wsrv.nl/?url=image.tmdb.org/t/p/original/rLb2cwF3Pazuxaj0sRXQ037tGI1.jpg', NULL, 'Christopher Nolan', 'Cillian Murphy, Emily Blunt, Matt Damon', 'T18', 'now_showing', '2026-05-19 07:40:55'),
(4, 'Inception', 'Một tên trộm ăn cắp thông tin bằng cách thâm nhập vào tiềm thức của người ngủ. Anh ta được giao nhiệm vụ cấy ghép ý tưởng vào tâm trí của CEO một tập đoàn.', 'Hành động, Khoa học viễn tưởng, Hồi hộp', 148, '2010-07-16', 9.3, 'https://upload.wikimedia.org/wikipedia/en/2/2e/Inception_%282010%29_theatrical_poster.jpg', 'https://wsrv.nl/?url=image.tmdb.org/t/p/original/s3TBrRGB1iav7gFOCNx3H31MoES.jpg', NULL, 'Christopher Nolan', 'Leonardo DiCaprio, Marion Cotillard, Joseph Gordon-Levitt', 'T13', 'now_showing', '2026-05-19 07:40:55'),
(5, 'Interstellar', 'Câu chuyện về một nhóm nhà thám hiểm sử dụng một con sâu được phát hiện gần Sao Thổ để vượt qua các giới hạn của du hành không gian của con người.', 'Hành động, Kịch tính, Khoa học viễn tưởng', 169, '2014-11-07', 9.4, 'https://upload.wikimedia.org/wikipedia/en/b/bc/Interstellar_film_poster.jpg', 'https://wsrv.nl/?url=image.tmdb.org/t/p/original/xJHokMbljvjADYdit5fK5VQsXEG.jpg', NULL, 'Christopher Nolan', 'Matthew McConaughey, Anne Hathaway, Jessica Chastain', 'T13', 'now_showing', '2026-05-19 07:40:55'),
(6, 'Guardians of the Galaxy Vol. 3', 'Nhóm Vệ binh Dải Ngân Hà đang cố gắng bảo vệ Rocket trong khi chống lại một kẻ thù mạnh mẽ muốn kết thúc mọi thứ.', 'Hành động, Phiêu lưu, Hài hước', 150, '2026-07-24', 8.7, 'https://upload.wikimedia.org/wikipedia/en/7/74/Guardians_of_the_Galaxy_Vol._3_poster.jpg', 'https://wsrv.nl/?url=image.tmdb.org/t/p/original/nHf61UzkfFno5X1ofIhugCPus2R.jpg', NULL, 'James Gunn', 'Chris Pratt, Zoe Saldana, Bradley Cooper', 'T13', 'coming_soon', '2026-05-19 07:40:55'),
(7, 'The Batman', 'Khi một kẻ giết người ám ảnh gây ra hành vi tội ác ở Gotham City, Batman buộc phải điều tra thành phố tham nhũng trong khi đối mặt với kẻ thù.', 'Hành động, Tội phạm, Chính kịch', 176, '2026-10-20', 8.8, 'https://upload.wikimedia.org/wikipedia/en/f/ff/The_Batman_%28film%29_poster.jpg', 'https://wsrv.nl/?url=image.tmdb.org/t/p/original/b0PlSFdDwbyK0cf5RxwDpaOJQvQ.jpg', NULL, 'Matt Reeves', 'Robert Pattinson, Zoë Kravitz, Paul Dano', 'T16', 'coming_soon', '2026-05-19 07:40:55'),
(8, 'Spider-Man: Across the Spider-Verse', 'Miles Morales trở lại trong một cuộc phiêu lưu sử thi mới của Spider-Man đưa Miles Morales trên khắp Đa Vũ Trụ.', 'Hoạt hình, Hành động, Phiêu lưu', 140, '2026-07-24', 9.1, 'https://wsrv.nl/?url=image.tmdb.org/t/p/w500/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg', 'https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1600', NULL, 'Joaquim Dos Santos', 'Shameik Moore, Hailee Steinfeld, Oscar Isaac', 'T13', 'coming_soon', '2026-05-19 07:40:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `movie_reviews`
--

CREATE TABLE `movie_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(10) UNSIGNED NOT NULL,
  `booking_code` varchar(50) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `movie_reviews`
--

INSERT INTO `movie_reviews` (`id`, `user_id`, `movie_id`, `booking_code`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 1, 'MF20260519001', 10, 'Hay mà đau ví :_))', '2026-05-19 10:37:23'),
(4, 3, 1, 'MF202605191E2ECE', 10, 'chào bạn', '2026-05-20 01:33:52');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`, `expires_at`) VALUES
(2, 'phatnha1702@gmail.com', '581450', '2026-05-19 14:58:40', '2026-05-19 22:13:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reconciliation_errors`
--

CREATE TABLE `reconciliation_errors` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_code` varchar(50) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `error_type` varchar(100) NOT NULL,
  `time_ago` varchar(50) NOT NULL,
  `sys_time` varchar(50) NOT NULL,
  `sys_amount` varchar(50) NOT NULL,
  `bank_time` varchar(50) NOT NULL,
  `bank_amount` varchar(50) NOT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `reconciliation_errors`
--

INSERT INTO `reconciliation_errors` (`id`, `ticket_code`, `branch_name`, `error_type`, `time_ago`, `sys_time`, `sys_amount`, `bank_time`, `bank_amount`, `is_resolved`) VALUES
(1, '#MF20260524ERR1', 'Galaxy Nguyễn Du', 'LỆCH GIÁ TIỀN (Hệ thống > Ngân hàng)', '2 phút trước', '24/05/2026 10:15', '120.000₫', '24/05/2026 10:17', '100.000₫', 0),
(2, '#MF20260524ERR2', 'CGV Vincom Center', 'THIẾU GIAO DỊCH NGÂN HÀNG', '15 phút trước', '24/05/2026 09:40', '150.000₫', '—', '0₫', 0),
(3, '#MF20260524ERR3', 'Galaxy Nguyễn Du', 'SAI BIÊN LAI THANH TOÁN', '1 giờ trước', '24/05/2026 08:12', '90.000₫', '24/05/2026 08:12', '80.000₫', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sales_trend`
--

CREATE TABLE `sales_trend` (
  `id` int(10) UNSIGNED NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `tickets_sold` int(11) NOT NULL DEFAULT 0,
  `order_index` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sales_trend`
--

INSERT INTO `sales_trend` (`id`, `day_name`, `tickets_sold`, `order_index`) VALUES
(1, 'Thứ Hai', 450, 1),
(2, 'Thứ Ba', 380, 2),
(3, 'Thứ Tư', 520, 3),
(4, 'Thứ Năm', 410, 4),
(5, 'Thứ Sáu', 680, 5),
(6, 'Thứ Bảy', 950, 6),
(7, 'Chủ Nhật', 1100, 7);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `seats`
--

CREATE TABLE `seats` (
  `id` int(10) UNSIGNED NOT NULL,
  `showtime_id` int(10) UNSIGNED NOT NULL,
  `seat_row` char(1) NOT NULL COMMENT 'A-J',
  `seat_num` tinyint(4) NOT NULL,
  `seat_type` enum('standard','vip','sweetbox') DEFAULT 'standard',
  `status` enum('available','booked','hold') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `showtimes`
--

CREATE TABLE `showtimes` (
  `id` int(10) UNSIGNED NOT NULL,
  `movie_id` int(10) UNSIGNED NOT NULL,
  `cinema_id` int(10) UNSIGNED NOT NULL,
  `hall_name` varchar(50) DEFAULT 'Phòng 01',
  `show_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `format` enum('2D','3D','IMAX','PREMIUM','4DX') DEFAULT '2D',
  `subtitle_type` enum('Phụ đề','Lồng tiếng','Thuyết minh') DEFAULT 'Phụ đề',
  `price` decimal(10,0) DEFAULT 90000,
  `total_seats` int(11) DEFAULT 120,
  `available_seats` int(11) DEFAULT 120,
  `is_cancelled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `showtimes`
--

INSERT INTO `showtimes` (`id`, `movie_id`, `cinema_id`, `hall_name`, `show_date`, `start_time`, `end_time`, `format`, `subtitle_type`, `price`, `total_seats`, `available_seats`, `is_cancelled`) VALUES
(16, 4, 1, 'Phòng 03', '2026-05-19', '09:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(17, 4, 1, 'Phòng 03', '2026-05-19', '12:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(18, 4, 1, 'Phòng 02', '2026-05-19', '15:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(19, 4, 1, 'Phòng 05', '2026-05-19', '18:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(20, 1, 1, 'Phòng 01', '2026-05-19', '10:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(21, 1, 1, 'Phòng 02', '2026-05-19', '13:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(22, 1, 1, 'Phòng 02', '2026-05-19', '16:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(23, 1, 1, 'Phòng 02', '2026-05-19', '19:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(24, 1, 1, 'Phòng 02', '2026-05-19', '22:50:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 118, 0),
(25, 6, 1, 'Phòng 01', '2026-05-19', '11:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(26, 6, 1, 'Phòng 01', '2026-05-19', '14:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(27, 6, 1, 'Phòng 03', '2026-05-19', '17:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(28, 8, 1, 'Phòng 04', '2026-05-19', '12:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(29, 8, 1, 'Phòng 03', '2026-05-19', '15:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(30, 8, 1, 'Phòng 03', '2026-05-19', '18:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(31, 8, 1, 'Phòng 04', '2026-05-19', '21:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(32, 3, 2, 'Phòng 05', '2026-05-19', '09:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(33, 3, 2, 'Phòng 02', '2026-05-19', '12:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(34, 3, 2, 'Phòng 05', '2026-05-19', '15:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(35, 3, 2, 'Phòng 04', '2026-05-19', '18:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(36, 7, 2, 'Phòng 02', '2026-05-19', '10:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(37, 7, 2, 'Phòng 06', '2026-05-19', '13:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(38, 7, 2, 'Phòng 03', '2026-05-19', '16:30:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(39, 7, 2, 'Phòng 04', '2026-05-19', '19:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(40, 6, 2, 'Phòng 01', '2026-05-19', '11:40:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(41, 6, 2, 'Phòng 03', '2026-05-19', '14:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(42, 6, 2, 'Phòng 06', '2026-05-19', '17:50:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(43, 1, 2, 'Phòng 06', '2026-05-19', '12:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(44, 1, 2, 'Phòng 03', '2026-05-19', '15:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(45, 1, 2, 'Phòng 06', '2026-05-19', '18:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(46, 1, 2, 'Phòng 06', '2026-05-19', '21:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(47, 8, 3, 'Phòng 05', '2026-05-19', '09:30:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(48, 8, 3, 'Phòng 03', '2026-05-19', '12:30:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(49, 8, 3, 'Phòng 01', '2026-05-19', '15:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(50, 8, 3, 'Phòng 03', '2026-05-19', '18:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(51, 6, 3, 'Phòng 05', '2026-05-19', '10:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(52, 6, 3, 'Phòng 01', '2026-05-19', '13:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(53, 6, 3, 'Phòng 04', '2026-05-19', '16:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(54, 6, 3, 'Phòng 04', '2026-05-19', '19:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(55, 2, 3, 'Phòng 02', '2026-05-19', '11:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(56, 2, 3, 'Phòng 02', '2026-05-19', '14:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(57, 2, 3, 'Phòng 04', '2026-05-19', '17:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(58, 2, 3, 'Phòng 03', '2026-05-19', '20:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(59, 7, 3, 'Phòng 05', '2026-05-19', '12:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(60, 7, 3, 'Phòng 05', '2026-05-19', '15:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(61, 7, 3, 'Phòng 01', '2026-05-19', '18:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(62, 7, 3, 'Phòng 06', '2026-05-19', '21:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(63, 3, 4, 'Phòng 06', '2026-05-19', '09:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(64, 3, 4, 'Phòng 03', '2026-05-19', '12:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(65, 3, 4, 'Phòng 01', '2026-05-19', '15:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(66, 3, 4, 'Phòng 03', '2026-05-19', '18:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(67, 3, 4, 'Phòng 02', '2026-05-19', '21:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(68, 2, 4, 'Phòng 03', '2026-05-19', '10:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(69, 2, 4, 'Phòng 06', '2026-05-19', '13:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(70, 2, 4, 'Phòng 01', '2026-05-19', '16:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(71, 4, 4, 'Phòng 06', '2026-05-19', '11:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(72, 4, 4, 'Phòng 04', '2026-05-19', '14:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(73, 4, 4, 'Phòng 03', '2026-05-19', '17:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(74, 4, 4, 'Phòng 04', '2026-05-19', '20:30:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(75, 8, 4, 'Phòng 06', '2026-05-19', '12:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(76, 8, 4, 'Phòng 05', '2026-05-19', '15:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(77, 8, 4, 'Phòng 05', '2026-05-19', '18:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(78, 8, 4, 'Phòng 05', '2026-05-19', '21:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(79, 7, 5, 'Phòng 06', '2026-05-19', '09:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(80, 7, 5, 'Phòng 01', '2026-05-19', '12:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(81, 7, 5, 'Phòng 03', '2026-05-19', '15:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(82, 7, 5, 'Phòng 06', '2026-05-19', '18:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(83, 7, 5, 'Phòng 04', '2026-05-19', '21:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(84, 1, 5, 'Phòng 04', '2026-05-19', '10:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(85, 1, 5, 'Phòng 02', '2026-05-19', '13:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(86, 1, 5, 'Phòng 05', '2026-05-19', '16:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(87, 1, 5, 'Phòng 03', '2026-05-19', '19:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 117, 0),
(88, 4, 5, 'Phòng 06', '2026-05-19', '11:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(89, 4, 5, 'Phòng 05', '2026-05-19', '14:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(90, 4, 5, 'Phòng 01', '2026-05-19', '17:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(91, 4, 5, 'Phòng 05', '2026-05-19', '20:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(92, 2, 5, 'Phòng 02', '2026-05-19', '12:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(93, 2, 5, 'Phòng 05', '2026-05-19', '15:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(94, 2, 5, 'Phòng 02', '2026-05-19', '18:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(95, 2, 5, 'Phòng 05', '2026-05-19', '21:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(96, 6, 1, 'Phòng 01', '2026-05-20', '09:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(97, 6, 1, 'Phòng 06', '2026-05-20', '12:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(98, 6, 1, 'Phòng 01', '2026-05-20', '15:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(99, 4, 1, 'Phòng 05', '2026-05-20', '10:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(100, 4, 1, 'Phòng 05', '2026-05-20', '13:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(101, 4, 1, 'Phòng 02', '2026-05-20', '16:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(102, 4, 1, 'Phòng 06', '2026-05-20', '19:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(103, 4, 1, 'Phòng 06', '2026-05-20', '22:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(104, 8, 1, 'Phòng 06', '2026-05-20', '11:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(105, 8, 1, 'Phòng 02', '2026-05-20', '14:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(106, 8, 1, 'Phòng 02', '2026-05-20', '17:00:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(107, 5, 1, 'Phòng 06', '2026-05-20', '12:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(108, 5, 1, 'Phòng 04', '2026-05-20', '15:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(109, 5, 1, 'Phòng 05', '2026-05-20', '18:50:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(110, 2, 2, 'Phòng 05', '2026-05-20', '09:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(111, 2, 2, 'Phòng 04', '2026-05-20', '12:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(112, 2, 2, 'Phòng 01', '2026-05-20', '15:30:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(113, 2, 2, 'Phòng 06', '2026-05-20', '18:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(114, 4, 2, 'Phòng 02', '2026-05-20', '10:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(115, 4, 2, 'Phòng 04', '2026-05-20', '13:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(116, 4, 2, 'Phòng 02', '2026-05-20', '16:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(117, 4, 2, 'Phòng 03', '2026-05-20', '19:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(118, 4, 2, 'Phòng 05', '2026-05-20', '22:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(119, 8, 2, 'Phòng 01', '2026-05-20', '11:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(120, 8, 2, 'Phòng 02', '2026-05-20', '14:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(121, 8, 2, 'Phòng 01', '2026-05-20', '17:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(122, 3, 2, 'Phòng 01', '2026-05-20', '12:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(123, 3, 2, 'Phòng 05', '2026-05-20', '15:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(124, 3, 2, 'Phòng 05', '2026-05-20', '18:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(125, 1, 3, 'Phòng 05', '2026-05-20', '09:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 117, 0),
(126, 1, 3, 'Phòng 01', '2026-05-20', '12:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(127, 1, 3, 'Phòng 04', '2026-05-20', '15:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(128, 1, 3, 'Phòng 02', '2026-05-20', '18:50:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(129, 1, 3, 'Phòng 01', '2026-05-20', '21:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(130, 3, 3, 'Phòng 05', '2026-05-20', '10:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(131, 3, 3, 'Phòng 06', '2026-05-20', '13:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(132, 3, 3, 'Phòng 04', '2026-05-20', '16:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(133, 7, 3, 'Phòng 04', '2026-05-20', '11:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(134, 7, 3, 'Phòng 05', '2026-05-20', '14:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(135, 7, 3, 'Phòng 06', '2026-05-20', '17:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(136, 7, 3, 'Phòng 03', '2026-05-20', '20:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(137, 5, 3, 'Phòng 02', '2026-05-20', '12:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(138, 5, 3, 'Phòng 05', '2026-05-20', '15:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(139, 5, 3, 'Phòng 03', '2026-05-20', '18:50:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(140, 5, 3, 'Phòng 04', '2026-05-20', '21:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(141, 7, 4, 'Phòng 05', '2026-05-20', '09:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(142, 7, 4, 'Phòng 02', '2026-05-20', '12:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(143, 7, 4, 'Phòng 05', '2026-05-20', '15:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(144, 5, 4, 'Phòng 06', '2026-05-20', '10:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(145, 5, 4, 'Phòng 02', '2026-05-20', '13:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(146, 5, 4, 'Phòng 05', '2026-05-20', '16:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(147, 5, 4, 'Phòng 05', '2026-05-20', '19:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(148, 5, 4, 'Phòng 04', '2026-05-20', '22:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(149, 6, 4, 'Phòng 03', '2026-05-20', '11:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(150, 6, 4, 'Phòng 05', '2026-05-20', '14:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(151, 6, 4, 'Phòng 04', '2026-05-20', '17:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(152, 8, 4, 'Phòng 04', '2026-05-20', '12:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(153, 8, 4, 'Phòng 05', '2026-05-20', '15:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(154, 8, 4, 'Phòng 01', '2026-05-20', '18:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(155, 8, 4, 'Phòng 02', '2026-05-20', '21:50:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(156, 3, 5, 'Phòng 05', '2026-05-20', '09:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(157, 3, 5, 'Phòng 05', '2026-05-20', '12:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 118, 0),
(158, 3, 5, 'Phòng 01', '2026-05-20', '15:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 118, 0),
(159, 5, 5, 'Phòng 04', '2026-05-20', '10:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 116, 0),
(160, 5, 5, 'Phòng 02', '2026-05-20', '13:50:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(161, 5, 5, 'Phòng 03', '2026-05-20', '16:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(162, 1, 5, 'Phòng 03', '2026-05-20', '11:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(163, 1, 5, 'Phòng 01', '2026-05-20', '14:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(164, 1, 5, 'Phòng 06', '2026-05-20', '17:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(165, 1, 5, 'Phòng 02', '2026-05-20', '20:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(166, 1, 5, 'Phòng 03', '2026-05-20', '23:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(167, 7, 5, 'Phòng 05', '2026-05-20', '12:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(168, 7, 5, 'Phòng 05', '2026-05-20', '15:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(169, 7, 5, 'Phòng 05', '2026-05-20', '18:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(170, 7, 5, 'Phòng 03', '2026-05-20', '21:50:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(171, 8, 1, 'Phòng 01', '2026-05-21', '09:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(172, 8, 1, 'Phòng 03', '2026-05-21', '12:00:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(173, 8, 1, 'Phòng 02', '2026-05-21', '15:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(174, 8, 1, 'Phòng 03', '2026-05-21', '18:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(175, 8, 1, 'Phòng 01', '2026-05-21', '21:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(176, 2, 1, 'Phòng 01', '2026-05-21', '10:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(177, 2, 1, 'Phòng 06', '2026-05-21', '13:00:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(178, 2, 1, 'Phòng 06', '2026-05-21', '16:40:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(179, 3, 1, 'Phòng 06', '2026-05-21', '11:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(180, 3, 1, 'Phòng 03', '2026-05-21', '14:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(181, 3, 1, 'Phòng 01', '2026-05-21', '17:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(182, 3, 1, 'Phòng 01', '2026-05-21', '20:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(183, 3, 1, 'Phòng 03', '2026-05-21', '23:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(184, 1, 1, 'Phòng 04', '2026-05-21', '12:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(185, 1, 1, 'Phòng 02', '2026-05-21', '15:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(186, 1, 1, 'Phòng 06', '2026-05-21', '18:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(187, 4, 2, 'Phòng 02', '2026-05-21', '09:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(188, 4, 2, 'Phòng 03', '2026-05-21', '12:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(189, 4, 2, 'Phòng 03', '2026-05-21', '15:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(190, 4, 2, 'Phòng 06', '2026-05-21', '18:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(191, 4, 2, 'Phòng 06', '2026-05-21', '21:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(192, 2, 2, 'Phòng 03', '2026-05-21', '10:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(193, 2, 2, 'Phòng 04', '2026-05-21', '13:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(194, 2, 2, 'Phòng 01', '2026-05-21', '16:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(195, 1, 2, 'Phòng 02', '2026-05-21', '11:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(196, 1, 2, 'Phòng 04', '2026-05-21', '14:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(197, 1, 2, 'Phòng 01', '2026-05-21', '17:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(198, 1, 2, 'Phòng 03', '2026-05-21', '20:50:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(199, 1, 2, 'Phòng 03', '2026-05-21', '23:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(200, 7, 2, 'Phòng 04', '2026-05-21', '12:30:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(201, 7, 2, 'Phòng 06', '2026-05-21', '15:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(202, 7, 2, 'Phòng 04', '2026-05-21', '18:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(203, 7, 3, 'Phòng 01', '2026-05-21', '09:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(204, 7, 3, 'Phòng 02', '2026-05-21', '12:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(205, 7, 3, 'Phòng 04', '2026-05-21', '15:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(206, 7, 3, 'Phòng 05', '2026-05-21', '18:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(207, 6, 3, 'Phòng 01', '2026-05-21', '10:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(208, 6, 3, 'Phòng 02', '2026-05-21', '13:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(209, 6, 3, 'Phòng 02', '2026-05-21', '16:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(210, 6, 3, 'Phòng 03', '2026-05-21', '19:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(211, 2, 3, 'Phòng 02', '2026-05-21', '11:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(212, 2, 3, 'Phòng 05', '2026-05-21', '14:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(213, 2, 3, 'Phòng 01', '2026-05-21', '17:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(214, 1, 3, 'Phòng 01', '2026-05-21', '12:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(215, 1, 3, 'Phòng 02', '2026-05-21', '15:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(216, 1, 3, 'Phòng 06', '2026-05-21', '18:30:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(217, 1, 3, 'Phòng 04', '2026-05-21', '21:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(218, 6, 4, 'Phòng 05', '2026-05-21', '09:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(219, 6, 4, 'Phòng 01', '2026-05-21', '12:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(220, 6, 4, 'Phòng 03', '2026-05-21', '15:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(221, 6, 4, 'Phòng 04', '2026-05-21', '18:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(222, 6, 4, 'Phòng 04', '2026-05-21', '21:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(223, 1, 4, 'Phòng 03', '2026-05-21', '10:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(224, 1, 4, 'Phòng 04', '2026-05-21', '13:40:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(225, 1, 4, 'Phòng 06', '2026-05-21', '16:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(226, 4, 4, 'Phòng 05', '2026-05-21', '11:30:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(227, 4, 4, 'Phòng 05', '2026-05-21', '14:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(228, 4, 4, 'Phòng 05', '2026-05-21', '17:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(229, 4, 4, 'Phòng 06', '2026-05-21', '20:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(230, 5, 4, 'Phòng 01', '2026-05-21', '12:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(231, 5, 4, 'Phòng 05', '2026-05-21', '15:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(232, 5, 4, 'Phòng 03', '2026-05-21', '18:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(233, 5, 4, 'Phòng 06', '2026-05-21', '21:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(234, 6, 5, 'Phòng 05', '2026-05-21', '09:50:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(235, 6, 5, 'Phòng 05', '2026-05-21', '12:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(236, 6, 5, 'Phòng 03', '2026-05-21', '15:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(237, 6, 5, 'Phòng 01', '2026-05-21', '18:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(238, 6, 5, 'Phòng 04', '2026-05-21', '21:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(239, 5, 5, 'Phòng 06', '2026-05-21', '10:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(240, 5, 5, 'Phòng 01', '2026-05-21', '13:30:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(241, 5, 5, 'Phòng 04', '2026-05-21', '16:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(242, 5, 5, 'Phòng 02', '2026-05-21', '19:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(243, 2, 5, 'Phòng 04', '2026-05-21', '11:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(244, 2, 5, 'Phòng 02', '2026-05-21', '14:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(245, 2, 5, 'Phòng 04', '2026-05-21', '17:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(246, 2, 5, 'Phòng 06', '2026-05-21', '20:30:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(247, 2, 5, 'Phòng 06', '2026-05-21', '23:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(248, 3, 5, 'Phòng 04', '2026-05-21', '12:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(249, 3, 5, 'Phòng 03', '2026-05-21', '15:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(250, 3, 5, 'Phòng 01', '2026-05-21', '18:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(251, 8, 1, 'Phòng 04', '2026-05-22', '09:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(252, 8, 1, 'Phòng 05', '2026-05-22', '12:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(253, 8, 1, 'Phòng 02', '2026-05-22', '15:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(254, 8, 1, 'Phòng 01', '2026-05-22', '18:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(255, 2, 1, 'Phòng 02', '2026-05-22', '10:30:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(256, 2, 1, 'Phòng 03', '2026-05-22', '13:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(257, 2, 1, 'Phòng 05', '2026-05-22', '16:40:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(258, 2, 1, 'Phòng 06', '2026-05-22', '19:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(259, 2, 1, 'Phòng 05', '2026-05-22', '22:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(260, 3, 1, 'Phòng 03', '2026-05-22', '11:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(261, 3, 1, 'Phòng 01', '2026-05-22', '14:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(262, 3, 1, 'Phòng 01', '2026-05-22', '17:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(263, 3, 1, 'Phòng 06', '2026-05-22', '20:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(264, 4, 1, 'Phòng 05', '2026-05-22', '12:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(265, 4, 1, 'Phòng 05', '2026-05-22', '15:30:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(266, 4, 1, 'Phòng 04', '2026-05-22', '18:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(267, 4, 1, 'Phòng 04', '2026-05-22', '21:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(268, 3, 2, 'Phòng 05', '2026-05-22', '09:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(269, 3, 2, 'Phòng 01', '2026-05-22', '12:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(270, 3, 2, 'Phòng 04', '2026-05-22', '15:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(271, 4, 2, 'Phòng 02', '2026-05-22', '10:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(272, 4, 2, 'Phòng 02', '2026-05-22', '13:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(273, 4, 2, 'Phòng 04', '2026-05-22', '16:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(274, 7, 2, 'Phòng 01', '2026-05-22', '11:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(275, 7, 2, 'Phòng 06', '2026-05-22', '14:50:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(276, 7, 2, 'Phòng 03', '2026-05-22', '17:50:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(277, 7, 2, 'Phòng 05', '2026-05-22', '20:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(278, 7, 2, 'Phòng 06', '2026-05-22', '23:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(279, 2, 2, 'Phòng 02', '2026-05-22', '12:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(280, 2, 2, 'Phòng 03', '2026-05-22', '15:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(281, 2, 2, 'Phòng 03', '2026-05-22', '18:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(282, 1, 3, 'Phòng 06', '2026-05-22', '09:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(283, 1, 3, 'Phòng 04', '2026-05-22', '12:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(284, 1, 3, 'Phòng 05', '2026-05-22', '15:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(285, 1, 3, 'Phòng 02', '2026-05-22', '18:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(286, 2, 3, 'Phòng 03', '2026-05-22', '10:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(287, 2, 3, 'Phòng 03', '2026-05-22', '13:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(288, 2, 3, 'Phòng 01', '2026-05-22', '16:30:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(289, 2, 3, 'Phòng 01', '2026-05-22', '19:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(290, 8, 3, 'Phòng 01', '2026-05-22', '11:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(291, 8, 3, 'Phòng 02', '2026-05-22', '14:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(292, 8, 3, 'Phòng 03', '2026-05-22', '17:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(293, 7, 3, 'Phòng 06', '2026-05-22', '12:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(294, 7, 3, 'Phòng 05', '2026-05-22', '15:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(295, 7, 3, 'Phòng 03', '2026-05-22', '18:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(296, 7, 3, 'Phòng 04', '2026-05-22', '21:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(297, 5, 4, 'Phòng 01', '2026-05-22', '09:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(298, 5, 4, 'Phòng 01', '2026-05-22', '12:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(299, 5, 4, 'Phòng 04', '2026-05-22', '15:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(300, 5, 4, 'Phòng 03', '2026-05-22', '18:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(301, 7, 4, 'Phòng 02', '2026-05-22', '10:00:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(302, 7, 4, 'Phòng 01', '2026-05-22', '13:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(303, 7, 4, 'Phòng 04', '2026-05-22', '16:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(304, 7, 4, 'Phòng 01', '2026-05-22', '19:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(305, 3, 4, 'Phòng 06', '2026-05-22', '11:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(306, 3, 4, 'Phòng 03', '2026-05-22', '14:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(307, 3, 4, 'Phòng 01', '2026-05-22', '17:40:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(308, 4, 4, 'Phòng 05', '2026-05-22', '12:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(309, 4, 4, 'Phòng 03', '2026-05-22', '15:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(310, 4, 4, 'Phòng 05', '2026-05-22', '18:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(311, 4, 4, 'Phòng 03', '2026-05-22', '21:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(312, 6, 5, 'Phòng 05', '2026-05-22', '09:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(313, 6, 5, 'Phòng 01', '2026-05-22', '12:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(314, 6, 5, 'Phòng 04', '2026-05-22', '15:50:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(315, 6, 5, 'Phòng 06', '2026-05-22', '18:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(316, 6, 5, 'Phòng 05', '2026-05-22', '21:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(317, 8, 5, 'Phòng 05', '2026-05-22', '10:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(318, 8, 5, 'Phòng 02', '2026-05-22', '13:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(319, 8, 5, 'Phòng 04', '2026-05-22', '16:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(320, 8, 5, 'Phòng 05', '2026-05-22', '19:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(321, 4, 5, 'Phòng 06', '2026-05-22', '11:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(322, 4, 5, 'Phòng 05', '2026-05-22', '14:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(323, 4, 5, 'Phòng 01', '2026-05-22', '17:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(324, 4, 5, 'Phòng 02', '2026-05-22', '20:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(325, 5, 5, 'Phòng 05', '2026-05-22', '12:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(326, 5, 5, 'Phòng 05', '2026-05-22', '15:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(327, 5, 5, 'Phòng 05', '2026-05-22', '18:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(328, 6, 1, 'Phòng 03', '2026-05-23', '09:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(329, 6, 1, 'Phòng 06', '2026-05-23', '12:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(330, 6, 1, 'Phòng 02', '2026-05-23', '15:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(331, 6, 1, 'Phòng 04', '2026-05-23', '18:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(332, 6, 1, 'Phòng 01', '2026-05-23', '21:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(333, 4, 1, 'Phòng 05', '2026-05-23', '10:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(334, 4, 1, 'Phòng 05', '2026-05-23', '13:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(335, 4, 1, 'Phòng 06', '2026-05-23', '16:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(336, 4, 1, 'Phòng 02', '2026-05-23', '19:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 117, 0),
(337, 4, 1, 'Phòng 04', '2026-05-23', '22:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(338, 1, 1, 'Phòng 04', '2026-05-23', '11:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(339, 1, 1, 'Phòng 03', '2026-05-23', '14:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(340, 1, 1, 'Phòng 03', '2026-05-23', '17:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(341, 5, 1, 'Phòng 03', '2026-05-23', '12:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(342, 5, 1, 'Phòng 02', '2026-05-23', '15:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(343, 5, 1, 'Phòng 06', '2026-05-23', '18:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(344, 3, 2, 'Phòng 04', '2026-05-23', '09:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(345, 3, 2, 'Phòng 06', '2026-05-23', '12:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(346, 3, 2, 'Phòng 06', '2026-05-23', '15:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(347, 8, 2, 'Phòng 02', '2026-05-23', '10:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(348, 8, 2, 'Phòng 06', '2026-05-23', '13:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(349, 8, 2, 'Phòng 03', '2026-05-23', '16:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(350, 8, 2, 'Phòng 06', '2026-05-23', '19:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(351, 7, 2, 'Phòng 01', '2026-05-23', '11:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(352, 7, 2, 'Phòng 04', '2026-05-23', '14:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(353, 7, 2, 'Phòng 01', '2026-05-23', '17:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(354, 7, 2, 'Phòng 06', '2026-05-23', '20:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(355, 7, 2, 'Phòng 05', '2026-05-23', '23:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(356, 4, 2, 'Phòng 06', '2026-05-23', '12:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(357, 4, 2, 'Phòng 04', '2026-05-23', '15:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(358, 4, 2, 'Phòng 02', '2026-05-23', '18:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(359, 4, 2, 'Phòng 06', '2026-05-23', '21:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(360, 2, 3, 'Phòng 04', '2026-05-23', '09:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(361, 2, 3, 'Phòng 06', '2026-05-23', '12:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(362, 2, 3, 'Phòng 04', '2026-05-23', '15:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(363, 1, 3, 'Phòng 06', '2026-05-23', '10:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(364, 1, 3, 'Phòng 03', '2026-05-23', '13:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(365, 1, 3, 'Phòng 04', '2026-05-23', '16:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(366, 1, 3, 'Phòng 05', '2026-05-23', '19:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(367, 1, 3, 'Phòng 04', '2026-05-23', '22:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(368, 7, 3, 'Phòng 06', '2026-05-23', '11:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(369, 7, 3, 'Phòng 05', '2026-05-23', '14:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(370, 7, 3, 'Phòng 04', '2026-05-23', '17:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(371, 8, 3, 'Phòng 03', '2026-05-23', '12:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(372, 8, 3, 'Phòng 04', '2026-05-23', '15:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(373, 8, 3, 'Phòng 01', '2026-05-23', '18:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(374, 8, 4, 'Phòng 03', '2026-05-23', '09:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(375, 8, 4, 'Phòng 02', '2026-05-23', '12:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(376, 8, 4, 'Phòng 04', '2026-05-23', '15:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(377, 8, 4, 'Phòng 02', '2026-05-23', '18:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(378, 8, 4, 'Phòng 02', '2026-05-23', '21:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(379, 2, 4, 'Phòng 06', '2026-05-23', '10:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(380, 2, 4, 'Phòng 01', '2026-05-23', '13:40:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(381, 2, 4, 'Phòng 06', '2026-05-23', '16:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(382, 6, 4, 'Phòng 04', '2026-05-23', '11:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(383, 6, 4, 'Phòng 04', '2026-05-23', '14:50:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(384, 6, 4, 'Phòng 05', '2026-05-23', '17:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(385, 6, 4, 'Phòng 01', '2026-05-23', '20:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(386, 6, 4, 'Phòng 06', '2026-05-23', '23:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(387, 3, 4, 'Phòng 02', '2026-05-23', '12:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(388, 3, 4, 'Phòng 04', '2026-05-23', '15:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(389, 3, 4, 'Phòng 03', '2026-05-23', '18:30:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(390, 3, 4, 'Phòng 04', '2026-05-23', '21:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(391, 3, 5, 'Phòng 05', '2026-05-23', '09:50:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(392, 3, 5, 'Phòng 04', '2026-05-23', '12:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(393, 3, 5, 'Phòng 02', '2026-05-23', '15:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(394, 3, 5, 'Phòng 04', '2026-05-23', '18:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(395, 5, 5, 'Phòng 01', '2026-05-23', '10:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(396, 5, 5, 'Phòng 06', '2026-05-23', '13:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(397, 5, 5, 'Phòng 01', '2026-05-23', '16:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(398, 4, 5, 'Phòng 02', '2026-05-23', '11:30:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(399, 4, 5, 'Phòng 04', '2026-05-23', '14:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(400, 4, 5, 'Phòng 02', '2026-05-23', '17:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(401, 4, 5, 'Phòng 04', '2026-05-23', '20:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(402, 4, 5, 'Phòng 04', '2026-05-23', '23:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(403, 2, 5, 'Phòng 06', '2026-05-23', '12:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(404, 2, 5, 'Phòng 01', '2026-05-23', '15:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(405, 2, 5, 'Phòng 01', '2026-05-23', '18:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(406, 2, 5, 'Phòng 04', '2026-05-23', '21:30:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(407, 7, 1, 'Phòng 01', '2026-05-24', '09:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(408, 7, 1, 'Phòng 05', '2026-05-24', '12:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(409, 7, 1, 'Phòng 05', '2026-05-24', '15:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(410, 7, 1, 'Phòng 06', '2026-05-24', '18:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(411, 7, 1, 'Phòng 03', '2026-05-24', '21:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(412, 1, 1, 'Phòng 04', '2026-05-24', '10:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(413, 1, 1, 'Phòng 04', '2026-05-24', '13:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(414, 1, 1, 'Phòng 02', '2026-05-24', '16:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(415, 1, 1, 'Phòng 02', '2026-05-24', '19:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(416, 8, 1, 'Phòng 06', '2026-05-24', '11:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(417, 8, 1, 'Phòng 04', '2026-05-24', '14:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(418, 8, 1, 'Phòng 02', '2026-05-24', '17:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(419, 8, 1, 'Phòng 06', '2026-05-24', '20:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(420, 4, 1, 'Phòng 01', '2026-05-24', '12:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(421, 4, 1, 'Phòng 05', '2026-05-24', '15:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(422, 4, 1, 'Phòng 04', '2026-05-24', '18:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(423, 4, 1, 'Phòng 04', '2026-05-24', '21:00:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(424, 3, 2, 'Phòng 04', '2026-05-24', '09:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(425, 3, 2, 'Phòng 03', '2026-05-24', '12:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(426, 3, 2, 'Phòng 06', '2026-05-24', '15:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(427, 3, 2, 'Phòng 02', '2026-05-24', '18:40:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(428, 3, 2, 'Phòng 05', '2026-05-24', '21:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(429, 2, 2, 'Phòng 02', '2026-05-24', '10:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(430, 2, 2, 'Phòng 05', '2026-05-24', '13:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(431, 2, 2, 'Phòng 06', '2026-05-24', '16:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(432, 2, 2, 'Phòng 02', '2026-05-24', '19:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(433, 2, 2, 'Phòng 04', '2026-05-24', '22:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(434, 7, 2, 'Phòng 01', '2026-05-24', '11:40:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(435, 7, 2, 'Phòng 03', '2026-05-24', '14:50:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(436, 7, 2, 'Phòng 04', '2026-05-24', '17:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(437, 7, 2, 'Phòng 04', '2026-05-24', '20:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(438, 5, 2, 'Phòng 05', '2026-05-24', '12:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(439, 5, 2, 'Phòng 05', '2026-05-24', '15:00:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(440, 5, 2, 'Phòng 04', '2026-05-24', '18:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(441, 5, 2, 'Phòng 01', '2026-05-24', '21:50:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(442, 2, 3, 'Phòng 03', '2026-05-24', '09:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(443, 2, 3, 'Phòng 04', '2026-05-24', '12:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(444, 2, 3, 'Phòng 01', '2026-05-24', '15:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(445, 2, 3, 'Phòng 03', '2026-05-24', '18:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(446, 2, 3, 'Phòng 03', '2026-05-24', '21:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(447, 7, 3, 'Phòng 04', '2026-05-24', '10:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(448, 7, 3, 'Phòng 02', '2026-05-24', '13:50:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(449, 7, 3, 'Phòng 06', '2026-05-24', '16:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(450, 3, 3, 'Phòng 06', '2026-05-24', '11:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(451, 3, 3, 'Phòng 04', '2026-05-24', '14:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(452, 3, 3, 'Phòng 04', '2026-05-24', '17:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(453, 3, 3, 'Phòng 03', '2026-05-24', '20:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(454, 6, 3, 'Phòng 05', '2026-05-24', '12:10:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(455, 6, 3, 'Phòng 03', '2026-05-24', '15:30:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(456, 6, 3, 'Phòng 04', '2026-05-24', '18:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(457, 6, 3, 'Phòng 01', '2026-05-24', '21:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(458, 2, 4, 'Phòng 06', '2026-05-24', '09:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(459, 2, 4, 'Phòng 01', '2026-05-24', '12:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(460, 2, 4, 'Phòng 04', '2026-05-24', '15:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(461, 5, 4, 'Phòng 02', '2026-05-24', '10:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(462, 5, 4, 'Phòng 03', '2026-05-24', '13:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(463, 5, 4, 'Phòng 04', '2026-05-24', '16:30:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(464, 5, 4, 'Phòng 05', '2026-05-24', '19:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(465, 5, 4, 'Phòng 02', '2026-05-24', '22:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(466, 3, 4, 'Phòng 03', '2026-05-24', '11:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(467, 3, 4, 'Phòng 06', '2026-05-24', '14:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(468, 3, 4, 'Phòng 06', '2026-05-24', '17:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(469, 3, 4, 'Phòng 06', '2026-05-24', '20:30:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(470, 3, 4, 'Phòng 04', '2026-05-24', '23:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(471, 7, 4, 'Phòng 02', '2026-05-24', '12:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(472, 7, 4, 'Phòng 04', '2026-05-24', '15:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(473, 7, 4, 'Phòng 05', '2026-05-24', '18:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(474, 7, 4, 'Phòng 03', '2026-05-24', '21:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(475, 3, 5, 'Phòng 05', '2026-05-24', '09:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(476, 3, 5, 'Phòng 02', '2026-05-24', '12:00:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(477, 3, 5, 'Phòng 06', '2026-05-24', '15:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(478, 4, 5, 'Phòng 05', '2026-05-24', '10:30:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(479, 4, 5, 'Phòng 04', '2026-05-24', '13:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(480, 4, 5, 'Phòng 06', '2026-05-24', '16:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(481, 4, 5, 'Phòng 02', '2026-05-24', '19:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(482, 8, 5, 'Phòng 06', '2026-05-24', '11:10:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(483, 8, 5, 'Phòng 05', '2026-05-24', '14:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(484, 8, 5, 'Phòng 06', '2026-05-24', '17:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(485, 8, 5, 'Phòng 01', '2026-05-24', '20:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(486, 8, 5, 'Phòng 06', '2026-05-24', '23:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(487, 5, 5, 'Phòng 05', '2026-05-24', '12:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(488, 5, 5, 'Phòng 04', '2026-05-24', '15:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(489, 5, 5, 'Phòng 03', '2026-05-24', '18:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(490, 5, 5, 'Phòng 05', '2026-05-24', '21:00:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(491, 4, 1, 'Phòng 02', '2026-05-25', '09:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(492, 4, 1, 'Phòng 01', '2026-05-25', '12:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(493, 4, 1, 'Phòng 04', '2026-05-25', '15:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(494, 3, 1, 'Phòng 06', '2026-05-25', '10:50:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(495, 3, 1, 'Phòng 01', '2026-05-25', '13:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(496, 3, 1, 'Phòng 04', '2026-05-25', '16:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(497, 3, 1, 'Phòng 02', '2026-05-25', '19:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(498, 3, 1, 'Phòng 06', '2026-05-25', '22:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(499, 1, 1, 'Phòng 04', '2026-05-25', '11:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 117, 1),
(500, 1, 1, 'Phòng 03', '2026-05-25', '14:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(501, 1, 1, 'Phòng 02', '2026-05-25', '17:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 117, 0),
(502, 1, 1, 'Phòng 03', '2026-05-25', '20:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(503, 1, 1, 'Phòng 06', '2026-05-25', '23:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 115, 0),
(504, 6, 1, 'Phòng 02', '2026-05-25', '12:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 117, 0),
(505, 6, 1, 'Phòng 01', '2026-05-25', '15:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(506, 6, 1, 'Phòng 02', '2026-05-25', '18:50:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(507, 6, 1, 'Phòng 02', '2026-05-25', '21:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(508, 3, 2, 'Phòng 06', '2026-05-25', '09:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(509, 3, 2, 'Phòng 03', '2026-05-25', '12:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(510, 3, 2, 'Phòng 06', '2026-05-25', '15:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(511, 3, 2, 'Phòng 02', '2026-05-25', '18:30:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(512, 3, 2, 'Phòng 03', '2026-05-25', '21:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(513, 8, 2, 'Phòng 05', '2026-05-25', '10:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(514, 8, 2, 'Phòng 05', '2026-05-25', '13:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(515, 8, 2, 'Phòng 04', '2026-05-25', '16:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(516, 5, 2, 'Phòng 02', '2026-05-25', '11:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(517, 5, 2, 'Phòng 04', '2026-05-25', '14:00:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(518, 5, 2, 'Phòng 01', '2026-05-25', '17:30:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(519, 5, 2, 'Phòng 01', '2026-05-25', '20:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(520, 5, 2, 'Phòng 06', '2026-05-25', '23:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 117, 0),
(521, 2, 2, 'Phòng 04', '2026-05-25', '12:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(522, 2, 2, 'Phòng 05', '2026-05-25', '15:20:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(523, 2, 2, 'Phòng 01', '2026-05-25', '18:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(524, 2, 3, 'Phòng 05', '2026-05-25', '09:30:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(525, 2, 3, 'Phòng 01', '2026-05-25', '12:00:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(526, 2, 3, 'Phòng 03', '2026-05-25', '15:20:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(527, 4, 3, 'Phòng 06', '2026-05-25', '10:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(528, 4, 3, 'Phòng 05', '2026-05-25', '13:00:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(529, 4, 3, 'Phòng 05', '2026-05-25', '16:10:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(530, 4, 3, 'Phòng 04', '2026-05-25', '19:30:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(531, 3, 3, 'Phòng 05', '2026-05-25', '11:40:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(532, 3, 3, 'Phòng 01', '2026-05-25', '14:10:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(533, 3, 3, 'Phòng 05', '2026-05-25', '17:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(534, 3, 3, 'Phòng 06', '2026-05-25', '20:20:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(535, 3, 3, 'Phòng 04', '2026-05-25', '23:30:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0);
INSERT INTO `showtimes` (`id`, `movie_id`, `cinema_id`, `hall_name`, `show_date`, `start_time`, `end_time`, `format`, `subtitle_type`, `price`, `total_seats`, `available_seats`, `is_cancelled`) VALUES
(536, 7, 3, 'Phòng 02', '2026-05-25', '12:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(537, 7, 3, 'Phòng 05', '2026-05-25', '15:50:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(538, 7, 3, 'Phòng 05', '2026-05-25', '18:10:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(539, 7, 3, 'Phòng 05', '2026-05-25', '21:20:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(540, 5, 4, 'Phòng 06', '2026-05-25', '09:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(541, 5, 4, 'Phòng 06', '2026-05-25', '12:10:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(542, 5, 4, 'Phòng 02', '2026-05-25', '15:40:00', NULL, '2D', 'Lồng tiếng', 120000, 120, 120, 0),
(543, 7, 4, 'Phòng 03', '2026-05-25', '10:20:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(544, 7, 4, 'Phòng 05', '2026-05-25', '13:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(545, 7, 4, 'Phòng 03', '2026-05-25', '16:50:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(546, 7, 4, 'Phòng 02', '2026-05-25', '19:40:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(547, 8, 4, 'Phòng 03', '2026-05-25', '11:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(548, 8, 4, 'Phòng 03', '2026-05-25', '14:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(549, 8, 4, 'Phòng 03', '2026-05-25', '17:20:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(550, 8, 4, 'Phòng 01', '2026-05-25', '20:50:00', NULL, '2D', 'Phụ đề', 120000, 120, 120, 0),
(551, 6, 4, 'Phòng 04', '2026-05-25', '12:40:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 120, 0),
(552, 6, 4, 'Phòng 05', '2026-05-25', '15:40:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(553, 6, 4, 'Phòng 02', '2026-05-25', '18:40:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(554, 6, 4, 'Phòng 03', '2026-05-25', '21:20:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(555, 7, 5, 'Phòng 02', '2026-05-25', '09:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(556, 7, 5, 'Phòng 05', '2026-05-25', '12:20:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(557, 7, 5, 'Phòng 04', '2026-05-25', '15:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(558, 6, 5, 'Phòng 06', '2026-05-25', '10:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(559, 6, 5, 'Phòng 03', '2026-05-25', '13:40:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(560, 6, 5, 'Phòng 05', '2026-05-25', '16:50:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(561, 6, 5, 'Phòng 06', '2026-05-25', '19:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 120, 0),
(562, 3, 5, 'Phòng 01', '2026-05-25', '11:00:00', NULL, 'IMAX', 'Phụ đề', 200000, 120, 120, 0),
(563, 3, 5, 'Phòng 04', '2026-05-25', '14:00:00', NULL, '3D', 'Lồng tiếng', 120000, 120, 120, 0),
(564, 3, 5, 'Phòng 06', '2026-05-25', '17:10:00', NULL, 'IMAX', 'Lồng tiếng', 200000, 120, 120, 0),
(565, 1, 5, 'Phòng 01', '2026-05-25', '12:50:00', NULL, 'PREMIUM', 'Lồng tiếng', 200000, 120, 117, 1),
(566, 1, 5, 'Phòng 06', '2026-05-25', '15:00:00', NULL, 'PREMIUM', 'Phụ đề', 200000, 120, 116, 0),
(567, 1, 5, 'Phòng 02', '2026-05-25', '18:10:00', NULL, '3D', 'Phụ đề', 120000, 120, 120, 0),
(569, 2, 5, 'Phòng chiếu 1', '2026-05-15', '21:00:00', '00:32:00', 'IMAX', 'Phụ đề', 80000, 100, 100, 0),
(572, 1, 1, 'Phòng 01', '2026-05-26', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(573, 4, 1, 'Phòng 01', '2026-05-26', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(574, 3, 1, 'Phòng 01', '2026-05-26', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(575, 5, 1, 'Phòng 01', '2026-05-26', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(576, 2, 1, 'Phòng 02', '2026-05-26', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(577, 5, 1, 'Phòng 02', '2026-05-26', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(578, 1, 1, 'Phòng 02', '2026-05-26', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(579, 4, 1, 'Phòng 02', '2026-05-26', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(580, 3, 1, 'Phòng 03', '2026-05-26', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(581, 1, 1, 'Phòng 03', '2026-05-26', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(582, 2, 1, 'Phòng 03', '2026-05-26', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(583, 4, 1, 'Phòng 03', '2026-05-26', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 99, 0),
(584, 1, 2, 'Phòng 01', '2026-05-26', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(585, 4, 2, 'Phòng 01', '2026-05-26', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(586, 3, 2, 'Phòng 01', '2026-05-26', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(587, 5, 2, 'Phòng 01', '2026-05-26', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(588, 2, 2, 'Phòng 02', '2026-05-26', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(589, 5, 2, 'Phòng 02', '2026-05-26', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(590, 1, 2, 'Phòng 02', '2026-05-26', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(591, 4, 2, 'Phòng 02', '2026-05-26', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(592, 3, 2, 'Phòng 03', '2026-05-26', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(593, 1, 2, 'Phòng 03', '2026-05-26', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(594, 2, 2, 'Phòng 03', '2026-05-26', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(595, 4, 2, 'Phòng 03', '2026-05-26', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(596, 1, 3, 'Phòng 01', '2026-05-26', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(597, 4, 3, 'Phòng 01', '2026-05-26', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(598, 3, 3, 'Phòng 01', '2026-05-26', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(599, 5, 3, 'Phòng 01', '2026-05-26', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(600, 2, 3, 'Phòng 02', '2026-05-26', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(601, 5, 3, 'Phòng 02', '2026-05-26', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(602, 1, 3, 'Phòng 02', '2026-05-26', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(603, 4, 3, 'Phòng 02', '2026-05-26', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(604, 3, 3, 'Phòng 03', '2026-05-26', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(605, 1, 3, 'Phòng 03', '2026-05-26', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(606, 2, 3, 'Phòng 03', '2026-05-26', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(607, 4, 3, 'Phòng 03', '2026-05-26', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(608, 1, 4, 'Phòng 01', '2026-05-26', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(609, 4, 4, 'Phòng 01', '2026-05-26', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(610, 3, 4, 'Phòng 01', '2026-05-26', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(611, 5, 4, 'Phòng 01', '2026-05-26', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(612, 2, 4, 'Phòng 02', '2026-05-26', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(613, 5, 4, 'Phòng 02', '2026-05-26', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(614, 1, 4, 'Phòng 02', '2026-05-26', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(615, 4, 4, 'Phòng 02', '2026-05-26', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(616, 3, 4, 'Phòng 03', '2026-05-26', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(617, 1, 4, 'Phòng 03', '2026-05-26', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(618, 2, 4, 'Phòng 03', '2026-05-26', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(619, 4, 4, 'Phòng 03', '2026-05-26', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(620, 1, 5, 'Phòng 01', '2026-05-26', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(621, 4, 5, 'Phòng 01', '2026-05-26', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(622, 3, 5, 'Phòng 01', '2026-05-26', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(623, 5, 5, 'Phòng 01', '2026-05-26', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(624, 2, 5, 'Phòng 02', '2026-05-26', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(625, 5, 5, 'Phòng 02', '2026-05-26', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(626, 1, 5, 'Phòng 02', '2026-05-26', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(627, 4, 5, 'Phòng 02', '2026-05-26', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(628, 3, 5, 'Phòng 03', '2026-05-26', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(629, 1, 5, 'Phòng 03', '2026-05-26', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(630, 2, 5, 'Phòng 03', '2026-05-26', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(631, 4, 5, 'Phòng 03', '2026-05-26', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(632, 1, 1, 'Phòng 01', '2026-05-27', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(633, 4, 1, 'Phòng 01', '2026-05-27', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(634, 3, 1, 'Phòng 01', '2026-05-27', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 98, 1),
(635, 5, 1, 'Phòng 01', '2026-05-27', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(636, 2, 1, 'Phòng 02', '2026-05-27', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(637, 5, 1, 'Phòng 02', '2026-05-27', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(638, 1, 1, 'Phòng 02', '2026-05-27', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(639, 4, 1, 'Phòng 02', '2026-05-27', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(640, 3, 1, 'Phòng 03', '2026-05-27', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(641, 1, 1, 'Phòng 03', '2026-05-27', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(642, 2, 1, 'Phòng 03', '2026-05-27', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(643, 4, 1, 'Phòng 03', '2026-05-27', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(644, 1, 2, 'Phòng 01', '2026-05-27', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(645, 4, 2, 'Phòng 01', '2026-05-27', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(646, 3, 2, 'Phòng 01', '2026-05-27', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(647, 5, 2, 'Phòng 01', '2026-05-27', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(648, 2, 2, 'Phòng 02', '2026-05-27', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(649, 5, 2, 'Phòng 02', '2026-05-27', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(650, 1, 2, 'Phòng 02', '2026-05-27', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(651, 4, 2, 'Phòng 02', '2026-05-27', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(652, 3, 2, 'Phòng 03', '2026-05-27', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(653, 1, 2, 'Phòng 03', '2026-05-27', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(654, 2, 2, 'Phòng 03', '2026-05-27', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(655, 4, 2, 'Phòng 03', '2026-05-27', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(656, 1, 3, 'Phòng 01', '2026-05-27', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(657, 4, 3, 'Phòng 01', '2026-05-27', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(658, 3, 3, 'Phòng 01', '2026-05-27', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(659, 5, 3, 'Phòng 01', '2026-05-27', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(660, 2, 3, 'Phòng 02', '2026-05-27', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(661, 5, 3, 'Phòng 02', '2026-05-27', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(662, 1, 3, 'Phòng 02', '2026-05-27', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(663, 4, 3, 'Phòng 02', '2026-05-27', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(664, 3, 3, 'Phòng 03', '2026-05-27', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(665, 1, 3, 'Phòng 03', '2026-05-27', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(666, 2, 3, 'Phòng 03', '2026-05-27', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(667, 4, 3, 'Phòng 03', '2026-05-27', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(668, 1, 4, 'Phòng 01', '2026-05-27', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(669, 4, 4, 'Phòng 01', '2026-05-27', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(670, 3, 4, 'Phòng 01', '2026-05-27', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(671, 5, 4, 'Phòng 01', '2026-05-27', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(672, 2, 4, 'Phòng 02', '2026-05-27', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(673, 5, 4, 'Phòng 02', '2026-05-27', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(674, 1, 4, 'Phòng 02', '2026-05-27', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(675, 4, 4, 'Phòng 02', '2026-05-27', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(676, 3, 4, 'Phòng 03', '2026-05-27', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(677, 1, 4, 'Phòng 03', '2026-05-27', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(678, 2, 4, 'Phòng 03', '2026-05-27', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(679, 4, 4, 'Phòng 03', '2026-05-27', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(680, 1, 5, 'Phòng 01', '2026-05-27', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(681, 4, 5, 'Phòng 01', '2026-05-27', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(682, 3, 5, 'Phòng 01', '2026-05-27', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(683, 5, 5, 'Phòng 01', '2026-05-27', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(684, 2, 5, 'Phòng 02', '2026-05-27', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(685, 5, 5, 'Phòng 02', '2026-05-27', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(686, 1, 5, 'Phòng 02', '2026-05-27', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(687, 4, 5, 'Phòng 02', '2026-05-27', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(688, 3, 5, 'Phòng 03', '2026-05-27', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(689, 1, 5, 'Phòng 03', '2026-05-27', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(690, 2, 5, 'Phòng 03', '2026-05-27', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(691, 4, 5, 'Phòng 03', '2026-05-27', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(692, 1, 1, 'Phòng 01', '2026-05-28', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(693, 4, 1, 'Phòng 01', '2026-05-28', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(694, 3, 1, 'Phòng 01', '2026-05-28', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(695, 5, 1, 'Phòng 01', '2026-05-28', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(696, 2, 1, 'Phòng 02', '2026-05-28', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(697, 5, 1, 'Phòng 02', '2026-05-28', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(698, 1, 1, 'Phòng 02', '2026-05-28', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(699, 4, 1, 'Phòng 02', '2026-05-28', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(700, 3, 1, 'Phòng 03', '2026-05-28', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(701, 1, 1, 'Phòng 03', '2026-05-28', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(702, 2, 1, 'Phòng 03', '2026-05-28', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(703, 4, 1, 'Phòng 03', '2026-05-28', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(704, 1, 2, 'Phòng 01', '2026-05-28', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(705, 4, 2, 'Phòng 01', '2026-05-28', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(706, 3, 2, 'Phòng 01', '2026-05-28', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(707, 5, 2, 'Phòng 01', '2026-05-28', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(708, 2, 2, 'Phòng 02', '2026-05-28', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(709, 5, 2, 'Phòng 02', '2026-05-28', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(710, 1, 2, 'Phòng 02', '2026-05-28', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(711, 4, 2, 'Phòng 02', '2026-05-28', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(712, 3, 2, 'Phòng 03', '2026-05-28', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(713, 1, 2, 'Phòng 03', '2026-05-28', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(714, 2, 2, 'Phòng 03', '2026-05-28', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(715, 4, 2, 'Phòng 03', '2026-05-28', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(716, 1, 3, 'Phòng 01', '2026-05-28', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(717, 4, 3, 'Phòng 01', '2026-05-28', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(718, 3, 3, 'Phòng 01', '2026-05-28', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(719, 5, 3, 'Phòng 01', '2026-05-28', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(720, 2, 3, 'Phòng 02', '2026-05-28', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(721, 5, 3, 'Phòng 02', '2026-05-28', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(722, 1, 3, 'Phòng 02', '2026-05-28', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(723, 4, 3, 'Phòng 02', '2026-05-28', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(724, 3, 3, 'Phòng 03', '2026-05-28', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(725, 1, 3, 'Phòng 03', '2026-05-28', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(726, 2, 3, 'Phòng 03', '2026-05-28', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(727, 4, 3, 'Phòng 03', '2026-05-28', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(728, 1, 4, 'Phòng 01', '2026-05-28', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(729, 4, 4, 'Phòng 01', '2026-05-28', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(730, 3, 4, 'Phòng 01', '2026-05-28', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(731, 5, 4, 'Phòng 01', '2026-05-28', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(732, 2, 4, 'Phòng 02', '2026-05-28', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(733, 5, 4, 'Phòng 02', '2026-05-28', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(734, 1, 4, 'Phòng 02', '2026-05-28', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(735, 4, 4, 'Phòng 02', '2026-05-28', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(736, 3, 4, 'Phòng 03', '2026-05-28', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(737, 1, 4, 'Phòng 03', '2026-05-28', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(738, 2, 4, 'Phòng 03', '2026-05-28', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(739, 4, 4, 'Phòng 03', '2026-05-28', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(740, 1, 5, 'Phòng 01', '2026-05-28', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(741, 4, 5, 'Phòng 01', '2026-05-28', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(742, 3, 5, 'Phòng 01', '2026-05-28', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(743, 5, 5, 'Phòng 01', '2026-05-28', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(744, 2, 5, 'Phòng 02', '2026-05-28', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(745, 5, 5, 'Phòng 02', '2026-05-28', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(746, 1, 5, 'Phòng 02', '2026-05-28', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(747, 4, 5, 'Phòng 02', '2026-05-28', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(748, 3, 5, 'Phòng 03', '2026-05-28', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(749, 1, 5, 'Phòng 03', '2026-05-28', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(750, 2, 5, 'Phòng 03', '2026-05-28', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(751, 4, 5, 'Phòng 03', '2026-05-28', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(752, 1, 1, 'Phòng 01', '2026-05-29', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(753, 4, 1, 'Phòng 01', '2026-05-29', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(754, 3, 1, 'Phòng 01', '2026-05-29', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(755, 5, 1, 'Phòng 01', '2026-05-29', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(756, 2, 1, 'Phòng 02', '2026-05-29', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(757, 5, 1, 'Phòng 02', '2026-05-29', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(758, 1, 1, 'Phòng 02', '2026-05-29', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(759, 4, 1, 'Phòng 02', '2026-05-29', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(760, 3, 1, 'Phòng 03', '2026-05-29', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(761, 1, 1, 'Phòng 03', '2026-05-29', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(762, 2, 1, 'Phòng 03', '2026-05-29', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(763, 4, 1, 'Phòng 03', '2026-05-29', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(764, 1, 2, 'Phòng 01', '2026-05-29', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(765, 4, 2, 'Phòng 01', '2026-05-29', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(766, 3, 2, 'Phòng 01', '2026-05-29', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(767, 5, 2, 'Phòng 01', '2026-05-29', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(768, 2, 2, 'Phòng 02', '2026-05-29', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(769, 5, 2, 'Phòng 02', '2026-05-29', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(770, 1, 2, 'Phòng 02', '2026-05-29', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(771, 4, 2, 'Phòng 02', '2026-05-29', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(772, 3, 2, 'Phòng 03', '2026-05-29', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(773, 1, 2, 'Phòng 03', '2026-05-29', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(774, 2, 2, 'Phòng 03', '2026-05-29', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(775, 4, 2, 'Phòng 03', '2026-05-29', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(776, 1, 3, 'Phòng 01', '2026-05-29', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(777, 4, 3, 'Phòng 01', '2026-05-29', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(778, 3, 3, 'Phòng 01', '2026-05-29', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(779, 5, 3, 'Phòng 01', '2026-05-29', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(780, 2, 3, 'Phòng 02', '2026-05-29', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(781, 5, 3, 'Phòng 02', '2026-05-29', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(782, 1, 3, 'Phòng 02', '2026-05-29', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(783, 4, 3, 'Phòng 02', '2026-05-29', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(784, 3, 3, 'Phòng 03', '2026-05-29', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(785, 1, 3, 'Phòng 03', '2026-05-29', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(786, 2, 3, 'Phòng 03', '2026-05-29', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(787, 4, 3, 'Phòng 03', '2026-05-29', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(788, 1, 4, 'Phòng 01', '2026-05-29', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(789, 4, 4, 'Phòng 01', '2026-05-29', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(790, 3, 4, 'Phòng 01', '2026-05-29', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(791, 5, 4, 'Phòng 01', '2026-05-29', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(792, 2, 4, 'Phòng 02', '2026-05-29', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(793, 5, 4, 'Phòng 02', '2026-05-29', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(794, 1, 4, 'Phòng 02', '2026-05-29', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(795, 4, 4, 'Phòng 02', '2026-05-29', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(796, 3, 4, 'Phòng 03', '2026-05-29', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(797, 1, 4, 'Phòng 03', '2026-05-29', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(798, 2, 4, 'Phòng 03', '2026-05-29', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(799, 4, 4, 'Phòng 03', '2026-05-29', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(800, 1, 5, 'Phòng 01', '2026-05-29', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(801, 4, 5, 'Phòng 01', '2026-05-29', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(802, 3, 5, 'Phòng 01', '2026-05-29', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(803, 5, 5, 'Phòng 01', '2026-05-29', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(804, 2, 5, 'Phòng 02', '2026-05-29', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(805, 5, 5, 'Phòng 02', '2026-05-29', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(806, 1, 5, 'Phòng 02', '2026-05-29', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(807, 4, 5, 'Phòng 02', '2026-05-29', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(808, 3, 5, 'Phòng 03', '2026-05-29', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(809, 1, 5, 'Phòng 03', '2026-05-29', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(810, 2, 5, 'Phòng 03', '2026-05-29', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(811, 4, 5, 'Phòng 03', '2026-05-29', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(812, 1, 1, 'Phòng 01', '2026-05-30', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(813, 4, 1, 'Phòng 01', '2026-05-30', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(814, 3, 1, 'Phòng 01', '2026-05-30', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(815, 5, 1, 'Phòng 01', '2026-05-30', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(816, 2, 1, 'Phòng 02', '2026-05-30', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(817, 5, 1, 'Phòng 02', '2026-05-30', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(818, 1, 1, 'Phòng 02', '2026-05-30', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(819, 4, 1, 'Phòng 02', '2026-05-30', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(820, 3, 1, 'Phòng 03', '2026-05-30', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(821, 1, 1, 'Phòng 03', '2026-05-30', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(822, 2, 1, 'Phòng 03', '2026-05-30', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(823, 4, 1, 'Phòng 03', '2026-05-30', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(824, 1, 2, 'Phòng 01', '2026-05-30', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(825, 4, 2, 'Phòng 01', '2026-05-30', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(826, 3, 2, 'Phòng 01', '2026-05-30', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(827, 5, 2, 'Phòng 01', '2026-05-30', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(828, 2, 2, 'Phòng 02', '2026-05-30', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(829, 5, 2, 'Phòng 02', '2026-05-30', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(830, 1, 2, 'Phòng 02', '2026-05-30', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(831, 4, 2, 'Phòng 02', '2026-05-30', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(832, 3, 2, 'Phòng 03', '2026-05-30', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(833, 1, 2, 'Phòng 03', '2026-05-30', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(834, 2, 2, 'Phòng 03', '2026-05-30', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(835, 4, 2, 'Phòng 03', '2026-05-30', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(836, 1, 3, 'Phòng 01', '2026-05-30', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(837, 4, 3, 'Phòng 01', '2026-05-30', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(838, 3, 3, 'Phòng 01', '2026-05-30', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(839, 5, 3, 'Phòng 01', '2026-05-30', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(840, 2, 3, 'Phòng 02', '2026-05-30', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(841, 5, 3, 'Phòng 02', '2026-05-30', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(842, 1, 3, 'Phòng 02', '2026-05-30', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(843, 4, 3, 'Phòng 02', '2026-05-30', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(844, 3, 3, 'Phòng 03', '2026-05-30', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(845, 1, 3, 'Phòng 03', '2026-05-30', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(846, 2, 3, 'Phòng 03', '2026-05-30', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(847, 4, 3, 'Phòng 03', '2026-05-30', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(848, 1, 4, 'Phòng 01', '2026-05-30', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(849, 4, 4, 'Phòng 01', '2026-05-30', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(850, 3, 4, 'Phòng 01', '2026-05-30', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(851, 5, 4, 'Phòng 01', '2026-05-30', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(852, 2, 4, 'Phòng 02', '2026-05-30', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(853, 5, 4, 'Phòng 02', '2026-05-30', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(854, 1, 4, 'Phòng 02', '2026-05-30', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(855, 4, 4, 'Phòng 02', '2026-05-30', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(856, 3, 4, 'Phòng 03', '2026-05-30', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(857, 1, 4, 'Phòng 03', '2026-05-30', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(858, 2, 4, 'Phòng 03', '2026-05-30', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(859, 4, 4, 'Phòng 03', '2026-05-30', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(860, 1, 5, 'Phòng 01', '2026-05-30', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(861, 4, 5, 'Phòng 01', '2026-05-30', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(862, 3, 5, 'Phòng 01', '2026-05-30', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(863, 5, 5, 'Phòng 01', '2026-05-30', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(864, 2, 5, 'Phòng 02', '2026-05-30', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(865, 5, 5, 'Phòng 02', '2026-05-30', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(866, 1, 5, 'Phòng 02', '2026-05-30', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(867, 4, 5, 'Phòng 02', '2026-05-30', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(868, 3, 5, 'Phòng 03', '2026-05-30', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(869, 1, 5, 'Phòng 03', '2026-05-30', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(870, 2, 5, 'Phòng 03', '2026-05-30', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(871, 4, 5, 'Phòng 03', '2026-05-30', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(872, 1, 1, 'Phòng 01', '2026-05-31', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(873, 4, 1, 'Phòng 01', '2026-05-31', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(874, 3, 1, 'Phòng 01', '2026-05-31', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(875, 5, 1, 'Phòng 01', '2026-05-31', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(876, 2, 1, 'Phòng 02', '2026-05-31', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(877, 5, 1, 'Phòng 02', '2026-05-31', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(878, 1, 1, 'Phòng 02', '2026-05-31', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(879, 4, 1, 'Phòng 02', '2026-05-31', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(880, 3, 1, 'Phòng 03', '2026-05-31', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(881, 1, 1, 'Phòng 03', '2026-05-31', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(882, 2, 1, 'Phòng 03', '2026-05-31', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(883, 4, 1, 'Phòng 03', '2026-05-31', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(884, 1, 2, 'Phòng 01', '2026-05-31', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(885, 4, 2, 'Phòng 01', '2026-05-31', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(886, 3, 2, 'Phòng 01', '2026-05-31', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(887, 5, 2, 'Phòng 01', '2026-05-31', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(888, 2, 2, 'Phòng 02', '2026-05-31', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(889, 5, 2, 'Phòng 02', '2026-05-31', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(890, 1, 2, 'Phòng 02', '2026-05-31', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(891, 4, 2, 'Phòng 02', '2026-05-31', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(892, 3, 2, 'Phòng 03', '2026-05-31', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(893, 1, 2, 'Phòng 03', '2026-05-31', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(894, 2, 2, 'Phòng 03', '2026-05-31', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(895, 4, 2, 'Phòng 03', '2026-05-31', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(896, 1, 3, 'Phòng 01', '2026-05-31', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(897, 4, 3, 'Phòng 01', '2026-05-31', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(898, 3, 3, 'Phòng 01', '2026-05-31', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(899, 5, 3, 'Phòng 01', '2026-05-31', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(900, 2, 3, 'Phòng 02', '2026-05-31', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(901, 5, 3, 'Phòng 02', '2026-05-31', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(902, 1, 3, 'Phòng 02', '2026-05-31', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(903, 4, 3, 'Phòng 02', '2026-05-31', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(904, 3, 3, 'Phòng 03', '2026-05-31', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(905, 1, 3, 'Phòng 03', '2026-05-31', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(906, 2, 3, 'Phòng 03', '2026-05-31', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(907, 4, 3, 'Phòng 03', '2026-05-31', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(908, 1, 4, 'Phòng 01', '2026-05-31', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(909, 4, 4, 'Phòng 01', '2026-05-31', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(910, 3, 4, 'Phòng 01', '2026-05-31', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(911, 5, 4, 'Phòng 01', '2026-05-31', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(912, 2, 4, 'Phòng 02', '2026-05-31', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(913, 5, 4, 'Phòng 02', '2026-05-31', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(914, 1, 4, 'Phòng 02', '2026-05-31', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(915, 4, 4, 'Phòng 02', '2026-05-31', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(916, 3, 4, 'Phòng 03', '2026-05-31', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(917, 1, 4, 'Phòng 03', '2026-05-31', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(918, 2, 4, 'Phòng 03', '2026-05-31', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(919, 4, 4, 'Phòng 03', '2026-05-31', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(920, 1, 5, 'Phòng 01', '2026-05-31', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(921, 4, 5, 'Phòng 01', '2026-05-31', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(922, 3, 5, 'Phòng 01', '2026-05-31', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(923, 5, 5, 'Phòng 01', '2026-05-31', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(924, 2, 5, 'Phòng 02', '2026-05-31', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(925, 5, 5, 'Phòng 02', '2026-05-31', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(926, 1, 5, 'Phòng 02', '2026-05-31', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(927, 4, 5, 'Phòng 02', '2026-05-31', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(928, 3, 5, 'Phòng 03', '2026-05-31', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(929, 1, 5, 'Phòng 03', '2026-05-31', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(930, 2, 5, 'Phòng 03', '2026-05-31', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(931, 4, 5, 'Phòng 03', '2026-05-31', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(932, 1, 1, 'Phòng 01', '2026-06-01', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 99, 0),
(933, 4, 1, 'Phòng 01', '2026-06-01', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(934, 3, 1, 'Phòng 01', '2026-06-01', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(935, 5, 1, 'Phòng 01', '2026-06-01', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(936, 2, 1, 'Phòng 02', '2026-06-01', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(937, 5, 1, 'Phòng 02', '2026-06-01', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(938, 1, 1, 'Phòng 02', '2026-06-01', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(939, 4, 1, 'Phòng 02', '2026-06-01', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(940, 3, 1, 'Phòng 03', '2026-06-01', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(941, 1, 1, 'Phòng 03', '2026-06-01', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(942, 2, 1, 'Phòng 03', '2026-06-01', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(943, 4, 1, 'Phòng 03', '2026-06-01', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(944, 1, 2, 'Phòng 01', '2026-06-01', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(945, 4, 2, 'Phòng 01', '2026-06-01', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(946, 3, 2, 'Phòng 01', '2026-06-01', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(947, 5, 2, 'Phòng 01', '2026-06-01', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(948, 2, 2, 'Phòng 02', '2026-06-01', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(949, 5, 2, 'Phòng 02', '2026-06-01', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(950, 1, 2, 'Phòng 02', '2026-06-01', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(951, 4, 2, 'Phòng 02', '2026-06-01', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(952, 3, 2, 'Phòng 03', '2026-06-01', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(953, 1, 2, 'Phòng 03', '2026-06-01', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(954, 2, 2, 'Phòng 03', '2026-06-01', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(955, 4, 2, 'Phòng 03', '2026-06-01', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(956, 1, 3, 'Phòng 01', '2026-06-01', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(957, 4, 3, 'Phòng 01', '2026-06-01', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(958, 3, 3, 'Phòng 01', '2026-06-01', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(959, 5, 3, 'Phòng 01', '2026-06-01', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(960, 2, 3, 'Phòng 02', '2026-06-01', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(961, 5, 3, 'Phòng 02', '2026-06-01', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(962, 1, 3, 'Phòng 02', '2026-06-01', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(963, 4, 3, 'Phòng 02', '2026-06-01', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(964, 3, 3, 'Phòng 03', '2026-06-01', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(965, 1, 3, 'Phòng 03', '2026-06-01', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(966, 2, 3, 'Phòng 03', '2026-06-01', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(967, 4, 3, 'Phòng 03', '2026-06-01', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(968, 1, 4, 'Phòng 01', '2026-06-01', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(969, 4, 4, 'Phòng 01', '2026-06-01', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(970, 3, 4, 'Phòng 01', '2026-06-01', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(971, 5, 4, 'Phòng 01', '2026-06-01', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(972, 2, 4, 'Phòng 02', '2026-06-01', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(973, 5, 4, 'Phòng 02', '2026-06-01', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(974, 1, 4, 'Phòng 02', '2026-06-01', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(975, 4, 4, 'Phòng 02', '2026-06-01', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(976, 3, 4, 'Phòng 03', '2026-06-01', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(977, 1, 4, 'Phòng 03', '2026-06-01', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(978, 2, 4, 'Phòng 03', '2026-06-01', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(979, 4, 4, 'Phòng 03', '2026-06-01', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(980, 1, 5, 'Phòng 01', '2026-06-01', '08:30:00', '11:17:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(981, 4, 5, 'Phòng 01', '2026-06-01', '12:30:00', '14:58:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(982, 3, 5, 'Phòng 01', '2026-06-01', '16:15:00', '19:15:00', 'IMAX', 'Phụ đề', 140000, 100, 100, 0),
(983, 5, 5, 'Phòng 01', '2026-06-01', '20:30:00', '23:19:00', 'PREMIUM', 'Phụ đề', 150000, 100, 100, 0),
(984, 2, 5, 'Phòng 02', '2026-06-01', '09:00:00', '12:12:00', '3D', 'Phụ đề', 110000, 100, 100, 0),
(985, 5, 5, 'Phòng 02', '2026-06-01', '13:30:00', '16:19:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(986, 1, 5, 'Phòng 02', '2026-06-01', '17:30:00', '20:17:00', '2D', 'Phụ đề', 85000, 100, 100, 0),
(987, 4, 5, 'Phòng 02', '2026-06-01', '21:30:00', '23:58:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(988, 3, 5, 'Phòng 03', '2026-06-01', '08:00:00', '11:00:00', '2D', 'Phụ đề', 80000, 100, 100, 0),
(989, 1, 5, 'Phòng 03', '2026-06-01', '12:15:00', '15:02:00', '2D', 'Lồng tiếng', 75000, 100, 100, 0),
(990, 2, 5, 'Phòng 03', '2026-06-01', '16:30:00', '19:42:00', '3D', 'Phụ đề', 120000, 100, 100, 0),
(991, 4, 5, 'Phòng 03', '2026-06-01', '21:00:00', '23:28:00', '4DX', 'Phụ đề', 160000, 100, 100, 0),
(992, 4, 1, 'Phòng 04', '2026-06-01', '10:00:00', '12:28:00', '2D', 'Phụ đề', 80000, 100, 100, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `snacks`
--

CREATE TABLE `snacks` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(10,0) NOT NULL,
  `img_url` varchar(500) DEFAULT NULL,
  `category` enum('popcorn','drink','combo') DEFAULT 'combo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `snacks`
--

INSERT INTO `snacks` (`id`, `name`, `price`, `img_url`, `category`) VALUES
(1, 'Bắp rang bơ size L', 45000, NULL, 'popcorn'),
(2, 'Bắp rang caramel size M', 40000, NULL, 'popcorn'),
(3, 'Nước ngọt Pepsi size L', 35000, NULL, 'drink'),
(4, 'Combo 1 bắp + 1 nước', 75000, NULL, 'combo'),
(5, 'Combo 2 bắp + 2 nước', 130000, NULL, 'combo');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `fullname`, `email`, `phone`, `subject`, `content`, `status`, `created_at`) VALUES
(1, 'Trần Đức Phát', 'phatnha1702@gmail.com', NULL, 'adsd', 'sdsdsd', 'pending', '2026-05-19 13:30:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_name` varchar(150) NOT NULL,
  `role` varchar(50) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `action_desc` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `system_logs`
--

INSERT INTO `system_logs` (`id`, `log_time`, `user_name`, `role`, `action_type`, `action_desc`) VALUES
(1, '2026-05-24 05:09:39', 'Trần Minh Anh', 'Kế toán', 'Phê duyệt đối soát', 'Đã gán nhãn trùng khớp cho vé #MF20260524XYZ'),
(2, '2026-05-24 04:34:39', 'Nguyễn Văn Đô', 'Quản trị viên', 'Cập nhật phim', 'Đã thay đổi trạng thái phim \"Lật Mặt 7\" thành Đang chiếu'),
(3, '2026-05-24 03:19:39', 'Lê Thị Bình', 'CSKH', 'Đăng nhập', 'Đăng nhập vào hệ thống từ IP 192.168.1.15'),
(4, '2026-05-24 01:19:39', 'Nguyễn Văn Đô', 'Quản trị viên', 'Cài đặt hệ thống', 'Đã thay đổi tỉ lệ tích điểm thành viên STANDARD lên 1%'),
(5, '2026-05-24 07:23:28', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Oppenheimer\"'),
(6, '2026-05-24 07:23:38', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Oppenheimer\"'),
(7, '2026-05-24 07:24:32', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Spider-Man: Across the Spider-Verse\"'),
(8, '2026-05-24 07:24:49', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Spider-Man: Across the Spider-Verse\"'),
(9, '2026-05-24 08:51:47', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Guardians of the Galaxy Vol. 3\"'),
(10, '2026-05-24 08:52:48', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Guardians of the Galaxy Vol. 3\"'),
(11, '2026-05-24 13:09:07', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Guardians of the Galaxy Vol. 3\"'),
(12, '2026-05-24 13:09:19', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Guardians of the Galaxy Vol. 3\"'),
(13, '2026-05-24 13:21:34', 'Admin MovieFlex', 'admin', 'Hủy suất chiếu khẩn cấp', 'Đã HỦY KHẨN CẤP suất chiếu #499 (Phim: \"Dune: Hành Tinh Cát Phần Hai\", Ngày: 25/05/2026 lúc 11:20) tại CGV Vincom Center (Phòng 04). Đã hoàn trả 1 vé với tổng số tiền 855.000₫.'),
(14, '2026-05-24 13:28:32', 'Admin MovieFlex', 'admin', 'Hủy suất chiếu khẩn cấp', 'Đã HỦY KHẨN CẤP suất chiếu #499 (Phim: \"Dune: Hành Tinh Cát Phần Hai\", Ngày: 25/05/2026 lúc 11:20) tại CGV Vincom Center (Phòng 04). Đã hoàn trả 0 vé với tổng số tiền 0₫.'),
(15, '2026-05-24 13:28:57', 'Admin MovieFlex', 'admin', 'Hủy suất chiếu khẩn cấp', 'Đã HỦY KHẨN CẤP suất chiếu #565 (Phim: \"Dune: Hành Tinh Cát Phần Hai\", Ngày: 25/05/2026 lúc 12:50) tại BHD Star Phạm Ngọc Thạch (Phòng 01). Đã hoàn trả 1 vé với tổng số tiền 825.000₫.'),
(16, '2026-05-24 13:36:47', 'Admin MovieFlex', 'admin', 'Thêm suất chiếu', 'Đã tạo suất chiếu mới #569'),
(17, '2026-05-25 07:48:02', 'Admin MovieFlex', 'admin', 'Xóa suất chiếu', 'Đã xóa suất chiếu #570'),
(18, '2026-05-25 07:33:38', 'Nhân Viên Quầy', 'Nhân viên', 'Bán vé tại quầy', 'Đặt vé & bắp nước trực tiếp tại quầy rạp CGV Vincom Center. Tổng tiền: 468,000₫.'),
(19, '2026-05-25 07:33:38', 'Nhân Viên Quầy', 'Nhân viên', 'Bán vé tại quầy', 'Đặt vé & bắp nước trực tiếp tại quầy rạp CGV Vincom Center. Tổng tiền: 720,000₫.'),
(20, '2026-05-25 07:33:38', 'Nhân Viên Quầy', 'Nhân viên', 'Bán vé tại quầy', 'Đặt vé & bắp nước trực tiếp tại quầy rạp CGV Vincom Center. Tổng tiền: 520,000₫.'),
(21, '2026-05-25 13:50:59', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Spider-Man: Across the Spider-Verse\"'),
(22, '2026-05-25 13:51:40', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Spider-Man: Across the Spider-Verse\"'),
(23, '2026-05-26 15:05:33', 'Admin MovieFlex', 'admin', 'Hủy suất chiếu khẩn cấp', 'Đã HỦY KHẨN CẤP suất chiếu #634 (Phim: \"Oppenheimer\", Ngày: 27/05/2026 lúc 16:15) tại CGV Vincom Center (Phòng 01). Đã hoàn trả 2 vé với tổng số tiền 364.000₫.'),
(24, '2026-05-26 15:07:19', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Spider-Man: Across the Spider-Verse\"'),
(25, '2026-05-26 15:10:42', 'Admin MovieFlex', 'admin', 'Cập nhật phim', 'Đã cập nhật thông tin phim: \"Spider-Man: Across the Spider-Verse\"'),
(26, '2026-05-26 15:26:42', 'Admin MovieFlex', 'admin', 'Thêm suất chiếu', 'Đã tạo suất chiếu mới #992');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `role` enum('user','admin','staff','admin_monitor') NOT NULL DEFAULT 'user',
  `status` enum('active','locked','pending') NOT NULL DEFAULT 'active',
  `loyalty_points` int(10) UNSIGNED DEFAULT 0,
  `member_tier` enum('STANDARD','SILVER','GOLD','PLATINUM') DEFAULT 'STANDARD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `avatar_url`, `role`, `status`, `loyalty_points`, `member_tier`, `created_at`, `last_login`) VALUES
(1, 'Admin MovieFlex', 'admin@movieflex.com', '0901234567', '$2y$12$2C/BeVyKfd9YP.BN5ybTq.3AOC/cjM3jmr5va1sGtj3N3vrx5e0wG', NULL, 'admin', 'active', 0, 'STANDARD', '2026-05-19 07:40:55', '2026-06-20 17:13:42'),
(2, 'Nguyễn Văn An', 'an@gmail.com', '0912345678', '$2y$10$h74AVrRJQDDdwajM1JxB8ONO.0UNR0FcUvqHZEwKLeYDvPlYZxb66', NULL, 'user', 'active', 98, 'STANDARD', '2026-05-19 07:40:55', '2026-05-25 21:02:20'),
(6, 'Trần Đức Phát', 'tmlong1702@gmail.com', '0999999999', '$2y$12$CWfL8YWu9zjiafR03WfrGO0FUYP6un6VBtOF4wMVvopkOh2FIOvxm', NULL, 'user', 'active', 266, 'STANDARD', '2026-05-24 08:12:58', '2026-06-20 17:15:39'),
(8, 'Trần phát', 'nghia@gmail.com', '0377272727', '$2y$12$PNwijGP5GGkltE/qRlXhhup7Z4pqD0LbBE/Kr3yyGwF5gHmcqayE6', NULL, 'user', 'active', 0, 'STANDARD', '2026-05-24 13:15:15', '2026-05-24 20:15:20'),
(9, 'Nhân Viên Quầy', 'staff@movieflex.vn', NULL, '$2y$12$MmnEiwQIXdGaW.zYXQd.QOxEl83h85JSARXC1z/MPCqK2s2yBhZni', NULL, 'staff', 'active', 0, 'STANDARD', '2026-05-25 08:29:11', '2026-06-20 17:13:59'),
(10, 'Khách Vãng Lai', 'counter_guest@movieflex.vn', NULL, '$2y$10$cWFNhM5B8w8637zRfGApD.4oTLdEDLEe4kv//adB4Tis8xhZ5iWCa', NULL, 'user', 'active', 0, 'STANDARD', '2026-05-25 08:35:21', NULL),
(11, 'Admin Monitor', 'monitor@movieflex.vn', NULL, '$2y$12$wdMDAKM50lQZWZY784enh.pC1VdP.dRXwC/nLTqRurZQCgaMKHuSe', NULL, 'admin', 'active', 0, 'STANDARD', '2026-05-26 15:22:18', '2026-05-26 23:13:06');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_pct` tinyint(4) DEFAULT 0 COMMENT 'Giảm theo %',
  `discount_amt` decimal(10,0) DEFAULT 0 COMMENT 'Giảm số tiền cố định',
  `min_order` decimal(10,0) DEFAULT 0,
  `max_uses` int(11) DEFAULT 100,
  `used_count` int(11) DEFAULT 0,
  `expire_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `user_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `description`, `discount_pct`, `discount_amt`, `min_order`, `max_uses`, `used_count`, `expire_date`, `is_active`, `user_id`) VALUES
(8, 'REDM30K', '[Chương trình đổi thưởng] Voucher giảm giá 30.000₫', 0, 30000, 0, 9999, 0, NULL, 1, NULL),
(9, 'REDM50K', '[Chương trình đổi thưởng] Voucher giảm giá 50.000₫', 0, 50000, 0, 9999, 0, NULL, 0, NULL),
(10, 'REDM100K', '[Chương trình đổi thưởng] Voucher giảm giá 100.000₫', 0, 100000, 0, 9999, 0, NULL, 1, NULL),
(11, 'GIFTPOP', '[Chương trình đổi thưởng] Combo Bắp + Nước miễn phí', 100, 0, 0, 9999, 0, NULL, 1, NULL),
(18, 'SUMMER30', 'Ưu đãi mùa hè giảm 30k', 0, 30000, 90000, 9999, 0, NULL, 1, NULL),
(19, 'NEWUSER50', 'Giảm 50k cho thành viên mới', 0, 50000, 150000, 9999, 0, NULL, 1, NULL),
(20, 'MOVIE20', 'Giảm 20% cho đơn từ 100k', 20, 0, 100000, 9999, 0, NULL, 1, NULL),
(21, 'SUMMER30-D4F765', 'Ưu đãi mùa hè giảm 30k', 0, 30000, 90000, 1, 0, '2026-06-23', 1, 2),
(22, 'NEWUSER50-D500AA', 'Giảm 50k cho thành viên mới', 0, 50000, 150000, 1, 0, '2026-06-23', 1, 2),
(23, 'MOVIE20-D504B0', 'Giảm 20% cho đơn từ 100k', 20, 0, 100000, 1, 0, '2026-06-23', 1, 2),
(24, 'SUMMER30-FED9E0', 'Ưu đãi mùa hè giảm 30k', 0, 30000, 90000, 1, 0, '2026-06-23', 1, 7),
(25, 'NEWUSER50-FEDDAF', 'Giảm 50k cho thành viên mới', 0, 50000, 150000, 1, 0, '2026-06-23', 1, 7),
(26, 'MOVIE20-FEE3FE', 'Giảm 20% cho đơn từ 100k', 20, 0, 100000, 1, 0, '2026-06-23', 1, 7),
(27, 'SUMMER30-3C3C14', 'Ưu đãi mùa hè giảm 30k', 0, 30000, 90000, 1, 0, '2026-06-23', 1, 8),
(28, 'NEWUSER50-3C3FCA', 'Giảm 50k cho thành viên mới', 0, 50000, 150000, 1, 0, '2026-06-23', 1, 8),
(29, 'SUMMER30-47846C', 'Ưu đãi mùa hè giảm 30k', 0, 30000, 90000, 1, 1, '2026-06-24', 1, 6),
(30, 'NEWUSER50-47921C', 'Giảm 50k cho thành viên mới', 0, 50000, 150000, 1, 1, '2026-06-24', 1, 6),
(31, 'MOVIE20-47A660', 'Giảm 20% cho đơn từ 100k', 20, 0, 100000, 1, 0, '2026-06-24', 1, 6),
(32, 'SUMMER30-0736B3', 'Ưu đãi mùa hè giảm 30k', 0, 30000, 90000, 1, 0, '2026-06-24', 1, 9),
(33, 'NEWUSER50-0818C9', 'Giảm 50k cho thành viên mới', 0, 50000, 150000, 1, 0, '2026-06-24', 1, 9),
(34, 'MOVIE20-0828A0', 'Giảm 20% cho đơn từ 100k', 20, 0, 100000, 1, 0, '2026-06-24', 1, 9),
(35, 'REDM50K-A58B69', 'Đổi thưởng (25 điểm): Voucher giảm giá 50.000₫', 0, 50000, 0, 1, 0, '2026-06-24', 1, 6);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `showtime_id` (`showtime_id`);

--
-- Chỉ mục cho bảng `checkin_hourly`
--
ALTER TABLE `checkin_hourly`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `cinemas`
--
ALTER TABLE `cinemas`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `cinema_halls`
--
ALTER TABLE `cinema_halls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cinema_id` (`cinema_id`);

--
-- Chỉ mục cho bảng `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `kpis`
--
ALTER TABLE `kpis`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `movie_reviews`
--
ALTER TABLE `movie_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_movie_booking` (`user_id`,`movie_id`,`booking_code`);

--
-- Chỉ mục cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `reconciliation_errors`
--
ALTER TABLE `reconciliation_errors`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `sales_trend`
--
ALTER TABLE `sales_trend`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_seat` (`showtime_id`,`seat_row`,`seat_num`);

--
-- Chỉ mục cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `cinema_id` (`cinema_id`);

--
-- Chỉ mục cho bảng `snacks`
--
ALTER TABLE `snacks`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT cho bảng `checkin_hourly`
--
ALTER TABLE `checkin_hourly`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `cinemas`
--
ALTER TABLE `cinemas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `cinema_halls`
--
ALTER TABLE `cinema_halls`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `kpis`
--
ALTER TABLE `kpis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `movie_reviews`
--
ALTER TABLE `movie_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `reconciliation_errors`
--
ALTER TABLE `reconciliation_errors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `sales_trend`
--
ALTER TABLE `sales_trend`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `seats`
--
ALTER TABLE `seats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=993;

--
-- AUTO_INCREMENT cho bảng `snacks`
--
ALTER TABLE `snacks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `cinema_halls`
--
ALTER TABLE `cinema_halls`
  ADD CONSTRAINT `cinema_halls_ibfk_1` FOREIGN KEY (`cinema_id`) REFERENCES `cinemas` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_ibfk_1` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  ADD CONSTRAINT `showtimes_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `showtimes_ibfk_2` FOREIGN KEY (`cinema_id`) REFERENCES `cinemas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
