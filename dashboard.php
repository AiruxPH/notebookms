<?php
include 'includes/data_access.php';
// session_start(); is handled in db/data_access
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        <!-- Welcome / Announcements Section -->
        <div class="dashboard-section announcement-box" style="margin-bottom: 20px;">
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
            <!-- Sidebar: Controls & Quick Cats -->
            <div class="dashboard-sidebar">
                <div
                    style="background: #fff; padding: 20px; border: 1px solid #ccc; box-shadow: 2px 2px 0 rgba(0,0,0,0.05); margin-bottom: 20px;">
                    <h3 style="margin-top: 0; margin-bottom: 15px;">Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <a href="notepad.php" class="btn btn-primary"
                            style="text-decoration: none; text-align: center;">+ New Note</a>
                        <div style="display: flex; gap: 8px;">
                            <a href="categories.php" class="btn btn-secondary"
                                style="border: 1px solid #ccc; flex: 1; text-decoration: none; text-align: center; font-size: 13px; padding: 10px 5px;">Categories</a>
                            <a href="index.php" class="btn btn-secondary"
                                style="text-decoration: none; text-align: center; flex: 1; border: 1px solid #ccc; font-size: 13px; padding: 10px 5px;">All
                                Notes</a>
                        </div>
                    </div>
                </div>

                <div
                    style="background: #fdfdad; padding: 20px; border: 1px solid #d1d190; box-shadow: 2px 2px 0 rgba(0,0,0,0.05);">
                    <h3 style="margin-top: 0; margin-bottom: 15px;">Quick Categories</h3>
                    <div class="category-list" style="max-height: 250px; overflow-y: auto;">
                        <?php
                        $quick_cats = get_categories();
                        foreach ($quick_cats as $c) {
                            $cname = htmlspecialchars($c['name']);
                            $ccolor = htmlspecialchars($c['color']);
                            echo "<a href='index.php?cat={$c['id']}' class='cat-btn' style='border-left: 5px solid $ccolor; margin-bottom: 8px; display: block; background: #fff; padding: 8px 12px; text-decoration: none; color: #333; font-size: 14px; border-radius: 0 4px 4px 0;'>$cname</a>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Main: Pinned Notes -->
            <div class="dashboard-main">
                <h3 style="margin-top: 0; margin-bottom: 15px;">📌 Pinned Notes</h3>
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
                            $ddatl = date("g:i A", strtotime($row['date_last']));
                            $ddate = date("M j, Y", strtotime($row['date_last']));

                            // Determine Color
                            $bg_color = isset($cat_colors[$dcat]) ? $cat_colors[$dcat] : '#ffffff';

                            $raw_text = $row['text'] ?? '';
                            $raw_text = str_replace(['</div>', '</p>', '<h1>', '<h2>', '<h3>', '<h4>', '</h5>', '<h6>'], '<br>', $raw_text);
                            $raw_text = str_replace('<li>', '<br>&bull; ', $raw_text);
                            $clean_text = strip_tags($raw_text, '<b><i><u><strong><em><br>');
                            $dtxt = $clean_text;

                            if (empty(trim(strip_tags($dtxt))))
                                $dtxt = "<em>No content...</em>";

                            echo "<a href='notepad.php?id=$nid' class='note-card'>";
                            echo "<div class='category_streak' style='background-color: $bg_color;'><br></div>";
                            echo "<div class='card_wrap'>";
                            echo "<div class='note-title'>$dtitle</div>";
                            echo "<div class='note-preview'>$dtxt</div>";
                            echo "<div class='note-footer'>";
                            echo "<div style='font-weight: bold; margin-bottom: 5px; color: #333;'>$dcat</div>";
                            echo "Updated: $ddate $ddatl</div>";
                            echo "</div>";
                            echo "</a>";
                        }
                    } else {
                        echo "<div class='note-card note-add-card' style='border: 1px dashed #ccc; cursor: default; background: rgba(255,255,255,0.2); width: 100%;'>
                                <div style='text-align: center; color: #777;'>
                                    <div style='font-size: 32px; margin-bottom: 10px;'>📌</div>
                                    <div style='font-weight: bold;'>No pinned notes yet.</div>
                                    <div style='font-size: 12px; margin-top: 5px;'>Pin your favorite notes!</div>
                                </div>
                            </div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Logic Scripts -->
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