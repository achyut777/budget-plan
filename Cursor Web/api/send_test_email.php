<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

// Get user information
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !$user['email']) {
    echo json_encode(['success' => false, 'message' => 'User email not found']);
    exit;
}

// Generate test email content
$email_content = generateTestEmailContent($user['name']);

// Send test email
$success = sendTestEmail($user['email'], $user['name'], $email_content);

if ($success) {
    // Log the test email
    // Test email sent successfully
    
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully to ' . $user['email']
    ]);
} else {
    // Log the failed attempt
    // Test email failed
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send test email. Please check your email configuration.'
    ]);
}

function generateTestEmailContent($user_name) {
    $current_time = date('F j, Y g:i A');
    
    $html_content = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Budget Planner Test Email</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f8f9fa;
            }
            .email-container {
                max-width: 600px;
                margin: 20px auto;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .email-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .email-header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 300;
            }
            .email-header .icon {
                font-size: 48px;
                margin-bottom: 15px;
            }
            .email-body {
                padding: 40px 30px;
            }
            .welcome-message {
                background: #e3f2fd;
                border-left: 4px solid #2196f3;
                padding: 20px;
                margin: 20px 0;
                border-radius: 5px;
            }
            .feature-list {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 25px;
                margin: 25px 0;
            }
            .feature-item {
                display: flex;
                align-items: center;
                margin: 15px 0;
                padding: 10px;
                background: white;
                border-radius: 5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .feature-icon {
                background: #667eea;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 15px;
                font-size: 18px;
            }
            .cta-button {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 25px;
                font-weight: bold;
                margin: 20px 0;
                transition: transform 0.3s ease;
            }
            .cta-button:hover {
                transform: translateY(-2px);
            }
            .email-footer {
                background: #f8f9fa;
                padding: 30px;
                text-align: center;
                border-top: 1px solid #dee2e6;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin: 25px 0;
            }
            .stat-item {
                background: white;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                border: 2px solid #e9ecef;
            }
            .stat-number {
                font-size: 24px;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 5px;
            }
            .stat-label {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            @media (max-width: 600px) {
                .email-container {
                    margin: 10px;
                    border-radius: 0;
                }
                .email-body {
                    padding: 20px;
                }
                .stats-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='email-header'>
                <div class='icon'>🎯</div>
                <h1>Budget Planner</h1>
                <p>Test Email Delivery Successful!</p>
            </div>
            
            <div class='email-body'>
                <h2>Hello " . htmlspecialchars($user_name) . "! 👋</h2>
                
                <div class='welcome-message'>
                    <h3>✅ Email System Is Working!</h3>
                    <p>This test email confirms that your email system is properly configured and ready to send important account communications.</p>
                    <p><strong>Test sent on:</strong> {$current_time}</p>
                </div>
                
                <h3>🔔 What You'll Receive:</h3>
                <div class='feature-list'>
                    <div class='feature-item'>
                        <div class='feature-icon'>⚠️</div>
                        <div>
                            <strong>Budget Alerts</strong><br>
                            <small>Get notified when you're approaching your spending limits</small>
                        </div>
                    </div>
                    
                    <div class='feature-item'>
                        <div class='feature-icon'>🏆</div>
                        <div>
                            <strong>Goal Achievements</strong><br>
                            <small>Celebrate milestones and completed financial goals</small>
                        </div>
                    </div>
                    
                    <div class='feature-item'>
                        <div class='feature-icon'>📅</div>
                        <div>
                            <strong>Bill Reminders</strong><br>
                            <small>Never miss a payment with automated reminders</small>
                        </div>
                    </div>
                    
                    <div class='feature-item'>
                        <div class='feature-icon'>📊</div>
                        <div>
                            <strong>Monthly Reports</strong><br>
                            <small>Comprehensive financial summaries delivered monthly</small>
                        </div>
                    </div>
                </div>
                
                <div class='stats-grid'>
                    <div class='stat-item'>
                        <div class='stat-number'>🎯</div>
                        <div class='stat-label'>Smart Alerts</div>
                    </div>
                    <div class='stat-item'>
                        <div class='stat-number'>📈</div>
                        <div class='stat-label'>Progress Tracking</div>
                    </div>
                    <div class='stat-item'>
                        <div class='stat-number'>🔒</div>
                        <div class='stat-label'>Secure & Private</div>
                    </div>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='#' class='cta-button'>Access Your Dashboard</a>
                </div>
                
                <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                    <h4 style='margin-top: 0; color: #856404;'>💡 Pro Tip</h4>
                    <p style='margin-bottom: 0; color: #856404;'>
                        You can access all your financial features from your dashboard. 
                        Track expenses, manage goals, and view reports all in one place!
                    </p>
                </div>
            </div>
            
            <div class='email-footer'>
                <p style='margin: 0; color: #666;'>
                    <strong>Budget Planner</strong> - Your Personal Finance Companion<br>
                    This is a test email to verify your email settings.
                </p>
                <p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>
                    If you didn't request this test email, please contact support.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $text_content = "
    Budget Planner - Test Email
    
    Hello {$user_name}!
    
    ✅ Email System Is Working!
    
    This test email confirms that your email system is properly configured and ready to send important account communications.
    
    Test sent on: {$current_time}
    
    What You'll Receive:
    • Budget Alerts - Get notified when approaching spending limits
    • Goal Achievements - Celebrate milestones and completed goals
    • Bill Reminders - Never miss a payment with automated reminders
    • Monthly Reports - Comprehensive financial summaries
    
    You can access all your financial features from your dashboard.
    
    Best regards,
    Budget Planner Team
    ";
    
    return [
        'html' => $html_content,
        'text' => $text_content
    ];
}

function sendTestEmail($email, $name, $content) {
    try {
        // Email headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: Budget Planner <noreply@budgetplanner.com>";
        $headers[] = "Reply-To: support@budgetplanner.com";
        $headers[] = "X-Mailer: Budget Planner Email System";
        $headers[] = "X-Priority: 3";
        
        $subject = "Budget Planner - Test Email";
        
        // Use PHP's mail function (in production, use a proper email service like SendGrid, Mailgun, etc.)
        $success = mail(
            $email,
            $subject,
            $content['html'],
            implode("\r\n", $headers)
        );
        
        return $success;
        
    } catch (Exception $e) {
        error_log("Error sending test email: " . $e->getMessage());
        return false;
    }
}

?>