<?php
session_start();
require_once 'config/database.php';
require_once 'config/remember_tokens.php';

// Handle remember token cleanup
if (isset($_COOKIE['remember_token'])) {
    $rememberTokens = new RememberTokens($conn);
    $rememberTokens->removeToken($_COOKIE['remember_token']);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?> 