<?php
session_start();
require_once('config/database.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Budget Planner</title>
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
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Privacy Policy</h2>
                        
                        <div class="mb-4">
                            <h4>1. Information We Collect</h4>
                            <p>We collect information that you provide directly to us, including:</p>
                            <ul>
                                <li>Name and contact information</li>
                                <li>Account credentials</li>
                                <li>Financial information and transactions</li>
                                <li>Profile images and other personal data</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h4>2. How We Use Your Information</h4>
                            <p>We use the information we collect to:</p>
                            <ul>
                                <li>Provide and maintain our services</li>
                                <li>Process your transactions</li>
                                <li>Send you important updates</li>
                                <li>Improve our services</li>
                                <li>Protect against fraud and unauthorized access</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h4>3. Data Security</h4>
                            <p>We implement appropriate security measures to protect your personal information, including:</p>
                            <ul>
                                <li>Encryption of sensitive data</li>
                                <li>Secure data storage</li>
                                <li>Regular security assessments</li>
                                <li>Access controls and authentication</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h4>4. Data Sharing</h4>
                            <p>We do not sell or share your personal information with third parties except as described in this policy or with your consent.</p>
                        </div>

                        <div class="mb-4">
                            <h4>5. Your Rights</h4>
                            <p>You have the right to:</p>
                            <ul>
                                <li>Access your personal information</li>
                                <li>Correct inaccurate data</li>
                                <li>Request deletion of your data</li>
                                <li>Opt-out of marketing communications</li>
                                <li>Export your data</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h4>6. Cookies</h4>
                            <p>We use cookies and similar technologies to enhance your experience and collect usage data. You can control cookie settings through your browser preferences.</p>
                        </div>

                        <div class="mb-4">
                            <h4>7. Changes to This Policy</h4>
                            <p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new policy on this page.</p>
                        </div>

                        <div>
                            <h4>8. Contact Us</h4>
                            <p>If you have any questions about this privacy policy, please contact us at:</p>
                            <p>Email: privacy@budgetplanner.com<br>
                            Address: 123 Finance Street, Money City, MC 12345</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 