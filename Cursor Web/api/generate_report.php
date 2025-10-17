<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user name
$user_sql = "SELECT name FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_name = $user_data['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    
    try {
        switch ($type) {
            case 'monthly':
                $month = (int)$_POST['month'];
                $year = (int)$_POST['year'];
                $report_data = generateMonthlyReport($conn, $user_id, $user_name, $month, $year);
                break;
                
            case 'yearly':
                $year = (int)$_POST['year'];
                $report_data = generateYearlyReport($conn, $user_id, $user_name, $year);
                break;
                
            case 'goals':
                $report_data = generateGoalsReport($conn, $user_id, $user_name);
                break;
                
            case 'category':
                $from_date = $_POST['from_date'];
                $to_date = $_POST['to_date'];
                $report_data = generateCategoryReport($conn, $user_id, $user_name, $from_date, $to_date);
                break;
                
            default:
                throw new Exception('Invalid report type');
        }
        
        // Generate PDF
        $pdf_result = generatePDF($report_data);
        
        echo json_encode([
            'success' => true,
            'file_url' => $pdf_result['file_url'],
            'filename' => $pdf_result['filename']
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function generateMonthlyReport($conn, $user_id, $user_name, $month, $year) {
    // Get monthly income
    $income_sql = "SELECT COALESCE(SUM(t.amount), 0) as total_income 
                   FROM transactions t 
                   JOIN categories c ON t.category_id = c.id 
                   WHERE t.user_id = ? AND c.type = 'income' 
                   AND YEAR(t.date) = ? AND MONTH(t.date) = ?";
    $income_stmt = $conn->prepare($income_sql);
    $income_stmt->bind_param("iii", $user_id, $year, $month);
    $income_stmt->execute();
    $total_income = $income_stmt->get_result()->fetch_assoc()['total_income'];

    // Get monthly expenses
    $expense_sql = "SELECT COALESCE(SUM(t.amount), 0) as total_expenses 
                    FROM transactions t 
                    JOIN categories c ON t.category_id = c.id 
                    WHERE t.user_id = ? AND c.type = 'expense' 
                    AND YEAR(t.date) = ? AND MONTH(t.date) = ?";
    $expense_stmt = $conn->prepare($expense_sql);
    $expense_stmt->bind_param("iii", $user_id, $year, $month);
    $expense_stmt->execute();
    $total_expenses = $expense_stmt->get_result()->fetch_assoc()['total_expenses'];

    // Get category breakdown
    $category_sql = "SELECT c.name, c.type, COALESCE(SUM(t.amount), 0) as amount,
                            COUNT(t.id) as transaction_count
                     FROM categories c
                     LEFT JOIN transactions t ON c.id = t.category_id 
                         AND t.user_id = ? 
                         AND YEAR(t.date) = ? 
                         AND MONTH(t.date) = ?
                     WHERE c.user_id = ? OR c.user_id IS NULL
                     GROUP BY c.id, c.name, c.type
                     HAVING amount > 0
                     ORDER BY c.type, amount DESC";
    $category_stmt = $conn->prepare($category_sql);
    $category_stmt->bind_param("iiii", $user_id, $year, $month, $user_id);
    $category_stmt->execute();
    $categories = $category_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get recent transactions
    $transactions_sql = "SELECT t.date, t.description, t.amount, c.name as category_name, c.type
                         FROM transactions t
                         JOIN categories c ON t.category_id = c.id
                         WHERE t.user_id = ? AND YEAR(t.date) = ? AND MONTH(t.date) = ?
                         ORDER BY t.date DESC, t.id DESC
                         LIMIT 20";
    $transactions_stmt = $conn->prepare($transactions_sql);
    $transactions_stmt->bind_param("iii", $user_id, $year, $month);
    $transactions_stmt->execute();
    $transactions = $transactions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return [
        'type' => 'monthly',
        'user_name' => $user_name,
        'period' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'net_savings' => $total_income - $total_expenses,
        'savings_rate' => $total_income > 0 ? (($total_income - $total_expenses) / $total_income) * 100 : 0,
        'categories' => $categories,
        'transactions' => $transactions,
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

function generateYearlyReport($conn, $user_id, $user_name, $year) {
    // Get yearly totals
    $yearly_sql = "SELECT 
                      COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
                      COALESCE(SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expenses
                   FROM transactions t
                   JOIN categories c ON t.category_id = c.id
                   WHERE t.user_id = ? AND YEAR(t.date) = ?";
    $yearly_stmt = $conn->prepare($yearly_sql);
    $yearly_stmt->bind_param("ii", $user_id, $year);
    $yearly_stmt->execute();
    $yearly_totals = $yearly_stmt->get_result()->fetch_assoc();

    // Get monthly breakdown
    $monthly_sql = "SELECT 
                       MONTH(t.date) as month,
                       COALESCE(SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END), 0) as income,
                       COALESCE(SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END), 0) as expenses
                    FROM transactions t
                    JOIN categories c ON t.category_id = c.id
                    WHERE t.user_id = ? AND YEAR(t.date) = ?
                    GROUP BY MONTH(t.date)
                    ORDER BY MONTH(t.date)";
    $monthly_stmt = $conn->prepare($monthly_sql);
    $monthly_stmt->bind_param("ii", $user_id, $year);
    $monthly_stmt->execute();
    $monthly_data = $monthly_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Create full year array
    $monthly_breakdown = [];
    for ($i = 1; $i <= 12; $i++) {
        $monthly_breakdown[$i] = ['month' => $i, 'income' => 0, 'expenses' => 0];
    }
    
    foreach ($monthly_data as $month_data) {
        $monthly_breakdown[$month_data['month']] = $month_data;
    }

    return [
        'type' => 'yearly',
        'user_name' => $user_name,
        'period' => $year,
        'total_income' => $yearly_totals['total_income'],
        'total_expenses' => $yearly_totals['total_expenses'],
        'net_savings' => $yearly_totals['total_income'] - $yearly_totals['total_expenses'],
        'monthly_breakdown' => array_values($monthly_breakdown),
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

function generateGoalsReport($conn, $user_id, $user_name) {
    $goals_sql = "SELECT id, name, target_amount, current_amount, deadline, 
                         (current_amount / target_amount * 100) as progress_percentage,
                         CASE 
                           WHEN deadline < CURDATE() AND current_amount < target_amount THEN 'overdue'
                           WHEN current_amount >= target_amount THEN 'completed'
                           ELSE 'active'
                         END as status
                  FROM goals 
                  WHERE user_id = ? 
                  ORDER BY status, deadline";
    $goals_stmt = $conn->prepare($goals_sql);
    $goals_stmt->bind_param("i", $user_id);
    $goals_stmt->execute();
    $goals = $goals_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return [
        'type' => 'goals',
        'user_name' => $user_name,
        'period' => 'All Goals as of ' . date('F j, Y'),
        'goals' => $goals,
        'total_goals' => count($goals),
        'completed_goals' => count(array_filter($goals, fn($g) => $g['status'] === 'completed')),
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

function generateCategoryReport($conn, $user_id, $user_name, $from_date, $to_date) {
    $category_sql = "SELECT c.name, c.type, 
                            COALESCE(SUM(t.amount), 0) as total_amount,
                            COUNT(t.id) as transaction_count,
                            AVG(t.amount) as avg_amount
                     FROM categories c
                     LEFT JOIN transactions t ON c.id = t.category_id 
                         AND t.user_id = ? 
                         AND t.date BETWEEN ? AND ?
                     WHERE c.user_id = ? OR c.user_id IS NULL
                     GROUP BY c.id, c.name, c.type
                     HAVING total_amount > 0
                     ORDER BY c.type, total_amount DESC";
    $category_stmt = $conn->prepare($category_sql);
    $category_stmt->bind_param("issi", $user_id, $from_date, $to_date, $user_id);
    $category_stmt->execute();
    $categories = $category_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return [
        'type' => 'category',
        'user_name' => $user_name,
        'period' => date('M j, Y', strtotime($from_date)) . ' - ' . date('M j, Y', strtotime($to_date)),
        'categories' => $categories,
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

function generatePDF($data) {
    // Simple HTML to PDF conversion
    // Note: For production, consider using libraries like TCPDF, mPDF, or DomPDF
    
    $html = generateReportHTML($data);
    
    // Create reports directory if it doesn't exist
    $reports_dir = '../reports';
    if (!file_exists($reports_dir)) {
        mkdir($reports_dir, 0755, true);
    }
    
    // Generate filename
    $filename = 'financial_report_' . $data['type'] . '_' . date('Y-m-d_H-i-s') . '.html';
    $filepath = $reports_dir . '/' . $filename;
    
    // Save HTML file (in production, this would be a PDF)
    file_put_contents($filepath, $html);
    
    return [
        'file_url' => 'reports/' . $filename,
        'filename' => str_replace('.html', '.pdf', $filename)
    ];
}

function generateReportHTML($data) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Financial Report - <?php echo htmlspecialchars($data['user_name']); ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .header { text-align: center; border-bottom: 2px solid #667eea; padding-bottom: 20px; margin-bottom: 30px; }
            .summary { background: #f8f9ff; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
            .summary-item { display: inline-block; margin: 10px 20px; text-align: center; }
            .summary-value { font-size: 24px; font-weight: bold; color: #667eea; }
            .summary-label { font-size: 14px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #667eea; color: white; }
            .income { color: #28a745; }
            .expense { color: #dc3545; }
            .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Financial Report</h1>
            <h2><?php echo htmlspecialchars($data['user_name']); ?></h2>
            <h3><?php echo htmlspecialchars($data['period']); ?></h3>
            <p>Generated on <?php echo date('F j, Y \a\t g:i A', strtotime($data['generated_at'])); ?></p>
        </div>

        <?php if ($data['type'] === 'monthly' || $data['type'] === 'yearly'): ?>
        <div class="summary">
            <div class="summary-item">
                <div class="summary-value income">₹<?php echo number_format($data['total_income'], 2); ?></div>
                <div class="summary-label">Total Income</div>
            </div>
            <div class="summary-item">
                <div class="summary-value expense">₹<?php echo number_format($data['total_expenses'], 2); ?></div>
                <div class="summary-label">Total Expenses</div>
            </div>
            <div class="summary-item">
                <div class="summary-value <?php echo $data['net_savings'] >= 0 ? 'income' : 'expense'; ?>">
                    ₹<?php echo number_format($data['net_savings'], 2); ?>
                </div>
                <div class="summary-label">Net Savings</div>
            </div>
            <?php if (isset($data['savings_rate'])): ?>
            <div class="summary-item">
                <div class="summary-value"><?php echo number_format($data['savings_rate'], 1); ?>%</div>
                <div class="summary-label">Savings Rate</div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($data['type'] === 'monthly' && !empty($data['categories'])): ?>
        <h3>Category Breakdown</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Transactions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['categories'] as $category): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td class="<?php echo $category['type']; ?>"><?php echo ucfirst($category['type']); ?></td>
                    <td class="<?php echo $category['type']; ?>">₹<?php echo number_format($category['amount'], 2); ?></td>
                    <td><?php echo $category['transaction_count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if ($data['type'] === 'yearly' && !empty($data['monthly_breakdown'])): ?>
        <h3>Monthly Breakdown</h3>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Income</th>
                    <th>Expenses</th>
                    <th>Net Savings</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['monthly_breakdown'] as $month_data): ?>
                <tr>
                    <td><?php echo date('F', mktime(0, 0, 0, $month_data['month'], 1)); ?></td>
                    <td class="income">₹<?php echo number_format($month_data['income'], 2); ?></td>
                    <td class="expense">₹<?php echo number_format($month_data['expenses'], 2); ?></td>
                    <td class="<?php echo ($month_data['income'] - $month_data['expenses']) >= 0 ? 'income' : 'expense'; ?>">
                        ₹<?php echo number_format($month_data['income'] - $month_data['expenses'], 2); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if ($data['type'] === 'goals' && !empty($data['goals'])): ?>
        <div class="summary">
            <div class="summary-item">
                <div class="summary-value"><?php echo $data['total_goals']; ?></div>
                <div class="summary-label">Total Goals</div>
            </div>
            <div class="summary-item">
                <div class="summary-value income"><?php echo $data['completed_goals']; ?></div>
                <div class="summary-label">Completed</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?php echo $data['total_goals'] - $data['completed_goals']; ?></div>
                <div class="summary-label">In Progress</div>
            </div>
        </div>

        <h3>Goals Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Goal</th>
                    <th>Target</th>
                    <th>Current</th>
                    <th>Progress</th>
                    <th>Deadline</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['goals'] as $goal): ?>
                <tr>
                    <td><?php echo htmlspecialchars($goal['name']); ?></td>
                    <td>₹<?php echo number_format($goal['target_amount'], 2); ?></td>
                    <td>₹<?php echo number_format($goal['current_amount'], 2); ?></td>
                    <td><?php echo number_format($goal['progress_percentage'], 1); ?>%</td>
                    <td><?php echo date('M j, Y', strtotime($goal['deadline'])); ?></td>
                    <td><?php echo ucfirst($goal['status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="footer">
            <p>This report was generated by Budget Planner - Your Personal Finance Management System</p>
            <p>Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>