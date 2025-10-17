<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT id, name, email, is_admin FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo "<h2>User Status Check</h2>";
echo "<pre>";
echo "User ID: " . $user['id'] . "\n";
echo "Name: " . $user['name'] . "\n";
echo "Email: " . $user['email'] . "\n";
echo "Is Admin: " . ($user['is_admin'] ? "Yes" : "No") . "\n";
echo "</pre>";

// Show table structure
$result = $conn->query("DESCRIBE users");
echo "<h3>Users Table Structure:</h3>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Show all admin users
$admin_users = $conn->query("SELECT id, name, email FROM users WHERE is_admin = 1");
echo "<h3>Admin Users:</h3>";
echo "<pre>";
while ($admin = $admin_users->fetch_assoc()) {
    print_r($admin);
}
echo "</pre>";
?> 