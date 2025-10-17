<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth_middleware.php';

// Check if user is logged in as admin
check_auth(false, true);

// Helper function to get date range conditions
function getDateCondition($date_range, $from_date = null, $to_date = null, $table_alias = '') {
    $column = $table_alias ? $table_alias . '.created_at' : 'created_at';
    
    if ($date_range === 'custom' && $from_date && $to_date) {
        return "AND DATE($column) BETWEEN '$from_date' AND '$to_date'";
    } elseif ($date_range === 'all') {
        return "";
    } else {
        $days = intval($date_range);
        return "AND $column >= DATE_SUB(NOW(), INTERVAL $days DAY)";
    }
}

// Helper function to format date range for display
function getDateRangeDisplay($date_range, $from_date = null, $to_date = null) {
    if ($date_range === 'custom' && $from_date && $to_date) {
        return date('M d, Y', strtotime($from_date)) . ' to ' . date('M d, Y', strtotime($to_date));
    } elseif ($date_range === 'all') {
        return "All Time";
    } else {
        $days = intval($date_range);
        return "Last $days days";
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

// Get form data
$report_type = $_POST['report_type'] ?? '';
$date_range = $_POST['date_range'] ?? '';
$from_date = $_POST['from_date'] ?? null;
$to_date = $_POST['to_date'] ?? null;
$format = $_POST['format'] ?? 'csv';
$include_summary = isset($_POST['include_summary']);
$include_charts = isset($_POST['include_charts']);

// Validate inputs
if (empty($report_type) || empty($date_range) || empty($format)) {
    header('Location: dashboard.php?error=invalid_parameters');
    exit();
}

// Get date condition
$date_condition = getDateCondition($date_range, $from_date, $to_date);
$date_display = getDateRangeDisplay($date_range, $from_date, $to_date);

// Generate report based on type
switch ($report_type) {
    case 'users':
        generateUsersReport($conn, $date_condition, $date_display, $format, $include_summary);
        break;
    case 'transactions':
        generateTransactionsReport($conn, $date_condition, $date_display, $format, $include_summary);
        break;
    case 'financial_summary':
        generateFinancialSummaryReport($conn, $date_condition, $date_display, $format, $include_summary);
        break;
    case 'user_activity':
        generateUserActivityReport($conn, $date_condition, $date_display, $format, $include_summary);
        break;
    default:
        header('Location: dashboard.php?error=invalid_report_type');
        exit();
}

/**
 * Generate Users Report
 */
function generateUsersReport($conn, $date_condition, $date_display, $format, $include_summary) {
    // For users report, we need to modify the date condition to use the appropriate table aliases
    $user_date_condition = str_replace('created_at', 'u.created_at', $date_condition);
    $transaction_date_condition = str_replace('created_at', 't.created_at', $date_condition);
    
    $sql = "SELECT 
                u.id, u.name, u.email, u.email_verified, u.created_at,
                COUNT(t.id) as transaction_count,
                COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expenses
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id " . ($transaction_date_condition ? substr($transaction_date_condition, 4) : '') . "
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE u.created_at >= '2020-01-01' $user_date_condition
            GROUP BY u.id, u.name, u.email, u.email_verified, u.created_at
            ORDER BY u.created_at DESC";
    
    $result = $conn->query($sql);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'ID' => $row['id'],
            'Name' => $row['name'],
            'Email' => $row['email'],
            'Email Verified' => $row['email_verified'] ? 'Yes' : 'No',
            'Joined Date' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
            'Transaction Count' => $row['transaction_count'],
            'Total Income' => number_format($row['total_income'], 2),
            'Total Expenses' => number_format($row['total_expenses'], 2),
            'Net Amount' => number_format($row['total_income'] - $row['total_expenses'], 2)
        ];
    }
    
    $summary = null;
    if ($include_summary) {
        $summary = [
            'Total Users' => count($data),
            'Verified Users' => count(array_filter($data, function($user) { return $user['Email Verified'] === 'Yes'; })),
            'Total Transactions' => array_sum(array_column($data, 'Transaction Count')),
            'Total Income' => number_format(array_sum(array_map(function($user) { 
                return floatval(str_replace(',', '', $user['Total Income'])); 
            }, $data)), 2),
            'Total Expenses' => number_format(array_sum(array_map(function($user) { 
                return floatval(str_replace(',', '', $user['Total Expenses'])); 
            }, $data)), 2)
        ];
    }
    
    exportData($data, 'Users Report', $date_display, $format, $summary);
}

/**
 * Generate Transactions Report
 */
