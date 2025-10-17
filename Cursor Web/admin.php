<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch admin statistics
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'transactions' => $conn->query("SELECT COUNT(*) as count FROM transactions")->fetch_assoc()['count'],
    'goals' => $conn->query("SELECT COUNT(*) as count FROM goals")->fetch_assoc()['count'],
    'categories' => $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count']
];

// Fetch recent users
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Fetch recent transactions
$recent_transactions = $conn->query("
    SELECT t.*, u.name as user_name, c.name as category_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    JOIN categories c ON t.category_id = c.id 
    ORDER BY t.date DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Budget Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #7209b7;
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
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
            transform: translateY(-1px);
        }

        .nav-link i {
            margin-right: 0.5rem;
        }

        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 1.5rem;
        }

        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        .table th {
            background: var(--primary);
            color: white;
            font-weight: 500;
            border: none;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-admin {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .admin-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .admin-subtitle {
            opacity: 0.9;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .status-inactive {
            background: rgba(247, 37, 133, 0.1);
            color: var(--warning);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Admin Header -->
        <div class="admin-header">
            <h1 class="admin-title">Admin Dashboard</h1>
            <p class="admin-subtitle">Welcome back, Administrator! Here's what's happening with your application.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['transactions']; ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['goals']; ?></div>
                    <div class="stat-label">Total Goals</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['categories']; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="admin-card">
            <h4 class="mb-4">Recent Users</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $recent_users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-2">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <span class="status-badge status-active">Active</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-admin">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="admin-card">
            <h4 class="mb-4">Recent Transactions</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($transaction = $recent_transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                            <td class="<?php echo $transaction['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                                $<?php echo number_format($transaction['amount'], 2); ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-admin">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 