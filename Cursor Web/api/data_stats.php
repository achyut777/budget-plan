<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to view data statistics']);
    exit;
}

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    // Get transaction count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $transaction_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get goal count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM goals WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $goal_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get category count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM categories WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $category_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get first and last transaction dates
    $stmt = $conn->prepare("
        SELECT 
            MIN(date) as first_transaction,
            MAX(date) as last_transaction
        FROM transactions 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $date_range = $stmt->get_result()->fetch_assoc();
    
    // Format dates
    $first_transaction = $date_range['first_transaction'] ? 
        date('M d, Y', strtotime($date_range['first_transaction'])) : null;
    $last_transaction = $date_range['last_transaction'] ? 
        date('M d, Y', strtotime($date_range['last_transaction'])) : null;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'transactions' => $transaction_count,
            'goals' => $goal_count,
            'categories' => $category_count,
            'first_transaction' => $first_transaction,
            'last_transaction' => $last_transaction
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>