function generateTransactionsReport($conn, $date_condition, $date_display, $format, $include_summary) {
    // For transactions report, use t.created_at
    $transaction_date_condition = str_replace('created_at', 't.created_at', $date_condition);
    
    $sql = "SELECT 
                t.id, t.amount, t.description, t.created_at,
                u.name as user_name, u.email as user_email,
                c.name as category_name, c.type as transaction_type
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            JOIN categories c ON t.category_id = c.id
            WHERE 1=1 $transaction_date_condition
            ORDER BY t.created_at DESC";
    
    $result = $conn->query($sql);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'Transaction ID' => $row['id'],
            'User Name' => $row['user_name'],
            'User Email' => $row['user_email'],
            'Category' => $row['category_name'],
            'Type' => ucfirst($row['transaction_type']),
            'Amount' => number_format($row['amount'], 2),
            'Description' => $row['description'] ?: 'N/A',
            'Date' => date('Y-m-d H:i:s', strtotime($row['created_at']))
        ];
    }
    
    $summary = null;
    if ($include_summary) {
        $income_transactions = array_filter($data, function($t) { return $t['Type'] === 'Income'; });
        $expense_transactions = array_filter($data, function($t) { return $t['Type'] === 'Expense'; });
        
        $summary = [
            'Total Transactions' => count($data),
            'Income Transactions' => count($income_transactions),
            'Expense Transactions' => count($expense_transactions),
            'Total Income' => number_format(array_sum(array_map(function($t) { 
                return $t['Type'] === 'Income' ? floatval(str_replace(',', '', $t['Amount'])) : 0; 
            }, $data)), 2),
            'Total Expenses' => number_format(array_sum(array_map(function($t) { 
                return $t['Type'] === 'Expense' ? floatval(str_replace(',', '', $t['Amount'])) : 0; 
            }, $data)), 2)
        ];
    }
    
    exportData($data, 'Transactions Report', $date_display, $format, $summary);
}

/**
 * Generate Financial Summary Report
 */
function generateFinancialSummaryReport($conn, $date_condition, $date_display, $format, $include_summary) {
    // For financial summary, use t.created_at
    $transaction_date_condition = str_replace('created_at', 't.created_at', $date_condition);
    
    // Monthly summary
    $sql = "SELECT 
                DATE_FORMAT(t.created_at, '%Y-%m') as month,
                COUNT(t.id) as transaction_count,
                SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as income,
                SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as expenses,
                COUNT(DISTINCT t.user_id) as active_users
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE 1=1 $transaction_date_condition
            GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
            ORDER BY month DESC";
    
    $result = $conn->query($sql);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $net_amount = $row['income'] - $row['expenses'];
        $data[] = [
            'Month' => $row['month'],
            'Transaction Count' => $row['transaction_count'],
            'Income' => number_format($row['income'], 2),
            'Expenses' => number_format($row['expenses'], 2),
            'Net Amount' => number_format($net_amount, 2),
            'Active Users' => $row['active_users']
        ];
    }
    
    $summary = null;
    if ($include_summary) {
        $summary = [
            'Total Months' => count($data),
            'Total Transactions' => array_sum(array_column($data, 'Transaction Count')),
            'Total Income' => number_format(array_sum(array_map(function($month) { 
                return floatval(str_replace(',', '', $month['Income'])); 
            }, $data)), 2),
            'Total Expenses' => number_format(array_sum(array_map(function($month) { 
                return floatval(str_replace(',', '', $month['Expenses'])); 
            }, $data)), 2),
            'Average Monthly Income' => count($data) > 0 ? number_format(array_sum(array_map(function($month) { 
                return floatval(str_replace(',', '', $month['Income'])); 
            }, $data)) / count($data), 2) : '0.00'
        ];
    }
    
    exportData($data, 'Financial Summary Report', $date_display, $format, $summary);
}

/**
 * Generate User Activity Report
 */
function generateUserActivityReport($conn, $date_condition, $date_display, $format, $include_summary) {
    // For user activity, use t.created_at for transaction filtering
    $transaction_date_condition = str_replace('created_at', 't.created_at', $date_condition);
    
    $sql = "SELECT 
                u.id, u.name, u.email,
                COUNT(t.id) as transaction_count,
                MAX(t.created_at) as last_activity,
                SUM(CASE WHEN c.type = 'income' THEN 1 ELSE 0 END) as income_count,
                SUM(CASE WHEN c.type = 'expense' THEN 1 ELSE 0 END) as expense_count,
                AVG(CASE WHEN c.type = 'income' THEN t.amount END) as avg_income,
                AVG(CASE WHEN c.type = 'expense' THEN t.amount END) as avg_expense
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id " . ($transaction_date_condition ? substr($transaction_date_condition, 4) : '') . "
            LEFT JOIN categories c ON t.category_id = c.id
            GROUP BY u.id, u.name, u.email
            ORDER BY transaction_count DESC, last_activity DESC";
    
    $result = $conn->query($sql);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'User ID' => $row['id'],
            'Name' => $row['name'],
            'Email' => $row['email'],
            'Total Transactions' => $row['transaction_count'] ?: 0,
            'Income Transactions' => $row['income_count'] ?: 0,
            'Expense Transactions' => $row['expense_count'] ?: 0,
            'Average Income' => $row['avg_income'] ? number_format($row['avg_income'], 2) : '0.00',
            'Average Expense' => $row['avg_expense'] ? number_format($row['avg_expense'], 2) : '0.00',
            'Last Activity' => $row['last_activity'] ? date('Y-m-d H:i:s', strtotime($row['last_activity'])) : 'Never'
        ];
    }
    
    $summary = null;
    if ($include_summary) {
        $active_users = array_filter($data, function($user) { return $user['Total Transactions'] > 0; });
        $summary = [
            'Total Users' => count($data),
            'Active Users' => count($active_users),
            'Inactive Users' => count($data) - count($active_users),
            'Total Transactions' => array_sum(array_column($data, 'Total Transactions')),
            'Most Active User' => count($data) > 0 ? $data[0]['Name'] . ' (' . $data[0]['Total Transactions'] . ' transactions)' : 'N/A'
        ];
    }
    
    exportData($data, 'User Activity Report', $date_display, $format, $summary);
}

