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

// Get expense distribution by category
$sql = "SELECT c.name, SUM(t.amount) as total_amount
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND c.type = 'expense'
        AND MONTH(t.date) = MONTH(CURRENT_DATE())
        GROUP BY c.id, c.name
        ORDER BY total_amount DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$labels = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['name'];
    $values[] = floatval($row['total_amount']);
}

// If no data, return empty arrays
if (empty($labels)) {
    $labels = ['No expenses'];
    $values = [0];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'values' => $values
]);
?> 