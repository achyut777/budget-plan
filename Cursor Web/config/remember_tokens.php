<?php
/**
 * Remember Me Token Management
 * Handles remember me functionality for persistent login
 */

class RememberTokens {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Generate a secure remember token
     * @return string
     */
    public function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Create a remember token for a user
     * @param int $user_id
     * @return string The generated token
     */
    public function createRememberToken($user_id) {
        // Clean up old tokens for this user
        $this->cleanupUserTokens($user_id);
        
        $token = $this->generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $this->conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $token, $expires);
        
        if ($stmt->execute()) {
            // Set the cookie
            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
            return $token;
        }
        
        return false;
    }
    
    /**
     * Validate a remember token and get user data
     * @param string $token
     * @return array|false User data if valid, false if invalid
     */
    public function validateToken($token) {
        $stmt = $this->conn->prepare("
            SELECT rt.user_id, u.id, u.name, u.email, u.email_verified 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens() {
        $stmt = $this->conn->prepare("DELETE FROM remember_tokens WHERE expires_at <= NOW()");
        $stmt->execute();
    }
    
    /**
     * Clean up all tokens for a specific user
     * @param int $user_id
     */
    public function cleanupUserTokens($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    /**
     * Remove a specific token (for logout)
     * @param string $token
     */
    public function removeToken($token) {
        $stmt = $this->conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Clear the cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    /**
     * Check if user has remember me cookie and auto-login
     * @return bool True if logged in via remember token
     */
    public function checkRememberToken() {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $user = $this->validateToken($_COOKIE['remember_token']);
        if ($user) {
            // Log the user in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = false;
            
            // Extend the token
            $this->createRememberToken($user['id']);
            
            return true;
        } else {
            // Invalid token, remove the cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            return false;
        }
    }
}
?>