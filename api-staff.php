<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// Check staff authentication
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'staff' && $_SESSION['user_role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu đăng nhập tài khoản nhân viên hoặc quản trị viên.']);
    exit;
}

$action = $_GET['action'] ?? '';

// 1. SET WORKING CINEMA
if ($action === 'set_working_cinema') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cinema_id = (int)($data['cinema_id'] ?? 0);
    
    if (!$cinema_id) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn chi nhánh rạp.']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT name FROM cinemas WHERE id = ? LIMIT 1");
    $stmt->execute([$cinema_id]);
    $cinema_name = $stmt->fetchColumn();
    
    if (!$cinema_name) {
        echo json_encode(['success' => false, 'message' => 'Chi nhánh rạp không tồn tại.']);
        exit;
    }
    
    $_SESSION['staff_cinema_id'] = $cinema_id;
    $_SESSION['staff_cinema_name'] = $cinema_name;
    
    echo json_encode(['success' => true, 'cinema_name' => $cinema_name]);
    exit;
}

// Ensure working cinema is selected for other actions
$working_cinema_id = $_SESSION['staff_cinema_id'] ?? 0;
if (!$working_cinema_id && $action !== 'get_cinemas') {
    echo json_encode(['success' => false, 'need_cinema' => true, 'message' => 'Vui lòng chọn chi nhánh rạp trước khi thực hiện.']);
    exit;
}

// 2. GET CINEMAS LIST (for initial overlay)
if ($action === 'get_cinemas') {
    $cinemas = $pdo->query("SELECT id, name, address FROM cinemas ORDER BY id ASC")->fetchAll();
    echo json_encode(['success' => true, 'data' => $cinemas]);
    exit;
}

// 3. GET CINEMA STATS
if ($action === 'get_cinema_stats') {
    // 3.1 Counter Revenue Today (total_amount from bookings booked at counter or generally today at this cinema)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(b.total_amount), 0) as today_revenue
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        WHERE s.cinema_id = ? AND b.status != 'cancelled' AND DATE(b.created_at) = CURDATE()
    ");
    $stmt->execute([$working_cinema_id]);
    $revenue = (float)$stmt->fetchColumn();

    // 3.2 Tickets Sold Today at this cinema
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(b.num_tickets), 0) as today_tickets
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        WHERE s.cinema_id = ? AND b.status != 'cancelled' AND DATE(b.created_at) = CURDATE()
    ");
    $stmt->execute([$working_cinema_id]);
    $tickets = (int)$stmt->fetchColumn();

    // 3.3 Check-ins completed today at this cinema
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(b.num_tickets), 0) as today_checkins
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        WHERE s.cinema_id = ? AND b.status = 'checked_in' AND DATE(b.created_at) = CURDATE()
    ");
    $stmt->execute([$working_cinema_id]);
    $checkins = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'stats' => [
            'revenue' => $revenue,
            'tickets' => $tickets,
            'checkins' => $checkins
        ]
    ]);
    exit;
}

