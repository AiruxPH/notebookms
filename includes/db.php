<?php
if (!isset($_SESSION)) {
    session_start();
}

$servername = "srv1858.hstgr.io";
$username = "u130348899_nbmstest";
$password = "Note999@";
$dbname = "u130348899_notebookmstest";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Authentication Check
// If script is NOT login.php or register.php, we just start session. 
// We DO NOT force redirect here anymore, because we support Guest Mode.
// Specific pages (like "change password" or "profile" if added later) might need manual checks.
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
// $user_id = 0 means Guest.
?>