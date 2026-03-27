<?php
// Disable output buffering to ensure headers are sent
ob_end_clean();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Delete the session cookie - multiple methods
$cookie_name = session_name();

// Method 1: Standard cookie deletion
setcookie($cookie_name, '', time() - 3600, '/');

// Method 2: Also try with full path
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        $cookie_name, 
        '', 
        time() - 3600,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy the session
@session_destroy();

// Clear the session ID from the cookie array
if (isset($_COOKIE[$cookie_name])) {
    unset($_COOKIE[$cookie_name]);
}

// Add cache control headers
header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Powered-By: ');

// Redirect to index.php
header('Location: index.php', true, 302);
exit;
?>
