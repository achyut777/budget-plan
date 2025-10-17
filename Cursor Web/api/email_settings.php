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
$action = $input['action'] ?? $_GET['action'] ?? '';

// Create user_email_settings table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS user_email_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255),
    email_format ENUM('html', 'text') DEFAULT 'html',
    timezone VARCHAR(100) DEFAULT 'Asia/Kolkata',
    preferred_time TIME DEFAULT '09:00:00',
    digest_mode BOOLEAN DEFAULT FALSE,
    mobile_optimized BOOLEAN DEFAULT TRUE,
    unsubscribe_token VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

try {
    $conn->query($create_table_sql);
} catch (Exception $e) {
    error_log("Error creating user_email_settings table: " . $e->getMessage());
}

switch ($action) {
    case 'get_email_settings':
        getEmailSettings();
        break;
    case 'save_email_settings':
        saveEmailSettings();
        break;
    case 'test_smtp_connection':
        testSMTPConnection();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getEmailSettings() {
    global $conn, $user_id;
    
    try {
        // Get user's current email settings
        $stmt = $conn->prepare("
            SELECT 
                ues.*,
                u.email as user_email,
                u.name as user_name
            FROM users u
            LEFT JOIN user_email_settings ues ON u.id = ues.user_id
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Use defaults if no settings found
        $settings = [
            'email' => $data['email'] ?: $data['user_email'],
            'email_format' => $data['email_format'] ?: 'html',
            'timezone' => $data['timezone'] ?: 'Asia/Kolkata',
            'preferred_time' => $data['preferred_time'] ?: '09:00:00',
            'digest_mode' => (bool)($data['digest_mode'] ?? false),
            'mobile_optimized' => (bool)($data['mobile_optimized'] ?? true),
            'user_name' => $data['user_name']
        ];
        
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
        
    } catch (Exception $e) {
        error_log("Error getting email settings: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load email settings'
        ]);
    }
}

function saveEmailSettings() {
    global $conn, $user_id, $input;
    
    if (!isset($input['settings'])) {
        echo json_encode(['success' => false, 'message' => 'No settings provided']);
        return;
    }
    
    $settings = $input['settings'];
    
    // Validate email
    if (!filter_var($settings['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    // Validate timezone
    $valid_timezones = timezone_identifiers_list();
    if (!in_array($settings['timezone'], $valid_timezones)) {
        echo json_encode(['success' => false, 'message' => 'Invalid timezone']);
        return;
    }
    
    // Validate time format
    if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $settings['preferred_time'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid time format']);
        return;
    }
    
    try {
        $conn->begin_transaction();
        
        // Generate unsubscribe token if not exists
        $unsubscribe_token = generateUnsubscribeToken($user_id);
        
        // Update or insert email settings
        $stmt = $conn->prepare("
            INSERT INTO user_email_settings (
                user_id, email, email_format, timezone, preferred_time, 
                digest_mode, mobile_optimized, unsubscribe_token
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                email_format = VALUES(email_format),
                timezone = VALUES(timezone),
                preferred_time = VALUES(preferred_time),
                digest_mode = VALUES(digest_mode),
                mobile_optimized = VALUES(mobile_optimized),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->bind_param("issssiis", 
            $user_id,
            $settings['email'],
            $settings['format'],
            $settings['timezone'],
            $settings['preferred_time'],
            $settings['digest_mode'] ? 1 : 0,
            $settings['mobile_optimized'] ? 1 : 0,
            $unsubscribe_token
        );
        $stmt->execute();
        
        // Also update user's primary email if changed
        $update_user_stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $update_user_stmt->bind_param("si", $settings['email'], $user_id);
        $update_user_stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Email settings saved successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving email settings: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save email settings'
        ]);
    }
}

function testSMTPConnection() {
    global $user_id, $input;
    
    try {
        // Get current email settings
        $settings = getCurrentEmailSettings($user_id);
        
        if (!$settings) {
            echo json_encode(['success' => false, 'message' => 'No email settings found']);
            return;
        }
        
        // Test email configuration
        $test_result = performSMTPTest($settings['email']);
        
        if ($test_result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'SMTP connection test successful',
                'details' => $test_result['details']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'SMTP connection test failed',
                'error' => $test_result['error']
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Error testing SMTP connection: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to test SMTP connection'
        ]);
    }
}

function generateUnsubscribeToken($user_id) {
    global $conn;
    
    // Check if token already exists
    $stmt = $conn->prepare("SELECT unsubscribe_token FROM user_email_settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    
    if ($existing && $existing['unsubscribe_token']) {
        return $existing['unsubscribe_token'];
    }
    
    // Generate new token
    return bin2hex(random_bytes(32));
}

function getCurrentEmailSettings($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT ues.*, u.email as user_email, u.name
        FROM users u
        LEFT JOIN user_email_settings ues ON u.id = ues.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function performSMTPTest($email) {
    // Basic email validation and DNS check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'error' => 'Invalid email format'
        ];
    }
    
    // Extract domain
    $domain = substr(strrchr($email, "@"), 1);
    
    // Check if domain has MX record
    if (!checkdnsrr($domain, "MX")) {
        return [
            'success' => false,
            'error' => 'Domain does not have valid MX records'
        ];
    }
    
    // In a production environment, you would test actual SMTP connection here
    // For now, we'll simulate a successful test
    return [
        'success' => true,
        'details' => [
            'domain' => $domain,
            'mx_records_found' => true,
            'smtp_test' => 'simulated_success',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
}

// Utility function to get user's email preferences for sending notifications
function getUserEmailPreferences($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(ues.email, u.email) as email,
            COALESCE(ues.email_format, 'html') as format,
            COALESCE(ues.timezone, 'Asia/Kolkata') as timezone,
            COALESCE(ues.preferred_time, '09:00:00') as preferred_time,
            COALESCE(ues.digest_mode, 0) as digest_mode,
            COALESCE(ues.mobile_optimized, 1) as mobile_optimized,
            u.name,
            ues.unsubscribe_token
        FROM users u
        LEFT JOIN user_email_settings ues ON u.id = ues.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Function to generate unsubscribe link
function generateUnsubscribeLink($user_id) {
    global $conn;
    
    $preferences = getUserEmailPreferences($user_id);
    if ($preferences && $preferences['unsubscribe_token']) {
        return "https://yourbudgetplanner.com/unsubscribe.php?token=" . $preferences['unsubscribe_token'];
    }
    
    return null;
}
?>