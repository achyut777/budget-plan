<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planner - Smart Financial Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/pattern.png') repeat;
            opacity: 0.1;
        }
        .hero-image {
            transform: perspective(1000px) rotateY(-15deg);
            transition: transform 0.5s ease;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
        }
        .hero-image:hover {
            transform: perspective(1000px) rotateY(0deg);
        }
        .feature-card {
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background: white;
            transition: transform 0.3s ease;
            margin-bottom: 30px;
            text-align: center;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-card img {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .feature-card:hover img {
            transform: scale(1.1);
        }
        .stats-section {
            background: #f8f9fa;
            padding: 80px 0;
        }
        .stat-card {
            text-align: center;
            padding: 30px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #000DFF;
        }
        .cta-section {
            background: linear-gradient(135deg, #000DFF 0%, #6B73FF 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        .navbar {
            background: rgba(0,0,0,0.1) !important;
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            z-index: 1000;
            transition: background 0.3s ease;
        }
        .navbar.scrolled {
            background: #000DFF !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            transition: transform 0.3s ease;
        }
        .btn-primary:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-wallet me-2"></i>Budget Planner
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-chart-pie me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-1"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6" data-aos="fade-right">
                    <h1 class="display-4 fw-bold mb-4">Master Your Money With Smart Budgeting</h1>
                    <p style="color:rgb(255, 255, 255);">Transform your financial future with our powerful budget planning tools. Track expenses, set goals, and make informed decisions.</p>
                    <div class="d-flex gap-3">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="register.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket me-2"></i>Get Started Free
                            </a>
                            <a href="about.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-info-circle me-2"></i>Learn More
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-left">
                    <img src="assets/images/hero-finance.svg" alt="Budget Planning" class="img-fluid hero-image">
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                        <div class="stat-number">10,000+</div>
                        <h3>Active Users</h3>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <i class="fas fa-chart-line fa-3x mb-3 text-primary"></i>
                        <div class="stat-number">₹5M+</div>
                        <h3>Savings Tracked</h3>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <i class="fas fa-star fa-3x mb-3 text-primary"></i>
                        <div class="stat-number">4.8/5</div>
                        <h3>User Rating</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features-section py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">Why Choose Budget Planner?</h2>
            <div class="row">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <img src="assets/images/analytics.svg" alt="Smart Analytics" class="mb-4">
                        <h3>Smart Analytics</h3>
                        <p>Get detailed insights into your spending patterns with interactive charts and real-time analysis.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <img src="assets/images/goals.svg" alt="Goal Tracking" class="mb-4">
                        <h3>Goal Tracking</h3>
                        <p>Set and monitor your savings goals with visual progress tracking and milestone celebrations.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <img src="assets/images/security.svg" alt="Secure & Private" class="mb-4">
                        <h3>Secure & Private</h3>
                        <p>Your financial data is protected with bank-level encryption and security measures.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h2 class="mb-4">Ready to Take Control of Your Finances?</h2>
                    <p class="lead mb-4">Join thousands of users who are already managing their money smarter.</p>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Free Account
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-wallet me-2"></i>Budget Planner</h5>
                    <p>Your trusted companion for financial planning and management.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-light"><i class="fas fa-info-circle me-2"></i>About Us</a></li>
                        <li><a href="contact.php" class="text-light"><i class="fas fa-envelope me-2"></i>Contact</a></li>
                        <li><a href="privacy.php" class="text-light"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-light"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.querySelector('.navbar').classList.add('scrolled');
            } else {
                document.querySelector('.navbar').classList.remove('scrolled');
            }
        });
    </script>
</body>
</html> 