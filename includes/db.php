<?php
// includes/db.php

// Require the Composer autoloader to load dependencies like phpdotenv.
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from the .env file in the root directory.
// This keeps your sensitive credentials out of the source code.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Retrieve database credentials from environment variables.
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

// --- Database Connection ---

// Set the internal MySQLi error reporting mode to throw exceptions.
// This allows us to use a try-catch block for cleaner error handling.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Attempt to create a new MySQLi connection object.
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Set the character set to utf8mb4.
    // This is crucial for supporting a wide range of characters, including emojis,
    // and preventing potential security issues related to character encoding.
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // If the connection fails, a mysqli_sql_exception will be thrown.

    // Log the detailed, specific error to your server's error log.
    // This is for your debugging purposes and should not be shown to the public.
    error_log("FATAL: Database connection failed: " . $e->getMessage());

    // For the end-user, display a generic error message.
    // This prevents leaking sensitive information about your database configuration.
    // We use http_response_code to send a "Service Unavailable" status, which is appropriate.
    http_response_code(503); // 503 Service Unavailable
    die("Our database is currently unavailable. Please try again later.");
}

// If the script reaches this point, the connection was successful.
// The $conn object is now available for use in any script that includes this file.

?>