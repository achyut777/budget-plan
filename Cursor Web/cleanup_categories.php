<?php
require_once 'config/database.php';

// Start transaction
$conn->begin_transaction();

try {
    // Get all categories grouped by name and type, keeping only the most recent one
    $sql = "DELETE c1 FROM categories c1
            INNER JOIN categories c2 
            WHERE c1.name = c2.name 
            AND c1.type = c2.type 
            AND c1.id < c2.id";
    
    $conn->query($sql);
    
    // Commit transaction
    $conn->commit();
    
    echo "Successfully removed duplicate categories.";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 