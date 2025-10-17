<?php
session_start();
require_once '../config/database.php';
require_once '../config/email_verification.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$user_id = $input['user_id'] ?? '';

if (empty($action) || empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

switch ($action) {
    case 'verify':
        // Manually verify user
        if ($emailVerification->manualVerify($user_id)) {
            echo json_encode(['success' => true, 'message' => 'User verified successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to verify user']);
        }
        break;
        
    case 'unverify':
        // Unverify user (set email_verified = 0)
        try {
            $stmt = $conn->prepare("UPDATE users SET email_verified = 0, verified_at = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User verification revoked']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to revoke verification']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
        break;
        
    case 'resend_verification':
        // Resend verification email
        try {
            $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? AND email_verified = 0");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if ($emailVerification->sendVerificationEmail($user_id, $user['email'], $user['name'])) {
                    echo json_encode(['success' => true, 'message' => 'Verification email sent successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send verification email']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found or already verified']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error sending verification email']);
        }
        break;
        
    case 'reset_attempts':
        // Reset verification attempts
        try {
            $stmt = $conn->prepare("UPDATE users SET verification_attempts = 0 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Verification attempts reset']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reset attempts']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>