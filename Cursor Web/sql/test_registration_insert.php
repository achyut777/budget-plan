<?php
// Quick test script to verify the INSERT used by register.php works
require_once __DIR__ . '/../config/database.php';

$name = 'Test User ' . time();
$email = 'test+' . time() . '@example.com';
$password = password_hash('TestPass123!', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password, email_verified) VALUES (?, ?, ?, 0)");
if (!$stmt) {
    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n";
    exit(1);
}

$stmt->bind_param('sss', $name, $email, $password);

if ($stmt->execute()) {
    $insertId = $conn->insert_id;
    echo "Insert succeeded, new user id: " . $insertId . "\n";
    // cleanup
    $del = $conn->prepare("DELETE FROM users WHERE id = ?");
    $del->bind_param('i', $insertId);
    $del->execute();
    echo "Cleanup done (deleted test user).\n";
    exit(0);
} else {
    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error . "\n";
    exit(1);
}
?>
