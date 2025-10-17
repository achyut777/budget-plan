<?php
// Test manual generation of recurring transactions
require_once 'config/database.php';

echo "=== Testing Recurring Transaction Generation ===\n";

// Simulate the API call
$user_id = 18; // The user with the due transaction

// Get all active recurring transactions that are due
$due_sql = "SELECT * FROM recurring_transactions 
            WHERE user_id = ? 
              AND is_active = 1 
              AND auto_generate = 1 
              AND next_occurrence <= CURDATE()
              AND (end_date IS NULL OR end_date >= CURDATE())";

$due_stmt = $conn->prepare($due_sql);
$due_stmt->bind_param("i", $user_id);
$due_stmt->execute();
$due_transactions = $due_stmt->get_result();

$generated_count = 0;

echo "Found " . $due_transactions->num_rows . " due transactions\n\n";

while ($recurring = $due_transactions->fetch_assoc()) {
    echo "Processing: " . $recurring['description'] . "\n";
    echo "Amount: " . $recurring['amount'] . "\n";
    echo "Due Date: " . $recurring['next_occurrence'] . "\n";
    
    // Create the actual transaction
    $insert_sql = "INSERT INTO transactions (user_id, category_id, description, amount, date) 
                   VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iisds", 
        $user_id, 
        $recurring['category_id'], 
        $recurring['description'], 
        $recurring['amount'], 
        $recurring['next_occurrence']
    );
    
    if ($insert_stmt->execute()) {
        echo "✅ Transaction created successfully!\n";
        
        // Calculate next occurrence
        $date = new DateTime($recurring['next_occurrence']);
        switch ($recurring['frequency']) {
            case 'daily':
                $date->modify('+1 day');
                break;
            case 'weekly':
                $date->modify('+1 week');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'yearly':
                $date->modify('+1 year');
                break;
        }
        
        $next_occurrence = $date->format('Y-m-d');
        echo "Next occurrence will be: " . $next_occurrence . "\n";
        
        // Update next occurrence
        $update_sql = "UPDATE recurring_transactions 
                       SET next_occurrence = ? 
                       WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $next_occurrence, $recurring['id']);
        $update_stmt->execute();
        
        $generated_count++;
        echo "✅ Next occurrence updated!\n";
    } else {
        echo "❌ Failed to create transaction\n";
    }
    echo "---\n";
}

echo "\nTotal generated: $generated_count transactions\n";
?>