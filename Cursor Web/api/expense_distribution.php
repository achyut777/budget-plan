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
        break;
    case '6_months':
        $start_date = date('Y-m-d', strtotime('-6 months'));
        break;
    case '12_months':
        $start_date = date('Y-m-d', strtotime('-12 months'));
        break;
    case '2_years':
        $start_date = date('Y-m-d', strtotime('-2 years'));
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-12 months'));
}

try {
    $distribution = getExpenseDistribution($user_id, $start_date, $end_date);
    
    echo json_encode([
        'success' => true,
        'distribution' => $distribution
    ]);
    
} catch (Exception $e) {
    error_log("Error getting expense distribution: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load expense distribution'
    ]);
}

function getExpenseDistribution($user_id, $start_date, $end_date) {
    global $conn;
    
    // Get expense distribution by category
    $stmt = $conn->prepare("
        SELECT 
            c.name as category_name,
            SUM(t.amount) as total_amount,
            COUNT(t.id) as transaction_count,
            AVG(t.amount) as avg_amount,
            MAX(t.amount) as max_amount,
            c.budget_limit
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.date BETWEEN ? AND ?
        AND c.type = 'expense'
        GROUP BY c.id, c.name, c.budget_limit
        HAVING total_amount > 0
        ORDER BY total_amount DESC
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    $total_expenses = 0;
    
    while ($row = $result->fetch_assoc()) {
        $amount = (float)$row['total_amount'];
        $total_expenses += $amount;
        
        $categories[] = [
            'name' => $row['category_name'],
            'amount' => $amount,
            'transaction_count' => (int)$row['transaction_count'],
            'avg_amount' => round((float)$row['avg_amount'], 2),
            'max_amount' => (float)$row['max_amount'],
            'budget_limit' => (float)$row['budget_limit']
        ];
    }
    
    // Calculate percentages and prepare chart data
    $labels = [];
    $values = [];
    $colors = [];
    $details = [];
    
    $color_palette = [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
        '#4BC0C0', '#36A2EB', '#FFCE56', '#FF6384'
    ];
    
    foreach ($categories as $index => $category) {
        $percentage = $total_expenses > 0 ? ($category['amount'] / $total_expenses) * 100 : 0;
        
        // Only include categories with at least 1% of total expenses
        if ($percentage >= 1.0) {
            $labels[] = $category['name'];
            $values[] = $category['amount'];
            $colors[] = $color_palette[$index % count($color_palette)];
            
            $details[] = [
                'name' => $category['name'],
                'amount' => $category['amount'],
                'percentage' => round($percentage, 1),
                'transaction_count' => $category['transaction_count'],
                'avg_amount' => $category['avg_amount'],
                'max_amount' => $category['max_amount'],
                'budget_limit' => $category['budget_limit'],
                'budget_usage' => $category['budget_limit'] > 0 ? 
                    round(($category['amount'] / $category['budget_limit']) * 100, 1) : null
            ];
        }
    }
    
    // Group small categories as "Others"
    $other_amount = 0;
    $other_count = 0;
    foreach ($categories as $category) {
        $percentage = $total_expenses > 0 ? ($category['amount'] / $total_expenses) * 100 : 0;
        if ($percentage < 1.0) {
            $other_amount += $category['amount'];
            $other_count++;
        }
    }
    
    if ($other_amount > 0) {
        $labels[] = 'Others';
        $values[] = $other_amount;
        $colors[] = '#C9CBCF';
        
        $details[] = [
            'name' => 'Others',
            'amount' => $other_amount,
            'percentage' => round(($other_amount / $total_expenses) * 100, 1),
            'transaction_count' => $other_count,
            'avg_amount' => $other_count > 0 ? round($other_amount / $other_count, 2) : 0,
            'max_amount' => null,
            'budget_limit' => null,
            'budget_usage' => null
        ];
    }
    
    // Get additional analytics
    $analytics = getExpenseAnalytics($user_id, $start_date, $end_date, $categories);
    
    return [
        'labels' => $labels,
        'values' => $values,
        'colors' => $colors,
        'total' => $total_expenses,
        'details' => $details,
        'analytics' => $analytics
    ];
}

function getExpenseAnalytics($user_id, $start_date, $end_date, $categories) {
    global $conn;
    
    // Calculate various analytics metrics
    $total_categories = count($categories);
    $total_amount = array_sum(array_column($categories, 'amount'));
    
    // Find largest and smallest expense categories
    $largest_category = null;
    $smallest_category = null;
    $largest_amount = 0;
    $smallest_amount = PHP_FLOAT_MAX;
    
    foreach ($categories as $category) {
        if ($category['amount'] > $largest_amount) {
            $largest_amount = $category['amount'];
            $largest_category = $category['name'];
        }
        if ($category['amount'] < $smallest_amount && $category['amount'] > 0) {
            $smallest_amount = $category['amount'];
            $smallest_category = $category['name'];
        }
    }
    
    // Calculate concentration index (Herfindahl-Hirschman Index)
    $hhi = 0;
    foreach ($categories as $category) {
        $share = $total_amount > 0 ? $category['amount'] / $total_amount : 0;
        $hhi += $share * $share;
    }
    $hhi *= 10000; // Convert to standard HHI scale
    
    // Determine spending concentration
    $concentration_level = 'Low';
    if ($hhi > 2500) {
        $concentration_level = 'High';
    } elseif ($hhi > 1500) {
        $concentration_level = 'Moderate';
    }
    
    // Get day-of-week spending pattern
    $stmt = $conn->prepare("
        SELECT 
            DAYOFWEEK(t.date) as day_of_week,
            SUM(t.amount) as daily_total,
            COUNT(t.id) as transaction_count
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.date BETWEEN ? AND ?
        AND c.type = 'expense'
        GROUP BY DAYOFWEEK(t.date)
        ORDER BY day_of_week
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $weekly_pattern = [];
    
    while ($row = $result->fetch_assoc()) {
        $day_index = (int)$row['day_of_week'] - 1; // MySQL DAYOFWEEK is 1-based
        $weekly_pattern[] = [
            'day' => $day_names[$day_index],
            'amount' => (float)$row['daily_total'],
            'transactions' => (int)$row['transaction_count']
        ];
    }
    
    // Find peak spending day
    $peak_day = null;
    $peak_amount = 0;
    foreach ($weekly_pattern as $day_data) {
        if ($day_data['amount'] > $peak_amount) {
            $peak_amount = $day_data['amount'];
            $peak_day = $day_data['day'];
        }
    }
    
    return [
        'total_categories' => $total_categories,
        'largest_category' => [
            'name' => $largest_category,
            'amount' => $largest_amount,
            'percentage' => $total_amount > 0 ? round(($largest_amount / $total_amount) * 100, 1) : 0
        ],
        'smallest_category' => [
            'name' => $smallest_category,
            'amount' => $smallest_amount,
            'percentage' => $total_amount > 0 ? round(($smallest_amount / $total_amount) * 100, 1) : 0
        ],
        'concentration' => [
            'index' => round($hhi, 0),
            'level' => $concentration_level,
            'description' => getConcentrationDescription($concentration_level)
        ],
        'weekly_pattern' => $weekly_pattern,
        'peak_spending_day' => $peak_day,
        'average_daily_spending' => round($total_amount / max(getDaysDifference($start_date, $end_date), 1), 2)
    ];
}

function getConcentrationDescription($level) {
    switch ($level) {
        case 'High':
            return 'Your spending is highly concentrated in a few categories. Consider diversifying.';
        case 'Moderate':
            return 'Your spending is moderately concentrated across categories.';
        case 'Low':
            return 'Your spending is well-distributed across many categories.';
        default:
            return 'Spending concentration analysis unavailable.';
    }
}

function getDaysDifference($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    return $start->diff($end)->days + 1; // Include both start and end dates
}
?>