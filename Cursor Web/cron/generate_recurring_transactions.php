<?php
/**
 * Cron Job Script for Recurring Transactions
 * 
 * This script should be run daily to automatically generate
 * transactions from recurring transaction schedules.
 * 
 * Add to crontab:
 * 0 1 * * * /usr/bin/php /path/to/your/project/cron/generate_recurring_transactions.php
 */

// Include database configuration
require_once dirname(__DIR__) . '/config/database.php';

// Log file for cron job results
$log_file = dirname(__DIR__) . '/logs/recurring_transactions.log';

// Ensure logs directory exists
$logs_dir = dirname($log_file);
if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

try {
    logMessage("Starting recurring transactions generation");
    
    // Get all users with active recurring transactions due today
    $users_sql = "SELECT DISTINCT user_id 
                  FROM recurring_transactions 
                  WHERE is_active = 1 
                    AND auto_generate = 1 
                    AND next_occurrence <= CURDATE()
                    AND (end_date IS NULL OR end_date >= CURDATE())";
    
    $users_result = $conn->query($users_sql);
    $processed_users = 0;
    $total_generated = 0;
    
    while ($user_row = $users_result->fetch_assoc()) {
        $user_id = $user_row['user_id'];
        
        // Get all due recurring transactions for this user
        $recurring_sql = "SELECT * FROM recurring_transactions 
                          WHERE user_id = ? 
                            AND is_active = 1 
                            AND auto_generate = 1 
                            AND next_occurrence <= CURDATE()
                            AND (end_date IS NULL OR end_date >= CURDATE())";
        
        $recurring_stmt = $conn->prepare($recurring_sql);
        $recurring_stmt->bind_param("i", $user_id);
        $recurring_stmt->execute();
        $recurring_result = $recurring_stmt->get_result();
        
        $user_generated = 0;
        
        while ($recurring = $recurring_result->fetch_assoc()) {
            try {
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
                    // Update next occurrence
                    $next_occurrence = calculateNextOccurrence($recurring['next_occurrence'], $recurring['frequency']);
                    
                    $update_sql = "UPDATE recurring_transactions 
                                   SET next_occurrence = ? 
                                   WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $next_occurrence, $recurring['id']);
                    $update_stmt->execute();
                    
                    $user_generated++;
                    $total_generated++;
                    
                    logMessage("Generated transaction for user $user_id: {$recurring['description']} - ₹{$recurring['amount']}");
                } else {
                    logMessage("Failed to create transaction for user $user_id: {$recurring['description']}");
                }
            } catch (Exception $e) {
                logMessage("Error processing recurring transaction ID {$recurring['id']}: " . $e->getMessage());
            }
        }
        
        if ($user_generated > 0) {
            $processed_users++;
            logMessage("User $user_id: Generated $user_generated transactions");
        }
    }
    
    logMessage("Completed: Processed $processed_users users, generated $total_generated transactions");
    
    // Clean up old log entries (keep only last 30 days)
    cleanupLogs();
    
} catch (Exception $e) {
    logMessage("CRITICAL ERROR: " . $e->getMessage());
    
    // Optionally send email notification to admin about the error
    // mail('admin@yoursite.com', 'Recurring Transactions Cron Error', $e->getMessage());
}

function calculateNextOccurrence($current_date, $frequency) {
    $date = new DateTime($current_date);
    
    switch ($frequency) {
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
        default:
            throw new Exception('Invalid frequency: ' . $frequency);
    }
    
    return $date->format('Y-m-d');
}

function cleanupLogs() {
    global $log_file;
    
    if (!file_exists($log_file)) {
        return;
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $cutoff_date = date('Y-m-d', strtotime('-30 days'));
    $filtered_lines = [];
    
    foreach ($lines as $line) {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
            if ($matches[1] >= $cutoff_date) {
                $filtered_lines[] = $line;
            }
        }
    }
    
    file_put_contents($log_file, implode("\n", $filtered_lines) . "\n");
}

logMessage("Script completed successfully");
?>