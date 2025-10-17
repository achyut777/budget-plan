<?php
require_once 'database.php';

// Drop and recreate transactions table
$sql = "
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
);";

if ($conn->multi_query($sql)) {
    echo "Database schema updated successfully";
} else {
    echo "Error updating database schema: " . $conn->error;
}

$conn->close();
?> 