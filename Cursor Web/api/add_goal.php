<?php
// Prevent any output before JSON response
ob_start();

// Enable error reporting but log to file instead of output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

header('Content-Type: application/json');

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

try {
    // Get JSON data from request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Debug log
    error_log("Received data: " . print_r($data, true));

    // Validate required fields
    if (!isset($data['name']) || !isset($data['target_amount']) || !isset($data['deadline'])) {
        throw new Exception('Missing required fields: ' . json_encode($data));
    }

    // Sanitize and validate data
    $user_id = $_SESSION['user_id'];
    $name = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
    $target_amount = filter_var($data['target_amount'], FILTER_VALIDATE_FLOAT);
    $current_amount = isset($data['current_amount']) ? filter_var($data['current_amount'], FILTER_VALIDATE_FLOAT) : 0;
    $deadline = date('Y-m-d', strtotime($data['deadline']));

    // Debug log
    error_log("Sanitized data: user_id=$user_id, name=$name, target_amount=$target_amount, current_amount=$current_amount, deadline=$deadline");

    // Validate data after sanitization
    if (!$name || !$target_amount || !$deadline) {
        throw new Exception('Invalid data after sanitization: ' . json_encode([
            'name' => $name,
            'target_amount' => $target_amount,
            'current_amount' => $current_amount,
            'deadline' => $deadline
        ]));
    }

    // Verify database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Insert goal
    $sql = "INSERT INTO goals (user_id, name, target_amount, current_amount, deadline) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Goal prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isdds", $user_id, $name, $target_amount, $current_amount, $deadline);

    if (!$stmt->execute()) {
        throw new Exception("Goal execution failed: " . $stmt->error);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Goal added successfully',
        'goal_id' => $conn->insert_id
    ]);

} catch (Exception $e) {
    error_log("Error in add_goal.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error adding goal',
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    ob_end_flush();
}
?> 