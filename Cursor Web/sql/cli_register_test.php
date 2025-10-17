<?php
// Simulate register.php logic in CLI for testing DB insert and verification flow
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_verification.php';

function cli_exit($msg, $code = 0) {
    echo $msg . "\n";
    exit($code);
}

$name = 'CLI Test ' . time();
$email = 'cli_test+' . time() . '@example.com';
$password = 'TestPass123!';

// Basic validation similar to register.php
if (empty($name) || empty($email) || empty($password)) {
    cli_exit('Validation failed: fields required', 1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    cli_exit('Validation failed: invalid email', 1);
}
if (strlen($password) < 8) {
    cli_exit('Validation failed: password too short', 1);
}

// Check existing
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    cli_exit('Email already exists', 1);
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name, email, password, email_verified) VALUES (?, ?, ?, 0)");
$stmt->bind_param('sss', $name, $email, $hashed_password);
if (!$stmt->execute()) {
    cli_exit('Insert failed: ' . $stmt->error, 1);
}
$user_id = $conn->insert_id;
echo "Inserted user id: $user_id\n";

// Create default categories (reuse function from register.php simplified)
$categories = [
    ['Salary','income'],['Investments','income'],['Freelance','income'],
    ['Rent','expense'],['Food','expense'],['Transportation','expense'],
    ['Utilities','expense'],['Entertainment','expense'],['Healthcare','expense'],['Shopping','expense']
];
foreach ($categories as $c) {
    $s = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
    $s->bind_param('iss', $user_id, $c[0], $c[1]);
    $s->execute();
}

echo "Default categories created.\n";

// Attempt to send verification email (may fail if mail not configured)
$emailVerification = new EmailVerification($conn);
$sent = $emailVerification->sendVerificationEmail($user_id, $email, $name);
if ($sent) {
    echo "Verification email dispatched (or queued).\n";
} else {
    echo "Verification email NOT sent (mail may not be configured).\n";
}

// Cleanup - delete user and categories
$del = $conn->prepare('DELETE FROM users WHERE id = ?');
$del->bind_param('i', $user_id);
$del->execute();
$conn->query('DELETE FROM categories WHERE user_id = ' . intval($user_id));
echo "Cleanup done.\n";

exit(0);
?>
