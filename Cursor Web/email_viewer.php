<?php
/**
 * Email Viewer for Development
 * View simulated emails sent during development
 */
session_start();

// Simple security check - only allow on localhost
if ($_SERVER['SERVER_NAME'] !== 'localhost' && strpos($_SERVER['SERVER_NAME'], '127.0.0.1') === false) {
    die('This page is only available in development mode.');
}

$email_dir = __DIR__ . '/logs/emails/';
$log_file = $email_dir . 'email_log.txt';

// Create directory if it doesn't exist
if (!file_exists($email_dir)) {
    mkdir($email_dir, 0777, true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Viewer - Development</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .email-preview {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 10px 0;
            overflow: hidden;
        }
        .email-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        .email-content {
            height: 400px;
            overflow-y: auto;
        }
        .dev-notice {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="dev-notice">
            <h4><i class="fas fa-tools"></i> Development Email Viewer</h4>
            <p class="mb-0">This page shows simulated emails sent during development. In production, these would be actual emails.</p>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Email Log</h2>
            <div>
                <a href="." class="btn btn-primary">Back to App</a>
                <button onclick="location.reload()" class="btn btn-secondary">Refresh</button>
            </div>
        </div>

        <?php
        // Get all email files
        $email_files = glob($email_dir . 'email_*.html');
        
        if (empty($email_files)): ?>
            <div class="alert alert-info">
                <h5>No emails sent yet</h5>
                <p>Register a new user to see verification emails appear here.</p>
            </div>
        <?php else:
            // Sort by modification time (newest first)
            usort($email_files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            foreach ($email_files as $file):
                $filename = basename($file);
                $file_time = date('Y-m-d H:i:s', filemtime($file));
                
                // Extract email address from filename
                preg_match('/email_.*?_(.+?)\.html/', $filename, $matches);
                $email_to = $matches[1] ?? 'Unknown';
        ?>
            <div class="email-preview">
                <div class="email-header">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>To:</strong> <?php echo htmlspecialchars($email_to); ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Sent:</strong> <?php echo $file_time; ?>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="logs/emails/<?php echo $filename; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            Open in New Tab
                        </a>
                    </div>
                </div>
                <div class="email-content">
                    <iframe src="logs/emails/<?php echo $filename; ?>" width="100%" height="100%" frameborder="0"></iframe>
                </div>
            </div>
        <?php endforeach; endif; ?>
        
        <?php if (file_exists($log_file)): ?>
        <div class="mt-4">
            <h4>Email Log Summary</h4>
            <div class="bg-light p-3 rounded">
                <pre class="mb-0" style="max-height: 200px; overflow-y: auto;"><?php echo htmlspecialchars(file_get_contents($log_file)); ?></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>