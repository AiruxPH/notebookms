<?php
include '../includes/data_access.php';
session_start();

if (!is_admin()) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $uid = $_POST['user_id'];
    $current_status = $_POST['current_status'];

    if (toggle_user_status($uid, $current_status)) {
        $action = ($current_status == 1) ? "Deactivated" : "Activated";
        $_SESSION['flash'] = ['message' => "User successfully $action.", 'type' => 'success'];
    } else {
        $_SESSION['flash'] = ['message' => "Error updating user status.", 'type' => 'error'];
    }
}

header("Location: dashboard.php");
exit();
?>