<?php
require_once __DIR__ . '/../config/database.php';

function columnExists($conn, $table, $column) {
    // SHOW COLUMNS does not accept parameterized identifiers reliably, so escape and run directly
    $colEscaped = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "` LIKE '" . $colEscaped . "'");
    return ($res && $res->num_rows > 0);
}

$cols = [
    'email_verified' => "ADD COLUMN email_verified TINYINT(1) DEFAULT 0",
    'verification_token' => "ADD COLUMN verification_token VARCHAR(255) NULL",
    'verification_expires' => "ADD COLUMN verification_expires DATETIME NULL",
    'verified_at' => "ADD COLUMN verified_at DATETIME NULL",
    'verification_attempts' => "ADD COLUMN verification_attempts INT DEFAULT 0"
];

$alterParts = [];
foreach ($cols as $col => $sqlPart) {
    if (!columnExists($conn, 'users', $col)) {
        $alterParts[] = $sqlPart;
    }
}

if (empty($alterParts)) {
    echo "No changes needed. 'users' table already has the required columns.\n";
    exit(0);
}

$alterSql = "ALTER TABLE users " . implode(", ", $alterParts);

if ($conn->query($alterSql) === TRUE) {
    echo "Migration applied successfully:\n" . $alterSql . "\n";

    // Add indexes if they don't exist (check result rows)
    $res1 = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_verification_token'");
    if (!($res1 && $res1->num_rows > 0)) {
        $conn->query("ALTER TABLE users ADD INDEX idx_verification_token (verification_token)");
    }
    $res2 = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_email_verified'");
    if (!($res2 && $res2->num_rows > 0)) {
        $conn->query("ALTER TABLE users ADD INDEX idx_email_verified (email_verified)");
    }
} else {
    echo "Error applying migration: " . $conn->error . "\n";
}

?>
