<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'inactive']);
    exit();
}

$status = check_session_timeout();
echo json_encode(['status' => $status]); 