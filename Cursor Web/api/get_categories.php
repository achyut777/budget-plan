<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to access categories']);
    exit;
}

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    // Get user's categories
    $stmt = $conn->prepare("
        SELECT id, name, type 
        FROM categories 
        WHERE user_id = ? 
        ORDER BY type, name
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>