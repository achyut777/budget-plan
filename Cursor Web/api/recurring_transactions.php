<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle different request methods
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'upcoming') {
        // Get upcoming recurring transactions (next 30 days)
        $upcoming_sql = "SELECT rt.*, c.name as category_name, c.type as category_type
                         FROM recurring_transactions rt
                         JOIN categories c ON rt.category_id = c.id
                         WHERE rt.user_id = ? 
                           AND rt.is_active = 1 
                           AND rt.next_occurrence <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                         ORDER BY rt.next_occurrence ASC
                         LIMIT 10";
        $upcoming_stmt = $conn->prepare($upcoming_sql);
        $upcoming_stmt->bind_param("i", $user_id);
        $upcoming_stmt->execute();
        $upcoming = $upcoming_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $upcoming]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                addRecurringTransaction($conn, $user_id, $input);
                break;
                
            case 'toggle':
                toggleRecurringTransaction($conn, $user_id, $input);
                break;
                
            case 'delete':
                deleteRecurringTransaction($conn, $user_id, $input);
                break;
                
            case 'generate':
                generateScheduledTransactions($conn, $user_id);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function addRecurringTransaction($conn, $user_id, $data) {
    // Validate required fields
    $required = ['description', 'category_id', 'amount', 'frequency', 'start_date'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Calculate next occurrence based on frequency and start date
    $start_date = $data['start_date'];
    $frequency = $data['frequency'];
    $next_occurrence = calculateNextOccurrence($start_date, $frequency);
    
    $sql = "INSERT INTO recurring_transactions 
            (user_id, category_id, description, amount, frequency, start_date, end_date, next_occurrence, auto_generate) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $auto_generate = isset($data['auto_generate']) ? 1 : 0;
    $end_date = !empty($data['end_date']) ? $data['end_date'] : null;
    
    $stmt->bind_param("iisdssssi", 
        $user_id, 
        $data['category_id'], 
        $data['description'], 
        $data['amount'], 
        $frequency, 
        $start_date, 
        $end_date, 
        $next_occurrence, 
        $auto_generate
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Recurring transaction created successfully']);
    } else {
        throw new Exception('Failed to create recurring transaction');
    }
}

function toggleRecurringTransaction($conn, $user_id, $data) {
    $sql = "UPDATE recurring_transactions 
            SET is_active = ? 
            WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $is_active = $data['is_active'] ? 1 : 0;
    $stmt->bind_param("iii", $is_active, $data['id'], $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Recurring transaction updated']);
    } else {
        throw new Exception('Failed to update recurring transaction');
    }
}

function deleteRecurringTransaction($conn, $user_id, $data) {
    $sql = "DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $data['id'], $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Recurring transaction deleted']);
    } else {
        throw new Exception('Failed to delete recurring transaction');
    }
}

function generateScheduledTransactions($conn, $user_id) {
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
    
    while ($recurring = $due_transactions->fetch_assoc()) {
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
            
            $generated_count++;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Generated $generated_count transactions",
        'count' => $generated_count
    ]);
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
            throw new Exception('Invalid frequency');
    }
    
    return $date->format('Y-m-d');
}
?>