<?php
include 'includes/data_access.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user = get_user_by_username($_SESSION['username']);
$uid = $user['id'];
$username = $user['username'];
$join_date = date("F j, Y", strtotime($user['date_created']));

// Fetch Stats (Note Count)
$notes_res = get_notes(['limit' => 1000]); // Re-using get_notes but just counting
$note_count = count($notes_res);

// POST Handlers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. Update Username
    if (isset($_POST['update_username_action'])) {
        $new_name = trim($_POST['new_username']);
        if (!empty($new_name)) {
            if (update_username($uid, $new_name)) {
                $_SESSION['flash'] = ['message' => "Username updated to '$new_name'!", 'type' => 'success'];
                $username = $new_name; // Reflect immediate change
            } else {
                $_SESSION['flash'] = ['message' => "Error: Username '$new_name' is already taken.", 'type' => 'error'];
            }
        }
        header("Location: profile.php");
        exit();
    }

    // 2. Change Password
    if (isset($_POST['change_password'])) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass === $confirm_pass) {
            if (!empty($new_pass)) {
                if (update_password($username, $new_pass)) {
                    $_SESSION['flash'] = ['message' => "Password updated successfully!", 'type' => 'success'];
                } else {
                    $_SESSION['flash'] = ['message' => "Error updating password.", 'type' => 'error'];
                }
            } else {
                $_SESSION['flash'] = ['message' => "Password cannot be empty.", 'type' => 'error'];
            }
        } else {
            $_SESSION['flash'] = ['message' => "Passwords do not match.", 'type' => 'error'];
        }
        header("Location: profile.php");
        exit();
    }

    // 3. Update Security Word
    if (isset($_POST['update_security_word'])) {
        $word = $_POST['security_word'];
        if (!empty($word)) {
            if (set_security_word($uid, $word)) {
                $_SESSION['flash'] = ['message' => "Security Word updated!", 'type' => 'success'];
            } else {
                $_SESSION['flash'] = ['message' => "Error updating Security Word.", 'type' => 'error'];
            }
        } else {
            $_SESSION['flash'] = ['message' => "Security Word cannot be empty.", 'type' => 'error'];
        }
        header("Location: profile.php");
        exit();
    }
}

// Check for Flash Message
if (isset($_SESSION['flash'])) {
    $msg = $_SESSION['flash']['message'];
    $msg_type = $_SESSION['flash']['type'];
    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="favicon.png" type="image/png">
    <title>My Profile - Notebook</title>
    <style>
        .profile-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            align-items: start;
        }

        @media (max-width: 768px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }

        .profile-card {
            background: #fff;
            border: 1px solid #ccc;
            padding: 30px;
            text-align: center;
            box-shadow: 2px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #eee;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #888;
            border: 2px solid #ddd;
        }

        .stat-box {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
        }

        .settings-section {
            background: #fff;
            border: 1px solid #ddd;
            padding: 25px;
            margin-bottom: 20px;
        }

        .section-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            color: #444;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 14px;
            color: #555;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .check-status {
            font-size: 12px;
            margin-top: 5px;
            font-weight: bold;
            display: none;
        }

        .status-avail {
            color: green;
        }

        .status-taken {
            color: red;
        }

        .status-loading {
            color: orange;
        }
    </style>
</head>

