<?php
require_once 'config.php';

session_destroy();
header('Location: login.php?msg=You have been logged out successfully');
exit();
?>