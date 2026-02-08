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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .close-modal:hover {
            color: #333;
        }

        .migration-section {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR ADMIN</a></h1>
            <nav>
                <a href="dashboard.php" style="background: #fff;">Dashboard</a>
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
        $sort = $_GET['sort'] ?? 'user_id';
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
                    <p style="margin: 5px 0 0; color: #666;">Total Users: <strong><?php echo $total_users; ?></strong>
                    </p>
                </div>
                <a href="add_user.php" class="btn btn-primary">+ Add New User</a>
            </div>

            <!-- Migration Utility -->
            <div class="migration-section">
                <h3 style="margin-top: 0; font-size: 16px;">Data Migration Utility</h3>
                <p style="font-size: 12px; color: #555;">Transfer all notes and categories from one user to another.</p>
                <form id="migrationForm" style="display: flex; gap: 10px; align-items: flex-end;">
                    <div style="flex: 1; position: relative;">
                        <label style="font-size: 11px; font-weight: bold;">From Username:</label>
                        <input type="text" name="from_username" id="from_username" required placeholder="Owner..."
                            style="width: 100%; padding: 6px; border: 1px solid #ccc;"
                            oninput="validateMigrationUser('from')">
                        <div id="from_status" style="font-size: 10px; margin-top: 2px;"></div>
                    </div>
                    <div style="flex: 1; position: relative;">
                        <label style="font-size: 11px; font-weight: bold;">To Username:</label>
                        <input type="text" name="to_username" id="to_username" required placeholder="Recipient..."
                            style="width: 100%; padding: 6px; border: 1px solid #ccc;"
                            oninput="validateMigrationUser('to')">
                        <div id="to_status" style="font-size: 10px; margin-top: 2px;"></div>
                    </div>
                    <button type="button" id="migrate_btn" class="btn"
                        style="background: #1976d2; color: #fff; opacity: 0.5;" disabled
                        onclick="openMigrationPreview()">Migrate
                        Data</button>
                </form>
            </div>

            <!-- Search & Filter Form -->
            <form method="get"
                style="background: #f9f9f9; padding: 15px; border: 1px solid #eee; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label style="font-size: 12px; font-weight: bold;">Search:</label>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Username..." style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                </div>

                <div style="flex: 1; min-width: 120px;">
                    <label style="font-size: 12px; font-weight: bold;">Role:</label>
                    <select name="role" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                        <option value="">All Roles</option>
                        <option value="admin" <?php if ($role_filter === 'admin')
                            echo 'selected'; ?>>Admin</option>
                        <option value="user" <?php if ($role_filter === 'user')
                            echo 'selected'; ?>>User</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 120px;">
                    <label style="font-size: 12px; font-weight: bold;">Status:</label>
                    <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                        <option value="">All Status</option>
                        <option value="1" <?php if ($status_filter === '1')
                            echo 'selected'; ?>>Active</option>
                        <option value="0" <?php if ($status_filter === '0')
                            echo 'selected'; ?>>Deactivated</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 120px;">
                    <label style="font-size: 12px; font-weight: bold;">Sort:</label>
                    <select name="sort" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                        <option value="user_id" <?php if ($sort === 'user_id')
                            echo 'selected'; ?>>ID</option>
                        <option value="username" <?php if ($sort === 'username')
                            echo 'selected'; ?>>Username</option>
                        <option value="date_created" <?php if ($sort === 'date_created')
                            echo 'selected'; ?>>Date Joined
                        </option>
                    </select>
                </div>

                <div style="flex: 0 0 auto;">
                    <label style="font-size: 12px; font-weight: bold;">Order:</label>
                    <select name="order" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                        <option value="ASC" <?php if ($order === 'ASC')
                            echo 'selected'; ?>>ASC</option>
                        <option value="DESC" <?php if ($order === 'DESC')
                            echo 'selected'; ?>>DESC</option>
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
                        <th>User ID</th>
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
                                <td><?php echo $u['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span
                                            style="background: #333; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px;">ADMIN</span>
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
                                    <?php if ($u['user_id'] != $current_uid): ?>
                                        <div style="display: flex; gap: 5px;">
                                            <form method="post" action="user_action.php" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $u['is_active']; ?>">
                                                <?php if ($u['is_active']): ?>
                                                    <button type="submit" name="toggle_status" class="btn btn-sm"
                                                        style="background:#ffebee; color:#c62828; border-color:#ef9a9a;">Deactivate</button>
                                                <?php else: ?>
                                                    <button type="submit" name="toggle_status" class="btn btn-sm"
                                                        style="background:#e8f5e9; color:#2e7d32; border-color:#a5d6a7;">Activate</button>
                                                <?php endif; ?>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                style="background: #e3f2fd; color: #1976d2;"
                                                onclick='openPasswordModal(<?php echo $u['user_id']; ?>, "<?php echo addslashes($u['username']); ?>")'>PW</button>
                                            <?php if ($u['role'] === 'user'): ?>
                                                <button type="button" class="btn btn-sm btn-secondary"
                                                    style="background: #f5f5f5; color: #333;"
                                                    onclick='openNotesModal(<?php echo $u['user_id']; ?>, "<?php echo addslashes($u['username']); ?>")'>Notes</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="display: flex; gap: 5px; align-items: center;">
                                            <span style="color:#999; font-size: 12px;">(You)</span>
                                            <?php if ($u['role'] === 'user'): ?>
                                                <button type="button" class="btn btn-sm btn-secondary"
                                                    style="background: #f5f5f5; color: #333;"
                                                    onclick='openNotesModal(<?php echo $u['id']; ?>, "<?php echo addslashes($u['username']); ?>")'>Notes</button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #777;">No users found matching
                                your criteria.</td>
                        </tr>
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
                        <a href="?<?php echo $base_query; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary"
                            style="padding: 5px 10px;">&laquo; Prev</a>
                    <?php endif; ?>

                    <span style="margin: 0 10px; font-weight: bold;">Page <?php echo $page; ?> of
                        <?php echo $total_pages; ?></span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo $base_query; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary"
                            style="padding: 5px 10px;">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    </div>

    <!-- Password Modal -->
    <div id="pwModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Edit Password: <span id="pwModalUsername"></span></h3>
                <span class="close-modal" onclick="closeModal('pwModal')">&times;</span>
            </div>
            <form action="user_action.php" method="POST"
                onsubmit="return confirm('Are you sure you want to update the password for ' + document.getElementById('pwModalUsername').textContent + '?');">
                <input type="hidden" name="user_id" id="pwModalUid">
                <div style="margin-bottom: 15px;">
                    <label>New Password:</label>
                    <input type="text" name="new_password" required
                        style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc;">
                </div>
                <div style="text-align: right;">
                    <button type="submit" name="admin_update_password" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notes Modal -->
    <div id="notesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Notes for <span id="notesModalUsername"></span></h3>
                <span class="close-modal" onclick="closeModal('notesModal')">&times;</span>
            </div>

            <div style="margin-bottom: 15px; display: flex; gap: 10px;">
                <input type="text" id="notesSearch" placeholder="Search notes..."
                    style="flex: 1; padding: 8px; border: 1px solid #ccc;" oninput="fetchUserNotes()">
                <select id="notesStatus" style="padding: 8px; border: 1px solid #ccc;" onchange="fetchUserNotes()">
                    <option value="all">All Status</option>
                    <option value="pinned">Pinned</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div id="notesContainer" style="min-height: 200px;">
                <!-- Content loaded via AJAX -->
                <p style="text-align: center; color: #999; padding: 20px;">Loading notes...</p>
            </div>
        </div>
    </div>

    <!-- Migration Preview Modal -->
    <div id="migrationPreviewModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Migration Preview</h3>
                <span class="close-modal" onclick="closeModal('migrationPreviewModal')">&times;</span>
            </div>

            <div id="migrationPreviewContent" style="min-height: 100px; margin-bottom: 20px;">
                <p style="text-align: center; color: #999; padding: 20px;">Loading preview...</p>
            </div>

            <div id="migrationProgress" style="display: none; margin-bottom: 20px;">
                <div style="width: 100%; background: #eee; height: 10px; border-radius: 5px; overflow: hidden;">
                    <div id="migrationProgressBar"
                        style="width: 0%; height: 100%; background: #1976d2; transition: width 0.3s;"></div>
                </div>
                <p id="migrationStatus" style="font-size: 12px; margin-top: 5px; color: #666; text-align: center;">
                    Migrating...</p>
            </div>

            <div id="migrationFooter" style="text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" style="background: #eee; color: #333;"
                    onclick="closeModal('migrationPreviewModal')">Cancel</button>
                <button type="button" id="confirmMigrationBtn" class="btn btn-primary"
                    onclick="performMigration()">Confirm Migration</button>
            </div>
        </div>
    </div>

    <script>
        let currentNotesUid = 0;

        function openPasswordModal(uid, username) {
            document.getElementById('pwModalUid').value = uid;
            document.getElementById('pwModalUsername').textContent = username;
            document.getElementById('pwModal').style.display = 'flex';
        }

        function openNotesModal(uid, username) {
            currentNotesUid = uid;
            document.getElementById('notesModalUsername').textContent = username;
            document.getElementById('notesModal').style.display = 'flex';
            document.getElementById('notesSearch').value = '';
            document.getElementById('notesStatus').value = 'all';
            fetchUserNotes();
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        const migrationState = {
            from: false,
            to: false
        };

        function validateMigrationUser(type) {
            const input = document.getElementById(type + '_username');
            const status = document.getElementById(type + '_status');
            const username = input.value.trim();

            if (username === '') {
                status.textContent = '';
                migrationState[type] = false;
                updateMigrateButton();
                return;
            }

            fetch(`ajax_check_user.php?username=${encodeURIComponent(username)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.exists) {
                        status.textContent = '❌ Not found';
                        status.style.color = '#c62828';
                        migrationState[type] = false;
                    } else if (data.role === 'admin') {
                        status.textContent = '❌ Admin accounts invalid';
                        status.style.color = '#c62828';
                        migrationState[type] = false;
                    } else {
                        status.textContent = '✅ Valid';
                        status.style.color = '#2e7d32';
                        migrationState[type] = true;
                    }
                    updateMigrateButton();
                });
        }

        function updateMigrateButton() {
            const btn = document.getElementById('migrate_btn');
            if (migrationState.from && migrationState.to) {
                btn.disabled = false;
                btn.style.opacity = '1';
            } else {
                btn.disabled = true;
                btn.style.opacity = '0.5';
            }
        }

        function openMigrationPreview() {
            const from = document.getElementById('from_username').value;
            const to = document.getElementById('to_username').value;

            document.getElementById('migrationPreviewContent').innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Loading preview...</p>';
            document.getElementById('migrationProgress').style.display = 'none';
            document.getElementById('migrationFooter').style.display = 'flex';
            document.getElementById('confirmMigrationBtn').disabled = true;

            document.getElementById('migrationPreviewModal').style.display = 'flex';

            fetch(`ajax_migration_preview.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('migrationPreviewContent').innerHTML = html;
                    const canMigrate = document.getElementById('can_migrate').value === '1';
                    document.getElementById('confirmMigrationBtn').disabled = !canMigrate;
                });
        }

        function performMigration() {
            const from = document.getElementById('from_username').value;
            const to = document.getElementById('to_username').value;
            const progress = document.getElementById('migrationProgress');
            const footer = document.getElementById('migrationFooter');
            const bar = document.getElementById('migrationProgressBar');
            const status = document.getElementById('migrationStatus');

            footer.style.display = 'none';
            progress.style.display = 'block';
            bar.style.width = '30%';
            status.textContent = 'Migrating records...';

            const formData = new FormData();
            formData.append('from_username', from);
            formData.append('to_username', to);

            fetch('ajax_perform_migration.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        bar.style.width = '100%';
                        bar.style.background = '#2e7d32';
                        status.textContent = data.message;
                        status.style.color = '#2e7d32';
                        status.style.fontWeight = 'bold';

                        // Add a "Done" button
                        const doneBtn = document.createElement('button');
                        doneBtn.className = 'btn';
                        doneBtn.style.background = '#2e7d32';
                        doneBtn.style.color = '#fff';
                        doneBtn.style.marginTop = '10px';
                        doneBtn.textContent = 'Close & Refresh';
                        doneBtn.onclick = () => window.location.reload();
                        progress.appendChild(doneBtn);
                    } else {
                        bar.style.width = '100%';
                        bar.style.background = '#c62828';
                        status.textContent = 'Error: ' + data.message;
                        status.style.color = '#c62828';

                        setTimeout(() => {
                            footer.style.display = 'flex';
                            progress.style.display = 'none';
                        }, 3000);
                    }
                })
                .catch(err => {
                    status.textContent = 'Connection error.';
                    status.style.color = '#c62828';
                    footer.style.display = 'flex';
                });
        }

        function fetchUserNotes() {
            const search = document.getElementById('notesSearch').value;
            const status = document.getElementById('notesStatus').value;
            const container = document.getElementById('notesContainer');

            container.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Loading notes...</p>';

            fetch(`ajax_notes.php?uid=${currentNotesUid}&q=${encodeURIComponent(search)}&status=${status}`)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(err => {
                    container.innerHTML = '<p style="text-align: center; color: red; padding: 20px;">Error loading notes.</p>';
                });
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

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