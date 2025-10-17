<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to export data']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    $export_type = $input['type'] ?? '';
    $export_format = $input['format'] ?? 'csv';
    
    $data = [];
    $filename = '';
    
    switch ($export_type) {
        case 'transactions':
            $data = exportTransactions($conn, $user_id, $input);
            $filename = 'transactions_' . date('Y-m-d');
            break;
        case 'goals':
            $data = exportGoals($conn, $user_id);
            $filename = 'goals_' . date('Y-m-d');
            break;
        case 'complete_backup':
            $data = exportCompleteBackup($conn, $user_id);
            $filename = 'complete_backup_' . date('Y-m-d');
            $export_format = 'json'; // Force JSON for complete backup
            break;
        case 'custom':
            $data = exportCustomData($conn, $user_id, $input);
            $filename = 'custom_export_' . date('Y-m-d');
            break;
        default:
            throw new Exception('Invalid export type specified');
    }
    
    // Generate file based on format
    switch ($export_format) {
        case 'csv':
            generateCSVDownload($data, $filename);
            break;
        case 'excel':
            generateExcelDownload($data, $filename);
            break;
        case 'json':
            generateJSONDownload($data, $filename);
            break;
        default:
            throw new Exception('Unsupported export format');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function exportTransactions($conn, $user_id, $options = []) {
    $where_clause = "WHERE t.user_id = ?";
    $params = [$user_id];
    $param_types = "i";
    
    // Add date filters if specified
    if (!empty($options['from_date'])) {
        $where_clause .= " AND t.date >= ?";
        $params[] = $options['from_date'];
        $param_types .= "s";
    }
    
    if (!empty($options['to_date'])) {
        $where_clause .= " AND t.date <= ?";
        $params[] = $options['to_date'];
        $param_types .= "s";
    }
    
    $sql = "
        SELECT 
            t.date,
            t.description,
            t.amount,
            c.name as category,
            c.type as transaction_type,
            t.created_at
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        $where_clause
        ORDER BY t.date DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        // Format amount for display
        $row['amount'] = number_format($row['amount'], 2);
        $transactions[] = $row;
    }
    
    return [
        'headers' => ['Date', 'Description', 'Amount (₹)', 'Category', 'Type', 'Created'],
        'data' => $transactions
    ];
}

function exportGoals($conn, $user_id) {
    $sql = "
        SELECT 
            name,
            target_amount,
            current_amount,
            (current_amount / target_amount * 100) as progress_percentage,
            deadline,
            status,
            description,
            created_at
        FROM goals
        WHERE user_id = ?
        ORDER BY deadline ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $goals = [];
    while ($row = $result->fetch_assoc()) {
        $row['target_amount'] = number_format($row['target_amount'], 2);
        $row['current_amount'] = number_format($row['current_amount'], 2);
        $row['progress_percentage'] = number_format($row['progress_percentage'], 1) . '%';
        $goals[] = $row;
    }
    
    return [
        'headers' => ['Goal Name', 'Target Amount (₹)', 'Current Amount (₹)', 'Progress', 'Deadline', 'Status', 'Description', 'Created'],
        'data' => $goals
    ];
}

