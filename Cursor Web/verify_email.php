<?php
session_start();
require_once 'config/email_verification.php';

$message = '';
$success = false;

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $result = $emailVerification->verifyToken($token);
    
    $success = $result['success'];
    $message = $result['message'];
    
    if ($success) {
        // Auto-login the user after verification
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['name'] = $result['user']['name'];
        $_SESSION['email'] = $result['user']['email'];
    }
} else {
    $message = 'Invalid verification link.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Budget Planner</title>
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
        }

        .verification-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .verification-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
        }

        .success-icon {
            color: var(--success);
        }

        .error-icon {
            color: var(--danger);
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

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.75rem 2rem;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <?php if ($success): ?>
            <i class="fas fa-check-circle verification-icon success-icon"></i>
            <h2 class="text-success mb-3">Email Verified!</h2>
            <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
            <div class="d-grid gap-3">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                </a>
                <a href="profile.php" class="btn btn-outline-primary">
                    <i class="fas fa-user me-2"></i>View Profile
                </a>
            </div>
        <?php else: ?>
            <i class="fas fa-times-circle verification-icon error-icon"></i>
            <h2 class="text-danger mb-3">Verification Failed</h2>
            <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
            <div class="d-grid gap-3">
                <a href="verification_pending.php" class="btn btn-primary">
                    <i class="fas fa-envelope me-2"></i>Resend Verification Email
                </a>
                <a href="login.php" class="btn btn-outline-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Back to Login
                </a>
            </div>
        <?php endif; ?>
        
        <hr class="my-4">
        <div class="text-muted">
            <small>
                <i class="fas fa-shield-alt me-2"></i>
                Your account security is important to us
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>