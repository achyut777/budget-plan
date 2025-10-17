<?php
/**
 * Email Configuration for Budget Planner
 * Using PHP's built-in mail() function for XAMPP compatibility
 */

// Email configuration
define('FROM_EMAIL', 'noreply@budgetplanner.local');
define('FROM_NAME', 'Budget Planner');

// Application settings
define('APP_NAME', 'Budget Planner');
define('APP_URL', 'http://localhost/new%202/Cursor%20Web');
define('VERIFICATION_TOKEN_EXPIRY', 24); // Hours
define('MAX_VERIFICATION_ATTEMPTS', 3);

/**
 * Check if email configuration is valid
 * @return bool
 */
function is_email_configured() {
    return !empty(FROM_EMAIL) && !empty(FROM_NAME);
}

/**
 * Get email configuration array
 * @return array
 */
function get_email_config() {
    return [
        'from_email' => FROM_EMAIL,
        'from_name' => FROM_NAME,
        'app_name' => APP_NAME,
        'app_url' => APP_URL
    ];
}
?>
?>