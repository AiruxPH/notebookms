<?php
include 'includes/data_access.php';
// session_start(); is handled in db/data_access
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <title>Dashboard - Notebook</title>
</head>

<body>

    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR</a></h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="index.php">Notes</a>
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

        <!-- Welcome / Announcements Section -->
        <div class="dashboard-section announcement-box">
            <h2>📢 Developer Announcements</h2>
            <p>Welcome to <strong>Notebook-BAR v1.0</strong>! We have recently updated our features:</p>
            <ul>
                <li>✨ <strong>Dynamic Categories:</strong> Create custom categories with your own colors!</li>
                <li>🚀 <strong>Guest Mode:</strong> Try the app without logging in (your data saves automatically when
                    you register).</li>
                <li>📌 <strong>Pinning & Archiving:</strong> Keep your workspace organized.</li>
                <li>📊 <strong>Stats:</strong> Real-time word and character counts.</li>
            </ul>
        </div>

        <div class="dashboard-grid-layout">
            <!-- Left Column: Categories & Tools -->
            <div class="dashboard-sidebar">
                <h3>Quick Categories</h3>
                <div class="category-list" style="max-height: 300px; overflow-y: auto;">
                    <?php
                    $quick_cats = get_categories();
                    foreach ($quick_cats as $c) {
                        $cname = htmlspecialchars($c['name']);
                        $ccolor = htmlspecialchars($c['color']);
                        // Add a little color dot
                        echo "<a href='index.php?cat={$c['id']}' class='cat-btn' style='border-left: 5px solid $ccolor;'>$cname</a>";
                    }
                    ?>
                </div>

                <?php
                // Handle Category Add/Delete
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_cat'])) {
                    if ($_POST['action_cat'] == 'add') {
                        $c_name = trim($_POST['cat_name']);
                        $c_color = $_POST['cat_color'];
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
                        $c_name = trim($_POST['cat_name']);
                        if (delete_category($c_name)) {
                            $_SESSION['flash'] = ['message' => 'Category Deleted', 'type' => 'success'];
                        } else {
                            $_SESSION['flash'] = ['message' => 'Error deleting category', 'type' => 'error'];
                        }
                    }
                    // Redirect to avoid resubmission
                    header("Location: dashboard.php");
                    exit();
                }
                ?>

                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                    <a href="notepad.php" class="btn btn-primary" style="text-decoration: none; text-align: center;">+
                        New Note</a>
                    <div style="display: flex; gap: 5px;">
                        <button onclick="document.getElementById('cat-modal').style.display='block'"
                            class="btn btn-secondary" style="border: 1px solid #ccc; flex: 1;">Categories</button>
                        <a href="index.php" class="btn btn-secondary"
                            style="text-decoration: none; text-align: center; flex: 1; border: 1px solid #ccc;">All
                            Notes</a>
                    </div>
                </div>

                <!-- Category Modal -->
                <div id="cat-modal"
                    style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
                    <div
                        style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 300px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                        <span onclick="document.getElementById('cat-modal').style.display='none'"
                            style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                        <h3>Add Category</h3>
                        <form method="post">
                            <input type="hidden" name="action_cat" value="add">
                            <label style="display: block; margin-bottom: 5px;">Name:</label>
                            <input type="text" name="cat_name" required
                                style="width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box;">

                            <label style="display: block; margin-bottom: 5px;">Color:</label>
                            <input type="color" name="cat_color" value="#e0f7fa"
                                style="width: 100%; height: 40px; padding: 2px; margin-bottom: 15px; border: none; cursor: pointer;">

                            <button type="submit" class="btn btn-primary" style="width: 100%;">Add Category</button>
                        </form>

                        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

                        <h4>Your Categories</h4>
                        <div style="max-height: 150px; overflow-y: auto;">
                            <?php
                            $my_cats = get_categories();
                            foreach ($my_cats as $mc):
                                $is_custom = isset($mc['user_id']) && $mc['user_id'] != 0;
                                // For guests, we marked user_id=0 for custom in session, but let's assume session ones are deletable.
                                // In data_access for guest, we set user_id=0. We need a way to distinguish.
                                // Actually, guests can delete anything they added to session. 
                                // But our loop in data_access returns defaults first.
                                // Let's simplify: If it's NOT in the defaults list, it's custom.
                                $defaults_names = ['General', 'Personal', 'Work', 'Study', 'Ideas'];
                                $is_custom_guest = !in_array($mc['name'], $defaults_names);

                                $can_delete = is_logged_in() ? $is_custom : $is_custom_guest;
                                ?>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f0f0f0;">
                                    <span style="display: flex; align-items: center; gap: 5px;">
                                        <span
                                            style="width: 15px; height: 15px; background-color: <?php echo $mc['color']; ?>; border: 1px solid #ccc; display: inline-block; border-radius: 3px;"></span>
                                        <?php echo htmlspecialchars($mc['name']); ?>
                                    </span>
                                    <?php if ($can_delete): ?>
                                        <form method="post" style="margin: 0;">
                                            <input type="hidden" name="action_cat" value="delete">
                                            <input type="hidden" name="cat_name"
                                                value="<?php echo htmlspecialchars($mc['name']); ?>">
                                            <button type="submit"
                                                style="background: none; border: none; color: red; cursor: pointer; font-weight: bold;"
                                                title="Delete">&times;</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- Sidebar Actions moved to top -->
            </div>

            <!-- Right Column: Pinned Notes -->
            <div class="dashboard-main">
                <h3>📌 Pinned Notes</h3>
                <div class="note-grid">
                    <div class="note-grid">
                        <?php
                        // Fetch Categories for Colors
                        $all_cats = get_categories();
                        $cat_colors = [];
                        foreach ($all_cats as $c) {
                            $cat_colors[$c['name']] = $c['color'];
                        }

                        // Fetch ALL notes (get_notes handles archive filter, we want active)
                        $filters = ['archived' => 0];
                        $all_notes = get_notes($filters);

                        // Filter for PINNED only, limit 4
                        $pinned_notes = [];
                        foreach ($all_notes as $n) {
                            if ($n['is_pinned'] == 1) {
                                $pinned_notes[] = $n;
                            }
                            if (count($pinned_notes) >= 4)
                                break;
                        }

                        if (count($pinned_notes) > 0) {
                            foreach ($pinned_notes as $row) {
                                $nid = $row['id'];
                                $dtitle = htmlspecialchars($row['title']);
                                $dcat = htmlspecialchars($row['category']);
                                $ddatl = date("M j, H:i", strtotime($row['date_last']));

                                // Determine Color
                                $bg_color = isset($cat_colors[$dcat]) ? $cat_colors[$dcat] : '#ffffff';

                                // Strip tags regarding block elements, but allow inline styles
                                // Use CSS line-clamp for truncation
                                $raw_text = $row['text'] ?? '';
                                $raw_text = str_replace('<li>', ' &bull; ', $raw_text);
                                $raw_text = str_replace(['</p>', '</div>', '<br>', '<br/>'], ' ', $raw_text);

                                $clean_text = strip_tags($raw_text, '<b><i><u><strong><em>');
                                $dtxt = $clean_text;

                                if (empty(trim($dtxt)))
                                    $dtxt = "<em>No content...</em>";

                                echo "<a href='notepad.php?id=$nid' class='note-card' style='background-color: $bg_color;'>";
                                echo "<div class='note-title'>$dtitle</div>";
                                echo "<div class='note-meta'>$dcat &bull; $ddatl</div>";
                                echo "<div class='note-preview'>$dtxt</div>";
                                echo "</a>";
                            }
                        } else {
                            echo "<div style='color: #777; font-style: italic;'>No pinned notes yet. Pin a note to see it here!</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

        </div>

</body>

</html>