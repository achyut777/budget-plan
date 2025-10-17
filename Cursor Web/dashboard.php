<?php
/**
 * Budget Planner - Main Dashboard
 * 
 * Displays financial overview, charts, and quick actions
 * for authenticated and verified users.
 */

// Start session and include required files
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';

// Check if user is logged in and verified
check_auth(true, false);

// Ensure user session is valid
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?msg=session_expired');
    exit();
}

try {
    // Get user's financial summary
    $user_id = $_SESSION['user_id'];

    // Default to current month, but allow date range selection
    $selected_month = $_GET['month'] ?? date('Y-m');
    $selected_year = substr($selected_month, 0, 4);
    $selected_month_num = substr($selected_month, 5, 2);

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) {
        $selected_month = date('Y-m');
        $selected_year = date('Y');
        $selected_month_num = date('m');
    }

// Get total income for selected month
$income_sql = "SELECT COALESCE(SUM(amount), 0) as total_income 
               FROM transactions t 
               JOIN categories c ON t.category_id = c.id 
               WHERE t.user_id = ? AND c.type = 'income' 
               AND YEAR(t.date) = ? AND MONTH(t.date) = ?";
$income_stmt = $conn->prepare($income_sql);
$income_stmt->bind_param("iii", $user_id, $selected_year, $selected_month_num);
$income_stmt->execute();
$income_result = $income_stmt->get_result();
$total_income = $income_result->fetch_assoc()['total_income'];

// Get total expenses for selected month
$expense_sql = "SELECT COALESCE(SUM(amount), 0) as total_expenses 
                FROM transactions t 
                JOIN categories c ON t.category_id = c.id 
                WHERE t.user_id = ? AND c.type = 'expense' 
                AND YEAR(t.date) = ? AND MONTH(t.date) = ?";
$expense_stmt = $conn->prepare($expense_sql);
$expense_stmt->bind_param("iii", $user_id, $selected_year, $selected_month_num);
$expense_stmt->execute();
$expense_result = $expense_stmt->get_result();
$total_expenses = $expense_result->fetch_assoc()['total_expenses'];

// Get recent transactions
$transactions_sql = "SELECT t.*, c.name as category_name, c.type 
                    FROM transactions t 
                    JOIN categories c ON t.category_id = c.id 
                    WHERE t.user_id = ? 
                    ORDER BY t.date DESC LIMIT 5";
$transactions_stmt = $conn->prepare($transactions_sql);
$transactions_stmt->bind_param("i", $user_id);
$transactions_stmt->execute();
$recent_transactions = $transactions_stmt->get_result();

// Get savings goals
$goals_sql = "SELECT * FROM goals WHERE user_id = ? ORDER BY deadline ASC";
$goals_stmt = $conn->prepare($goals_sql);
$goals_stmt->bind_param("i", $user_id);
$goals_stmt->execute();
$savings_goals = $goals_stmt->get_result();

// Get expense distribution data for selected month
$expense_distribution_sql = "SELECT c.name, COALESCE(SUM(t.amount), 0) as total_amount 
                           FROM categories c 
                           LEFT JOIN transactions t ON c.id = t.category_id AND t.user_id = ?
                           WHERE c.user_id = ? AND c.type = 'expense' 
                           AND (t.date IS NULL OR (YEAR(t.date) = ? AND MONTH(t.date) = ?))
                           GROUP BY c.id, c.name 
                           HAVING total_amount > 0
                           ORDER BY total_amount DESC";
$expense_dist_stmt = $conn->prepare($expense_distribution_sql);
$expense_dist_stmt->bind_param("iiii", $user_id, $user_id, $selected_year, $selected_month_num);
$expense_dist_stmt->execute();
$expense_distribution = $expense_dist_stmt->get_result();

$expense_labels = [];
$expense_data = [];
while ($row = $expense_distribution->fetch_assoc()) {
    if ($row['total_amount'] > 0) {
        $expense_labels[] = $row['name'];
        $expense_data[] = $row['total_amount'];
    }
}

// Get income distribution data for selected month
$income_distribution_sql = "SELECT c.name, COALESCE(SUM(t.amount), 0) as total_amount 
                           FROM categories c 
                           LEFT JOIN transactions t ON c.id = t.category_id AND t.user_id = ?
                           WHERE c.user_id = ? AND c.type = 'income' 
                           AND (t.date IS NULL OR (YEAR(t.date) = ? AND MONTH(t.date) = ?))
                           GROUP BY c.id, c.name 
                           HAVING total_amount > 0
                           ORDER BY total_amount DESC";
