<?php
include 'includes/data_access.php';

// Handle Category Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_cat'])) {
    $action = $_POST['action_cat'];

    if ($action == 'add') {
        $c_name = trim($_POST['cat_name'] ?? '');
        $c_color = $_POST['cat_color'] ?? '#fff9c4';
        if ($c_name) {
            $res = add_category($c_name, $c_color);
            if ($res === 1) {
                $_SESSION['flash'] = ['message' => 'Category Created', 'type' => 'success'];
            } elseif ($res === -1) {
                $_SESSION['flash'] = ['message' => 'Limit reached (Max 20 categories)', 'type' => 'error'];
            } else {
                $_SESSION['flash'] = ['message' => 'Error: Category name already exists', 'type' => 'error'];
            }
        }
    } elseif ($action == 'update') {
        $cid = $_POST['cat_id'] ?? '';
        $c_name = trim($_POST['cat_name'] ?? '');
        $c_color = $_POST['cat_color'] ?? '#fff9c4';
        if ($cid && $c_name) {
            $res = update_category($cid, $c_name, $c_color);
            if ($res) {
                $_SESSION['flash'] = ['message' => 'Category Updated', 'type' => 'success'];
            } else {
                $_SESSION['flash'] = ['message' => 'Error updating category', 'type' => 'error'];
            }
        }
    } elseif ($action == 'delete') {
        $cid = $_POST['cat_id'] ?? '';
        if ($cid) {
            if (delete_category($cid)) {
                $_SESSION['flash'] = ['message' => 'Category Deleted. Notes moved to General.', 'type' => 'success'];
            } else {
                $_SESSION['flash'] = ['message' => 'Error deleting category', 'type' => 'error'];
            }
        }
    }
    header("Location: categories.php");
    exit();
}

$categories = get_categories();

