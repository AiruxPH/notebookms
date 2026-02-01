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
// If script is NOT login.php or register.php, require login
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'login.php' && $current_page != 'register.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>