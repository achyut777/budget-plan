<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get POST data
$date_range = $_POST['date_range'] ?? 'all';
$format = $_POST['format'] ?? 'csv';

// Build date filter
$date_condition = '';
$params = [$user_id];
$param_types = 'i';

switch ($date_range) {
    case 'month':
        $date_condition = ' AND t.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
        break;
    case '3months':
        $date_condition = ' AND t.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)';
        break;
    case '6months':
        $date_condition = ' AND t.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)';
        break;
    case 'year':
        $date_condition = ' AND t.date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
        break;
    case 'all':
    default:
        // No date filter
        break;
}

// Get transaction data
$sql = "SELECT 
    t.id,
    t.amount,
    t.description,
    t.date,
    c.name as category_name,
    c.type as category_type,
    t.created_at
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?" . $date_condition . "
    ORDER BY t.date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Get user info for file naming
$user_sql = "SELECT name, email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Generate filename
$date_suffix = '';
switch ($date_range) {
    case 'month':
        $date_suffix = '_last_month';
        break;
    case '3months':
        $date_suffix = '_last_3_months';
        break;
    case '6months':
        $date_suffix = '_last_6_months';
        break;
    case 'year':
        $date_suffix = '_last_year';
        break;
    case 'all':
        $date_suffix = '_all_time';
        break;
}

$filename = 'budget_planner_data' . $date_suffix . '_' . date('Y-m-d');

// Handle different export formats
switch ($format) {
    case 'csv':
        exportToCSV($transactions, $filename, $user);
        break;
    case 'pdf':
        exportToPDF($transactions, $filename, $user, $date_range);
        break;
    case 'excel':
        exportToExcel($transactions, $filename, $user);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid format']);
        exit();
}

function exportToCSV($transactions, $filename, $user) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['Budget Planner - Financial Data Export']);
    fputcsv($output, ['User: ' . $user['name'] . ' (' . $user['email'] . ')']);
    fputcsv($output, ['Export Date: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Column headers
    fputcsv($output, [
        'Transaction ID',
        'Date',
        'Category',
        'Type',
        'Amount',
        'Description',
        'Created At'
    ]);
    
    // Data rows
    $total_income = 0;
    $total_expenses = 0;
    
    foreach ($transactions as $transaction) {
        fputcsv($output, [
            $transaction['id'],
            $transaction['date'],
            $transaction['category_name'],
            ucfirst($transaction['category_type']),
            number_format($transaction['amount'], 2),
            $transaction['description'],
            $transaction['created_at']
        ]);
        
        if ($transaction['category_type'] == 'income') {
            $total_income += $transaction['amount'];
        } else {
            $total_expenses += $transaction['amount'];
        }
    }
    
    // Summary
    fputcsv($output, []);
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Transactions', count($transactions)]);
    fputcsv($output, ['Total Income', number_format($total_income, 2)]);
    fputcsv($output, ['Total Expenses', number_format($total_expenses, 2)]);
    fputcsv($output, ['Net Amount', number_format($total_income - $total_expenses, 2)]);
    
    fclose($output);
    exit();
}

function exportToPDF($transactions, $filename, $user, $date_range) {
    // Simple PDF generation using HTML and browser print
    header('Content-Type: text/html');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Budget Planner - Financial Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .user-info { margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; }
            .summary { background-color: #f9f9f9; padding: 15px; }
            .income { color: green; }
            .expense { color: red; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()">Print/Save as PDF</button>
            <button onclick="window.close()">Close</button>
        </div>
        
        <div class="header">
            <h1>Budget Planner - Financial Report</h1>
            <p>Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <div class="user-info">
            <strong>User:</strong> <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)<br>
            <strong>Period:</strong> <?php echo ucfirst(str_replace('_', ' ', $date_range)); ?><br>
            <strong>Total Transactions:</strong> <?php echo count($transactions); ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_income = 0;
                $total_expenses = 0;
                foreach ($transactions as $transaction): 
                    if ($transaction['category_type'] == 'income') {
                        $total_income += $transaction['amount'];
                    } else {
                        $total_expenses += $transaction['amount'];
                    }
                ?>
                <tr>
                    <td><?php echo $transaction['date']; ?></td>
                    <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                    <td class="<?php echo $transaction['category_type']; ?>">
                        <?php echo ucfirst($transaction['category_type']); ?>
                    </td>
                    <td class="<?php echo $transaction['category_type']; ?>">
                        ₹<?php echo number_format($transaction['amount'], 2); ?>
                    </td>
                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="summary">
            <h3>Summary</h3>
            <p><strong>Total Income:</strong> <span class="income">₹<?php echo number_format($total_income, 2); ?></span></p>
            <p><strong>Total Expenses:</strong> <span class="expense">₹<?php echo number_format($total_expenses, 2); ?></span></p>
            <p><strong>Net Amount:</strong> ₹<?php echo number_format($total_income - $total_expenses, 2); ?></p>
        </div>
        
        <script>
            // Auto print when page loads
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>
    <?php
    exit();
}

function exportToExcel($transactions, $filename, $user) {
    // Simple Excel-compatible format (tab-separated values)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: no-cache, must-revalidate');
    
    echo "<table border='1'>";
    echo "<tr><td colspan='7'><b>Budget Planner - Financial Data Export</b></td></tr>";
    echo "<tr><td colspan='7'>User: " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")</td></tr>";
    echo "<tr><td colspan='7'>Export Date: " . date('Y-m-d H:i:s') . "</td></tr>";
    echo "<tr><td colspan='7'></td></tr>";
    
    // Headers
    echo "<tr>";
    echo "<th>Transaction ID</th>";
    echo "<th>Date</th>";
    echo "<th>Category</th>";
    echo "<th>Type</th>";
    echo "<th>Amount</th>";
    echo "<th>Description</th>";
    echo "<th>Created At</th>";
    echo "</tr>";
    
    // Data
    $total_income = 0;
    $total_expenses = 0;
    
    foreach ($transactions as $transaction) {
        echo "<tr>";
        echo "<td>" . $transaction['id'] . "</td>";
        echo "<td>" . $transaction['date'] . "</td>";
        echo "<td>" . htmlspecialchars($transaction['category_name']) . "</td>";
        echo "<td>" . ucfirst($transaction['category_type']) . "</td>";
        echo "<td>" . number_format($transaction['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($transaction['description']) . "</td>";
        echo "<td>" . $transaction['created_at'] . "</td>";
        echo "</tr>";
        
        if ($transaction['category_type'] == 'income') {
            $total_income += $transaction['amount'];
        } else {
            $total_expenses += $transaction['amount'];
        }
    }
    
    // Summary
    echo "<tr><td colspan='7'></td></tr>";
    echo "<tr><td colspan='7'><b>SUMMARY</b></td></tr>";
    echo "<tr><td>Total Transactions</td><td colspan='6'>" . count($transactions) . "</td></tr>";
    echo "<tr><td>Total Income</td><td colspan='6'>" . number_format($total_income, 2) . "</td></tr>";
    echo "<tr><td>Total Expenses</td><td colspan='6'>" . number_format($total_expenses, 2) . "</td></tr>";
    echo "<tr><td>Net Amount</td><td colspan='6'>" . number_format($total_income - $total_expenses, 2) . "</td></tr>";
    
    echo "</table>";
    exit();
}
?>