<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's goals
$stmt = $conn->prepare("SELECT * FROM goals WHERE user_id = ? ORDER BY deadline ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$goals = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Return goals as JSON
header('Content-Type: application/json');
echo json_encode($goals);
?> 