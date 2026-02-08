<?php
// admin/backups.php
include '../includes/data_access.php';
session_start();

// Protection: Admin Only
if (!is_admin()) {
    header("Location: ../login.php");
    exit();
}

$backup_dir = __DIR__ . '/backups/';

// Handle Delete Action
if (isset($_POST['delete_file'])) {
    $file = basename($_POST['delete_file']);
    $path = $backup_dir . $file;
    if (file_exists($path)) {
        unlink($path);
        $_SESSION['flash'] = ['message' => "Backup deleted successfully.", 'type' => 'success'];
    } else {
        $_SESSION['flash'] = ['message' => "File not found.", 'type' => 'error'];
    }
    header("Location: backups.php");
    exit();
}

// Handle Manual Backup Trigger
if (isset($_POST['create_backup'])) {
    // Capture output of backup script
    ob_start();
    include 'backup_db.php';
    ob_end_clean();
    $_SESSION['flash'] = ['message' => "New backup created successfully.", 'type' => 'success'];
    header("Location: backups.php");
    exit();
}

// Handle Download Action (Serves file via PHP to bypass .htaccess)
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $path = $backup_dir . $file;
    if (file_exists($path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    } else {
        $_SESSION['flash'] = ['message' => "File not found.", 'type' => 'error'];
        header("Location: backups.php");
        exit();
    }
}

// List Files
$files = [];
if (file_exists($backup_dir)) {
    $scandir = scandir($backup_dir);
    foreach ($scandir as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.htaccess' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $path = $backup_dir . $file;
            $files[] = [
                'name' => $file,
                'size' => round(filesize($path) / 1024, 2) . ' KB',
                'date' => date("M j, Y H:i:s", filemtime($path)),
                'timestamp' => filemtime($path)
            ];
        }
    }
}

// Sort by date desc
usort($files, function ($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../favicon.png" type="image/png">
    <title>Backup Manager - Notebook Admin</title>
</head>

<body>

    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR ADMIN</a> <span
                    style="font-size: 14px; font-weight: normal; opacity: 0.7;">Backup Manager</span></h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="../logout.php" style="color: #c62828;">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Toast Container -->
        <div id="toast-overlay" class="toast-overlay">
            <div id="toast-message" class="toast-message"></div>
        </div>

        <div class="dashboard-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0;">Database Backups</h2>
                    <p style="margin: 5px 0 0; color: #666;">Manage and restore automated backups.</p>
                </div>
                <form method="post" onsubmit="return confirm('Create a new backup now?');">
                    <button type="submit" name="create_backup" class="btn btn-primary">Create New Backup</button>
                </form>
            </div>

            <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #eee; border-bottom: 2px solid #ccc; text-align: left;">
                        <th style="padding: 10px;">Filename</th>
                        <th style="padding: 10px;">Size</th>
                        <th style="padding: 10px;">Date Created</th>
                        <th style="padding: 10px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($files) > 0): ?>
                        <?php foreach ($files as $f): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px; font-family: monospace;">
                                    <?php echo htmlspecialchars($f['name']); ?>
                                </td>
                                <td style="padding: 10px;"><?php echo $f['size']; ?></td>
                                <td style="padding: 10px;"><?php echo $f['date']; ?></td>
                                <td
                                    style="padding: 10px; text-align: right; display: flex; justify-content: flex-end; gap: 5px;">
                                    <!-- Download -->
                                    <a href="?download=<?php echo $f['name']; ?>" class="btn btn-sm btn-secondary"
                                        title="Download SQL">
                                        <i class="fa-solid fa-download"></i> DL
                                    </a>

                                    <!-- Restore -->
                                    <form method="post" action="restore_db.php" style="display:inline;"
                                        onsubmit="return confirm('WARNING: This will OVERWRITE the current database with this backup. This cannot be undone. Are you sure?');">
                                        <input type="hidden" name="file" value="<?php echo htmlspecialchars($f['name']); ?>">
                                        <button type="submit" class="btn btn-sm"
                                            style="background: #fff3e0; color: #ef6c00; border-color: #ffb74d;">Restore</button>
                                    </form>

                                    <!-- Delete -->
                                    <form method="post" style="display:inline;"
                                        onsubmit="return confirm('Delete this backup file?');">
                                        <input type="hidden" name="delete_file"
                                            value="<?php echo htmlspecialchars($f['name']); ?>">
                                        <button type="submit" class="btn btn-sm"
                                            style="background: #ffebee; color: #c62828; border-color: #ef9a9a;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 30px; color: #999;">No backups found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Reuse Toast Logic from dashboard
        <?php
        if (isset($_SESSION['flash'])) {
            $msg = $_SESSION['flash']['message'];
            $msg_type = $_SESSION['flash']['type'];
            unset($_SESSION['flash']);
            echo "
                const toastOverlay = document.getElementById('toast-overlay');
                const toastMessage = document.getElementById('toast-message');
                toastMessage.textContent = '" . addslashes($msg) . "';
                toastMessage.className = 'toast-message ' + ('$msg_type' === 'error' ? 'toast-error' : 'toast-success');
                void toastMessage.offsetWidth;
                toastOverlay.style.display = 'flex';
                requestAnimationFrame(() => { toastMessage.classList.add('show'); });
                setTimeout(() => {
                    toastMessage.classList.remove('show');
                    setTimeout(() => { toastOverlay.style.display = 'none'; }, 300);
                }, 3000);
            ";
        }
        ?>
    </script>
</body>

</html>