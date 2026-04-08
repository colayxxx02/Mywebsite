<?php
$servername = "localhost";
$username = "root";     // Default for XAMPP/WAMP
$password = "";         // Default for XAMPP/WAMP
$dbname = "library_db"; // Make sure this matches your DB name in phpMyAdmin

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check if the connection works
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>