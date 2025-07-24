<?php
// includes/db.php

// Require the Composer autoloader to load dependencies like phpdotenv.
require_once __DIR__ . '/../vendor/autoload.php';

// --- Load Environment Variables ---
// This part should be at the top and executed only once.
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    // Log success or key variable to confirm dotenv loaded correctly
    error_log("Dotenv loaded successfully. DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET'));
} catch (Dotenv\Exception\InvalidPathException $e) {
    // This specific exception is for when the .env file cannot be found
    error_log("FATAL: .env file not found or is not readable: " . $e->getMessage());
    http_response_code(503);
    die("Application configuration unavailable. Please contact support.");
} catch (Exception $e) {
    // Catch any other exceptions during dotenv loading
    error_log("FATAL: Error loading .env file: " . $e->getMessage());
    http_response_code(503);
    die("Application configuration unavailable. Please try again later.");
}

// Retrieve database credentials from environment variables.
// These variables should be available now if dotenv loaded successfully.
$servername = $_ENV['DB_HOST'] ?? null;
$username = $_ENV['DB_USER'] ?? null;
$password = $_ENV['DB_PASS'] ?? null;
$dbname = $_ENV['DB_NAME'] ?? null;

// Basic check to ensure environment variables were loaded
if (!$servername || !$username || !$password || !$dbname) {
    error_log("FATAL: Database credentials missing from .env file after loading.");
    http_response_code(503);
    die("Database connection details are incomplete. Please contact support.");
}


// --- Database Connection ---

// Set the internal MySQLi error reporting mode to throw exceptions.
// This allows us to use a try-catch block for cleaner error handling.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Attempt to create a new MySQLi connection object.
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Set the character set to utf8mb4.
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // If the connection fails, a mysqli_sql_exception will be thrown.

    // Log the detailed, specific error to your server's error log.
    error_log("FATAL: Database connection failed: " . $e->getMessage());

    // For the end-user, display a generic error message.
    http_response_code(503); // 503 Service Unavailable
    die("Our database is currently unavailable. Please try again later.");
}

// If the script reaches this point, the connection was successful.
// The $conn object is now available for use in any script that includes this file.

?>