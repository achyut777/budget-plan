<?php
require_once 'config/database.php';

echo "=== Checking Recurring Transactions ===\n";

// Check if recurring_transactions table exists
$tables = $conn->query("SHOW TABLES LIKE 'recurring_transactions'");
if ($tables->num_rows === 0) {
    echo "ERROR: recurring_transactions table does not exist!\n";
    exit;
}

// Get all recurring transactions
$result = $conn->query("SELECT * FROM recurring_transactions WHERE is_active = 1");
echo "Active Recurring Transactions: " . $result->num_rows . "\n\n";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "User: " . $row['user_id'] . "\n";
        echo "Description: " . $row['description'] . "\n";
        echo "Amount: " . $row['amount'] . "\n";
        echo "Frequency: " . $row['frequency'] . "\n";
        echo "Next Occurrence: " . $row['next_occurrence'] . "\n";
        echo "Auto Generate: " . ($row['auto_generate'] ? 'YES' : 'NO') . "\n";
        echo "Today's Date: " . date('Y-m-d') . "\n";
        echo "Due Today? " . ($row['next_occurrence'] <= date('Y-m-d') ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
} else {
    echo "No active recurring transactions found.\n";
}

// Check recent transactions
echo "\n=== Recent Transactions ===\n";
$recent = $conn->query("SELECT * FROM transactions ORDER BY date DESC LIMIT 5");
while($row = $recent->fetch_assoc()) {
    echo "Date: " . $row['date'] . " - " . $row['description'] . " - " . $row['amount'] . "\n";
}
?>