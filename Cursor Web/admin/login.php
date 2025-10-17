<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $sql = "SELECT id, password FROM admin_users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid credentials.";
            }
        } else {
            $error = "Invalid credentials.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Budget Planner</title>
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
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating label {
            color: var(--secondary);
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
        
        .footer-text a {
            color: var(--light);
            text-decoration: none;
            font-weight: 600;
        }
        
        .footer-text a:hover {
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
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <h3 class="text-center mb-4">Admin Login</h3>
            
            <form method="POST" action="">
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
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Administrative access only. Unauthorized access is prohibited.
                    </small>
                </div>

                <div class="text-center mt-2">
                    <a href="../index.php" class="text-primary">
                        <small><i class="fas fa-user me-1"></i>Switch to User Login</small>
                    </a>
                </div>
            </form>
        </div>
        
        <div class="footer-text">
            <p>&copy; <?php echo date('Y'); ?> Budget Planner. All rights reserved.</p>
        </div>
    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 