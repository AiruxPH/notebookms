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



                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                    <a href="notepad.php" class="btn btn-primary" style="text-decoration: none; text-align: center;">+
                        New Note</a>
                    <div style="display: flex; gap: 5px;">
                        <a href="categories.php" class="btn btn-secondary"
                            style="border: 1px solid #ccc; flex: 1; text-decoration: none; text-align: center;">Manage
                            Categories</a>
                        <a href="index.php" class="btn btn-secondary"
                            style="text-decoration: none; text-align: center; flex: 1; border: 1px solid #ccc;">All
                            Notes</a>
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
                                $ddatl = date("g:i A", strtotime($row['date_last']));
                                $ddate = date("M j, Y", strtotime($row['date_last']));

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
                                echo "<div class='card_wrap'>";
                                echo "<div class='note-title'>$dtitle</div>";
                                echo "<div class='note-preview'>$dtxt</div>";
                                echo "<div class='note-footer'>";
                                echo "<div style='font-weight: bold; margin-bottom: 5px;'>$dcat</div>";
                                echo "Updated: $ddate $ddatl</div>";
                                echo "</div>";
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