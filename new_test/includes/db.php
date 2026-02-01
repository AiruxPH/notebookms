<?php
if (!isset($_SESSION)) {
    session_start();
}

$servername = "srv1858.hstgr.io";
$username = "u130348899_notebookms";
$password = "Note999@";
$dbname = "u130348899_notebook";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
