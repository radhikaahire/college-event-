<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "college_event_management";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed");
}
?>
