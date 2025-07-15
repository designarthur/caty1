<?php
// includes/session.php
if (session_status() == PHP_SESSION_NONE) { // Add this check
    session_start();
}
require_once __DIR__ . '/functions.php';
/**
 * Checks if a user is logged in.
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user has a specific role.
 * @param string $role The role to check against (e.g., 'admin', 'customer', 'vendor').
 * @return bool True if the user has the role, false otherwise.
 */
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Redirects if the user is not logged in or does not have the required role.
 * @param string $required_role Optional. If set, checks for this specific role.
 * @param string $redirect_url URL to redirect to if not authorized.
 */
function require_login($required_role = null, $redirect_url = '/customer/login.php') {
    if (!is_logged_in()) {
        redirect($redirect_url);
    }
    if ($required_role && !has_role($required_role)) {
        // Log unauthorized access attempt
        error_log("Unauthorized access attempt by User ID: {$_SESSION['user_id']} (Role: {$_SESSION['user_role']}) to a page requiring role: {$required_role}");
        redirect('/unauthorized.php'); // Create an unauthorized access page
    }
}

/**
 * Logs out the current user by destroying the session.
 */
function logout() {
    $_SESSION = array(); // Clear all session variables
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"] // Corrected from "httplike" to "httponly"
        );
    }
    session_destroy();
    redirect('/customer/login.php'); // Redirect to login page after logout
}
?>