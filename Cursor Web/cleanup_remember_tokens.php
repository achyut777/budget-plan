<?php
/**
 * Remember Token Cleanup Script
 * Run this periodically to clean up expired tokens
 */

require_once 'config/database.php';
require_once 'config/remember_tokens.php';

$rememberTokens = new RememberTokens($conn);
$rememberTokens->cleanupExpiredTokens();

echo "Expired remember tokens cleaned up successfully.\n";
?>