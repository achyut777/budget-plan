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
    $categories = getCategoryAnalysis($user_id, $start_date, $end_date);
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    error_log("Error getting category analysis: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load category analysis'
    ]);
}

function getCategoryAnalysis($user_id, $start_date, $end_date) {
    global $conn;
    
    // Get category spending vs budget with trend analysis
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.name,
            c.budget_limit,
            COALESCE(SUM(t.amount), 0) as spent,
            COUNT(t.id) as transaction_count,
            AVG(t.amount) as avg_transaction,
            MAX(t.amount) as max_transaction,
            MIN(t.amount) as min_transaction
        FROM categories c
        LEFT JOIN transactions t ON c.id = t.category_id 
            AND t.user_id = ? 
            AND t.date BETWEEN ? AND ?
        WHERE c.user_id = ? 
        AND c.type = 'expense'
        GROUP BY c.id, c.name, c.budget_limit
        ORDER BY spent DESC
    ");
    $stmt->bind_param("issi", $user_id, $start_date, $end_date, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $spent = (float)$row['spent'];
        $budget = (float)$row['budget_limit'];
        $percentage = $budget > 0 ? ($spent / $budget) * 100 : 0;
        
        // Get monthly trend for this category
        $monthly_trend = getCategoryMonthlyTrend($user_id, $row['id'], $start_date, $end_date);
        
        // Calculate performance metrics
        $performance = calculateCategoryPerformance($spent, $budget, $monthly_trend);
        
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'spent' => $spent,
            'budget' => $budget,
            'percentage' => round($percentage, 1),
            'transaction_count' => (int)$row['transaction_count'],
            'avg_transaction' => round((float)$row['avg_transaction'], 2),
            'max_transaction' => (float)$row['max_transaction'],
            'min_transaction' => (float)$row['min_transaction'],
            'monthly_trend' => $monthly_trend,
            'performance' => $performance,
            'status' => getCategoryStatus($percentage),
            'recommendation' => getCategoryRecommendation($percentage, $performance['trend'])
        ];
    }
    
    return $categories;
}

function getCategoryMonthlyTrend($user_id, $category_id, $start_date, $end_date) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(t.date, '%Y-%m') as month,
            SUM(t.amount) as monthly_spent
        FROM transactions t
        WHERE t.user_id = ? 
        AND t.category_id = ?
        AND t.date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(t.date, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->bind_param("iiss", $user_id, $category_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trend_data = [];
    while ($row = $result->fetch_assoc()) {
        $trend_data[] = (float)$row['monthly_spent'];
    }
    
    return $trend_data;
}

function calculateCategoryPerformance($spent, $budget, $monthly_trend) {
    // Calculate trend direction
    $trend_direction = 0;
    if (count($monthly_trend) >= 2) {
        $recent_avg = array_sum(array_slice($monthly_trend, -3)) / min(3, count($monthly_trend));
        $earlier_avg = array_sum(array_slice($monthly_trend, 0, 3)) / min(3, count($monthly_trend));
        
        if ($earlier_avg > 0) {
            $trend_direction = (($recent_avg - $earlier_avg) / $earlier_avg) * 100;
        }
    }
    
    // Calculate spending consistency (lower is better)
    $consistency = 0;
    if (count($monthly_trend) > 1) {
        $avg = array_sum($monthly_trend) / count($monthly_trend);
        if ($avg > 0) {
            $variance = 0;
            foreach ($monthly_trend as $value) {
                $variance += pow($value - $avg, 2);
            }
            $variance = $variance / count($monthly_trend);
            $consistency = sqrt($variance) / $avg * 100;
        }
    }
    
    // Calculate efficiency score
    $efficiency = 100;
    if ($budget > 0) {
        $usage_ratio = $spent / $budget;
        if ($usage_ratio <= 0.8) {
            $efficiency = 90 + ($usage_ratio * 10); // Good usage
        } elseif ($usage_ratio <= 1.0) {
            $efficiency = 70 + ((1 - $usage_ratio) * 20); // Approaching limit
        } else {
            $efficiency = max(0, 70 - (($usage_ratio - 1) * 70)); // Over budget
        }
    }
    
    return [
        'trend' => round($trend_direction, 2),
        'consistency' => round($consistency, 2),
        'efficiency' => round($efficiency, 1)
    ];
}

function getCategoryStatus($percentage) {
    if ($percentage <= 60) {
        return 'excellent';
    } elseif ($percentage <= 80) {
        return 'good';
    } elseif ($percentage <= 100) {
        return 'warning';
    } else {
        return 'over_budget';
    }
}

function getCategoryRecommendation($percentage, $trend) {
    if ($percentage > 100) {
        return "You've exceeded your budget for this category. Consider reducing spending or adjusting your budget.";
    } elseif ($percentage > 80) {
        if ($trend > 10) {
            return "Spending is increasing and approaching your budget limit. Monitor carefully.";
        } else {
            return "You're near your budget limit but trending well. Stay mindful of spending.";
        }
    } elseif ($trend > 20) {
        return "Spending in this category is increasing rapidly. Consider reviewing recent expenses.";
    } elseif ($percentage < 30 && $trend < -10) {
        return "Great job! You're well under budget and spending is decreasing.";
    } else {
        return "Spending is well within budget. Keep up the good work!";
    }
}
?>