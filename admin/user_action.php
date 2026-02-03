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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_update_password'])) {
    $uid = intval($_POST['user_id']);
    $new_pw = $_POST['new_password'];

    // Actually, update_password takes username. 
    // Let's check common ways to get user by ID.
    // get_all_users exists, but let's use a direct query or a better helper.

    global $conn;
    $res = mysqli_query($conn, "SELECT username FROM users WHERE id = $uid");
    if ($row = mysqli_fetch_assoc($res)) {
        if (update_password($row['username'], $new_pw)) {
            $_SESSION['flash'] = ['message' => "Password updated for " . $row['username'], 'type' => 'success'];
        } else {
            $_SESSION['flash'] = ['message' => "Failed to update password.", 'type' => 'error'];
        }
    } else {
        $_SESSION['flash'] = ['message' => "User not found.", 'type' => 'error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_migrate_data'])) {
    $from_user = trim($_POST['from_username']);
    $to_user = trim($_POST['to_username']);

    $source = get_user_by_username($from_user);
    $target = get_user_by_username($to_user);

    if (!$source) {
        $_SESSION['flash'] = ['message' => "Source user '$from_user' not found.", 'type' => 'error'];
    } elseif ($source['role'] === 'admin') {
        $_SESSION['flash'] = ['message' => "Cannot migrate data from an admin account.", 'type' => 'error'];
    } elseif (!$target) {
        $_SESSION['flash'] = ['message' => "Target user '$to_user' not found.", 'type' => 'error'];
    } elseif ($target['role'] === 'admin') {
        $_SESSION['flash'] = ['message' => "Cannot migrate data to an admin account.", 'type' => 'error'];
    } elseif ($source['id'] == $target['id']) {
        $_SESSION['flash'] = ['message' => "Source and target users must be different.", 'type' => 'error'];
    } else {
        if (migrate_user_data($source['id'], $target['id'])) {
            $_SESSION['flash'] = ['message' => "Data migrated from $from_user to $to_user.", 'type' => 'success'];
        } else {
            $_SESSION['flash'] = ['message' => "Migration failed.", 'type' => 'error'];
        }
    }
}

header("Location: dashboard.php");
exit();
?>