<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get last 6 months of data
$sql = "SELECT 
            DATE_FORMAT(t.date, '%Y-%m') as month,
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as expenses
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        AND t.date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(t.date, '%Y-%m')
        ORDER BY month ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$income = [];
$expenses = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $income[] = floatval($row['income']);
    $expenses[] = floatval($row['expenses']);
}

// If no data, return empty arrays
if (empty($labels)) {
    $labels = ['No data'];
    $income = [0];
    $expenses = [0];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'income' => $income,
    'expenses' => $expenses
]);
?> 