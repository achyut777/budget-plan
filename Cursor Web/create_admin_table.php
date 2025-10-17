<?php
require_once 'config/database.php';

// Create admin_users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Admin users table created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Check if admin user exists
$admin_email = "admin@budgetplanner.com";
$check_admin = $conn->query("SELECT id FROM admin_users WHERE email = '$admin_email'");

if ($check_admin->num_rows == 0) {
    // Create admin user
    $name = "Admin";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO admin_users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $admin_email, $password);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Email: admin@budgetplanner.com<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
} else {
    echo "Admin user already exists<br>";
}

// Display all admin users
echo "<br>Current admin users:<br>";
$admins = $conn->query("SELECT id, name, email FROM admin_users");
while ($admin = $admins->fetch_assoc()) {
    echo "ID: " . $admin['id'] . " | Name: " . $admin['name'] . " | Email: " . $admin['email'] . "<br>";
}

$conn->close();
?> 