function exportCompleteBackup($conn, $user_id) {
    $backup_data = [
        'version' => '1.0',
        'created_at' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'data' => []
    ];
    
    // Export categories
    $stmt = $conn->prepare("SELECT name, type, description FROM categories WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $backup_data['data']['categories'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Export transactions
    $stmt = $conn->prepare("
        SELECT 
            t.amount,
            t.description,
            t.date,
            t.type,
            t.created_at,
            c.name as category_name,
            c.type as category_type
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $backup_data['data']['transactions'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Export goals
    $stmt = $conn->prepare("
        SELECT name, target_amount, current_amount, deadline, status, description, created_at
        FROM goals WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $backup_data['data']['goals'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Export recurring transactions if table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'recurring_transactions'");
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("
            SELECT 
                rt.*,
                c.name as category_name,
                c.type as category_type
            FROM recurring_transactions rt
            JOIN categories c ON rt.category_id = c.id
            WHERE rt.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $backup_data['data']['recurring_transactions'] = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    return $backup_data;
}

function exportCustomData($conn, $user_id, $options) {
    $export_data = [
        'headers' => [],
        'data' => []
    ];
    
    $all_data = [];
    
    // Include transactions
    if ($options['include_transactions'] ?? false) {
        $transactions = exportTransactions($conn, $user_id, $options);
        $all_data['Transactions'] = $transactions;
    }
    
    // Include goals
    if ($options['include_goals'] ?? false) {
        $goals = exportGoals($conn, $user_id);
        $all_data['Goals'] = $goals;
    }
    
    // Include categories
    if ($options['include_categories'] ?? false) {
        $categories = exportCategories($conn, $user_id);
        $all_data['Categories'] = $categories;
    }
    
    // Include recurring transactions
    if ($options['include_recurring'] ?? false) {
        $recurring = exportRecurringTransactions($conn, $user_id);
        if (!empty($recurring['data'])) {
            $all_data['Recurring Transactions'] = $recurring;
        }
    }
    
    return $all_data;
}

function exportCategories($conn, $user_id) {
    $sql = "
        SELECT 
            name,
            type,
            description,
            (SELECT COUNT(*) FROM transactions WHERE category_id = c.id) as transaction_count,
            (SELECT SUM(amount) FROM transactions WHERE category_id = c.id) as total_amount
        FROM categories c
        WHERE user_id = ?
        ORDER BY name
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_amount'] = number_format($row['total_amount'] ?? 0, 2);
        $categories[] = $row;
    }
    
    return [
        'headers' => ['Category Name', 'Type', 'Description', 'Transaction Count', 'Total Amount (₹)'],
        'data' => $categories
    ];
}

function exportRecurringTransactions($conn, $user_id) {
    // Check if table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'recurring_transactions'");
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        return ['headers' => [], 'data' => []];
    }
    
    $sql = "
        SELECT 
            rt.description,
            rt.amount,
            c.name as category,
            rt.frequency,
            rt.next_occurrence,
            rt.status,
            rt.created_at
        FROM recurring_transactions rt
        JOIN categories c ON rt.category_id = c.id
        WHERE rt.user_id = ?
        ORDER BY rt.next_occurrence ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recurring = [];
    while ($row = $result->fetch_assoc()) {
        $row['amount'] = number_format($row['amount'], 2);
        $recurring[] = $row;
    }
    
    return [
        'headers' => ['Description', 'Amount (₹)', 'Category', 'Frequency', 'Next Occurrence', 'Status', 'Created'],
        'data' => $recurring
    ];
}

function generateCSVDownload($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    if (isset($data['headers']) && isset($data['data'])) {
        // Single dataset
        fputcsv($output, $data['headers']);
        foreach ($data['data'] as $row) {
            fputcsv($output, array_values($row));
        }
    } else {
        // Multiple datasets
        foreach ($data as $sheet_name => $sheet_data) {
            fputcsv($output, [$sheet_name]); // Sheet name as header
            fputcsv($output, []); // Empty row
            
            if (!empty($sheet_data['headers'])) {
                fputcsv($output, $sheet_data['headers']);
            }
            
            foreach ($sheet_data['data'] as $row) {
                fputcsv($output, array_values($row));
            }
            
            fputcsv($output, []); // Empty row between sheets
            fputcsv($output, []); // Extra empty row
        }
    }
    
    fclose($output);
    exit;
}

function generateExcelDownload($data, $filename) {
    // For Excel generation, you would typically use PhpSpreadsheet
    // For now, we'll generate CSV with Excel-compatible format
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';
    
    if (isset($data['headers']) && isset($data['data'])) {
        // Single dataset
        echo '<table border="1">';
        echo '<tr>';
        foreach ($data['headers'] as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        foreach ($data['data'] as $row) {
            echo '<tr>';
            foreach (array_values($row) as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    } else {
        // Multiple datasets
        foreach ($data as $sheet_name => $sheet_data) {
            echo '<h2>' . htmlspecialchars($sheet_name) . '</h2>';
            echo '<table border="1">';
            
            if (!empty($sheet_data['headers'])) {
                echo '<tr>';
                foreach ($sheet_data['headers'] as $header) {
                    echo '<th>' . htmlspecialchars($header) . '</th>';
                }
                echo '</tr>';
            }
            
            foreach ($sheet_data['data'] as $row) {
                echo '<tr>';
                foreach (array_values($row) as $cell) {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
                echo '</tr>';
            }
            echo '</table><br><br>';
        }
    }
    
    echo '</body></html>';
    exit;
}

function generateJSONDownload($data, $filename) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

?>