$income_dist_stmt = $conn->prepare($income_distribution_sql);
$income_dist_stmt->bind_param("iiii", $user_id, $user_id, $selected_year, $selected_month_num);
$income_dist_stmt->execute();
$income_distribution = $income_dist_stmt->get_result();

$income_labels = [];
$income_data = [];
while ($row = $income_distribution->fetch_assoc()) {
    if ($row['total_amount'] > 0) {
        $income_labels[] = $row['name'];
        $income_data[] = $row['total_amount'];
    }
}

// Get monthly trend data for the last 6 months
$trend_sql = "SELECT 
    DATE_FORMAT(t.date, '%Y-%m') as month,
    c.type,
    COALESCE(SUM(t.amount), 0) as total_amount
    FROM (
        SELECT DISTINCT DATE_FORMAT(CURRENT_DATE - INTERVAL n MONTH, '%Y-%m') as month
        FROM (
            SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
        ) months
    ) months
    LEFT JOIN transactions t ON DATE_FORMAT(t.date, '%Y-%m') = months.month AND t.user_id = ?
    LEFT JOIN categories c ON t.category_id = c.id
    GROUP BY month, c.type
    ORDER BY month ASC";
$trend_stmt = $conn->prepare($trend_sql);
$trend_stmt->bind_param("i", $user_id);
$trend_stmt->execute();
$trend_result = $trend_stmt->get_result();

$months = [];
$income_trend_data = [];
$expense_trend_data = [];
$month_totals = [];

while ($row = $trend_result->fetch_assoc()) {
    $month = date('M', strtotime($row['month'] . '-01'));
    if (!in_array($month, $months)) {
        $months[] = $month;
    }
    if ($row['type'] === 'income') {
        $month_totals[$month]['income'] = $row['total_amount'];
    } else if ($row['type'] === 'expense') {
        $month_totals[$month]['expense'] = $row['total_amount'];
    }
}

