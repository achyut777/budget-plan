<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if user exists and is admin
if (!$user) {
    header('Location: ../login.php');
    exit();
}

// If not admin, redirect to main page
if (!isset($user['is_admin']) || $user['is_admin'] != 1) {
    header('Location: ../index.php');
    exit();
}
?> 