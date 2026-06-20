<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'admin_monitor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'dashboard_data';

if ($action === 'dashboard_data') {
    $response = [];
    
    // Real dynamic KPIs calculation (Today's Totals)
    $total_tickets = (int)$pdo->query("SELECT SUM(num_tickets) FROM bookings WHERE status != 'cancelled' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
    $total_checkins = (int)$pdo->query("SELECT SUM(num_tickets) FROM bookings WHERE status = 'checked_in' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
    $total_revenue = (float)$pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status != 'cancelled' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
    $active_staff = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn() ?: 0;
    $locked_accounts = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'locked'")->fetchColumn() ?: 0;
    
    // Calculate dynamic trends today vs yesterday
    $stats_today_yesterday = $pdo->query("
        SELECT 
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN num_tickets ELSE 0 END) as t_today,
            SUM(CASE WHEN DATE(created_at) = CURDATE() - INTERVAL 1 DAY THEN num_tickets ELSE 0 END) as t_yesterday,
            SUM(CASE WHEN DATE(created_at) = CURDATE() AND status = 'checked_in' THEN num_tickets ELSE 0 END) as c_today,
            SUM(CASE WHEN DATE(created_at) = CURDATE() - INTERVAL 1 DAY AND status = 'checked_in' THEN num_tickets ELSE 0 END) as c_yesterday,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as r_today,
            SUM(CASE WHEN DATE(created_at) = CURDATE() - INTERVAL 1 DAY THEN total_amount ELSE 0 END) as r_yesterday
        FROM bookings
        WHERE status != 'cancelled'
    ")->fetch();

    $t_today = (int)($stats_today_yesterday['t_today'] ?? 0);
    $t_yesterday = (int)($stats_today_yesterday['t_yesterday'] ?? 0);
    $c_today = (int)($stats_today_yesterday['c_today'] ?? 0);
    $c_yesterday = (int)($stats_today_yesterday['c_yesterday'] ?? 0);
    $r_today = (float)($stats_today_yesterday['r_today'] ?? 0);
    $r_yesterday = (float)($stats_today_yesterday['r_yesterday'] ?? 0);

    if (!function_exists('calcTrend')) {
        function calcTrend($today, $yesterday) {
            if ($yesterday == 0) {
                if ($today > 0) {
                    return ['text' => '+100% so với hôm qua', 'class' => 'positive'];
                }
                return ['text' => '0% so với hôm qua', 'class' => 'neutral'];
            }
            $diff = (($today - $yesterday) / $yesterday) * 100;
            $diff_formatted = number_format(abs($diff), 1, ',', '.');
            if ($diff > 0) {
                return ['text' => '+' . $diff_formatted . '% so với hôm qua', 'class' => 'positive'];
            } elseif ($diff < 0) {
                return ['text' => '-' . $diff_formatted . '% so với hôm qua', 'class' => 'negative'];
            } else {
                return ['text' => '0% so với hôm qua', 'class' => 'neutral'];
            }
        }
    }

    $tickets_trend = calcTrend($t_today, $t_yesterday);
    $checkins_trend = calcTrend($c_today, $c_yesterday);
    $revenue_trend = calcTrend($r_today, $r_yesterday);

    $response['kpis'] = [
        'total_tickets' => $total_tickets,
        'total_checkins' => $total_checkins,
        'total_revenue' => $total_revenue,
        'active_staff' => $active_staff,
        'locked_accounts' => $locked_accounts,
        'tickets_trend' => $tickets_trend['text'],
        'tickets_trend_class' => $tickets_trend['class'],
        'checkins_trend' => $checkins_trend['text'],
        'checkins_trend_class' => $checkins_trend['class'],
        'revenue_trend' => $revenue_trend['text'],
        'revenue_trend_class' => $revenue_trend['class']
    ];
    
    // Real sales trend for the last 7 days (guaranteed date points in order)
    $sales_trend = [];
    for ($i = 6; $i >= 0; $i--) {
        $date_str = date('Y-m-d', strtotime("-$i days"));
        $day_name = date('d/m', strtotime("-$i days"));
        
        $stmt = $pdo->prepare("
            SELECT SUM(num_tickets) 
            FROM bookings 
            WHERE status != 'cancelled' AND DATE(created_at) = ?
        ");
        $stmt->execute([$date_str]);
        $tickets_sold = (int)$stmt->fetchColumn() ?: 0;
        
        $sales_trend[] = [
            'day_name' => $day_name,
            'tickets_sold' => $tickets_sold
        ];
    }
    $response['sales_trend'] = $sales_trend;
    
    // Real check-in hourly for today (grouped by 2-hour slots)
    $checkin_hourly = [];
    $hours = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
    foreach ($hours as $h) {
        $start_hour = (int)substr($h, 0, 2);
        $stmt = $pdo->prepare("
            SELECT SUM(num_tickets) 
            FROM bookings 
            WHERE status = 'checked_in' 
              AND DATE(created_at) = CURDATE() 
              AND HOUR(created_at) >= ? 
              AND HOUR(created_at) < ?
        ");
        $stmt->execute([$start_hour, $start_hour + 2]);
        $checkins = (int)$stmt->fetchColumn() ?: 0;
        
        $checkin_hourly[] = [
            'hour_label' => $h,
            'checkins' => $checkins
        ];
    }
    $response['checkin_hourly'] = $checkin_hourly;
    
    // Find dynamic peak check-in hour today
    $peak_hour = '19:30';
    $peak_count = 0;
    $peak_stmt = $pdo->query("
        SELECT HOUR(created_at) as hr, SUM(num_tickets) as checkins
        FROM bookings
        WHERE status = 'checked_in' AND DATE(created_at) = CURDATE()
        GROUP BY HOUR(created_at)
        ORDER BY checkins DESC
        LIMIT 1
    ");
    $peak = $peak_stmt->fetch();
    if ($peak && $peak['checkins'] > 0) {
        $peak_hour = sprintf('%02d:00', $peak['hr']);
        $peak_count = (int)$peak['checkins'];
    }
    
    $response['checkin_peak'] = [
        'hour' => $peak_hour,
        'count' => $peak_count
    ];
    
    // Only get 4 recent logs for dashboard
    $response['logs'] = $pdo->query("SELECT * FROM system_logs ORDER BY id DESC LIMIT 4")->fetchAll();
    $response['errors'] = []; 
    
    echo json_encode($response);
    exit;
}

if ($action === 'get_logs') {
    $logs = $pdo->query("SELECT * FROM system_logs ORDER BY id DESC")->fetchAll();
    echo json_encode(['success' => true, 'data' => $logs]);
    exit;
}

if ($action === 'get_employees') {
    $employees = $pdo->query("SELECT * FROM employees ORDER BY id ASC")->fetchAll();
    // Count stats
    $total = count($employees);
    $active = 0; $locked = 0;
    foreach($employees as $emp) {
        if($emp['status'] == 'active') $active++;
        if($emp['status'] == 'locked') $locked++;
    }
    echo json_encode([
        'success' => true, 
        'data' => $employees,
        'stats' => [
            'total' => $total,
            'active' => $active,
            'locked' => $locked,
            'pending' => 4 // Hardcoded for mockup
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
