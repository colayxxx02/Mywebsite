<?php
// Sugdan ang session
session_start();

// Tangtangon tanang session variables
$_SESSION = array();

// Gub-on ang session (Destroy)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// I-balik ang user sa login page (index.php)
header("Location: ../index.php");
exit();