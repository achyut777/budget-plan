<?php
require_once 'config/database.php';

// Admin user details
$name = "Admin";
$email = "admin@budgetplanner.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "admin";

// Check if admin already exists
$check = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($check->num_rows > 0) {
    echo "Admin user already exists!";
    exit();
}

// Insert admin user
$sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $name, $email, $password, $role);

if ($stmt->execute()) {
    echo "Admin user created successfully!<br>";
    echo "Email: admin@budgetplanner.com<br>";
    echo "Password: admin123";
} else {
    echo "Error creating admin user: " . $conn->error;
}

$stmt->close();
$conn->close();
?> 