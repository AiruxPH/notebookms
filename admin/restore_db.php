<?php
// admin/restore_db.php
include '../includes/data_access.php';
session_start();

// Protection: Admin Only
if (!is_admin()) {
    header("Location: ../login.php");
    exit();
}

$backup_dir = __DIR__ . '/backups/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file'])) {
    $file = basename($_POST['file']);
    $path = $backup_dir . $file;

    if (!file_exists($path)) {
        $_SESSION['flash'] = ['message' => "Backup file not found.", 'type' => 'error'];
        header("Location: backups.php");
        exit();
    }

    // Disable Foreign Key Checks temporarily
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

    // Read file
    $sql_contents = file_get_contents($path);

    // Execute Multiple Queries
    if (mysqli_multi_query($conn, $sql_contents)) {
        // Consume all results
        do {
            if ($res = mysqli_store_result($conn)) {
                mysqli_free_result($res);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));

        $msg = "Database restored successfully from $file.";
        $type = "success";
    } else {
        $msg = "Error restoring database: " . mysqli_error($conn);
        $type = "error";
    }

    // Re-enable Foreign Key Checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

    $_SESSION['flash'] = ['message' => $msg, 'type' => $type];
    header("Location: backups.php");
    exit();

} else {
    // Direct access not allowed
    header("Location: backups.php");
    exit();
}
?>