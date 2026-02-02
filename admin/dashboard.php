<?php
include '../includes/data_access.php';
session_start();

// Protection: Admin Only
if (!is_admin()) {
    header("Location: ../login.php");
    exit();
}

$users = get_all_users();
$current_uid = get_current_user_id();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../favicon.png" type="image/png">
    <title>Admin Dashboard - Notebook</title>
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 2px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .admin-table th,
        .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background-color: var(--nav-bg);
            border-bottom: 2px solid #ccc;
            font-weight: bold;
        }

        .status-active {
            color: green;
            font-weight: bold;
        }

        .status-banned {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR ADMIN</a></h1>
            <nav>
                <a href="dashboard.php" style="background: #fff;">Dashboard</a>
                <a href="../index.php" target="_blank">View Site</a>
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
            <h2 style="margin-top: 0;">User Management</h2>
            <p>Manage registered users and their access.</p>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Notes</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <?php echo $u['id']; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($u['username']); ?>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span
                                        style="background: #333; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px;">ADMIN</span>
                                <?php else: ?>
                                    User
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $u['note_count']; ?>
                            </td>
                            <td>
                                <?php echo date("M j, Y", strtotime($u['date_created'])); ?>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-banned">Deactivated</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['id'] != $current_uid): ?>
                                    <form method="post" action="user_action.php" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $u['is_active']; ?>">
                                        <?php if ($u['is_active']): ?>
                                            <button type="submit" name="toggle_status" class="btn btn-sm"
                                                style="background:#ffebee; color:#c62828; border-color:#ef9a9a;">Deactivate</button>
                                        <?php else: ?>
                                            <button type="submit" name="toggle_status" class="btn btn-sm"
                                                style="background:#e8f5e9; color:#2e7d32; border-color:#a5d6a7;">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#999; font-size: 12px;">(You)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Reuse Toast Logic
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