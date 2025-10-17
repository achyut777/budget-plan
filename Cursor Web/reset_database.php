<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost:3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'budget_planner');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop existing database if exists
$sql = "DROP DATABASE IF EXISTS " . DB_NAME;
if ($conn->query($sql) === FALSE) {
    die("Error dropping database: " . $conn->error);
}

// Create fresh database
$sql = "CREATE DATABASE " . DB_NAME;
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Create tables
$sql = "
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE budget_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    period ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);";

// Execute SQL queries
$queries = explode(';', $sql);
foreach ($queries as $query) {
    if (trim($query) != '') {
        if (!$conn->query($query)) {
            die("Error creating table: " . $conn->error);
        }
    }
}

// Create demo user
$name = 'Demo User';
$email = 'demo@example.com';
$password = password_hash('demo1234', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $password);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    echo "Demo user created successfully.<br>";
} else {
    die("Error creating demo user: " . $stmt->error);
}

// Insert default categories
$categories = [
    ['Salary', 'income'],
    ['Investments', 'income'],
    ['Freelance', 'income'],
    ['Rent', 'expense'],
    ['Food', 'expense'],
    ['Transportation', 'expense'],
    ['Utilities', 'expense'],
    ['Shopping', 'expense'],
    ['Entertainment', 'expense'],
    ['Healthcare', 'expense']
];

$stmt = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");

foreach ($categories as $category) {
    $stmt->bind_param("iss", $user_id, $category[0], $category[1]);
    if (!$stmt->execute()) {
        die("Error creating category: " . $stmt->error);
    }
}

echo "Default categories created successfully.<br>";

// Insert some sample transactions
$transactions = [
    ['income', 'Salary', 50000, 'Monthly salary', '2024-03-01'],
    ['income', 'Investments', 5000, 'Stock dividends', '2024-03-05'],
    ['expense', 'Rent', 15000, 'Monthly rent', '2024-03-02'],
    ['expense', 'Food', 8000, 'Groceries', '2024-03-03'],
    ['expense', 'Transportation', 3000, 'Fuel', '2024-03-04']
];

foreach ($transactions as $transaction) {
    // Get category ID
    $stmt = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ? LIMIT 1");
    $stmt->bind_param("iss", $user_id, $transaction[1], $transaction[0]);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    
    if ($category) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, category_id, amount, description, date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $user_id, $category['id'], $transaction[2], $transaction[3], $transaction[4]);
        if (!$stmt->execute()) {
            die("Error creating transaction: " . $stmt->error);
        }
    }
}

echo "Sample transactions created successfully.<br>";

// Close connection
$conn->close();

echo "Database reset and initialized successfully!<br>";
echo "You can now login with:<br>";
echo "Email: demo@example.com<br>";
echo "Password: demo1234<br>";
?> 