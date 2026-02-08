<?php include 'includes/data_access.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="favicon.png" type="image/png">
    <title>Patchlog - Notebook-BAR</title>
    <style>
        .changelog-entry {
            background: #fff;
            padding: 20px;
            border: 1px solid #eee;
            margin-bottom: 20px;
            box-shadow: 1px 1px 0 rgba(0, 0, 0, 0.05);
        }

        .changelog-version {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .changelog-date {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 15px;
            font-style: italic;
        }

        .changelog-list {
            list-style-type: none;
            padding: 0;
        }

        .changelog-list li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .changelog-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #555;
            font-weight: bold;
        }

        .tag-refactor {
            color: #d32f2f;
            font-weight: bold;
            font-size: 0.8rem;
            background: #ffebee;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 5px;
        }

        .tag-feat {
            color: #2e7d32;
            font-weight: bold;
            font-size: 0.8rem;
            background: #e8f5e9;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 5px;
        }

        .tag-fix {
            color: #f57c00;
            font-weight: bold;
            font-size: 0.8rem;
            background: #fff3e0;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 5px;
        }

        .tag-style {
            color: #7b1fa2;
            font-weight: bold;
            font-size: 0.8rem;
            background: #f3e5f5;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 5px;
        }

        .tag-sys {
            color: #0288d1;
            font-weight: bold;
            font-size: 0.8rem;
            background: #e1f5fe;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 5px;
        }
    </style>
</head>

