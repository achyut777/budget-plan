<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get and decode the JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$user_id = filter_var($data['user_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    // First check if user exists
    $check_sql = "SELECT id FROM users WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Delete user's transactions
    $delete_transactions = "DELETE FROM transactions WHERE user_id = ?";
    $stmt = $conn->prepare($delete_transactions);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Delete user's categories
    $delete_categories = "DELETE FROM categories WHERE user_id = ?";
    $stmt = $conn->prepare($delete_categories);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Finally delete the user
    $delete_user = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_user);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?> 