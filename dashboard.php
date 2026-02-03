<?php
include 'includes/data_access.php';
// session_start(); is handled in db/data_access
// session_start(); is handled in db/data_access

// Access Control: Redirect Admin to Admin Dashboard
if (is_admin()) {
    header("Location: admin/dashboard.php");
    exit();
}

// Handle Security Word Setup

// Handle Security Word Setup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_security_word_action'])) {
    $word = $_POST['security_word'];
    $uid = get_current_user_id();

    // Simple verification: Only updates if not set or just force update? 
    // Usually only if not set, or we can allow overwrite. 
    // For now, allow overwrite.
    if (!empty($word)) {
        if (set_security_word($uid, $word)) {
            $_SESSION['flash'] = ['message' => 'Security Word saved successfully!', 'type' => 'success'];
        } else {
            $_SESSION['flash'] = ['message' => 'Error saving Security Word.', 'type' => 'error'];
        }
    } else {
        $_SESSION['flash'] = ['message' => 'Security Word cannot be empty.', 'type' => 'error'];
    }

    header("Location: dashboard.php");
    exit();
}

// Check Security Word Status
$security_word_missing = false;
if (is_logged_in() && !has_security_word_set(get_current_user_id())) {
    $security_word_missing = true;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="favicon.png" type="image/png">
    <title>Dashboard - Notebook</title>
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
                <a href="dashboard.php" style="background: white;"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="index.php"><i class="fa-solid fa-note-sticky"></i> Notes</a>
                <a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a>
                <?php if (is_logged_in()): ?>
                    <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                    <a href="logout.php" style="color: #c62828;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color: #2e7d32;"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                <?php endif; ?>
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

        <?php if ($security_word_missing): ?>
            <div class="dashboard-section"
                style="border-left: 5px solid #ffa000; background: #fff8e1; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin-top: 0; color: #f57f17; font-size: 16px;">⚠️ Action Required: Set Security Word</h3>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    You haven't set a Security Word yet. This is required to recover your password if you forget it.
                </p>
                <form method="post" style="display: flex; gap: 10px; max-width: 400px;">
                    <input type="text" name="security_word" placeholder="Enter a secret word (e.g., pet name)" required
                        style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <button type="submit" name="set_security_word_action" class="btn btn-primary"
                        style="padding: 8px 15px;">Save</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Welcome / Announcements Section -->
        <!-- Welcome / Announcements Section -->
        <div id="dev-announcement" class="dashboard-section announcement-box" style="margin-bottom: 20px;">
            <div class="announcement-header">
                <h2 style="margin: 0;">📢 Updates & Announcements</h2>
                <button onclick="toggleAnnouncement()"
                    style="background: #fff; border: 1px solid #ccc; font-size: 13px; font-weight: bold; cursor: pointer; color: #333; padding: 4px 10px; border-radius: 4px; box-shadow: 1px 1px 0 rgba(0,0,0,0.1);"
                    title="Minimize">Hide</button>
            </div>
            <p>Welcome to <strong>Notebook-BAR v1.2</strong>! Latest improvements:</p>
            <ul style="padding-left: 20px; line-height: 1.6;">
                <li>⚡ <strong>Instant Pagination:</strong> Navigate pages instantly without reloads.</li>
                <li>📝 <strong>Enhanced Editor:</strong> 1,800 characters per page, Tab key support, and live word/char
                    counts.</li>
                <li>💾 <strong>Smart Saving:</strong> Single-click "Save All" for multi-page notes.</li>
                <li>📌 <strong>Organized Workflow:</strong> Move pinning to View Mode and focus on editing.</li>
            </ul>
        </div>

        <div id="dev-announcement-minimized" class="announcement-minimized" onclick="toggleAnnouncement()">
            <span>📢 Show Announcements</span>
            <span>+</span>
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

                <!-- Quick Categories REMOVED (Moved to Main Area) -->
            </div>

            <!-- Main: Chips & Pinned Notes -->
            <div class="dashboard-main">
                <h3 style="margin-top: 0; margin-bottom: 15px;">🔍 Filter by Category</h3>
                <div class="category-chips-container">
                    <?php
                    $quick_cats = get_categories();
                    foreach ($quick_cats as $c) {
                        $cname = htmlspecialchars($c['name']);
                        $ccolor = htmlspecialchars($c['color']);
                        echo "<a href='index.php?cat={$c['id']}' class='cat-chip'>
                                <span class='chip-dot' style='background-color: $ccolor;'></span>
                                $cname
                              </a>";
                    }
                    ?>
                    <a href="categories.php" class="cat-chip" style="background: var(--nav-bg); border-color: #999;">
                        <span style="font-size: 16px;">+</span> Manage
                    </a>
                </div>

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

                            // Truncate logic
                            if (strlen($dtitle) > 100) {
                                $dtitle = substr($dtitle, 0, 100) . "...";
                            }

                            $raw_text = $row['text'] ?? '';
                            $raw_text = str_replace(['</div>', '</p>', '<h1>', '<h2>', '<h3>', '<h4>', '</h5>', '<h6>'], '<br>', $raw_text);
                            $raw_text = str_replace('<li>', '<br>&bull; ', $raw_text);
                            $clean_text = strip_tags($raw_text, '<b><i><u><strong><em><br>');
                            $dtxt = trim($clean_text);

                            // Truncation now handled by CSS
                            // if (strlen(strip_tags($dtxt)) > 140) ... removed
                    
                            if (empty($dtxt))
                                $dtxt = "<em>No content...</em>";

                            echo "<a href='notepad.php?id=$nid' class='note-card'>";
                            echo "<div class='category_streak' style='background-color: $bg_color;'><br></div>";
                            echo "<div class='card_wrap'>";
                            echo "<div class='note-title'>$dtitle</div>";
                            echo "<div class='note-preview'>$dtxt</div>";
                            echo "<div class='note-footer'>";
                            echo "<div style='font-weight: bold; margin-bottom: 5px; color: #333;'>$dcat</div>";
                            if (!empty($row['reminder_date'])) {
                                $rem_display = date("M j, g:i A", strtotime($row['reminder_date']));
                                echo "<span style='color: #c62828; font-weight: bold; display: block; margin-bottom: 3px;'>⏰ $rem_display</span>";
                            }
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
        // Announcement Toggle with LocalStorage
        const announcementBox = document.getElementById('dev-announcement');
        const announcementMin = document.getElementById('dev-announcement-minimized');

        // Initialize state
        let isMinimized = localStorage.getItem('announcementMinimized') === 'true';

        function updateAnnouncementState() {
            if (isMinimized) {
                announcementBox.style.display = 'none';
                announcementMin.style.display = 'flex';
            } else {
                announcementBox.style.display = 'block';
                announcementMin.style.display = 'none';
            }
        }

        function toggleAnnouncement() {
            isMinimized = !isMinimized;
            localStorage.setItem('announcementMinimized', isMinimized);
            updateAnnouncementState();
        }

        // Run on load
        updateAnnouncementState();

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