<?php
include '../includes/data_access.php';
session_start();

// Protection: Admin Only
if (!is_admin()) {
    header("Location: ../login.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Plain text
    $role = $_POST['role'];
    $security_word = isset($_POST['security_word']) ? trim($_POST['security_word']) : '';

    // Basic Validation
    if (empty($username) || empty($password)) {
        $error = "Username and Password are required.";
    } else {
        // Check if username exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username already exists.";
        } else {
            // Insert User
            $role_esc = mysqli_real_escape_string($conn, $role);
            // Default active
            $is_active = 1;

            // Security Word Column Logic
            // If security word is provided, we set it. Otherwise logic is handled by specific column updates?
            // INSERT query supports all.

            $sw_val = !empty($security_word) ? "'" . mysqli_real_escape_string($conn, $security_word) . "'" : "NULL";
            $sw_set = !empty($security_word) ? 1 : 0;

            // Plain text password
            // We use SQL directly because data_access doesn't have a generic create_user function with role support yet
            $sql = "INSERT INTO users (username, password, role, is_active, security_word, security_word_set) 
                    VALUES ('$username', '$password', '$role_esc', 1, $sw_val, $sw_set)";

            if (mysqli_query($conn, $sql)) {
                $_SESSION['flash'] = ['message' => 'User created successfully!', 'type' => 'success'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../favicon.png" type="image/png">
    <title>Add User - Admin Notebook</title>
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
        <div
            style="max-width: 500px; margin: 50px auto; background: #fff; padding: 30px; border: 1px solid #ccc; box-shadow: 2px 2px 5px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0;">Add New User</h2>

            <?php if ($error): ?>
                <div
                    style="color: red; margin-bottom: 15px; padding: 10px; background: #ffebee; border: 1px solid #ef9a9a;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Username:</label>
                    <input type="text" name="username" required
                        style="width: 100%; padding: 10px; border: 1px solid #ccc; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Password:</label>
                    <input type="password" name="password" required
                        style="width: 100%; padding: 10px; border: 1px solid #ccc; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Role:</label>
                    <select name="role"
                        style="width: 100%; padding: 10px; border: 1px solid #ccc; box-sizing: border-box;">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Security Word
                        (Optional):</label>
                    <input type="text" name="security_word" placeholder="For password recovery"
                        style="width: 100%; padding: 10px; border: 1px solid #ccc; box-sizing: border-box;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Create User</button>
                    <a href="dashboard.php" class="btn btn-secondary"
                        style="text-decoration: none; text-align: center; border: 1px solid #ccc; padding: 10px 20px;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>