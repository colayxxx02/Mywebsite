<?php
require_once('../config/db.php');
if(isset($_GET['id'])) {
    $id = $_GET['id'];
    mysqli_query($conn, "DELETE FROM subjects WHERE id = $id");
    header("Location: course_sched.php?deleted");
}
?>