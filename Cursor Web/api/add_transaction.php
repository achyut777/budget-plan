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
    if (!isset($data['type']) || !isset($data['category_id']) || !isset($data['amount']) || !isset($data['date']) || !isset($data['description'])) {
        throw new Exception('Missing required fields: ' . json_encode($data));
    }

    // Sanitize and validate data
    $user_id = $_SESSION['user_id'];
    $type = in_array($data['type'], ['income', 'expense']) ? $data['type'] : null;
    $category_id = filter_var($data['category_id'], FILTER_VALIDATE_INT);
    $amount = filter_var($data['amount'], FILTER_VALIDATE_FLOAT);
    $date = date('Y-m-d', strtotime($data['date']));
    $description = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');

    // Debug log
    error_log("Sanitized data: user_id=$user_id, type=$type, category_id=$category_id, amount=$amount, date=$date, description=$description");

    // Validate data after sanitization
    if (!$type || !$category_id || !$amount || !$date || !$description) {
        throw new Exception('Invalid data after sanitization: ' . json_encode([
            'type' => $type,
            'category_id' => $category_id,
            'amount' => $amount,
            'date' => $date,
            'description' => $description
        ]));
    }

    // Verify database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Verify category belongs to user or is a default category
    $category_check_sql = "SELECT id FROM categories WHERE id = ? AND (user_id = ? OR user_id IS NULL)";
    $category_check_stmt = $conn->prepare($category_check_sql);
    
    if (!$category_check_stmt) {
        throw new Exception("Category check prepare failed: " . $conn->error);
    }
    
    $category_check_stmt->bind_param("ii", $category_id, $user_id);
    $category_check_stmt->execute();
    $category_result = $category_check_stmt->get_result();

    if ($category_result->num_rows === 0) {
        throw new Exception('Invalid category or category does not belong to user');
    }

    // Insert transaction
    $sql = "INSERT INTO transactions (user_id, type, category_id, amount, date, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Transaction prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isidss", $user_id, $type, $category_id, $amount, $date, $description);

    if (!$stmt->execute()) {
        throw new Exception("Transaction execution failed: " . $stmt->error);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Transaction added successfully',
        'transaction_id' => $conn->insert_id
    ]);

} catch (Exception $e) {
    error_log("Error in add_transaction.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error adding transaction',
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($category_check_stmt)) $category_check_stmt->close();
    if (isset($conn)) $conn->close();
    ob_end_flush();
}
?> 