// 4. CHECK TICKET (Strict cinema check)
if ($action === 'check_ticket') {
    $code = trim($_GET['code'] ?? '');
    
    if (!$code) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp mã đặt vé.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT b.*, m.title, m.poster_url, 
               s.show_date, s.start_time, s.hall_name, s.cinema_id,
               c.name as cinema_name, u.full_name as user_name
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN cinemas c ON s.cinema_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_code = ? LIMIT 1
    ");
    $stmt->execute([$code]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy vé xem phim này trên hệ thống.']);
        exit;
    }

    $ticket['seats'] = json_decode($ticket['seats_json'], true) ?? [];
    
    // Strict Cinema ID Validation
    if ((int)$ticket['cinema_id'] !== (int)$working_cinema_id) {
        echo json_encode([
            'success' => true,
            'is_valid' => false,
            'ticket' => $ticket,
            'message' => '⚠️ CẢNH BÁO: Vé này thuộc về chi nhánh ' . htmlspecialchars($ticket['cinema_name']) . '. Không thể soát vé tại rạp ' . htmlspecialchars($_SESSION['staff_cinema_name']) . '!'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'is_valid' => true,
        'ticket' => $ticket
    ]);
    exit;
}

// 5. DO TICKET CHECK-IN
if ($action === 'do_checkin') {
    $data = json_decode(file_get_contents('php://input'), true);
    $code = trim($data['code'] ?? '');

    if (!$code) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp mã đặt vé.']);
        exit;
    }

    // Verify ticket location and status again
    $stmt = $pdo->prepare("
        SELECT b.id, b.status, b.total_amount, b.user_id, s.cinema_id 
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        WHERE b.booking_code = ? LIMIT 1
    ");
    $stmt->execute([$code]);
    $bk = $stmt->fetch();

    if (!$bk) {
        echo json_encode(['success' => false, 'message' => 'Vé không tồn tại.']);
        exit;
    }

    if ((int)$bk['cinema_id'] !== (int)$working_cinema_id) {
        echo json_encode(['success' => false, 'message' => 'Thao tác bị từ chối: Suất chiếu này thuộc rạp khác.']);
        exit;
    }

    if ($bk['status'] === 'checked_in') {
        echo json_encode(['success' => false, 'message' => 'Vé này đã được soát và vào phòng chiếu trước đó.']);
        exit;
    }

    if ($bk['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Vé này đã bị hủy, không thể sử dụng.']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // Update booking status
        $upd = $pdo->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
        $upd->execute([$bk['id']]);

        // Reward loyalty points
        $points = floor((float)$bk['total_amount'] / 10000);
        if ($points > 0) {
            $updPoints = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $updPoints->execute([$points, $bk['user_id']]);
        }

        // Auto-tier update
        $pdo->prepare("
            UPDATE users u
            JOIN (
                SELECT user_id, SUM(total_amount) as spent
                FROM bookings
                WHERE status = 'checked_in' AND user_id = ?
                GROUP BY user_id
            ) b ON u.id = b.user_id
            SET u.member_tier = CASE 
                WHEN b.spent >= 100000000 THEN 'PLATINUM'
                WHEN b.spent >= 50000000 THEN 'GOLD'
                WHEN b.spent >= 10000000 THEN 'SILVER'
                ELSE 'STANDARD'
            END
            WHERE u.id = ?
        ")->execute([$bk['user_id'], $bk['user_id']]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Soát vé thành công! Đã cho phép khách vào phòng chiếu.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
    exit;
}

// 6. SEARCH CUSTOMER
if ($action === 'search_customer') {
    $q = trim($_GET['query'] ?? '');
    if (!$q) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp SĐT hoặc Email.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, loyalty_points, member_tier FROM users WHERE (email = ? OR phone = ?) LIMIT 1");
    $stmt->execute([$q, $q]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản thành viên này.']);
    }
    exit;
}

// 7. GET COUNTER MOVIES AND SHOWTIMES (Filtered by working cinema)
if ($action === 'get_counter_movies') {
    // Only get movies that have showtimes today or future in this specific working cinema
    $movies = $pdo->prepare("
        SELECT DISTINCT m.* 
        FROM movies m
        JOIN showtimes s ON s.movie_id = m.id
        WHERE s.cinema_id = ? AND s.is_cancelled = 0
          AND (
            s.show_date > CURDATE()
            OR (s.show_date = CURDATE() AND ADDTIME(s.start_time, '00:20:00') >= CURTIME())
          )
        ORDER BY m.id DESC
    ");
    $movies->execute([$working_cinema_id]);
    echo json_encode(['success' => true, 'data' => $movies->fetchAll()]);
    exit;
}

if ($action === 'get_movie_showtimes') {
    $movie_id = (int)($_GET['movie_id'] ?? 0);
    if (!$movie_id) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID phim.']);
        exit;
    }

    // Get showtimes of this movie in this working cinema
    $showtimes = $pdo->prepare("
        SELECT * 
        FROM showtimes 
        WHERE cinema_id = ? AND movie_id = ? AND is_cancelled = 0
          AND (
            show_date > CURDATE()
            OR (show_date = CURDATE() AND ADDTIME(start_time, '00:20:00') >= CURTIME())
          )
        ORDER BY show_date ASC, start_time ASC
    ");
    $showtimes->execute([$working_cinema_id, $movie_id]);
    echo json_encode(['success' => true, 'data' => $showtimes->fetchAll()]);
    exit;
}

// 8. GET BOOKED SEATS FOR SHOWTIME
if ($action === 'get_showtime_seats') {
    $showtime_id = (int)($_GET['showtime_id'] ?? 0);
    if (!$showtime_id) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID suất chiếu.']);
        exit;
    }

    // Verify showtime belongs to working cinema
    $stmt = $pdo->prepare("SELECT cinema_id, price FROM showtimes WHERE id = ? LIMIT 1");
    $stmt->execute([$showtime_id]);
    $st = $stmt->fetch();
    if (!$st || (int)$st['cinema_id'] !== (int)$working_cinema_id) {
        echo json_encode(['success' => false, 'message' => 'Suất chiếu không thuộc chi nhánh rạp của bạn.']);
        exit;
    }

    // Get booked seats
    $booked = $pdo->prepare("
        SELECT seats_json FROM bookings
        WHERE showtime_id = ? AND status != 'cancelled'
    ");
    $booked->execute([$showtime_id]);
    $bookedSeats = [];
    foreach ($booked->fetchAll() as $row) {
        $seats = json_decode($row['seats_json'], true) ?? [];
        foreach ($seats as $s) $bookedSeats[] = $s;
    }

    echo json_encode([
        'success' => true,
        'price' => $st['price'],
        'booked_seats' => $bookedSeats
    ]);
    exit;
}

// 9. CREATE COUNTER BOOKING (Tickets + Concessions)
if ($action === 'create_counter_booking') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $showtime_id = (int)($data['showtime_id'] ?? 0);
    $seats = $data['seats'] ?? []; // Array of seat codes
    $member_id = (int)($data['member_id'] ?? 0); // Optional member ID
    $payment_method = $data['payment_method'] ?? 'cash';
    
    $snacks = $data['snacks'] ?? []; // Array of {id, name, price, qty}
    $discount = (int)($data['discount'] ?? 0);

    if (!$showtime_id) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn suất chiếu.']);
        exit;
    }

    // Verify showtime belongs to working cinema
    $stmt = $pdo->prepare("SELECT * FROM showtimes WHERE id = ? LIMIT 1");
    $stmt->execute([$showtime_id]);
    $show = $stmt->fetch();
    if (!$show || (int)$show['cinema_id'] !== (int)$working_cinema_id) {
        echo json_encode(['success' => false, 'message' => 'Suất chiếu không thuộc chi nhánh rạp của bạn.']);
        exit;
    }

    if (empty($seats) && empty($snacks)) {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng trống. Vui lòng chọn vé hoặc bắp nước.']);
        exit;
    }

    // If member is not provided, bind to a generic counter guest account (create if not exists)
    $customer_id = $member_id;
    if (!$customer_id) {
        $stmt = $pdo->query("SELECT id FROM users WHERE email = 'counter_guest@movieflex.vn' LIMIT 1");
        $customer_id = $stmt->fetchColumn();
        if (!$customer_id) {
            // Create dummy guest
            $hash = password_hash(uniqid(), PASSWORD_BCRYPT);
            $insGuest = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status) VALUES ('Khách Vãng Lai', 'counter_guest@movieflex.vn', ?, 'user', 'active')");
            $insGuest->execute([$hash]);
            $customer_id = $pdo->lastInsertId();
        }
    }

    // Normalize seats array to support both array of strings or array of objects
    $normalized_seats = [];
    foreach ($seats as $sInfo) {
        if (is_array($sInfo)) {
            $normalized_seats[] = [
                'seat' => trim($sInfo['seat'] ?? ''),
                'booking_code' => trim($sInfo['booking_code'] ?? '')
            ];
        } else {
            $normalized_seats[] = [
                'seat' => trim($sInfo),
                'booking_code' => ''
            ];
        }
    }

    // Recalculate price server-side
    $basePrice = (int)$show['price'];
    $tickets_total = 0;
    $vipRows = ['E','F','G'];
    
    // Parse seats details
    foreach ($normalized_seats as $sInfo) {
        $seat = $sInfo['seat'];
        $row = substr($seat, 0, 1);
        if (strpos($seat, ',') !== false) { // Sweetbox double seat
            $tickets_total += $basePrice * 2;
        } elseif (in_array($row, $vipRows)) {
            $tickets_total += round($basePrice * 1.3);
        } else {
            $tickets_total += $basePrice;
        }
    }

    // Recalculate snacks total
    $snacks_total = 0;
    $dbSnacks = $pdo->query("SELECT * FROM snacks")->fetchAll();
    $snacksMap = [];
    foreach ($dbSnacks as $sn) {
        $snacksMap[$sn['id']] = $sn;
    }

    $sanitizedSnacks = [];
    foreach ($snacks as $item) {
        $sid = (int)$item['id'];
        $qty = (int)$item['qty'];
        if ($qty <= 0) continue;
        
        if (isset($snacksMap[$sid])) {
            $snPrice = (int)$snacksMap[$sid]['price'];
            $snacks_total += $snPrice * $qty;
            
            $sanitizedSnacks[] = [
                'id' => $sid,
                'name' => $snacksMap[$sid]['name'],
                'price' => $snPrice,
                'qty' => $qty
            ];
        }
    }

    // Final total calculation
    $subtotal = $tickets_total;
    $total_amount = max(0, $subtotal + $snacks_total - $discount);
    
    $transaction_id = 'COUNTER-' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    $first_booking_code = null;
    $accumulated_discount = 0;
    $n = count($normalized_seats);

    try {
        $pdo->beginTransaction();

        $ins = $pdo->prepare("
            INSERT INTO bookings (
                booking_code, user_id, showtime_id, seats_json, num_tickets, 
                subtotal, discount, total_amount, payment_method, 
                payment_status, status, transaction_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'checked_in', ?)
        ");

        $ticket_count = 0;
        foreach ($normalized_seats as $sInfo) {
            $ticket_count += (strpos($sInfo['seat'], ',') !== false) ? 2 : 1;
        }

        if ($n === 0) {
            // Snack only purchase
            $code = 'MF' . date('Ymd') . strtoupper(substr(md5(uniqid(microtime(), true)), -6));
            $first_booking_code = $code;
            
            $ins->execute([
                $code,
                $customer_id,
                $showtime_id,
                json_encode([]),
                0,
                0,
                0,
                $total_amount,
                $payment_method,
                $transaction_id
            ]);
        } else {
            // Multi-ticket split purchase
            foreach ($normalized_seats as $idx => $sInfo) {
                $seat = $sInfo['seat'];
                $parts = explode(',', $seat);
                $clean_parts = array_map('trim', $parts);
                $row = substr($clean_parts[0], 0, 1);
                
                if (count($clean_parts) === 2) {
                    $seat_subtotal = $basePrice * 2;
                    $seat_ticket_count = 2;
                } elseif (in_array($row, $vipRows)) {
                    $seat_subtotal = round($basePrice * 1.3);
                    $seat_ticket_count = 1;
                } else {
                    $seat_subtotal = $basePrice;
                    $seat_ticket_count = 1;
                }

                // Proportional discount allocation
                if ($idx === $n - 1) {
                    $seat_discount = $discount - $accumulated_discount;
                } else {
                    $seat_discount = round($discount * ($seat_subtotal / $subtotal));
                }
                $accumulated_discount += $seat_discount;

                // Add snacks total to first ticket
                $seat_total = $seat_subtotal - $seat_discount;
                if ($idx === 0) {
                    $seat_total += $snacks_total;
                }
                $seat_total = max(0, $seat_total);

                // Use client-provided pre-generated code or fallback to server generation
                $code = !empty($sInfo['booking_code']) ? $sInfo['booking_code'] : ('MF' . date('Ymd') . strtoupper(substr(md5(uniqid(microtime(), true)), -6)));
                if ($idx === 0) {
                    $first_booking_code = $code;
                }

                $ins->execute([
                    $code,
                    $customer_id,
                    $showtime_id,
                    json_encode($clean_parts),
                    $seat_ticket_count,
                    $seat_subtotal,
                    $seat_discount,
                    $seat_total,
                    $payment_method,
                    $transaction_id
                ]);
            }
        }

        // 2. Update showtimes available seats
        if ($ticket_count > 0) {
            $pdo->prepare("UPDATE showtimes SET available_seats = available_seats - ? WHERE id = ?")
                ->execute([$ticket_count, $showtime_id]);
        }

        // 3. Reward points to Member (if applicable)
        if ($member_id) {
            $points = floor($total_amount / 10000);
            if ($points > 0) {
                $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?")
                    ->execute([$points, $member_id]);
            }
            
            // Auto-tier update
            $pdo->prepare("
                UPDATE users u
                JOIN (
                    SELECT user_id, SUM(total_amount) as spent
                    FROM bookings
                    WHERE status = 'checked_in' AND user_id = ?
                    GROUP BY user_id
                ) b ON u.id = b.user_id
                SET u.member_tier = CASE 
                    WHEN b.spent >= 100000000 THEN 'PLATINUM'
                    WHEN b.spent >= 50000000 THEN 'GOLD'
                    WHEN b.spent >= 10000000 THEN 'SILVER'
                    ELSE 'STANDARD'
                END
                WHERE u.id = ?
            ")->execute([$member_id, $member_id]);
        }

        // Optional log activity
        $logDesc = "Đặt vé & bắp nước trực tiếp tại quầy rạp " . $_SESSION['staff_cinema_name'] . ". Tổng tiền: " . number_format($total_amount) . "₫.";
        $insLog = $pdo->prepare("INSERT INTO system_logs (log_time, user_name, role, action_type, action_desc) VALUES (NOW(), ?, ?, 'Bán vé tại quầy', ?)");
        $insLog->execute([$_SESSION['user_name'] ?? 'Nhân viên', 'Nhân viên', $logDesc]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'booking_code' => $first_booking_code,
            'total_amount' => $total_amount,
            'message' => 'Lập đơn đặt vé tại quầy thành công!'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Đặt vé quầy thất bại: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
