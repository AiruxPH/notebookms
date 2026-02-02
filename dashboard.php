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
    <link rel="icon" href="favicon.png" type="image/png">
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
        <!-- Welcome / Announcements Section -->
        <div id="dev-announcement" class="dashboard-section announcement-box" style="margin-bottom: 20px;">
            <div class="announcement-header">
                <h2 style="margin: 0;">📢 Updates & Announcements</h2>
                <button onclick="toggleAnnouncement()"
                    style="background: #fff; border: 1px solid #ccc; font-size: 13px; font-weight: bold; cursor: pointer; color: #333; padding: 4px 10px; border-radius: 4px; box-shadow: 1px 1px 0 rgba(0,0,0,0.1);"
                    title="Minimize">Hide</button>
            </div>
            <p>Welcome to <strong>Notebook-BAR v1.1 Refined</strong>! Latest improvements:</p>
            <ul style="padding-left: 20px; line-height: 1.6;">
                <li>📱 <strong>Mobile Polish:</strong> Improved headers and layout scaling on all devices.</li>
                <li>🎨 <strong>Dynamic Categories:</strong> Custom colors with a unified look.</li>
                <li>🚀 <strong>Refined Interface:</strong> Collapsible widgets and chip-style category filters.</li>
                <li>📌 <strong>Pinning & Archiving:</strong> Organize your workspace efficiently.</li>
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

                            if (strlen(strip_tags($dtxt)) > 200) {
                                $dtxt = substr(strip_tags($dtxt), 0, 200) . "...";
                            }

                            if (empty($dtxt))
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