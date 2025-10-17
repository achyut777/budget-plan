<?php
/**
 * Email Verification System for Budget Planner
 * Handles sending verification emails and managing user verification status
 */

require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_test.php';

class EmailVerification {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Generate a secure verification token
     * @return string
     */
    public function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Send verification email to user
     * @param int $user_id
     * @param string $email
     * @param string $name
     * @return bool
     */
    public function sendVerificationEmail($user_id, $email, $name) {
        try {
            // Generate verification token
            $token = $this->generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_TOKEN_EXPIRY . ' hours'));
            
            // Store token in database
            $stmt = $this->conn->prepare("UPDATE users SET verification_token = ?, verification_expires = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expires, $user_id);
            
            if (!$stmt->execute()) {
                return false;
            }
            
            // Send email
            return $this->sendEmail($email, $name, $token);
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send the actual email using PHP's mail function
     * @param string $email
     * @param string $name
     * @param string $token
     * @return bool
     */
    private function sendEmail($email, $name, $token) {
        $verification_url = APP_URL . "/verify_email.php?token=" . $token;
        
        // Email template
        $subject = "Verify Your Email - " . APP_NAME;
        $message = $this->getEmailTemplate($name, $verification_url);
        
        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
        $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
        
        // For development: use email simulator instead of actual mail
        if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false) {
            $emailSimulator = new EmailSimulator();
            $result = $emailSimulator->sendMail($email, $subject, $message, $headers);
            
            // Also show a user-friendly message
            if ($result) {
                error_log("DEVELOPMENT: Email simulated and saved to logs/emails/ directory");
            }
            return $result;
        } else {
            // For production: use actual mail function
            return mail($email, $subject, $message, $headers);
        }
    }
    
    /**
     * Get HTML email template
     * @param string $name
     * @param string $verification_url
     * @return string
     */
    private function getEmailTemplate($name, $verification_url) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
            <style>
                .email-container { max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; }
                .header { background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .button { display: inline-block; background: #4361ee; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>Welcome to " . APP_NAME . "!</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($name) . ",</h2>
                    <p>Thank you for registering with " . APP_NAME . ". To complete your registration and start managing your finances, please verify your email address.</p>
                    
                    <p style='text-align: center;'>
                        <a href='" . $verification_url . "' class='button'>Verify Email Address</a>
                    </p>
                    
                    <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #4361ee;'>" . $verification_url . "</p>
                    
                    <p><strong>Important:</strong> This verification link will expire in " . VERIFICATION_TOKEN_EXPIRY . " hours for security reasons.</p>
                    
                    <p>If you didn't create an account with us, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . APP_NAME . ". All rights reserved.</p>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Verify email token
     * @param string $token
     * @return array Result with success status and user data
     */
    public function verifyToken($token) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, verification_expires 
                FROM users 
                WHERE verification_token = ? AND email_verified = 0
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Invalid or already used verification token.'];
            }
            
            $user = $result->fetch_assoc();
            
            // Check if token has expired
            if (strtotime($user['verification_expires']) < time()) {
                return ['success' => false, 'message' => 'Verification token has expired. Please request a new one.'];
            }
            
            // Mark user as verified
            $update_stmt = $this->conn->prepare("
                UPDATE users 
                SET email_verified = 1, verified_at = CURRENT_TIMESTAMP, verification_token = NULL, verification_expires = NULL 
                WHERE id = ?
            ");
            $update_stmt->bind_param("i", $user['id']);
            
            if ($update_stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Email verified successfully! You can now log in.',
                    'user' => $user
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to verify email. Please try again.'];
            }
            
        } catch (Exception $e) {
            error_log("Token verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during verification.'];
        }
    }
    
    /**
     * Resend verification email
     * @param string $email
     * @return array
     */
    public function resendVerification($email) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, email, verification_attempts 
                FROM users 
                WHERE email = ? AND email_verified = 0
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Email not found or already verified.'];
            }
            
            $user = $result->fetch_assoc();
            
            // Check verification attempts
            if ($user['verification_attempts'] >= MAX_VERIFICATION_ATTEMPTS) {
                return ['success' => false, 'message' => 'Maximum verification attempts reached. Please contact support.'];
            }
            
            // Increment attempts
            $increment_stmt = $this->conn->prepare("UPDATE users SET verification_attempts = verification_attempts + 1 WHERE id = ?");
            $increment_stmt->bind_param("i", $user['id']);
            $increment_stmt->execute();
            
            // Send new verification email
            if ($this->sendVerificationEmail($user['id'], $user['email'], $user['name'])) {
                return ['success' => true, 'message' => 'Verification email sent successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to send verification email.'];
            }
            
        } catch (Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while resending verification.'];
        }
    }
    
    /**
     * Check if user is verified
     * @param int $user_id
     * @return bool
     */
    public function isUserVerified($user_id) {
        $stmt = $this->conn->prepare("SELECT email_verified FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return (bool)$user['email_verified'];
        }
        
        return false;
    }
    
    /**
     * Manually verify user (admin function)
     * @param int $user_id
     * @return bool
     */
    public function manualVerify($user_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET email_verified = 1, verified_at = CURRENT_TIMESTAMP, verification_token = NULL, verification_expires = NULL 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $user_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Manual verification error: " . $e->getMessage());
            return false;
        }
    }
}

// Create global instance
$emailVerification = new EmailVerification($conn);
?>