<?php
// customer/logout.php - Handles customer logout

// Include the session management file which contains the logout function
require_once __DIR__ . '/../includes/session.php';

// Call the logout function to terminate the user's session and redirect
logout();
?>