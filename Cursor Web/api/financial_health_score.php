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
    $health_score = calculateFinancialHealthScore($user_id, $start_date, $end_date);
    
    echo json_encode([
        'success' => true,
        'score' => $health_score
    ]);
    
} catch (Exception $e) {
    error_log("Error calculating financial health score: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to calculate financial health score'
    ]);
}

function calculateFinancialHealthScore($user_id, $start_date, $end_date) {
    global $conn;
    
    // Get total income and expenses for the period
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as total_income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as total_expenses
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $totals = $result->fetch_assoc();
    
    $total_income = $totals['total_income'] ?? 0;
    $total_expenses = $totals['total_expenses'] ?? 0;
    
    // Calculate savings rate (30% of score)
    $savings = $total_income - $total_expenses;
    $savings_rate = $total_income > 0 ? ($savings / $total_income) * 100 : 0;
    $savings_score = min(($savings_rate / 20) * 30, 30); // 20% savings rate = full 30 points
    
    // Calculate expense ratio (25% of score)
    $expense_ratio = $total_income > 0 ? ($total_expenses / $total_income) * 100 : 100;
    $expense_score = max(25 - (($expense_ratio - 60) / 40) * 25, 0); // 60% or less = full 25 points
    
    // Calculate budget adherence (20% of score)
    $budget_adherence = calculateBudgetAdherence($user_id, $start_date, $end_date);
    $budget_score = ($budget_adherence / 100) * 20;
    
    // Calculate financial stability (15% of score)
    $stability_months = calculateEmergencyFundCoverage($user_id);
    $stability_score = min(($stability_months / 6) * 15, 15); // 6 months = full 15 points
    
    // Calculate debt management (10% of score)
    $debt_score = calculateDebtScore($user_id, $start_date, $end_date);
    
    // Calculate overall score
    $overall_score = round($savings_score + $expense_score + $budget_score + $stability_score + $debt_score);
    
    // Get previous period for comparison
    $prev_start = date('Y-m-d', strtotime($start_date . ' -' . getDaysDifference($start_date, $end_date) . ' days'));
    $prev_end = $start_date;
    $prev_score = calculatePreviousScore($user_id, $prev_start, $prev_end);
    
    return [
        'overall_score' => $overall_score,
        'savings_rate' => round($savings_rate, 1),
        'expense_ratio' => round($expense_ratio, 1),
        'budget_adherence' => round($budget_adherence, 1),
        'stability_months' => round($stability_months, 1),
        'debt_score' => round($debt_score, 1),
        'change' => round($overall_score - $prev_score, 1),
        'components' => [
            'savings' => round($savings_score, 1),
            'expenses' => round($expense_score, 1),
            'budget' => round($budget_score, 1),
            'stability' => round($stability_score, 1),
            'debt' => round($debt_score, 1)
        ]
    ];
}

function calculateBudgetAdherence($user_id, $start_date, $end_date) {
    global $conn;
    
    // Get categories with budgets
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.budget_limit,
            COALESCE(SUM(t.amount), 0) as spent
        FROM categories c
        LEFT JOIN transactions t ON c.id = t.category_id 
            AND t.user_id = ? 
            AND t.date BETWEEN ? AND ?
        WHERE c.user_id = ? 
        AND c.type = 'expense' 
        AND c.budget_limit > 0
        GROUP BY c.id, c.budget_limit
    ");
    $stmt->bind_param("issi", $user_id, $start_date, $end_date, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_categories = 0;
    $adhering_categories = 0;
    
    while ($row = $result->fetch_assoc()) {
        $total_categories++;
        $adherence = ($row['spent'] / $row['budget_limit']) * 100;
        if ($adherence <= 100) {
            $adhering_categories++;
        }
    }
    
    return $total_categories > 0 ? ($adhering_categories / $total_categories) * 100 : 100;
}

function calculateEmergencyFundCoverage($user_id) {
    global $conn;
    
    // Get current balance (simplified - in real app, you'd track actual balances)
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE -t.amount END) as balance
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $balance = $result->fetch_assoc()['balance'] ?? 0;
    
    // Get average monthly expenses for last 6 months
    $stmt = $conn->prepare("
        SELECT 
            AVG(monthly_expenses) as avg_monthly_expenses
        FROM (
            SELECT 
                YEAR(t.date) as year,
                MONTH(t.date) as month,
                SUM(t.amount) as monthly_expenses
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND c.type = 'expense'
            AND t.date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY YEAR(t.date), MONTH(t.date)
        ) as monthly_data
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $avg_monthly_expenses = $result->fetch_assoc()['avg_monthly_expenses'] ?? 0;
    
    return $avg_monthly_expenses > 0 ? $balance / $avg_monthly_expenses : 0;
}

function calculateDebtScore($user_id, $start_date, $end_date) {
    global $conn;
    
    // Check for debt-related categories (loans, credit cards, etc.)
    $stmt = $conn->prepare("
        SELECT 
            SUM(t.amount) as debt_payments
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.date BETWEEN ? AND ?
        AND c.type = 'expense'
        AND (LOWER(c.name) LIKE '%loan%' 
             OR LOWER(c.name) LIKE '%credit%' 
             OR LOWER(c.name) LIKE '%debt%'
             OR LOWER(c.name) LIKE '%emi%')
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $debt_payments = $result->fetch_assoc()['debt_payments'] ?? 0;
    
    // Get total income for the period
    $stmt = $conn->prepare("
        SELECT 
            SUM(t.amount) as total_income
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.date BETWEEN ? AND ?
        AND c.type = 'income'
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_income = $result->fetch_assoc()['total_income'] ?? 0;
    
    // Calculate debt-to-income ratio
    $debt_ratio = $total_income > 0 ? ($debt_payments / $total_income) * 100 : 0;
    
    // Score: 10 points for debt ratio under 20%, decreasing to 0 for 40%+
    if ($debt_ratio <= 20) {
        return 10;
    } elseif ($debt_ratio <= 40) {
        return 10 - (($debt_ratio - 20) / 20) * 10;
    } else {
        return 0;
    }
}

function calculatePreviousScore($user_id, $start_date, $end_date) {
    // Simplified previous score calculation
    // In a real implementation, you'd store historical scores or recalculate
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as expenses
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $income = $data['income'] ?? 0;
    $expenses = $data['expenses'] ?? 0;
    $savings_rate = $income > 0 ? (($income - $expenses) / $income) * 100 : 0;
    
    // Simplified score based on savings rate
    return min($savings_rate * 2, 85); // Cap at 85 for previous period
}

function getDaysDifference($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    return $start->diff($end)->days;
}
?>