/**
 * Export data in specified format
 */
function exportData($data, $report_title, $date_range, $format, $summary = null) {
    $filename = sanitizeFilename($report_title . '_' . $date_range . '_' . date('Y-m-d_H-i-s'));
    
    switch ($format) {
        case 'csv':
            exportCSV($data, $filename, $report_title, $date_range, $summary);
            break;
        case 'excel':
            exportExcel($data, $filename, $report_title, $date_range, $summary);
            break;
        case 'pdf':
            exportPDF($data, $filename, $report_title, $date_range, $summary);
            break;
        case 'json':
            exportJSON($data, $filename, $report_title, $date_range, $summary);
            break;
        default:
            exportCSV($data, $filename, $report_title, $date_range, $summary);
    }
}

/**
 * Export as CSV
 */
function exportCSV($data, $filename, $report_title, $date_range, $summary) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add report header
    fputcsv($output, [$report_title]);
    fputcsv($output, ['Date Range: ' . $date_range]);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty line
    
    // Add summary if provided
    if ($summary) {
        fputcsv($output, ['=== SUMMARY ===']);
        foreach ($summary as $key => $value) {
            fputcsv($output, [$key, $value]);
        }
        fputcsv($output, []); // Empty line
    }
    
    // Add data
    if (!empty($data)) {
        fputcsv($output, ['=== DATA ===']);
        fputcsv($output, array_keys($data[0])); // Headers
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}

/**
 * Export as Excel (simplified - using CSV with Excel headers)
 */
function exportExcel($data, $filename, $report_title, $date_range, $summary) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<h2>' . htmlspecialchars($report_title) . '</h2>';
    echo '<p><strong>Date Range:</strong> ' . htmlspecialchars($date_range) . '</p>';
    echo '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    
    if ($summary) {
        echo '<h3>Summary</h3>';
        echo '<table border="1">';
        foreach ($summary as $key => $value) {
            echo '<tr><td><strong>' . htmlspecialchars($key) . '</strong></td><td>' . htmlspecialchars($value) . '</td></tr>';
        }
        echo '</table><br>';
    }
    
    if (!empty($data)) {
        echo '<h3>Data</h3>';
        echo '<table border="1">';
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '</body></html>';
    exit();
}

/**
 * Export as PDF (simplified HTML to PDF)
 */
function exportPDF($data, $filename, $report_title, $date_range, $summary) {
    // For now, we'll output HTML that can be printed as PDF
    // In production, you might want to use a library like TCPDF or DOMPDF
    
    header('Content-Type: text/html; charset=UTF-8');
    
    echo '<!DOCTYPE html>';
    echo '<html><head>';
    echo '<meta charset="UTF-8">';
    echo '<title>' . htmlspecialchars($report_title) . '</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'table { width: 100%; border-collapse: collapse; margin: 10px 0; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f5f5f5; }';
    echo 'h1, h2, h3 { color: #333; }';
    echo '.summary-table { width: 50%; }';
    echo '@media print { body { margin: 0; } }';
    echo '</style>';
    echo '</head><body>';
    
    echo '<h1>' . htmlspecialchars($report_title) . '</h1>';
    echo '<p><strong>Date Range:</strong> ' . htmlspecialchars($date_range) . '</p>';
    echo '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    
    if ($summary) {
        echo '<h2>Summary</h2>';
        echo '<table class="summary-table">';
        foreach ($summary as $key => $value) {
            echo '<tr><td><strong>' . htmlspecialchars($key) . '</strong></td><td>' . htmlspecialchars($value) . '</td></tr>';
        }
        echo '</table>';
    }
    
    if (!empty($data)) {
        echo '<h2>Data</h2>';
        echo '<table>';
        echo '<thead><tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr></thead><tbody>';
        
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    echo '<script>window.print();</script>';
    echo '</body></html>';
    exit();
}

/**
 * Export as JSON
 */
function exportJSON($data, $filename, $report_title, $date_range, $summary) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    $export_data = [
        'report_title' => $report_title,
        'date_range' => $date_range,
        'generated_at' => date('Y-m-d H:i:s'),
        'summary' => $summary,
        'data' => $data,
        'total_records' => count($data)
    ];
    
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Sanitize filename for download
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}
?>