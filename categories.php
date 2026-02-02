<?php
include 'includes/data_access.php';
// session_start(); is handled in db/data_access

// Handle Category Add/Delete (Same logic as dashboard but redirected here)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_cat'])) {
    if ($_POST['action_cat'] == 'add') {
        $c_name = trim($_POST['cat_name'] ?? '');
        $c_color = $_POST['cat_color'] ?? '#ffffff';
        if ($c_name) {
            $res = add_category($c_name, $c_color);
            if ($res === 1) {
                $_SESSION['flash'] = ['message' => 'Category Added', 'type' => 'success'];
            } elseif ($res === -1) {
                $_SESSION['flash'] = ['message' => 'Limit reached (Max 20 categories)', 'type' => 'error'];
            } else {
                $_SESSION['flash'] = ['message' => 'Error adding category (Duplicate?)', 'type' => 'error'];
            }
        }
    } elseif ($_POST['action_cat'] == 'delete') {
        $c_name = trim($_POST['cat_name'] ?? '');
        if (delete_category($c_name)) {
            $_SESSION['flash'] = ['message' => 'Category Deleted', 'type' => 'success'];
        } else {
            $_SESSION['flash'] = ['message' => 'Error deleting category', 'type' => 'error'];
        }
    }
    header("Location: categories.php");
    exit();
}

$categories = get_categories();
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <title>Manage Categories - Notebook</title>
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

        <div class="dashboard-section">
            <h2>Manage Categories</h2>
            <p>Organize your notes with custom categories and colors.</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                <!-- Add Category Form -->
                <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
                    <h3>Add New Category</h3>
                    <form method="post">
                        <input type="hidden" name="action_cat" value="add">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px;">Category Name:</label>
                            <input type="text" name="cat_name" required placeholder="e.g. Travel, Recipes"
                                style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 2px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px;">Color Badge:</label>
                            <input type="color" name="cat_color" value="#e0f7fa"
                                style="width: 100%; height: 45px; cursor: pointer; border: 1px solid #ccc; background: #fff;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Create Category</button>
                    </form>
                </div>

                <!-- Category List -->
                <div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
                    <h3>Your Categories</h3>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php
                        $defaults_names = ['General', 'Personal', 'Work', 'Study', 'Ideas'];
                        foreach ($categories as $c):
                            $cname = htmlspecialchars($c['name']);
                            $ccolor = htmlspecialchars($c['color']);
                            $is_custom = isset($c['user_id']) && $c['user_id'] != 0;
                            $is_custom_guest = !in_array($c['name'], $defaults_names);
                            $can_delete = is_logged_in() ? $is_custom : $is_custom_guest;
                            ?>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; border-bottom: 1px solid #eee; background: #fafafa; margin-bottom: 5px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span
                                        style="width: 20px; height: 20px; background-color: <?php echo $ccolor; ?>; border: 1px solid #999; border-radius: 3px;"></span>
                                    <span style="font-weight: bold;">
                                        <?php echo $cname; ?>
                                    </span>
                                    <?php if (!$can_delete): ?>
                                        <span
                                            style="font-size: 10px; color: #888; background: #eee; padding: 2px 5px; border-radius: 3px;">DEFAULT</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($can_delete): ?>
                                    <form method="post" style="margin: 0;"
                                        onsubmit="return confirm('Delete this category? Notes using it will still exist but might lose their color association.');">
                                        <input type="hidden" name="action_cat" value="delete">
                                        <input type="hidden" name="cat_name" value="<?php echo $cname; ?>">
                                        <button type="submit" class="btn"
                                            style="padding: 5px 10px; color: red; border-color: red; box-shadow: 1px 1px 0 red; font-size: 12px;">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        <?php
        if (isset($_SESSION['flash'])) {
            $msg = $_SESSION['flash']['message'];
            $msg_type = $_SESSION['flash']['type'];
            unset($_SESSION['flash']);
            echo "showToast('" . addslashes($msg) . "', '$msg_type');";
        }
        ?>
    </script>
</body>

</html>