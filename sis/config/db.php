<?php
// Database Credentials
$host     = "127.0.0.1";
$user     = "root";
$password = "root123";
$dbname   = "sis_db";

// Create Connection
$conn = mysqli_connect($host, $user, $password, $dbname);

// Check Connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set Charset para sa mga special characters (sama sa Ñ)
mysqli_set_charset($conn, "utf8mb4");

// Start Session (kay gamiton ni sa tanan pages para sa login)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>