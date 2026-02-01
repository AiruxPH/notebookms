<?php
include 'includes/db.php';
session_start();
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
                <a href="about.html">About</a>
                <a href="index.php">Notes</a>
                <a href="contact.html">Contact Us</a>
            </nav>
        </div>
    </header>

    <div class="container">

        <!-- Welcome / Announcements Section -->
        <div class="dashboard-section announcement-box">
            <h2>📢 Developer Announcements</h2>
            <p>Welcome to <strong>Notebook-BAR v1.0</strong>! We have recently updated our features:</p>
            <ul>
                <li>✨ <strong>New Archiving Workflow:</strong> Safer and smoother.</li>
                <li>📌 <strong>Pinning:</strong> Keep your important notes at the top.</li>
                <li>📊 <strong>Stats:</strong> Word and character counts in real-time.</li>
            </ul>
        </div>

        <div class="dashboard-grid-layout">
            <!-- Left Column: Categories & Tools -->
            <div class="dashboard-sidebar">
                <h3>Quick Categories</h3>
                <div class="category-list">
                    <?php
                    $cats = ["General", "Personal", "Work", "Study", "Ideas"];
                    foreach ($cats as $c) {
                        echo "<a href='index.php?cat=$c' class='cat-btn'>$c</a>";
                    }
                    ?>
                </div>

                <div style="margin-top: 30px;">
                    <a href="notepad.php" class="btn btn-primary"
                        style="display: block; text-align: center; font-size: 1.2em;">+ Create New Note</a>
                    <a href="index.php" class="btn btn-secondary"
                        style="display: block; text-align: center; margin-top: 10px;">📂 View All Notes</a>
                </div>
            </div>

            <!-- Right Column: Pinned Notes -->
            <div class="dashboard-main">
                <h3>📌 Pinned Notes</h3>
                <div class="note-grid">
                    <?php
                    // Fetch ONLY pinned notes (limit 4 for space)
                    $sql = "SELECT n.id, n.title, n.category, n.date_last, p.text 
                            FROM notes n 
                            LEFT JOIN pages p ON n.id = p.note_id AND p.page_number = 1
                            WHERE n.is_pinned = 1 AND n.is_archived = 0
                            ORDER BY n.date_last DESC LIMIT 4";

                    $result = mysqli_query($conn, $sql);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $nid = $row['id'];
                            $dtitle = $row['title'];
                            $dcat = $row['category'];
                            $ddatl = date("M j, H:i", strtotime($row['date_last']));

                            // Strip tags regarding block elements, but allow inline styles
                            // Use CSS line-clamp for truncation
                            $raw_text = $row['text'] ?? '';
                            $raw_text = str_replace('<li>', ' &bull; ', $raw_text);
                            $raw_text = str_replace(['</p>', '</div>', '<br>', '<br/>'], ' ', $raw_text);

                            $clean_text = strip_tags($raw_text, '<b><i><u><strong><em>');
                            $dtxt = $clean_text;

                            if (empty(trim($dtxt)))
                                $dtxt = "<em>No content...</em>";

                            echo "<a href='notepad.php?id=$nid' class='note-card'>";
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