<body>

    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR</a></h1>

            <!-- Hamburger Button -->
            <button class="hamburger" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Desktop Nav -->
            <nav class="desktop-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="index.php">Notes</a>
                <a href="categories.php">Categories</a>
                <?php if (is_logged_in()): ?>
                    <a href="profile.php">Profile</a>
                    <a href="logout.php" style="color: #c62828;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="color: #2e7d32;">Login</a>
                <?php endif; ?>
                <a href="about.php" style="background: white;">About</a>
                <a href="contact.php">Contact Us</a>
            </nav>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay"></div>

    <!-- Mobile Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Menu</h3>
            <button class="sidebar-close">&times;</button>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="index.php">Notes</a>
            <a href="categories.php">Categories</a>
            <?php if (is_logged_in()): ?>
                <a href="profile.php">Profile</a>
                <a href="logout.php" style="color: #c62828;">Logout</a>
            <?php else: ?>
                <a href="login.php" style="color: #2e7d32;">Login</a>
            <?php endif; ?>
            <a href="about.php" style="background: #f0f0f0; font-weight: bold;">About</a>
            <a href="contact.php">Contact Us</a>
        </nav>
    </div>

    <script src="js/sidebar.js"></script>

    <div class="container">
        <div
            style="background: var(--card-bg); padding: 40px; border: 1px solid var(--border-color); box-shadow: var(--shadow); width: 100%; max-width: 100%; overflow: hidden;">

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
                <h2 style="font-size: 28px; margin: 0;">📜 Patchlog History</h2>
                <a href="about.php" class="btn btn-secondary" style="border: 1px solid #ccc; font-size: 13px;">&larr;
                    Back to About</a>
            </div>

            <div class="changelog-entry">
                <div class="changelog-version">v1.3.1 - Mobile Experience Update (Current)</div>
                <div class="changelog-date">Released: <?php echo date("F j, Y"); ?></div>
                <ul class="changelog-list">
                    <li><span class="tag-feat">feat</span> <strong>Collapsible Sidebar:</strong> Replaced the old mobile
                        menu with a smooth, slide-in sidebar navigation.</li>
                    <li><span class="tag-feat">feat</span> <strong>Floating Pagination:</strong> Added animated,
                        pill-shaped pagination controls for better usability and aesthetics.</li>
                    <li><span class="tag-style">style</span> <strong>Smart FABs:</strong> Floating Action Buttons now
                        intelligently hide/show to reduce screen clutter.</li>
                    <li><span class="tag-style">style</span> <strong>Animations:</strong> Implemented smooth CSS
                        transitions for pagination and menu interactions.</li>
                    <li><span class="tag-fix">fix</span> Resolved mobile layout issues where pagination controls would
                        overflow.</li>
                    <li><span class="tag-refactor">refactor</span> Unified header structure across all 11 application
                        pages.</li>
                </ul>
            </div>

            <div class="changelog-entry">
                <div class="changelog-version">v1.3.0 - Refactoring & Stability</div>
                <div class="changelog-date">Released: <?php echo date("F j, Y"); ?></div>
                <ul class="changelog-list">
                    <li><span class="tag-refactor">refactor</span> Standardized primary keys to `user_id`, `note_id`,
                        `category_id` across all tables.</li>
                    <li><span class="tag-refactor">refactor</span> Normalized `pages` table to use composite key
                        (`note_id`, `page_number`) instead of auto-increment `id`.</li>
                    <li><span class="tag-fix">fix</span> Resolved critical session key mismatch (`id` vs `note_id`)
                        causing guest redirection loops.</li>
                    <li><span class="tag-fix">fix</span> Corrected `category_id` injection for guest note creation.</li>
                    <li><span class="tag-fix">fix</span> Fixed pinning bug where saving a pinned note created a blank
                        duplicate.</li>
                    <li><span class="tag-fix">fix</span> Improved error handling and redirects for missing notes.</li>
                </ul>
            </div>

            <div class="changelog-entry">
                <div class="changelog-version">v1.2.0 - Advanced Features & Optimization</div>
                <div class="changelog-date">Released: February 2026</div>
                <ul class="changelog-list">
                    <li><span class="tag-feat">feat</span> Implemented Instant Client-Side Pagination (JSON-driven).
                    </li>
                    <li><span class="tag-feat">feat</span> Added "Save All" mechanism for bulk-persisting note pages.
                    </li>
                    <li><span class="tag-feat">feat</span> Integrated "Jump to Page" input and navigation shortcuts.
                    </li>
                    <li><span class="tag-sys">sys</span> Added `limit_category_count` SQL Trigger (Max 20 categories per
                        user).</li>
                    <li><span class="tag-feat">feat</span> Implemented "Security Word" mechanism for password recovery.
                    </li>
                    <li><span class="tag-style">style</span> Renovated Profile page with 2-column layout and "Member
                        Since" stats.</li>
                    <li><span class="tag-fix">fix</span> Fixed mobile overflow issues in pagination and navbar.</li>
                </ul>
            </div>

            <div class="changelog-entry">
                <div class="changelog-version">v1.1.5 - Database Normalization</div>
                <div class="changelog-date">Released: January 2026</div>
                <ul class="changelog-list">
                    <li><span class="tag-refactor">refactor</span> Migrated `category` string column to `category_id`
                        foreign key.</li>
                    <li><span class="tag-sys">sys</span> Added SQL script to map existing string categories to new IDs.
                    </li>
                    <li><span class="tag-style">style</span> Added category chips to dashboard for quick filtering.</li>
                </ul>
            </div>

            <div class="changelog-entry">
                <div class="changelog-version">v1.1.0 - Core Feature Expansion</div>
                <div class="changelog-date">Released: January 2026</div>
                <ul class="changelog-list">
                    <li><span class="tag-feat">feat</span> Added `is_pinned` and `is_archived` columns to database.</li>
                    <li><span class="tag-style">style</span> Added "Pinned Notes" section to top of Dashboard.</li>
                    <li><span class="tag-sys">sys</span> Created `categories` table with default seeds (General,
                        Personal, Work, etc).</li>
                </ul>
            </div>

            <div class="changelog-entry">
                <div class="changelog-version">v1.0.0 - Initial Release</div>
                <div class="changelog-date">Released: Late 2025</div>
                <ul class="changelog-list">
                    <li><span class="tag-sys">init</span> Transitioned from flat-file storage to MySQL Relational
                        Database.</li>
                    <li><span class="tag-feat">feat</span> Basic Note CRUD (Create, Read, Update, Delete).</li>
                    <li><span class="tag-feat">feat</span> User Authentication System with guest mode fallback.</li>
                    <li><span class="tag-style">style</span> Applied "Typewriter" aesthetic with Courier New typography.
                    </li>
                </ul>
            </div>

        </div>
    </div>
</body>

</html>