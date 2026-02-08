<?php
include 'includes/data_access.php';
// session_start(); handled in db.php via data_access.php

$step = 1;
$error = "";

if (isset($_SESSION['reset_param_username'])) {
    $reset_username = $_SESSION['reset_param_username'];
    $step = 2;
}

if (isset($_SESSION['reset_verified']) && $_SESSION['reset_verified'] === true) {
    // If verified, we are at step 3 (Change Password)
    // We keep username from session
    $step = 3;
}

// Handle Form Posts
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // STEP 1: VERIFY USERNAME
        if (isset($_POST['verify_username'])) {
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            // We check for 'security_word_set' flag
            $check = mysqli_query($conn, "SELECT user_id, security_word_set FROM users WHERE username='$username'");

            if (!$check) {
                throw new Exception("SQL Error: " . mysqli_error($conn));
            } elseif ($row = mysqli_fetch_assoc($check)) {
                if ($row['security_word_set'] == 0) {
                    $error = "This account does not have a security word set. Please contact admin.";
                } else {
                    $_SESSION['reset_param_username'] = $username;
                    $step = 2;
                }
            } else {
                $error = "Username not found.";
            }
        }

        // STEP 2: VERIFY SECURITY WORD
        if (isset($_POST['verify_word'])) {
            $word = $_POST['security_word'];
            $username = isset($_SESSION['reset_param_username']) ? $_SESSION['reset_param_username'] : '';

            if (empty($username)) {
                $step = 1;
                $error = "Session expired. Please start again.";
            } else {
                if (check_security_word($username, $word)) {
                    $_SESSION['reset_verified'] = true;
                    $step = 3;
                } else {
                    $error = "Incorrect security word.";
                    $step = 2; // Stay here
                }
            }
        }

        // STEP 3: RESET PASSWORD
        if (isset($_POST['reset_password'])) {
            $p1 = $_POST['new_password'];
            $p2 = $_POST['confirm_password'];
            $username = isset($_SESSION['reset_param_username']) ? $_SESSION['reset_param_username'] : '';

            if (empty($username)) {
                $step = 1;
                $error = "Session expired.";
            } elseif ($p1 !== $p2) {
                $error = "Passwords do not match.";
                $step = 3;
            } else {
                if (update_password($username, $p1)) {
                    // Done! Clean up session
                    unset($_SESSION['reset_param_username']);
                    unset($_SESSION['reset_verified']);

                    $_SESSION['flash'] = ['message' => 'Password changed successfully! Please login.', 'type' => 'success'];
                    header("Location: login.php");
                    exit();
                } else {
                    throw new Exception("Error updating password: " . mysqli_error($conn));
                }
            }
        }

        // BACK
        if (isset($_POST['back_step_1'])) {
            unset($_SESSION['reset_param_username']);
            $step = 1;
        }
    } catch (Throwable $e) {
        $error = "System Error: " . $e->getMessage();
        // Log it if possible, here we just show it to user for debug
    }
}

// If navigating directly or cancelling, we might want a reset link
if (isset($_GET['reset'])) {
    unset($_SESSION['reset_param_username']);
    unset($_SESSION['reset_verified']);
    header("Location: forgot_password.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="favicon.png" type="image/png">
    <title>Forgot Password - Notebook</title>
    <style>
        .auth-container {
            max-width: 400px;
            margin: 100px auto;
            background: #fff;
            padding: 30px;
            border: 1px solid #ccc;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .auth-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .step-indicator {
            margin-bottom: 20px;
            font-size: 14px;
            color: #777;
        }

        .step-active {
            font-weight: bold;
            color: #2e7d32;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR</a></h1>

            <!-- Hamburger Button -->
            <button class="hamburger" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Desktop Nav -->
            <nav class="desktop-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="index.php">Notes</a>
                <a href="categories.php">Categories</a>
                <?php if (is_logged_in()): ?>
                    <a href="logout.php" style="color: #c62828;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color: #2e7d32;">Login</a>
                <?php endif; ?>
                <a href="about.php">About</a>
                <a href="contact.php">Contact Us</a>
            </nav>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay"></div>

    <!-- Mobile Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Menu</h3>
            <button class="sidebar-close">&times;</button>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="index.php">Notes</a>
            <a href="categories.php">Categories</a>
            <?php if (is_logged_in()): ?>
                <a href="logout.php" style="color: #c62828;">Logout</a>
            <?php else: ?>
                <a href="login.php" style="color: #2e7d32;">Login</a>
            <?php endif; ?>
            <a href="about.php">About</a>
            <a href="contact.php">Contact Us</a>
        </nav>
    </div>

    <script src="js/sidebar.js"></script>
    <div class="container">
        <div class="auth-container">
            <h2>Reset Password</h2>

            <div class="step-indicator">
                <span class="<?php echo $step == 1 ? 'step-active' : ''; ?>">User</span> &gt;
                <span class="<?php echo $step == 2 ? 'step-active' : ''; ?>">Security Check</span> &gt;
                <span class="<?php echo $step == 3 ? 'step-active' : ''; ?>">New Password</span>
            </div>

            <?php if ($error): ?>
                <div style="color: red; margin-bottom: 15px; font-weight: bold;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- STEP 1 FORM -->
            <?php if ($step == 1): ?>
                <form method="post">
                    <p style="margin-bottom: 15px;">Enter your username to begin.</p>
                    <input type="text" name="username" class="auth-input" placeholder="Username" required autofocus>
                    <button type="submit" name="verify_username" class="btn btn-primary" style="width: 100%;">Next</button>
                    <div style="margin-top: 15px;">
                        <a href="login.php" style="color: #666; font-size: 13px;">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>

            <!-- STEP 2 FORM -->
            <?php if ($step == 2): ?>
                <form method="post">
                    <p style="margin-bottom: 15px;">
                        Enter your <strong>Security Word</strong> for <strong>
                            <?php echo htmlspecialchars(isset($reset_username) ? $reset_username : $_SESSION['reset_param_username']); ?>
                        </strong>.
                    </p>
                    <input type="text" name="security_word" class="auth-input" placeholder="Security Word" required
                        autocomplete="off">
                    <button type="submit" name="verify_word" class="btn btn-primary" style="width: 100%;">Verify</button>
                    <button type="submit" name="back_step_1" class="btn btn-secondary"
                        style="width: 100%; margin-top: 5px;">Back</button>
                </form>
            <?php endif; ?>

            <!-- STEP 3 FORM -->
            <?php if ($step == 3): ?>
                <form method="post">
                    <p style="margin-bottom: 15px;">Set your new password.</p>
                    <input type="password" name="new_password" class="auth-input" placeholder="New Password" required>
                    <input type="password" name="confirm_password" class="auth-input" placeholder="Confirm New Password"
                        required>
                    <button type="submit" name="reset_password" class="btn btn-primary" style="width: 100%;">Reset
                        Password</button>
                </form>
            <?php endif; ?>

        </div>
    </div>
</body>

</html>