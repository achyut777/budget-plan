<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing goal ID']);
    exit();
}

$user_id = $_SESSION['user_id'];
$goal_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get goal details
$sql = "SELECT * FROM goals WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $goal_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Goal not found']);
    exit();
}

$goal = $result->fetch_assoc();
echo json_encode(['success' => true, 'data' => $goal]);
?> 