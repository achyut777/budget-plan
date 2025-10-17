<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get data from POST or JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
} else {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
}

// Validate input
if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and email are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if email already exists for another user
$check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("si", $email, $user_id);
$check_stmt->execute();
$existing_user = $check_stmt->get_result()->fetch_assoc();

if ($existing_user) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit();
}

// Update user profile
$update_sql = "UPDATE users SET name = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ssi", $name, $email, $user_id);

if ($update_stmt->execute()) {
    // Update session with new name
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully', 'name' => $name, 'email' => $email]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
}

$update_stmt->close();
$conn->close();
?> 