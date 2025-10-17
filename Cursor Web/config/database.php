<?php
/**
 * Budget Planner - Database Configuration
 * 
 * This file contains database connection settings and common functions
 * for the Budget Planner application.
 * 
 * @author Budget Planner Team
 * @version 1.0
 */

// Prevent direct access
if (!defined('BUDGET_PLANNER_APP')) {
    define('BUDGET_PLANNER_APP', true);
}

// Error reporting for development (disable in production)
if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Session timeout protection
 * Automatically logs out users after 30 minutes of inactivity
 * 
 * @return string Session status: 'active', 'warning', or 'timeout'
 */
function check_session_timeout() {
    $timeout = 30 * 60; // 30 minutes
    $warning_time = 5 * 60; // 5 minutes warning before timeout
    
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        // Show warning if close to timeout
        if ($inactive_time > ($timeout - $warning_time) && !isset($_SESSION['warning_shown'])) {
            $_SESSION['warning_shown'] = true;
            return 'warning';
        }
        
        // Logout if timeout exceeded
        if ($inactive_time > $timeout) {
            session_unset();
            session_destroy();
            header("Location: login.php?msg=timeout");
            exit();
        }
    }
    
    $_SESSION['last_activity'] = time();
    unset($_SESSION['warning_shown']);
    return 'active';
}

/**
 * Database Configuration
 * Modify these settings according to your database setup
 */
class DatabaseConfig {
    private static $host = 'localhost';
    private static $username = 'root';
    private static $password = '';
    private static $database = 'budget_planner';
    private static $port = 3306;
    
    /**
     * Get database connection
     * 
     * @return mysqli Database connection object
     * @throws Exception If connection fails
     */
    public static function getConnection() {
        try {
            $conn = new mysqli(
                self::$host, 
                self::$username, 
                self::$password, 
                self::$database, 
                self::$port
            );
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            // Set charset to utf8mb4 for proper Unicode support
            if (!$conn->set_charset("utf8mb4")) {
                throw new Exception("Error setting charset: " . $conn->error);
            }
            
            return $conn;
            
        } catch (Exception $e) {
            // Log error for debugging
            error_log("Database connection error: " . $e->getMessage());
            
            // Show user-friendly error message
            if ($_SERVER['SERVER_NAME'] === 'localhost') {
                die("Database connection failed. Please check your database settings.<br>Error: " . $e->getMessage());
            } else {
                die("Service temporarily unavailable. Please try again later.");
            }
        }
    }
}

// Create global database connection
try {
    $conn = DatabaseConfig::getConnection();
} catch (Exception $e) {
    // This will be handled by the DatabaseConfig::getConnection() method
}

// Initialize database on first load if needed
if (!defined('DB_INITIALIZED')) {
    $check_tables = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_tables && $check_tables->num_rows == 0) {
        require_once __DIR__ . '/init_db.php';
        $initializer = new DatabaseInitializer($conn);
        $initializer->initialize();
    }
    define('DB_INITIALIZED', true);
}
?> 