// Predefined Light Palette
$light_palette = [
    '#fff9c4' => 'Yellow',
    '#e8f5e9' => 'Green',
    '#e3f2fd' => 'Blue',
    '#fce4ec' => 'Pink',
    '#f3e5f5' => 'Purple',
    '#fff3e0' => 'Orange',
    '#f5f5f5' => 'Gray',
    '#e0f2f1' => 'Teal',
    '#efebe9' => 'Brown'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <title>Manage Categories - Notebook</title>
    <style>
        .color-palette {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(35px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }

        .color-swatch {
            width: 35px;
            height: 35px;
            border-radius: 4px;
            border: 2px solid #ccc;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .color-swatch:hover {
            transform: scale(1.1);
            border-color: #333;
        }

        .color-swatch input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .color-swatch.selected {
            border-color: #333;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

        .color-swatch.selected::after {
            content: '✓';
            color: #333;
            font-weight: bold;
        }

        .flicker-red {
            animation: flickerRed 0.4s ease-in-out;
            border-color: #f44336 !important;
            color: #f44336 !important;
        }

        @keyframes flickerRed {
            0% {
                background: #fff;
            }

            50% {
                background: #ffebee;
            }

            100% {
                background: #fff;
            }
        }

        .category-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            background: #fafafa;
            margin-bottom: 5px;
            transition: background 0.2s;
        }

        .category-row:hover {
            background: #f1f1f1;
        }

        .cat-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-grow: 1;
        }

        .cat-badge {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #999;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
            margin-left: 5px;
        }

        .edit-form {
            display: none;
            width: 100%;
            background: #fff;
            padding: 15px;
            border: 1px solid var(--nav-bg);
            margin-top: -1px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
    </style>
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
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Toast Container -->
        <div id="toast-overlay" class="toast-overlay">
            <div id="toast-message" class="toast-message"></div>
        </div>

        <div class="dashboard-section">
            <h2>Category Management</h2>
            <p>Define categories with custom names and light colors. Max 20 categories.</p>

            <div class="dashboard-grid-layout">
                <!-- Sidebar: Add Category -->
                <div class="dashboard-sidebar">
                    <div
                        style="background: #fdfdad; padding: 20px; border: 1px solid #d1d190; box-shadow: 2px 2px 0 rgba(0,0,0,0.1);">
                        <h3>New Category</h3>
                        <form method="post" id="add-cat-form">
                            <input type="hidden" name="action_cat" value="add">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Name (Max
                                    50):</label>
                                <input type="text" name="cat_name" class="cat-name-input" required maxlength="50"
                                    placeholder="e.g. Health"
                                    style="width: 100%; padding: 10px; border: 1px solid #ccc; font-family: inherit;">
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-weight: bold;">Select Color:</label>
                                <div class="color-palette" id="add-palette">
                                    <?php foreach ($light_palette as $hex => $name): ?>
                                        <label class="color-swatch <?php echo ($hex == '#fff9c4') ? 'selected' : ''; ?>"
                                            style="background: <?php echo $hex; ?>;" title="<?php echo $name; ?>">
                                            <input type="radio" name="cat_color" value="<?php echo $hex; ?>" <?php echo ($hex == '#fff9c4') ? 'checked' : ''; ?>>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Create</button>
                        </form>
                    </div>
                </div>

                <!-- Main: Category List -->
                <div class="dashboard-main">
                    <h3>Your Categories</h3>
                    <div style="background: #fff; outline: 1px solid #eee;">
                        <?php
                        foreach ($categories as $index => $c):
                            $cid = $c['id'];
                            $cname = htmlspecialchars($c['name']);
                            $ccolor = htmlspecialchars($c['color']);
                            // Logic: IDs 1-5 are defaults
                            $is_default = (is_numeric($cid) && $cid <= 5);
                            ?>
                            <div class="category-item-container">
                                <div class="category-row" id="row-<?php echo $cid; ?>">
                                    <div class="cat-info">
                                        <div class="cat-badge" style="background-color: <?php echo $ccolor; ?>;"></div>
                                        <span style="font-weight: bold; font-family: Arial, sans-serif;">
                                            <?php echo $cname; ?>
                                        </span>
                                        <?php if ($is_default): ?>
                                            <span
                                                style="font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px;">Default</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!$is_default): ?>
                                        <div class="cat-actions">
                                            <button type="button" class="btn btn-sm"
                                                onclick="toggleEdit('<?php echo $cid; ?>')">Edit</button>
                                            <form method="post" style="display: inline;"
                                                onsubmit="return confirm('Delete \'<?php echo $cname; ?>\'? Notes in this category will move to General.');">
                                                <input type="hidden" name="action_cat" value="delete">
                                                <input type="hidden" name="cat_id" value="<?php echo $cid; ?>">
                                                <button type="submit" class="btn btn-sm"
                                                    style="color: #c62828; border-color: #c62828;">Delete</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$is_default): ?>
                                    <!-- Hidden Edit Form -->
                                    <div class="edit-form" id="edit-<?php echo $cid; ?>">
                                        <form method="post">
                                            <input type="hidden" name="action_cat" value="update">
                                            <input type="hidden" name="cat_id" value="<?php echo $cid; ?>">

                                            <div style="display: flex; gap: 15px; align-items: flex-end;">
                                                <div style="flex-grow: 1;">
                                                    <label style="display: block; font-size: 12px; margin-bottom: 4px;">Update
                                                        Name:</label>
                                                    <input type="text" name="cat_name" class="cat-name-input"
                                                        value="<?php echo $cname; ?>" required maxlength="50"
                                                        style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                                                </div>
                                                <button type="submit" class="btn btn-primary"
                                                    style="padding: 8px 15px;">Save</button>
                                                <button type="button" class="btn" style="padding: 8px 15px;"
                                                    onclick="toggleEdit('<?php echo $cid; ?>')">Cancel</button>
                                            </div>

                                            <div class="color-palette">
                                                <?php foreach ($light_palette as $hex => $name): ?>
                                                    <label class="color-swatch <?php echo ($hex == $ccolor) ? 'selected' : ''; ?>"
                                                        style="background: <?php echo $hex; ?>;" title="<?php echo $name; ?>">
                                                        <input type="radio" name="cat_color" value="<?php echo $hex; ?>" <?php echo ($hex == $ccolor) ? 'checked' : ''; ?>>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle Edit Form
        function toggleEdit(id) {
            const form = document.getElementById('edit-' + id);
            const row = document.getElementById('row-' + id);
            if (form.style.display === 'block') {
                form.style.display = 'none';
                row.style.background = '';
            } else {
                form.style.display = 'block';
                row.style.background = '#fffde7'; // highlight row
            }
        }

        // Color Swatch Selection
        document.querySelectorAll('.color-palette').forEach(palette => {
            palette.addEventListener('change', (e) => {
                if (e.target.type === 'radio') {
                    palette.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('selected'));
                    e.target.closest('.color-swatch').classList.add('selected');
                }
            });
        });

        // 50 Character Limit Visual Feedback
        document.querySelectorAll('.cat-name-input').forEach(input => {
            input.addEventListener('input', function () {
                if (this.value.length >= 50) {
                    this.classList.add('flicker-red');
                    // Remove class after animation so it can trigger again
                    setTimeout(() => {
                        this.classList.remove('flicker-red');
                    }, 400);
                }
            });
        });

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