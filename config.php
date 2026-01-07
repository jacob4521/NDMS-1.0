<?php
$host = "localhost";
$user = "root";   // WAMP default
$pass = "";       // WAMP default (empty)
$db   = "ndms";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

session_start();
?>
