<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user_id = intval($_GET['id']);

// Get user details with statistics
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) as total_transactions,
        (SELECT SUM(amount) FROM transactions WHERE user_id = u.id AND type = 'income') as total_income,
        (SELECT SUM(amount) FROM transactions WHERE user_id = u.id AND type = 'expense') as total_expenses,
        (SELECT COUNT(*) FROM categories WHERE user_id = u.id) as total_categories
        FROM users u WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: users.php');
    exit();
}

$user = $result->fetch_assoc();

// Get user's recent transactions
$transactions_sql = "SELECT t.*, c.name as category_name 
                    FROM transactions t 
                    LEFT JOIN categories c ON t.category_id = c.id 
                    WHERE t.user_id = ? 
                    ORDER BY t.created_at DESC LIMIT 5";
$stmt = $conn->prepare($transactions_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Admin Panel</title>
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
            min-height: 100vh;
        }

        .user-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .badge-income {
            background-color: var(--success);
            color: white;
        }

        .badge-expense {
            background-color: var(--danger);
            color: white;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <div class="container py-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                <li class="breadcrumb-item active">User Details</li>
            </ol>
        </nav>

        <!-- User Header -->
        <div class="user-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="col">
                    <h2 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="mb-2 text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="mb-0 small">Member since <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                        <i class="fas fa-trash me-2"></i>Delete User
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3 class="h5">Total Transactions</h3>
                    <h2 class="mb-0"><?php echo number_format($user['total_transactions']); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <h3 class="h5">Total Income</h3>
                    <h2 class="mb-0">₹<?php echo number_format($user['total_income'] ?? 0, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <h3 class="h5">Total Expenses</h3>
                    <h2 class="mb-0">₹<?php echo number_format($user['total_expenses'] ?? 0, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3 class="h5">Categories</h3>
                    <h2 class="mb-0"><?php echo number_format($user['total_categories']); ?></h2>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="transactions.php?user_id=<?php echo $user_id; ?>" class="btn btn-primary btn-sm">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transaction = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $transaction['type']; ?>">
                                        <?php echo ucfirst($transaction['type']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        async function deleteUser(id) {
            if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('../api/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'User deleted successfully!');
                    setTimeout(() => window.location.href = 'users.php', 1500);
                } else {
                    showAlert('danger', result.message || 'Error deleting user');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while deleting the user');
            }
        }
    </script>
</body>
</html> 