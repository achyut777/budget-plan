<?php
/**
 * Budget Planner - Authentication Middleware
 * 
 * Handles user authentication, session management, and access control
 * throughout the Budget Planner application.
 * 
 * @author Budget Planner Team
 * @version 1.0
 */

// Prevent direct access
if (!defined('BUDGET_PLANNER_APP')) {
    define('BUDGET_PLANNER_APP', true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/remember_tokens.php';

/**
 * Check if user is logged in and verified
 * Redirects to appropriate page if not
 * 
 * @param bool $require_verification Whether to require email verification
 * @param bool $is_admin Whether this is an admin page
 * @return void
 */
function check_auth($require_verification = true, $is_admin = false) {
    global $conn;
    
    try {
        // Check session timeout
        $session_status = check_session_timeout();
        if ($session_status === 'timeout') {
            return; // Function already handles redirect
        }
        
        // Check remember token if not logged in
        if (!isset($_SESSION['user_id'])) {
            $rememberTokens = new RememberTokens($conn);
            if (!$rememberTokens->checkRememberToken()) {
                redirect_to_login('Please log in to access this page.');
                return;
            }
        }
        
        $user_id = $_SESSION['user_id'];
        
        // For admin pages, check admin status
        if ($is_admin) {
            if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
                redirect_to_login('Admin access required.');
                return;
            }
            // Admins bypass verification requirements
            return;
        }
        
        // Check if email verification is required
        if ($require_verification && !is_user_verified()) {
            header('Location: verification_pending.php');
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        redirect_to_login('Authentication error occurred.');
    }
}

/**
 * Redirect to login page with optional message
 * 
 * @param string $message Optional message to display
 * @return void
 */
function redirect_to_login($message = '') {
    $url = 'login.php';
    if (!empty($message)) {
        $url .= '?msg=' . urlencode($message);
    }
    header("Location: {$url}");
    exit();
}

/**
 * Check if current user's email is verified
 * 
 * @return bool True if verified, false otherwise
 */
function is_user_verified() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Admins are always considered verified
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        return true;
    }
    
    try {
        $stmt = $conn->prepare("SELECT email_verified FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            return (bool)$user['email_verified'];
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error checking user verification: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify login credentials and handle session creation
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array Result array with success status and details
 */
function verify_login($email, $password) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id, name, email, password, email_verified, verified_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows !== 1) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
                'verified' => false
            ];
        }
        
        $user = $result->fetch_assoc();
        
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
                'verified' => false
            ];
        }
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = false;
        $_SESSION['last_activity'] = time();
        
        $is_verified = (bool)$user['email_verified'];
        $redirect_url = $is_verified ? 'dashboard.php' : 'verification_pending.php';
        
        return [
            'success' => true,
            'message' => 'Login successful.',
            'verified' => $is_verified,
            'redirect' => $redirect_url,
            'user' => $user
        ];
        
    } catch (Exception $e) {
        error_log("Login verification error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred during login. Please try again.',
            'verified' => false
        ];
    }
}

/**
 * Log out current user and clean up session/tokens
 * 
 * @return void
 */
function logout_user() {
    global $conn;
    
    try {
        // Clean up remember tokens if they exist
        if (isset($_COOKIE['remember_token'])) {
            require_once __DIR__ . '/remember_tokens.php';
            $rememberTokens = new RememberTokens($conn);
            $rememberTokens->removeToken($_COOKIE['remember_token']);
        }
        
        // Clear session
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
    
    header('Location: login.php?msg=logged_out');
    exit();
}

/**
 * Get current logged-in user information
 * 
 * @return array|false User data or false if not logged in
 */
function get_logged_in_user() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, name, email, email_verified, verified_at, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has specific permission
 * 
 * @param string $permission Permission to check
 * @return bool True if user has permission
 */
function has_permission($permission) {
    // For now, just check if user is admin
    // Can be extended for role-based permissions
    switch ($permission) {
        case 'admin':
            return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
        case 'verified_user':
            return is_user_verified();
        case 'user':
            return isset($_SESSION['user_id']);
        default:
            return false;
    }
}

/**
 * Generate CSRF token for forms
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>