<?php
session_start();
require_once 'config/email_verification.php';

$message = '';
$message_type = '';

// Handle resend verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result = $emailVerification->resendVerification($email);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    } else {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';
    }
}

// Get email from session if user is logged in but not verified
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Required - Budget Planner</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verification-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .verification-icon {
            font-size: 4rem;
            color: var(--warning);
            margin-bottom: 2rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            padding: 0.75rem 2rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: #6c757d;
            border-color: #6c757d;
            transform: translateY(-2px);
        }

        .instructions {
            background: var(--light);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            text-align: left;
        }

        .step {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .step-number {
            background: var(--primary);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 1rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <i class="fas fa-envelope verification-icon"></i>
        <h2 class="text-warning mb-3">Email Verification Required</h2>
        <p class="mb-4">To protect your account and access all features of Budget Planner, please verify your email address.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="instructions">
            <h5 class="mb-3"><i class="fas fa-list-ol me-2"></i>Verification Steps:</h5>
            <div class="step">
                <span class="step-number">1</span>
                <span>Check your email inbox (and spam folder) for our verification email</span>
            </div>
            <div class="step">
                <span class="step-number">2</span>
                <span>Click the "Verify Email Address" button in the email</span>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <span>You'll be automatically logged in and can start using Budget Planner</span>
            </div>
        </div>
        
        <div class="mb-4">
            <h5>Didn't receive the email?</h5>
            <p class="text-muted mb-3">Enter your email address below to resend the verification email:</p>
            
            <form method="POST" class="row g-3">
                <div class="col-12">
                    <input type="email" class="form-control" name="email" 
                           placeholder="Enter your email address" 
                           value="<?php echo htmlspecialchars($user_email); ?>" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i>Resend Verification Email
                    </button>
                </div>
            </form>
        </div>
        
        <div class="d-grid gap-2">
            <a href="login.php" class="btn btn-outline-secondary">
                <i class="fas fa-sign-in-alt me-2"></i>Back to Login
            </a>
        </div>
        
        <hr class="my-4">
        
        <?php if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false): ?>
        <div class="alert alert-info">
            <i class="fas fa-tools me-2"></i>
            <strong>Development Mode:</strong> Emails are being simulated. 
            <a href="email_viewer.php" target="_blank" class="alert-link">View sent emails here</a>
        </div>
        <?php endif; ?>
        
        <div class="text-muted">
            <small>
                <i class="fas fa-shield-alt me-2"></i>
                Verification emails expire in <?php echo VERIFICATION_TOKEN_EXPIRY; ?> hours for security
            </small>
        </div>
        
        <div class="mt-3">
            <small class="text-muted">
                Need help? Contact us at 
                <a href="mailto:<?php echo FROM_EMAIL; ?>" class="text-decoration-none">
                    <?php echo FROM_EMAIL; ?>
                </a>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>