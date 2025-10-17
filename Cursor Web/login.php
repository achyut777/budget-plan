<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth_middleware.php';
require_once 'config/remember_tokens.php';

// Check remember token first
$rememberTokens = new RememberTokens($conn);
if ($rememberTokens->checkRememberToken()) {
    // User was auto-logged in via remember token
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: admin/dashboard.php');
    } else {
        if (is_user_verified()) {
            header('Location: dashboard.php');
        } else {
            header('Location: verification_pending.php');
        }
    }
    exit();
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: admin/dashboard.php');
    } else {
        // Check if user is verified before redirecting to dashboard
        if (is_user_verified()) {
            header('Location: dashboard.php');
        } else {
            header('Location: verification_pending.php');
        }
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;

        if (empty($email) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            // First check admin_users table
            $stmt = $conn->prepare("SELECT * FROM admin_users WHERE email = ?");
            $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Admin login
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['name'] = $admin['name'];
                $_SESSION['is_admin'] = true;
                
                header('Location: admin/dashboard.php');
                exit();
            } else {
                $error = "Invalid credentials.";
            }
        } else {
            // Use new verification-aware login function
            $login_result = verify_login($email, $password);
            
            if ($login_result['success']) {
                // Handle remember me if requested
                if ($remember && !isset($_SESSION['is_admin'])) {
                    $rememberTokens->createRememberToken($_SESSION['user_id']);
                }
                
                if ($login_result['verified']) {
                    header('Location: ' . $login_result['redirect']);
                    exit();
                } else {
                    // User logged in but not verified
                    header('Location: ' . $login_result['redirect']);
                    exit();
                }
            } else {
                $error = $login_result['message'];
            }
        }
    }
    } // Close the CSRF else block
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Budget Planner</title>
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
            padding: 0;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
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
            margin-bottom: 30px;
        }

        .app-logo .logo-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 10px;
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
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: var(--light);
            font-size: 0.85rem;
        }
        
        .login-links {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
        }

        .login-links a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .login-links a:hover {
            color: #3a5ecc;
            text-decoration: underline;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .register-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--secondary);
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="app-logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Budget Planner</h2>
                <h3>Welcome Back!</h3>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="form-group">
                    <i class="fas fa-envelope form-icon"></i>
                    <input type="email" class="form-control icon-input" id="email" name="email" 
                           placeholder="Email Address" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" class="form-control icon-input" id="password" name="password" 
                           placeholder="Password" required>
                    <span class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="text-primary text-decoration-none">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>

                <div class="text-center mt-4">
                    <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary text-decoration-none">Sign up</a></p>
                </div>
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