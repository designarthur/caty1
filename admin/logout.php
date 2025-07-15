<?php
// admin/logout.php - Handles admin logout

// Include the session management file which contains the logout function
require_once __DIR__ . '/../includes/session.php';

// Call the logout function to terminate the user's session and redirect
// The logout() function in includes/session.php already redirects to /customer/login.php
// You might want to explicitly redirect to /admin/login.php here if you have a separate admin login page
// For now, we'll keep the default logout() redirect.
logout();
?>