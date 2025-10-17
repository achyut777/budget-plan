<?php
/**
 * Budget Planner - Installation Wizard
 * 
 * This script helps set up the Budget Planner application
 * by checking requirements and initializing the database.
 */

// Prevent running on production
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false) {
    die('Installation script can only be run on localhost for security reasons.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Step 1: Check requirements
if ($step == 1) {
    $requirements = [
        'PHP Version (7.4+)' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'MySQL Extension' => extension_loaded('mysqli'),
        'Session Support' => function_exists('session_start'),
        'JSON Support' => function_exists('json_encode'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'Logs Directory Writable' => is_writable(__DIR__ . '/logs') || mkdir(__DIR__ . '/logs', 0777, true)
    ];
}

// Step 2: Database setup
if ($step == 2 && $_POST) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'budget_planner';
    
    try {
        // Test database connection
        $test_conn = new mysqli($db_host, $db_user, $db_pass);
        if ($test_conn->connect_error) {
            throw new Exception("Connection failed: " . $test_conn->connect_error);
        }
        
        // Create database if it doesn't exist
        $test_conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $test_conn->select_db($db_name);
        
        // Update config file
        $config_content = file_get_contents(__DIR__ . '/config/database.php');
        $config_content = preg_replace(
            "/private static \$host = '.*?';/",
            "private static \$host = '$db_host';",
            $config_content
        );
        $config_content = preg_replace(
            "/private static \$username = '.*?';/",
            "private static \$username = '$db_user';",
            $config_content
        );
        $config_content = preg_replace(
            "/private static \$password = '.*?';/",
            "private static \$password = '$db_pass';",
            $config_content
        );
        $config_content = preg_replace(
            "/private static \$database = '.*?';/",
            "private static \$database = '$db_name';",
            $config_content
        );
        
        file_put_contents(__DIR__ . '/config/database.php', $config_content);
        
        $success = "Database configuration saved successfully!";
        $step = 3;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Step 3: Initialize database
if ($step == 3 && isset($_POST['init_db'])) {
    try {
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/config/init_db.php';
        
        $initializer = new DatabaseInitializer($conn);
        $initializer->initialize();
        
        $success = "Database initialized successfully! Installation complete.";
        $step = 4;
        
    } catch (Exception $e) {
        $error = "Database initialization failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planner - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .install-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
        }
        .step-indicator {
            margin-bottom: 30px;
        }
        .step {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            text-align: center;
            line-height: 40px;
            margin: 0 10px;
            font-weight: 600;
        }
        .step.active {
            background: #007bff;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .requirement {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .requirement:last-child {
            border-bottom: none;
        }
        .status-ok {
            color: #28a745;
        }
        .status-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <div class="text-center mb-4">
                <h1><i class="fas fa-chart-line text-primary"></i> Budget Planner</h1>
                <p class="text-muted">Installation Wizard</p>
            </div>
            
            <!-- Step Indicator -->
            <div class="step-indicator text-center">
                <span class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</span>
                <span class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</span>
                <span class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3</span>
                <span class="step <?= $step >= 4 ? 'active' : '' ?>">4</span>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Requirements Check -->
            <?php if ($step == 1): ?>
                <h3>Step 1: Requirements Check</h3>
                <p class="text-muted mb-4">Checking if your server meets the minimum requirements...</p>
                
                <?php foreach ($requirements as $name => $status): ?>
                    <div class="requirement">
                        <span><?= $name ?></span>
                        <span class="<?= $status ? 'status-ok' : 'status-error' ?>">
                            <i class="fas <?= $status ? 'fa-check' : 'fa-times' ?>"></i>
                            <?= $status ? 'OK' : 'Failed' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                
                <?php if (array_product($requirements)): ?>
                    <div class="text-center mt-4">
                        <a href="?step=2" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Continue to Database Setup
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-4">
                        <strong>Requirements not met!</strong> Please fix the failed requirements before continuing.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Step 2: Database Configuration -->
            <?php if ($step == 2): ?>
                <h3>Step 2: Database Configuration</h3>
                <p class="text-muted mb-4">Configure your database connection settings...</p>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Database Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Database Username</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Database Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Database Name</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="budget_planner" required>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-database"></i> Test & Save Configuration
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Step 3: Database Initialization -->
            <?php if ($step == 3): ?>
                <h3>Step 3: Database Initialization</h3>
                <p class="text-muted mb-4">Initialize the database with required tables and sample data...</p>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> What will be created:</h6>
                    <ul class="mb-0">
                        <li>All required database tables</li>
                        <li>Admin user: admin@budgetplanner.com (password: admin123)</li>
                        <li>Demo user: demo@example.com (password: demo12345)</li>
                        <li>Sample categories and transactions</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <div class="text-center">
                        <button type="submit" name="init_db" class="btn btn-primary btn-lg">
                            <i class="fas fa-rocket"></i> Initialize Database
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Step 4: Installation Complete -->
            <?php if ($step == 4): ?>
                <h3>🎉 Installation Complete!</h3>
                <p class="text-muted mb-4">Budget Planner has been successfully installed and configured.</p>
                
                <div class="alert alert-success">
                    <h6><i class="fas fa-user-shield"></i> Default Accounts Created:</h6>
                    <ul class="mb-0">
                        <li><strong>Admin:</strong> admin@budgetplanner.com / admin123</li>
                        <li><strong>Demo User:</strong> demo@example.com / demo12345</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Security Notice:</h6>
                    <ul class="mb-0">
                        <li>Change the default admin password immediately</li>
                        <li>Delete this installation file (install.php) for security</li>
                        <li>Configure email settings in config/email_config.php</li>
                    </ul>
                </div>
                
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                    <a href="README.md" class="btn btn-outline-secondary btn-lg ms-2">
                        <i class="fas fa-book"></i> View Documentation
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>