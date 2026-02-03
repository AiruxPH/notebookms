<?php
include 'includes/data_access.php';
session_start();

// Access Control: Redirect Admin to Admin Dashboard
if (is_admin()) {
	header("Location: admin/dashboard.php");
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<link rel="icon" href="favicon.png" type="image/png">
	<title>My Notes - Notebook</title>
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
				<a href="dashboard.php">Dashboard</a>
				<a href="index.php" style="background: white;">Notes</a>
				<a href="categories.php">Categories</a>
				<?php if (is_logged_in()): ?>
					<a href="profile.php">Profile</a>
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

		<div class="dashboard-grid-layout">
			<!-- Sidebar: Search & Filter -->
			<div class="dashboard-sidebar">
				<div
					style="background: #fff; padding: 20px; border: 1px solid #ccc; box-shadow: 2px 2px 0 rgba(0,0,0,0.05); margin-bottom: 20px;">
					<h3 style="margin-top: 0; margin-bottom: 15px;">Search & Filter</h3>
					<form method="get" style="display: flex; flex-direction: column; gap: 15px;">
						<div>
							<label
								style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Keyword:</label>
							<input type="text" name="q" placeholder="Search notes..."
								value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
								style="padding: 10px; font-family: inherit; font-size: 14px; width: 100%; border: 1px solid #ccc; box-sizing: border-box;">
						</div>

						<div>
							<label
								style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Category:</label>
							<select name="cat"
								style="padding: 10px; font-family: inherit; font-size: 14px; width: 100%; border: 1px solid #ccc; box-sizing: border-box;">
								<option value="">All Categories</option>
								<?php
								$all_cats = get_categories();
								$curr_cat = $_GET['cat'] ?? '';

								$defaults = [];
								$custom = [];
								$default_names = ['General', 'Personal', 'Work', 'Study', 'Ideas'];
								foreach ($all_cats as $c) {
									if (in_array($c['name'], $default_names)) {
										$defaults[] = $c;
									} else {
										$custom[] = $c;
									}
								}

								if (!empty($defaults)) {
									echo "<optgroup label='Defaults'>";
									foreach ($defaults as $c) {
										$cname = htmlspecialchars($c['name']);
										$cid = $c['id'];
										$sel = ($curr_cat == $cid) ? "selected" : "";
										echo "<option value='$cid' $sel>$cname</option>";
									}
									echo "</optgroup>";
								}

								if (!empty($custom)) {
									echo "<optgroup label='My Categories'>";
									foreach ($custom as $c) {
										$cname = htmlspecialchars($c['name']);
										$cid = $c['id'];
										$sel = ($curr_cat == $cid) ? "selected" : "";
										echo "<option value='$cid' $sel>$cname</option>";
									}
									echo "</optgroup>";
								}
								?>
							</select>
						</div>

						<div style="display: flex; gap: 10px;">
							<button type="submit" class="btn" style="flex: 2;">Apply</button>
							<?php if (isset($_GET['q']) || isset($_GET['cat'])): ?>
								<a href="index.php" class="btn btn-secondary"
									style="font-weight: normal; font-size: 12px; padding: 10px; text-decoration: none; text-align: center; flex: 1; border: 1px solid #ccc;">Clear</a>
							<?php endif; ?>
						</div>
					</form>
				</div>

				<!-- Archive Link Box -->
				<div style="background: #f9f9f9; padding: 15px; border: 1px solid #eee; text-align: center;">
					<?php if (isset($_GET['archived']) && $_GET['archived'] == 1): ?>
						<a href="index.php"
							style="color: green; font-weight: bold; font-size: 14px; text-decoration: none; display: block;">
							🚀 View Active Notes
						</a>
					<?php else: ?>
						<a href="index.php?archived=1"
							style="color: #666; font-size: 14px; text-decoration: none; display: block;">
							📁 View Archived Notes
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Main Content: Note Grid -->
			<div class="dashboard-main">
				<div class="note-grid">
					<!-- Add New Note Card (Hidden if searching, filtering category, OR viewing archives) -->
					<?php if (!isset($_GET['archived']) || $_GET['archived'] != 1): ?>
						<a href="notepad.php" class="note-card note-add-card">
							<div style="text-align: center;">
								<div style="font-size: 3rem; font-weight: bold;">+</div>
								<div>Add New Note</div>
							</div>
						</a>
					<?php endif; ?>

					<?php
					// Filter setup
					$is_archived_view = isset($_GET['archived']) && $_GET['archived'] == 1;
					$search_query = $_GET['q'] ?? '';
					$cat_filter = $_GET['cat'] ?? '';

					$filters = ['archived' => $is_archived_view];
					if ($search_query)
						$filters['search'] = $search_query;
					if ($cat_filter)
						$filters['category'] = $cat_filter;

					// Fetch Categories for Colors
					$all_cats = get_categories();
					$cat_colors = [];
					foreach ($all_cats as $c) {
						$cat_colors[$c['name']] = $c['color'];
					}

					// Fetch Notes (DB or Session)
					$notes = get_notes($filters);

					// Display
					if (count($notes) > 0) {
						foreach ($notes as $row) {
							$nid = $row['id'];
							$title = htmlspecialchars($row['title']);
							$category = htmlspecialchars($row['category']);
							$date_last = date("M j, g:i A", strtotime($row['date_last']));
							$pin_icon = ($row['is_pinned'] == 1) ? "<span style='float: right; font-size: 1.2rem;'>📌</span>" : "";
							$date_created = date("M j, Y, g:i A", strtotime($row['date_created']));

							// Determine Color
							$bg_color = isset($cat_colors[$category]) ? $cat_colors[$category] : '#ffffff';

							// Truncate logic
							if (strlen($title) > 100) {
								$title = substr($title, 0, 100) . "...";
							}

							// Preview Text
							$raw_text = $row['text'] ?? '';
							$raw_text = str_replace(['</div>', '</p>', '<h1>', '<h2>', '<h3>', '<h4>', '</h5>', '<h6>'], '<br>', $raw_text);
							$raw_text = str_replace('<li>', '<br>&bull; ', $raw_text);
							$clean_text = strip_tags($raw_text, '<b><i><u><strong><em><br>');
							$dtxt = trim($clean_text);

							// Truncation now handled by CSS with Percentage Height
							// if (strlen(strip_tags($dtxt)) > 140) ... removed
					
							if (empty($dtxt))
								$dtxt = "<em>No content...</em>";

							echo "<a href='notepad.php?id=$nid' class='note-card'>";
							echo "<div class='category_streak' style='background-color: $bg_color;'><br></div>";
							echo "<div class='card_wrap'>";
							echo "<div class='note-title'>$pin_icon" . $title . "</div>";
							echo "<div class='note-preview'>$dtxt</div>";
							echo "<div class='note-footer'>";
							echo "<div style='font-weight: bold; margin-bottom: 5px; color: #333;'>$category</div>";
							if (!empty($row['reminder_date'])) {
								$rem_display = date("M j, g:i A", strtotime($row['reminder_date']));
								echo "<span style='color: #c62828; font-weight: bold; display: block; margin-bottom: 3px;'>⏰ $rem_display</span>";
							}



							echo "<span>Created: $date_created</span><br>";

							$p_cnt = isset($row['page_count']) ? $row['page_count'] : 1;
							$page_info = ($p_cnt > 1) ? " <span style='color: #ccc; margin: 0 5px;'>|</span> $p_cnt Pages" : "";

							echo "<span>Updated: $date_last$page_info</span>";
							echo "</div>";
							echo "</div>";
							echo "</a>";
						}
					} else {
						// Empty State Message
						$empty_msg = "No notes found.";
						if ($is_archived_view) {
							$empty_msg = "No archived notes found.";
						} else if ($search_query) {
							$empty_msg = "No notes found matching your search.";
						}

						echo "<div class='note-card note-add-card' style='border: 1px dashed #ccc; cursor: default; background: rgba(255,255,255,0.2);'>
								<div style='text-align: center; color: #777;'>
									<div style='font-size: 40px; margin-bottom: 10px; opacity: 0.5;'>📭</div>
									<div style='font-size: 16px; font-weight: bold;'>$empty_msg</div>
									<div style='font-size: 12px; margin-top: 5px;'>Try clearing filters or search</div>
								</div>
							</div>";
					}
					?>
				</div>
			</div>
		</div>
	</div>

	</div>

	<script>
		// Popup Logic (Same as notepad.php)
		const popupOverlay = document.getElementById('popup-overlay');
		const popupMessage = document.getElementById('popup-message');

		function showPopup(msg, type) {
			popupMessage.textContent = msg;
			popupMessage.className = "popup-message " + (type === 'error' ? 'flash-error' : 'flash-success');
			popupMessage.style.color = (type === 'error') ? '#c62828' : '#2e7d32';
			popupOverlay.style.display = 'flex';
		}

		function closePopup() {
			popupOverlay.style.display = 'none';
		}

		// Check for PHP Flash Message
		<?php
		if (isset($_SESSION['flash'])) {
			$msg = $_SESSION['flash']['message'];
			$msg_type = $_SESSION['flash']['type'];
			unset($_SESSION['flash']); // Clear it so it doesn't show again
			echo "showPopup('" . addslashes($msg) . "', '$msg_type');";
		}
		?>
	</script>

</body>

</html>