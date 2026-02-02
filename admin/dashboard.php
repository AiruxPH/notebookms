<?php
include '../includes/data_access.php';
session_start();

// Protection: Admin Only
if (!is_admin()) {
    header("Location: ../login.php");
    exit();
}

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

        <?php
        // Get Parameters
        $search = $_GET['q'] ?? '';
        $role_filter = $_GET['role'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        $sort = $_GET['sort'] ?? 'id';
        $order = $_GET['order'] ?? 'ASC';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = 10;

        // Fetch Users
        $data = get_all_users($search, $role_filter, $status_filter, $sort, $order, $page, $limit);
        $users = $data['users'];
        $total_users = $data['total'];
        $total_pages = ceil($total_users / $limit);
        
        $current_uid = get_current_user_id();
        ?>

        <div class="dashboard-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;">User Management</h2>
                <p style="margin: 5px 0 0; color: #666;">Total Users: <strong><?php echo $total_users; ?></strong></p>
            </div>
            <a href="add_user.php" class="btn btn-primary">+ Add New User</a>
        </div>

        <!-- Search & Filter Form -->
        <form method="get" style="background: #f9f9f9; padding: 15px; border: 1px solid #eee; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 2; min-width: 200px;">
                <label style="font-size: 12px; font-weight: bold;">Search:</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Username..." style="width: 100%; padding: 8px; border: 1px solid #ccc;">
            </div>
            
            <div style="flex: 1; min-width: 120px;">
                <label style="font-size: 12px; font-weight: bold;">Role:</label>
                <select name="role" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                    <option value="">All Roles</option>
                    <option value="admin" <?php if ($role_filter === 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="user" <?php if ($role_filter === 'user') echo 'selected'; ?>>User</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 120px;">
                <label style="font-size: 12px; font-weight: bold;">Status:</label>
                <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                    <option value="">All Status</option>
                    <option value="1" <?php if ($status_filter === '1') echo 'selected'; ?>>Active</option>
                    <option value="0" <?php if ($status_filter === '0') echo 'selected'; ?>>Deactivated</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 120px;">
                <label style="font-size: 12px; font-weight: bold;">Sort:</label>
                <select name="sort" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                    <option value="id" <?php if ($sort === 'id') echo 'selected'; ?>>ID</option>
                    <option value="username" <?php if ($sort === 'username') echo 'selected'; ?>>Username</option>
                    <option value="date_created" <?php if ($sort === 'date_created') echo 'selected'; ?>>Date Joined</option>
                </select>
            </div>
            
             <div style="flex: 0 0 auto;">
                <label style="font-size: 12px; font-weight: bold;">Order:</label>
                <select name="order" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                    <option value="ASC" <?php if ($order === 'ASC') echo 'selected'; ?>>ASC</option>
                    <option value="DESC" <?php if ($order === 'DESC') echo 'selected'; ?>>DESC</option>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary" style="margin-bottom: 2px;">Filter</button>
            <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                <a href="dashboard.php" class="btn" style="margin-bottom: 2px; background: #ddd; color: #333;">Reset</a>
            <?php endif; ?>
        </form>

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
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span style="background: #333; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px;">ADMIN</span>
                                <?php else: ?>
                                    User
                                <?php endif; ?>
                            </td>
                            <td><?php echo $u['note_count']; ?></td>
                            <td><?php echo date("M j, Y", strtotime($u['date_created'])); ?></td>
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
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; padding: 30px; color: #777;">No users found matching your criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="margin-top: 20px; text-align: center;">
                <?php 
                $q_str = http_build_query(array_merge($_GET, []));
                // Remove page from query string to append easily
                $base_params = $_GET;
                unset($base_params['page']);
                $base_query = http_build_query($base_params);
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="?<?php echo $base_query; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary" style="padding: 5px 10px;">&laquo; Prev</a>
                <?php endif; ?>

                <span style="margin: 0 10px; font-weight: bold;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo $base_query; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary" style="padding: 5px 10px;">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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