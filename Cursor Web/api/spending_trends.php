<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$range = $_GET['range'] ?? '12_months';

// Calculate date range
$end_date = date('Y-m-d');
switch ($range) {
    case '3_months':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        $period_format = '%Y-%m';
        break;
    case '6_months':
        $start_date = date('Y-m-d', strtotime('-6 months'));
        $period_format = '%Y-%m';
        break;
    case '12_months':
        $start_date = date('Y-m-d', strtotime('-12 months'));
        $period_format = '%Y-%m';
        break;
    case '2_years':
        $start_date = date('Y-m-d', strtotime('-2 years'));
        $period_format = '%Y-%m';
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-12 months'));
        $period_format = '%Y-%m';
}

try {
    $trends_data = getSpendingTrends($user_id, $start_date, $end_date, $period_format);
    $metrics = calculateSpendingMetrics($trends_data);
    
    echo json_encode([
        'success' => true,
        'trends' => $trends_data,
        'metrics' => $metrics
    ]);
    
} catch (Exception $e) {
    error_log("Error getting spending trends: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load spending trends'
    ]);
}

function getSpendingTrends($user_id, $start_date, $end_date, $period_format) {
    global $conn;
    
    // Get monthly income, expenses, and savings
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(t.date, ?) as period,
            c.type,
            SUM(t.amount) as total_amount
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(t.date, ?), c.type
        ORDER BY period ASC
    ");
    $stmt->bind_param("sisss", $period_format, $user_id, $start_date, $end_date, $period_format);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $all_periods = [];
    
    while ($row = $result->fetch_assoc()) {
        $period = $row['period'];
        $type = $row['type'];
        $amount = (float)$row['total_amount'];
        
        if (!isset($data[$period])) {
            $data[$period] = ['income' => 0, 'expense' => 0];
        }
        
        $data[$period][$type] = $amount;
        
        if (!in_array($period, $all_periods)) {
            $all_periods[] = $period;
        }
    }
    
    // Fill in missing periods with zeros
    $start_period = date('Y-m', strtotime($start_date));
    $end_period = date('Y-m', strtotime($end_date));
    $current_period = $start_period;
    
    while ($current_period <= $end_period) {
        if (!isset($data[$current_period])) {
            $data[$current_period] = ['income' => 0, 'expense' => 0];
        }
        $current_period = date('Y-m', strtotime($current_period . '-01 +1 month'));
    }
    
    // Sort periods
    ksort($data);
    
    // Format data for Chart.js
    $labels = [];
    $income_data = [];
    $expense_data = [];
    $savings_data = [];
    
    foreach ($data as $period => $amounts) {
        $labels[] = date('M Y', strtotime($period . '-01'));
        $income_data[] = $amounts['income'];
        $expense_data[] = $amounts['expense'];
        $savings_data[] = $amounts['income'] - $amounts['expense'];
    }
    
    return [
        'labels' => $labels,
        'income' => $income_data,
        'expenses' => $expense_data,
        'savings' => $savings_data
    ];
}

function calculateSpendingMetrics($trends_data) {
    $expenses = $trends_data['expenses'];
    $income = $trends_data['income'];
    
    // Calculate average monthly spending
    $average_spending = array_sum($expenses) / max(count($expenses), 1);
    
    // Calculate spending volatility (coefficient of variation)
    $variance = 0;
    $count = count($expenses);
    
    if ($count > 1 && $average_spending > 0) {
        foreach ($expenses as $expense) {
            $variance += pow($expense - $average_spending, 2);
        }
        $variance = $variance / ($count - 1);
        $std_deviation = sqrt($variance);
        $volatility = ($std_deviation / $average_spending) * 100;
    } else {
        $volatility = 0;
    }
    
    // Calculate trend direction using linear regression
    $trend_direction = calculateTrendDirection($expenses);
    
    return [
        'average_spending' => round($average_spending, 2),
        'volatility' => round($volatility, 1),
        'trend_direction' => $trend_direction
    ];
}

function calculateTrendDirection($data) {
    $n = count($data);
    if ($n < 2) return 0;
    
    $sum_x = 0;
    $sum_y = 0;
    $sum_xy = 0;
    $sum_x2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $x = $i + 1;
        $y = $data[$i];
        
        $sum_x += $x;
        $sum_y += $y;
        $sum_xy += $x * $y;
        $sum_x2 += $x * $x;
    }
    
    $denominator = $n * $sum_x2 - $sum_x * $sum_x;
    if ($denominator == 0) return 0;
    
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / $denominator;
    
    // Convert slope to percentage change
    $avg_value = array_sum($data) / $n;
    $trend_percentage = $avg_value > 0 ? ($slope / $avg_value) * 100 : 0;
    
    return round($trend_percentage, 2);
}
?>