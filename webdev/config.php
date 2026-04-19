<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root123');
define('DB_NAME', 'sis_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout
$timeout = 1800; // 30 minutes

if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > $timeout) {
        session_destroy();
        header('Location: login.php?msg=Session expired. Please login again.');
        exit();
    }
}
$_SESSION['last_activity'] = time();
?>