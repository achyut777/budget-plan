<?php
/**
 * Email Test Configuration for XAMPP
 * This file provides a simple file-based email simulation for development
 */

class EmailSimulator {
    private $log_dir;
    
    public function __construct() {
        $this->log_dir = __DIR__ . '/../logs/emails/';
        if (!file_exists($this->log_dir)) {
            mkdir($this->log_dir, 0777, true);
        }
    }
    
    /**
     * Simulate sending email by saving to a file
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $headers
     * @return bool
     */
    public function sendMail($to, $subject, $message, $headers = '') {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = $this->log_dir . "email_{$timestamp}_{$to}.html";
        
        $email_content = "
<!DOCTYPE html>
<html>
<head>
    <title>Email Log - {$subject}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .email-info { background: #f0f0f0; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .email-content { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='email-info'>
        <h2>Email Details</h2>
        <p><strong>To:</strong> {$to}</p>
        <p><strong>Subject:</strong> {$subject}</p>
        <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Headers:</strong></p>
        <pre>" . htmlspecialchars($headers) . "</pre>
    </div>
    <div class='email-content'>
        <h3>Email Content:</h3>
        {$message}
    </div>
</body>
</html>";
        
        $result = file_put_contents($filename, $email_content);
        
        // Also log to a simple text file for easy viewing
        $log_file = $this->log_dir . 'email_log.txt';
        $log_entry = "[" . date('Y-m-d H:i:s') . "] Email to: {$to} | Subject: {$subject} | File: {$filename}\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        return $result !== false;
    }
}
?>