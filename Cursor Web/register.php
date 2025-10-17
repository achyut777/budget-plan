<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'config/email_verification.php';

/**
 * Create default categories for a new user
 */
function ensure_user_has_categories($conn, $user_id) {
    $categories = [
        ['Salary', 'income'],
        ['Investments', 'income'],
        ['Freelance', 'income'],
        ['Rent', 'expense'],
        ['Food', 'expense'],
        ['Transportation', 'expense'],
        ['Utilities', 'expense'],
        ['Entertainment', 'expense'],
        ['Healthcare', 'expense'],
        ['Shopping', 'expense']
    ];
    
    foreach ($categories as $category) {
        $stmt = $conn->prepare("INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $category[0], $category[1]);
        $stmt->execute();
    }
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate inputs
        if (empty($name) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already registered. Please use a different email or login.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user (email_verified defaults to 0)
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, email_verified) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Create default categories for new user
                    ensure_user_has_categories($conn, $user_id);
                    
                    // Send verification email
                    if ($emailVerification->sendVerificationEmail($user_id, $email, $name)) {
                        $success = "Registration successful! Please check your email to verify your account before logging in.";
                        // Don't auto-login, redirect to verification pending page
                        header('refresh:3;url=verification_pending.php');
                    } else {
                        $error = "Account created but failed to send verification email. Please contact support.";
                    }
                } else {
                    $error = "Error creating account. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Budget Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 2rem 0;
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }
        
        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .app-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .app-logo .logo-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        h2 {
            font-weight: 600;
            color: var(--dark);
            text-align: center;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        h3 {
            font-weight: 500;
            color: var(--secondary);
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.2rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            height: auto;
            font-size: 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 12px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #3a5ecc;
            border-color: #3a5ecc;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            border: none;
            animation: shake 0.5s linear;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-10px); }
            40%, 80% { transform: translateX(10px); }
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .form-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--secondary);
        }
        
        .icon-input {
            padding-left: 45px;
        }
        
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary);
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
            background-color: #e0e0e0;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .password-feedback {
            font-size: 0.75rem;
            margin-top: 5px;
            text-align: right;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: var(--light);
            font-size: 0.85rem;
        }
        
        .login-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--secondary);
            text-decoration: none;
        }
        
        .login-link:hover {
            color: var(--primary);
        }
        
        .features-list {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: rgba(78, 115, 223, 0.1);
            border-radius: 10px;
        }
        
        .features-list p {
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--secondary);
        }
        
        .features-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 10px;
        }
        
        .feature-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.8rem;
        }
        
        .feature-icon i {
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="app-logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Budget Planner</h2>
                <h3>Start Your Financial Journey</h3>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <i class="fas fa-user form-icon"></i>
                    <input type="text" class="form-control icon-input" id="name" name="name" 
                           placeholder="Full Name" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <i class="fas fa-envelope form-icon"></i>
                    <input type="email" class="form-control icon-input" id="email" name="email" 
                           placeholder="Email Address" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" class="form-control icon-input" id="password" name="password" 
                           placeholder="Password" required>
                    <span class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="password-feedback" id="passwordFeedback"></div>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" class="form-control icon-input" id="confirm_password" 
                           name="confirm_password" placeholder="Confirm Password" required>
                    <div id="passwordMatch" class="password-feedback"></div>
                </div>
                
                <div class="features-list">
                    <p>Join today and get access to:</p>
                    <div class="features-icons">
                        <div class="feature-icon">
                            <i class="fas fa-wallet"></i>
                            <span>Track Expenses</span>
                        </div>
                        <div class="feature-icon">
                            <i class="fas fa-chart-pie"></i>
                            <span>Financial Insights</span>
                        </div>
                        <div class="feature-icon">
                            <i class="fas fa-bullseye"></i>
                            <span>Set Goals</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-user-plus me-2"></i> Create Account
                </button>
                
                <a href="login.php" class="login-link">
                    Already have an account? <b>Log in</b>
                </a>
            </form>
        </div>
        
        <div class="footer-text">
            <p>&copy; <?php echo date('Y'); ?> Budget Planner. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const feedback = document.getElementById('passwordFeedback');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let message = '';
            
            if (password.length >= 8) strength += 25;
            if (password.match(/[a-z]+/)) strength += 25;
            if (password.match(/[A-Z]+/)) strength += 25;
            if (password.match(/[0-9]+/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.backgroundColor = '#e74a3b';
                message = 'Weak';
            } else if (strength <= 50) {
                strengthBar.style.backgroundColor = '#f6c23e';
                message = 'Fair';
            } else if (strength <= 75) {
                strengthBar.style.backgroundColor = '#4e73df';
                message = 'Good';
            } else {
                strengthBar.style.backgroundColor = '#1cc88a';
                message = 'Strong';
            }
            
            feedback.textContent = message;
            feedback.style.color = strengthBar.style.backgroundColor;
        });
        
        // Password matching check
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                passwordMatch.textContent = 'Passwords do not match';
                passwordMatch.style.color = '#e74a3b';
            } else {
                passwordMatch.textContent = 'Passwords match';
                passwordMatch.style.color = '#1cc88a';
            }
        });
        
        // Add focus animations
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.form-icon').style.color = '#4e73df';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.form-icon').style.color = '#858796';
            });
        });
    </script>
</body>
</html> 