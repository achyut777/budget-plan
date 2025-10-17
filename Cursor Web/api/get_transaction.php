<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get transaction ID from query string
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing transaction ID']);
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get transaction details
$sql = "SELECT t.*, c.type FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.id = ? AND t.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit();
}

$transaction = $result->fetch_assoc();
echo json_encode(['success' => true, 'data' => $transaction]);
?> 