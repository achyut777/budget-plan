<?php
session_start();
require_once('config/database.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Budget Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Budget Planner</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="transactions.php">Transactions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="goals.php">Goals</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">About Budget Planner</h2>
                        
                        <div class="mb-4">
                            <h4>Our Mission</h4>
                            <p>Budget Planner is dedicated to helping individuals and families take control of their finances through smart budgeting, goal setting, and financial tracking. We believe that everyone deserves to have a clear understanding of their financial situation and the tools to improve it.</p>
                        </div>

                        <div class="mb-4">
                            <h4>What We Offer</h4>
                            <ul>
                                <li>Easy-to-use expense tracking</li>
                                <li>Customizable budget categories</li>
                                <li>Financial goal setting and monitoring</li>
                                <li>Visual progress tracking</li>
                                <li>Secure and private data storage</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h4>Our Commitment</h4>
                            <p>We are committed to providing a secure, user-friendly platform that helps you make informed financial decisions. Your privacy and data security are our top priorities.</p>
                        </div>

                        <div>
                            <h4>Get Started</h4>
                            <p>Ready to take control of your finances? <a href="register.php">Create an account</a> today and start your journey towards better financial management.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 