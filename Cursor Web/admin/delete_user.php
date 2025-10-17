<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete user's transactions
        $stmt = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete user's goals
        $stmt = $conn->prepare("DELETE FROM goals WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "User and all associated data deleted successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
    }

    header('Location: dashboard.php');
    exit();
}

// If not POST request, redirect to dashboard
header('Location: dashboard.php');
exit(); 