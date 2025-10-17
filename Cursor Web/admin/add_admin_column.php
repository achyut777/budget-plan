<?php
require_once '../config/database.php';

// Check if is_admin column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");

if ($check_column->num_rows === 0) {
    // Add is_admin column if it doesn't exist
    $add_column = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0";
    if ($conn->query($add_column)) {
        echo "Successfully added is_admin column to users table<br>";
        
        // Set the first user as admin
        $update_admin = "UPDATE users SET is_admin = 1 ORDER BY id ASC LIMIT 1";
        if ($conn->query($update_admin)) {
            echo "Successfully set first user as admin<br>";
        } else {
            echo "Error setting admin: " . $conn->error . "<br>";
        }
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "is_admin column already exists<br>";
}

// Show current admin users
$admin_users = $conn->query("SELECT id, name, email FROM users WHERE is_admin = 1");
if ($admin_users->num_rows > 0) {
    echo "<br>Current admin users:<br>";
    while ($admin = $admin_users->fetch_assoc()) {
        echo "ID: {$admin['id']}, Name: {$admin['name']}, Email: {$admin['email']}<br>";
    }
} else {
    echo "<br>No admin users found<br>";
}

echo "<br>Done! You can now <a href='../login.php'>login</a> with an admin account.";
?> 