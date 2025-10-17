<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get transactions with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total transactions count
$total_sql = "SELECT COUNT(*) as total FROM transactions";
$total_result = $conn->query($total_sql);
$total_transactions = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_transactions / $limit);

// Get transactions for current page
$transactions_sql = "SELECT t.*, u.name as user_name, c.name as category_name 
                    FROM transactions t 
                    JOIN users u ON t.user_id = u.id 
                    JOIN categories c ON t.category_id = c.id 
                    ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
$transactions_stmt = $conn->prepare($transactions_sql);
$transactions_stmt->bind_param("ii", $limit, $offset);
$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();

// Get total income and expenses
$totals_sql = "SELECT 
               SUM(CASE WHEN t.type = 'income' THEN amount ELSE 0 END) as total_income,
               SUM(CASE WHEN t.type = 'expense' THEN amount ELSE 0 END) as total_expenses
               FROM transactions t";
$totals_result = $conn->query($totals_sql);
$totals = $totals_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark);
        }

        .transactions-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #eee;
            padding: 1rem;
        }

        .table td {
            vertical-align: middle;
            color: #666;
            border-bottom: 1px solid #eee;
            padding: 1rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .badge-income {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .badge-expense {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning);
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .pagination {
            margin: 1rem 0 0 0;
        }

        .page-link {
            color: var(--primary);
            border: none;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: var(--primary);
            color: white;
        }

        .page-item.active .page-link {
            background: var(--primary);
            color: white;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid #eee;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .page-header, .transactions-container {
                padding: 1rem;
            }
            
            .table-responsive {
                border-radius: 15px;
            }

            .filters {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-1"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="transactions.php">
                            <i class="fas fa-exchange-alt me-1"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags me-1"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i> Settings
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
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">Transactions Management</h1>
                <p class="text-muted mb-0">Monitor and manage all transactions</p>
            </div>
            <button class="btn btn-primary" onclick="exportTransactions()">
                <i class="fas fa-download me-2"></i>Export
            </button>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stats-title">Total Income</div>
                    <div class="stats-value">₹<?php echo number_format($totals['total_income'], 2); ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stats-title">Total Expenses</div>
                    <div class="stats-value">₹<?php echo number_format($totals['total_expenses'], 2); ?></div>
                </div>
            </div>
        </div>

        <div class="transactions-container">
            <div class="filters">
                <select class="filter-select" id="typeFilter">
                    <option value="">All Types</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
                <select class="filter-select" id="dateFilter">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                </select>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $transaction['type'] === 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                    <?php echo ucfirst($transaction['type']); ?>
                                </span>
                            </td>
                            <td>₹<?php echo number_format($transaction['amount'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="d-flex justify-content-center">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show alert function
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 3000);
        }

        // Handle transaction deletion
        async function deleteTransaction(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                try {
                    const response = await fetch('api/delete_transaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: id })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showAlert('success', 'Transaction deleted successfully!');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('danger', result.message || 'Error deleting transaction');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred while deleting the transaction');
                }
            }
        }

        // Handle transaction view
        async function viewTransaction(id) {
            try {
                const response = await fetch(`api/get_transaction.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    // Implement view transaction functionality
                    showAlert('info', 'View transaction functionality coming soon');
                } else {
                    showAlert('danger', result.message || 'Error fetching transaction data');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while fetching transaction data');
            }
        }

        // Handle export functionality
        function exportTransactions() {
            // Implement export functionality
            showAlert('info', 'Export functionality coming soon');
        }

        // Handle filters
        document.getElementById('typeFilter').addEventListener('change', function() {
            const type = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const transactionType = row.querySelector('td:nth-child(4) span').textContent.trim().toLowerCase();
                if (!type || transactionType === type) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        document.getElementById('dateFilter').addEventListener('change', function() {
            const filter = this.value;
            const today = new Date();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const dateStr = row.querySelector('td:nth-child(1)').textContent;
                const date = new Date(dateStr);
                let show = true;

                if (filter === 'today') {
                    show = date.toDateString() === today.toDateString();
                } else if (filter === 'week') {
                    const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                    show = date >= weekAgo && date <= today;
                } else if (filter === 'month') {
                    show = date.getMonth() === today.getMonth() && 
                           date.getFullYear() === today.getFullYear();
                } else if (filter === 'year') {
                    show = date.getFullYear() === today.getFullYear();
                }

                row.style.display = show ? '' : 'none';
            });
        });
    </script>
</body>
</html> 