<body>

    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR</a></h1>
            <input type="checkbox" id="menu-toggle" class="menu-toggle">
            <label for="menu-toggle" class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </label>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="index.php">Notes</a>
                <a href="categories.php">Categories</a>
                <?php if (is_logged_in()): ?>
                    <a href="profile.php" style="background: #fff;">Profile</a>
                    <a href="logout.php" style="color: #c62828;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color: #2e7d32;">Login</a>
                <?php endif; ?>
                <a href="about.php">About</a>
                <a href="contact.php">Contact Us</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Toast Container -->
        <div id="toast-overlay" class="toast-overlay">
            <div id="toast-message" class="toast-message"></div>
        </div>

        <h2 style="margin-bottom: 20px;">My Profile</h2>

        <div class="profile-layout">

            <!-- Left: Sidebar -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <h3 style="margin: 0; font-size: 22px;"><?php echo htmlspecialchars($username); ?></h3>
                <div style="color: #888; font-size: 14px; margin-top: 5px;">Member</div>

                <div class="stat-box">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $note_count; ?></div>
                        <div class="stat-label">Notes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo date("Y"); ?></div>
                        <div class="stat-label">Since</div>
                    </div>
                </div>

                <div style="margin-top: 20px; font-size: 12px; color: #999;">
                    Joined on <?php echo $join_date; ?>
                </div>
            </div>

            <!-- Right: Settings -->
            <div>

                <!-- Account Section -->
                <div class="settings-section">
                    <div class="section-header">Account Details</div>
                    <form method="post">
                        <input type="hidden" name="update_username_action" value="1">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <div style="display: flex; gap: 10px;">
                                <div style="flex-grow: 1;">
                                    <input type="text" id="username_input" name="new_username" class="form-input"
                                        value="<?php echo htmlspecialchars($username); ?>" required autocomplete="off">
                                    <div id="username_status" class="check-status">Checking...</div>
                                </div>
                                <button type="submit" id="save_username_btn" class="btn btn-primary"
                                    disabled>Update</button>
                            </div>
                            <div style="font-size: 11px; color: #999; margin-top: 5px;">
                                Changing your username requires a unique name.
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Security Section -->
                <div class="settings-section">
                    <div class="section-header">Security Settings</div>

                    <form method="post" style="margin-bottom: 30px;">
                        <h4 style="margin: 0 0 15px 0;">Change Password</h4>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-input" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-secondary">Update Password</button>
                    </form>

                    <hr style="border: 0; border-top: 1px dashed #ddd; margin: 25px 0;">

                    <form method="post">
                        <h4 style="margin: 0 0 15px 0;">Security Word</h4>
                        <div class="form-group">
                            <label class="form-label">Recovery Word</label>
                            <input type="text" name="security_word" class="form-input"
                                placeholder="e.g. Pet's name, favorite city" required>
                        </div>
                        <button type="submit" name="update_security_word" class="btn btn-secondary">Set Security
                            Word</button>
                    </form>
                </div>

            </div>

        </div>
    </div>

    <script>
        // Username Availability Checker
        const usernameInput = document.getElementById('username_input');
        const statusDiv = document.getElementById('username_status');
        const saveBtn = document.getElementById('save_username_btn');
        const currentUsername = "<?php echo addslashes($username); ?>";
        let typingTimer;

        usernameInput.addEventListener('input', function () {
            clearTimeout(typingTimer);
            const val = this.value.trim();

            if (val === currentUsername) {
                statusDiv.style.display = 'none';
                saveBtn.disabled = true;
                return;
            }

            if (val.length < 3) {
                statusDiv.textContent = "Too short (min 3 chars)";
                statusDiv.className = "check-status status-taken";
                statusDiv.style.display = 'block';
                saveBtn.disabled = true;
                return;
            }

            // Show Loading
            statusDiv.textContent = "Checking availability...";
            statusDiv.className = "check-status status-loading";
            statusDiv.style.display = 'block';
            saveBtn.disabled = true;

            typingTimer = setTimeout(() => {
                checkAvailability(val);
            }, 500);
        });

        async function checkAvailability(name) {
            try {
                const response = await fetch(`ajax_check_username_availability.php?username=${encodeURIComponent(name)}`);
                const data = await response.json();

                if (data.available) {
                    statusDiv.textContent = "✔ Username is available";
                    statusDiv.className = "check-status status-avail";
                    saveBtn.disabled = false;
                } else {
                    statusDiv.textContent = "✖ " + data.message;
                    statusDiv.className = "check-status status-taken";
                    saveBtn.disabled = true;
                }
            } catch (e) {
                console.error("Error checking username", e);
            }
        }

        // Toast Logic
        const toastOverlay = document.getElementById('toast-overlay');
        const toastMessage = document.getElementById('toast-message');

        function showToast(msg, type) {
            toastMessage.textContent = msg;
            toastMessage.className = "toast-message " + (type === 'error' ? 'toast-error' : 'toast-success');
            void toastMessage.offsetWidth;
            toastOverlay.style.display = 'flex';
            requestAnimationFrame(() => { toastMessage.classList.add('show'); });
            setTimeout(() => {
                toastMessage.classList.remove('show');
                setTimeout(() => { toastOverlay.style.display = 'none'; }, 300);
            }, 3000);
        }

        <?php if (isset($msg)): ?>
            showToast("<?php echo addslashes($msg); ?>", "<?php echo $msg_type; ?>");
        <?php endif; ?>
    </script>
</body>

</html>