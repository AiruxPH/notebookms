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

// POST Handlers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. Change Password
    if (isset($_POST['change_password'])) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass === $confirm_pass) {
            if (!empty($new_pass)) {
                // Determine user role to check permissions/policy (currently open)
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

    // 2. Update Security Word
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>My Profile - Notebook</title>
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
                <a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="index.php"><i class="fa-solid fa-note-sticky"></i> Notes</a>
                <a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a>
                <a href="profile.php" style="background: #fff;"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="logout.php" style="color: #c62828;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                <a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a>
                <a href="contact.php"><i class="fa-solid fa-envelope"></i> Contact Us</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Toast Container -->
        <div id="toast-overlay" class="toast-overlay">
            <div id="toast-message" class="toast-message"></div>
        </div>

        <div class="dashboard-section" style="max-width: 600px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 64px; color: #555; margin-bottom: 10px;">
                    <i class="fa-solid fa-circle-user"></i>
                </div>
                <h2 style="margin: 0;">
                    <?php echo htmlspecialchars($username); ?>
                </h2>
                <div style="color: #777; font-size: 14px; margin-top: 5px;">Member since
                    <?php echo $join_date; ?>
                </div>
            </div>

            <div style="border-top: 1px solid #eee; padding-top: 20px;">
                <h3 style="margin-top: 0; color: #333;"><i class="fa-solid fa-lock"></i> Change Password</h3>
                <form method="post" action="profile.php"
                    style="background: #fdfdad; padding: 20px; border: 1px solid #d1d190; border-radius: 4px;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">New Password:</label>
                        <input type="password" name="new_password" required
                            style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Confirm New
                            Password:</label>
                        <input type="password" name="confirm_password" required
                            style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary" style="width: 100%;">Update
                        Password</button>
                </form>
            </div>

            <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                <h3 style="margin-top: 0; color: #333;"><i class="fa-solid fa-shield-halved"></i> Security Word</h3>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                    This word is used to recover your account if you forget your password.
                </p>
                <form method="post" action="profile.php"
                    style="background: #f9f9f9; padding: 20px; border: 1px solid #e0e0e0; border-radius: 4px;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Update Security
                            Word:</label>
                        <input type="text" name="security_word" placeholder="Enter a secret word" required
                            style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    </div>
                    <button type="submit" name="update_security_word" class="btn btn-secondary"
                        style="width: 100%;">Save Security Word</button>
                </form>
            </div>

        </div>
    </div>

    <script>
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