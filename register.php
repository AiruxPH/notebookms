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
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username already exists.";
        } else {
            // Insert (Plain text password)
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $password);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;

                // --- MIGRATION: GUEST TO USER ---
                // Move session notes to this new user ID
                migrate_guest_data_to_db($_SESSION['user_id']);
                // --------------------------------

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Database error.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <title>Register - Notebook</title>
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
    <div class="auth-container">
        <h2>Register</h2>
        <?php if ($error): ?>
            <div style="color: red; margin-bottom: 10px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="username" class="auth-input" placeholder="Username" required>
            <input type="password" name="password" class="auth-input" placeholder="Password" required>
            <input type="password" name="confirm_password" class="auth-input" placeholder="Confirm Password" required>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
        </form>

        <p style="margin-top: 15px; font-size: 14px;">
            Already have an account? <a href="login.php" style="color: blue; text-decoration: underline;">Login here</a>
        </p>
    </div>
</body>

</html>