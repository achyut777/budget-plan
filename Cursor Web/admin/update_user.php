<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($name) || empty($email)) {
        $_SESSION['error'] = "Name and email are required.";
        header("Location: user_details.php?id=" . $user_id);
        exit();
    }

    // Start building the SQL query
    $sql_parts = ["UPDATE users SET name = ?, email = ?"];
    $types = "ss";
    $params = [$name, $email];

    // Add password update if provided
    if (!empty($password)) {
        $sql_parts[] = "password = ?";
        $types .= "s";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql_parts[] = "WHERE id = ?";
    $types .= "i";
    $params[] = $user_id;

    // Prepare and execute the query
    $sql = implode(" ", $sql_parts);
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['success'] = "User updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating user: " . $conn->error;
    }

    header("Location: user_details.php?id=" . $user_id);
    exit();
}

// If not POST request, redirect to dashboard
header('Location: dashboard.php');
exit(); 