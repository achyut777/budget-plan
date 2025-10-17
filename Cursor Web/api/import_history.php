<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to view import history']);
    exit;
}

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    // Create import log table if it doesn't exist
    $create_table = "
        CREATE TABLE IF NOT EXISTS import_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            import_type VARCHAR(50) NOT NULL,
            records_imported INT DEFAULT 0,
            status ENUM('success', 'failed') NOT NULL,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $conn->query($create_table);
    
    // Get import history
    $stmt = $conn->prepare("
        SELECT 
            filename,
            import_type,
            records_imported,
            status,
            error_message,
            created_at
        FROM import_log 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $row['created_at'] = date('M d, Y H:i', strtotime($row['created_at']));
        $history[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>