foreach ($months as $month) {
    $income_trend_data[] = isset($month_totals[$month]['income']) ? $month_totals[$month]['income'] : 0;
    $expense_trend_data[] = isset($month_totals[$month]['expense']) ? $month_totals[$month]['expense'] : 0;
}

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error_message = "An error occurred while loading the dashboard. Please try again.";
    
    // Set default values to prevent errors in the UI
    $total_income = 0;
    $total_expenses = 0;
    $savings = 0;
    $savings_rate = 0;
    $recent_transactions = [];
    $goals = [];
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $income_trend_data = [0, 0, 0, 0, 0, 0];
    $expense_trend_data = [0, 0, 0, 0, 0, 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Budget Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/currency.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);
            color: var(--dark);
            min-height: 100vh;
            position: relative;
            background-image: url('https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1951&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(253, 251, 251, 0.95) 0%, rgba(235, 237, 238, 0.95) 100%);
            z-index: -1;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="15" r="1" fill="%234361ee" fill-opacity="0.05"/></svg>');
            z-index: -1;
            opacity: 0.5;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            padding: 20px;
        }

        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .welcome-section h2 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .summary-card {
            padding: 1.5rem;
            border-radius: 15px;
            color: white;
            height: 100%;
        }

        .summary-card.income {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
        }

        .summary-card.expense {
            background: linear-gradient(135deg, var(--warning) 0%, var(--danger) 100%);
        }

        .summary-card.savings {
            background: linear-gradient(135deg, var(--success) 0%, var(--info) 100%);
        }

        .summary-card h5 {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .summary-card h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .chart-card {
            padding: 1.5rem;
            margin-bottom: 2rem;
            background: white;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        .transactions-table {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #eee;
        }

        .table td {
            vertical-align: middle;
            color: #666;
            border-bottom: 1px solid #eee;
        }

        .amount-positive {
            color: var(--success);
            font-weight: 600;
        }

        .amount-negative {
            color: var(--danger);
            font-weight: 600;
        }

        .goals-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
        }

        .goal-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            margin: 0.5rem 0;
        }

        .progress-bar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            border-radius: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .date-selector .form-select {
            min-width: 150px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            font-size: 0.9rem;
        }

        .date-selector .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        .date-selector .btn {
            border-radius: 8px;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .chart-card .text-muted i {
            opacity: 0.3;
        }

        .no-data-message {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Loading animation */
        .loading-overlay {
            background: linear-gradient(45deg, 
                rgba(67, 97, 238, 0.1), 
                rgba(67, 97, 238, 0.05), 
                rgba(67, 97, 238, 0.1));
            background-size: 300% 300%;
            animation: gradientShift 2s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .welcome-section .d-flex {
            flex-wrap: wrap;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .summary-card {
                margin-bottom: 1rem;
            }
            
            .chart-container {
                height: 200px;
            }

            .welcome-section .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .date-selector {
                width: 100%;
            }
            
            .date-selector form {
                flex-wrap: wrap;
                gap: 0.5rem !important;
            }
            
            .date-selector .form-select {
                min-width: 120px;
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-wallet me-2"></i>Budget Planner
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="fas fa-exchange-alt me-1"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recurring_transactions.php">
                            <i class="fas fa-redo me-1"></i> Recurring
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="goals.php">
                            <i class="fas fa-bullseye me-1"></i> Goals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="data_management.php">
                            <i class="fas fa-database me-1"></i> Data Management
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="welcome-section">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Welcome, <?php 
                        if (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
                            echo htmlspecialchars($_SESSION['name']);
                        } else {
                            echo '[User]';
                        }
                    ?>!</h2>
                    <p class="text-muted mb-0">Here's your financial overview for <?php echo date('F Y', strtotime($selected_month . '-01')); ?></p>
                </div>
                <div class="date-selector">
                    <form method="GET" class="d-flex align-items-center gap-3">
                        <label for="monthSelect" class="form-label mb-0 text-muted">Select Month:</label>
                        <select id="monthSelect" name="month" class="form-select" style="width: auto;" onchange="this.form.submit()">
                            <?php
                            // Generate options for last 12 months
                            for ($i = 11; $i >= 0; $i--) {
                                $option_date = date('Y-m', strtotime("-$i months"));
                                $option_text = date('F Y', strtotime("-$i months"));
                                $selected = ($option_date === $selected_month) ? 'selected' : '';
                                echo "<option value='$option_date' $selected>$option_text</option>";
                            }
                            ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="resetToCurrentMonth()">
                            <i class="fas fa-calendar-day me-1"></i>Current Month
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="summary-card income">
                    <i class="fas fa-arrow-up stats-icon"></i>
                    <h5>Total Income</h5>
                    <h2>₹<?php echo number_format($total_income, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card expense">
                    <i class="fas fa-arrow-down stats-icon"></i>
                    <h5>Total Expenses</h5>
                    <h2>₹<?php echo number_format($total_expenses, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card savings">
                    <i class="fas fa-piggy-bank stats-icon"></i>
                    <h5>Net Savings</h5>
                    <h2>₹<?php echo number_format($total_income - $total_expenses, 2); ?></h2>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-plus-circle fa-3x text-success"></i>
                        </div>
                        <h6 class="card-title fw-bold mb-3">Add Transaction</h6>
                        <p class="card-text text-muted small mb-3">Quickly record your income or expenses to keep your budget up to date.</p>
                        <a href="transactions.php" class="btn btn-success btn-sm" style="border-radius: 20px; padding: 8px 20px;">
                            <i class="fas fa-plus me-1"></i>Add Now
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-bullseye fa-3x text-info"></i>
                        </div>
                        <h6 class="card-title fw-bold mb-3">Set Financial Goal</h6>
                        <p class="card-text text-muted small mb-3">Create and track your financial goals to stay motivated and organized.</p>
                        <a href="goals.php" class="btn btn-info btn-sm text-white" style="border-radius: 20px; padding: 8px 20px;">
                            <i class="fas fa-target me-1"></i>Set Goal
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="chart-card">
                    <h5 class="card-title">
                        <i class="fas fa-chart-pie me-2 text-success"></i>
                        Income Distribution
                    </h5>
                    <div class="chart-container">
                        <?php if (!empty($income_data)): ?>
                            <canvas id="incomeChart"></canvas>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-pie fa-3x mb-3 opacity-25"></i>
                                    <p>No income data for this month</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-card">
                    <h5 class="card-title">
                        <i class="fas fa-chart-pie me-2 text-danger"></i>
                        Expense Distribution
                    </h5>
                    <div class="chart-container">
                        <?php if (!empty($expense_data)): ?>
                            <canvas id="expenseChart"></canvas>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-pie fa-3x mb-3 opacity-25"></i>
                                    <p>No expense data for this month</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-card">
                    <h5 class="card-title">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Monthly Trend
                    </h5>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="transactions-table">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-history me-2"></i>
                        Recent Transactions
                    </h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo htmlspecialchars($transaction['category_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="<?php echo $transaction['type'] === 'income' ? 'amount-positive' : 'amount-negative'; ?>">
                                        <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                                        ₹<?php echo number_format($transaction['amount'], 2); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-4">
                        <a href="transactions.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>View All Transactions
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="goals-section">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-flag me-2"></i>
                        Savings Goals
                    </h5>
                    <?php while ($goal = $savings_goals->fetch_assoc()): ?>
                    <div class="goal-item">
                        <h6><?php echo htmlspecialchars($goal['name']); ?></h6>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo ($goal['current_amount'] / $goal['target_amount']) * 100; ?>%">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Progress</small>
                            <small class="text-muted">
                                ₹<?php echo number_format($goal['current_amount'], 2); ?> /
                                ₹<?php echo number_format($goal['target_amount'], 2); ?>
                            </small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <div class="text-center mt-4">
                        <a href="goals.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Manage Goals
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to reset to current month
        function resetToCurrentMonth() {
            const currentMonth = new Date().toISOString().substr(0, 7);
            window.location.href = '?month=' + currentMonth;
        }

        // Initialize charts with actual data
        
        // Income Distribution Chart
        <?php if (!empty($income_data)): ?>
        const incomeCtx = document.getElementById('incomeChart').getContext('2d');
        new Chart(incomeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($income_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($income_data); ?>,
                    backgroundColor: [
                        '#28a745',
                        '#20c997',
                        '#17a2b8',
                        '#6f42c1',
                        '#007bff',
                        '#6610f2',
                        '#e83e8c',
                        '#fd7e14'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ₹' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Expense Distribution Chart
        <?php if (!empty($expense_data)): ?>
        const expenseCtx = document.getElementById('expenseChart').getContext('2d');
        new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($expense_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($expense_data); ?>,
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#e83e8c',
                        '#6f42c1',
                        '#6610f2',
                        '#343a40',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ₹' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Monthly Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Income',
                    data: <?php echo json_encode($income_trend_data); ?>,
                    borderColor: '#28a745',
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) {
                            return null;
                        }
                        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                        gradient.addColorStop(0, 'rgba(40, 167, 69, 0)');
                        gradient.addColorStop(0.5, 'rgba(40, 167, 69, 0.1)');
                        gradient.addColorStop(1, 'rgba(40, 167, 69, 0.2)');
                        return gradient;
                    },
                    borderWidth: 3,
                    tension: 0.6,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#28a745',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 3,
                    pointHoverBackgroundColor: '#28a745',
                    pointHoverBorderColor: '#fff'
                }, {
                    label: 'Expenses',
                    data: <?php echo json_encode($expense_trend_data); ?>,
                    borderColor: '#dc3545',
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) {
                            return null;
                        }
                        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                        gradient.addColorStop(0, 'rgba(220, 53, 69, 0)');
                        gradient.addColorStop(0.5, 'rgba(220, 53, 69, 0.1)');
                        gradient.addColorStop(1, 'rgba(220, 53, 69, 0.2)');
                        return gradient;
                    },
                    borderWidth: 3,
                    tension: 0.6,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#dc3545',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 3,
                    pointHoverBackgroundColor: '#dc3545',
                    pointHoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12,
                                family: "'Poppins', sans-serif",
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#000',
                        bodyColor: '#666',
                        bodyFont: {
                            size: 13,
                            family: "'Poppins', sans-serif"
                        },
                        titleFont: {
                            size: 14,
                            family: "'Poppins', sans-serif",
                            weight: '600'
                        },
                        padding: 15,
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        displayColors: true,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatChartCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                family: "'Poppins', sans-serif"
                            },
                            padding: 10
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5],
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                family: "'Poppins', sans-serif"
                            },
                            padding: 10,
                            callback: function(value) {
                                return formatINR(value, 0);
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        right: 20,
                        bottom: 20,
                        left: 20
                    }
                }
            }
        });

        // Add loading animation for month changes
        document.getElementById('monthSelect').addEventListener('change', function() {
            // Show loading overlay
            const overlay = document.createElement('div');
            overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
            overlay.style.background = 'rgba(255, 255, 255, 0.8)';
            overlay.style.zIndex = '9999';
            overlay.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 mb-0">Loading financial data...</p>
                </div>
            `;
            document.body.appendChild(overlay);
        });

        // Automatically process due recurring transactions when dashboard loads
        document.addEventListener('DOMContentLoaded', function() {
            processRecurringTransactions();
        });

        async function processRecurringTransactions() {
            try {
                const response = await fetch('api/recurring_transactions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'generate'
                    })
                });

                const result = await response.json();
                if (result.success && result.count > 0) {
                    // Show a subtle notification if transactions were generated
                    const toast = document.createElement('div');
                    toast.className = 'alert alert-success position-fixed';
                    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
                    toast.textContent = `Generated ${result.count} recurring transactions`;
                    
                    document.body.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.remove();
                        // Reload page to show updated data
                        location.reload();
                    }, 3000);
                }
            } catch (error) {
                console.error('Error processing recurring transactions:', error);
            }
        }
    </script>
</body>
</html> 