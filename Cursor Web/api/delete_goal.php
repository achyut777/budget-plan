<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing goal ID']);
    exit();
}

$user_id = $_SESSION['user_id'];
$goal_id = filter_var($input['id'], FILTER_SANITIZE_NUMBER_INT);

// Validate goal belongs to user
$check_sql = "SELECT id FROM goals WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $goal_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid goal']);
    exit();
}

// Delete the goal
$sql = "DELETE FROM goals WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $goal_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 