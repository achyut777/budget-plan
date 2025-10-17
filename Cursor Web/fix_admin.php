<?php
require_once 'config/database.php';

// First, check if the role column exists
$check_role = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($check_role->num_rows == 0) {
    // Add role column if it doesn't exist
    $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
    echo "Added role column to users table<br>";
}

// Check if admin user exists
$admin_email = "admin@budgetplanner.com";
$check_admin = $conn->query("SELECT * FROM users WHERE email = '$admin_email'");

if ($check_admin->num_rows == 0) {
    // Create admin user if doesn't exist
    $name = "Admin";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $role = "admin";
    
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $admin_email, $password, $role);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Email: admin@budgetplanner.com<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
} else {
    // Update existing admin user's role
    $conn->query("UPDATE users SET role = 'admin' WHERE email = '$admin_email'");
    echo "Updated existing admin user's role<br>";
}

// Display all users with their roles
echo "<br>Current users in database:<br>";
$users = $conn->query("SELECT id, name, email, role FROM users");
while ($user = $users->fetch_assoc()) {
    echo "ID: " . $user['id'] . " | Name: " . $user['name'] . " | Email: " . $user['email'] . " | Role: " . $user['role'] . "<br>";
}

$conn->close();
?> 