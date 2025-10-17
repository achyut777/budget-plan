<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planner Admin</title>
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
            --danger: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            position: relative;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding-top: 1rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,.85);
            padding: 0.8rem 1.5rem;
            margin: 0.2rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,.2);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1) 0%, rgba(76, 201, 240, 0.1) 100%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .table th {
            font-weight: 600;
            color: var(--primary);
            border-bottom-width: 1px;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: white;
        }

        .page-header {
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .breadcrumb {
            margin: 0;
            padding: 0;
        }

        .breadcrumb-item a {
            color: var(--primary);
            text-decoration: none;
        }

        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-footer {
            border-top: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="px-4 py-3">
            <h4 class="mb-0">Budget Planner</h4>
            <p class="text-light mb-0 opacity-75">Admin Panel</p>
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                   href="users.php">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="transactions.php">
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

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?> 