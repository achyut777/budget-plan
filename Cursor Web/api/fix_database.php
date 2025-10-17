<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Drop and recreate the transactions table
$sql = "
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS transactions;

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
";

// Execute each query separately
$queries = explode(';', $sql);
$success = true;
$errors = [];

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!$conn->query($query)) {
            $success = false;
            $errors[] = "Error executing query: " . $conn->error . "\nQuery: " . $query;
        }
    }
}

header('Content-Type: application/json');
if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Database structure fixed successfully. The transactions table has been recreated.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Errors occurred while fixing database',
        'errors' => $errors
    ]);
}

$conn->close();
?> 