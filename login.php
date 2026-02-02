<?php
include 'includes/data_access.php'; // For migration function
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Plain text as requested

    // Check user
    $sql = "SELECT id, username, password FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        // Verify Password (Plain text comparison)
        if ($password === $row['password']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];

            // --- MIGRATION: GUEST TO USER ---
            // If user had guest notes before logging in, move them now
            migrate_guest_data_to_db($_SESSION['user_id']);
            // --------------------------------

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="favicon.png" type="image/png">
    <title>Login - Notebook</title>
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
    </style>
</head>

<body>
    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR</a></h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="index.php">Notes</a>
                <a href="categories.php">Categories</a>
                <?php if (is_logged_in()): ?>
                    <a href="logout.php" style="color: #c62828;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color: #2e7d32; border-color: #2e7d32;">Login</a>
                <?php endif; ?>
                <a href="about.php">About</a>
                <a href="contact.php">Contact Us</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="auth-container">
            <h2>Login</h2>
            <?php if ($error): ?>
                <div style="color: red; margin-bottom: 10px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="text" name="username" class="auth-input" placeholder="Username" required>
                <input type="password" name="password" class="auth-input" placeholder="Password" required>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>

            <p style="margin-top: 15px; font-size: 14px;">
                No account? <a href="register.php" style="color: blue; text-decoration: underline;">Register here</a>
            </p>
        </div>
    </div>
</body>

</html>