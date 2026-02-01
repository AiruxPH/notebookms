<?php
include 'includes/data_access.php';
// session_start() is already in db.php (which is in data_access.php)sh message check (optional if we want to show messages on index too)
session_start();
?>
<!DOCTYPE html>
<html>

<head>
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<title>My Notes - Notebook</title>
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
		<!-- Toast Container -->
		<div id="toast-overlay" class="toast-overlay">
			<div id="toast-message" class="toast-message"></div>
		</div>

		<!-- Search & Filter Bar -->
		<!-- ... code omitted ... -->

		<!-- (Moving to END of file for script replacement) -->
		<script>
			// Toast Logic (Same as notepad.php)
			const toastOverlay = document.getElementById('toast-overlay');
			const toastMessage = document.getElementById('toast-message');

			function showToast(msg, type) {
				toastMessage.textContent = msg;
				toastMessage.className = "toast-message " + (type === 'error' ? 'toast-error' : 'toast-success');
				// Force reflow
				void toastMessage.offsetWidth;

				toastOverlay.style.display = 'flex';
				requestAnimationFrame(() => {
					toastMessage.classList.add('show');
				});

				// Auto hide after 3 seconds
				setTimeout(() => {
					toastMessage.classList.remove('show');
					setTimeout(() => {
						toastOverlay.style.display = 'none';
					}, 300); // Wait for fade out
				}, 3000);
			}

			// Check for PHP Flash Message
			<?php
			if (isset($_SESSION['flash'])) {
				$msg = $_SESSION['flash']['message'];
				$msg_type = $_SESSION['flash']['type'];
				unset($_SESSION['flash']); // Clear it so it doesn't show again
				echo "showToast('" . addslashes($msg) . "', '$msg_type');";
			}
			?>
		</script>

</body>
<div
	style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: #fff; padding: 10px; border: 1px solid #ccc; flex-wrap: wrap;">
	<a href="notepad.php" class="btn btn-primary" style="text-decoration: none;">+ New Note</a>

	<form method="get" style="display: flex; gap: 10px; flex-grow: 1;">
		<input type="text" name="q" placeholder="Search notes..."
			value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
			style="padding: 8px; font-family: inherit; font-size: 14px; flex-grow: 1; border: 1px solid #ccc;">

		<select name="cat" style="padding: 8px; font-family: inherit; font-size: 14px; border: 1px solid #ccc;">
			<option value="">All Categories</option>
			<?php
			$all_cats = get_categories();
			$curr_cat = $_GET['cat'] ?? '';
			foreach ($all_cats as $c) {
				// Access 'name' from the array returned by get_categories
				$cname = htmlspecialchars($c['name']);
				$sel = ($curr_cat == $cname) ? "selected" : "";
				echo "<option value='$cname' $sel>$cname</option>";
			}
			?>
		</select>

		<button type="submit" class="btn">Filter</button>
		<?php if (isset($_GET['q']) || isset($_GET['cat'])): ?>
			<a href="index.php" class="btn btn-secondary"
				style="font-weight: normal; font-size: 12px; padding: 10px;">Clear</a>
		<?php endif; ?>
	</form>

	<div style="border-left: 1px solid #ccc; padding-left: 10px; margin-left: auto;">
		<?php if (isset($_GET['archived']) && $_GET['archived'] == 1): ?>
			<a href="index.php" style="color: green; font-weight: bold; font-size: 14px;">&laquo; View Active
				Notes</a>
		<?php else: ?>
			<a href="index.php?archived=1" style="color: #999; font-size: 14px;">View Archived Notes &raquo;</a>
		<?php endif; ?>
	</div>
</div>

<div class="note-grid">
	<!-- Add New Note Card (Hidden if searching, filtering category, OR viewing archives) -->
	<?php if (!isset($_GET['q']) && !isset($_GET['cat']) && (!isset($_GET['archived']) || $_GET['archived'] != 1)): ?>
		<a href="notepad.php" class="note-card note-add-card">
			<div style="text-align: center;">
				<div style="font-size: 3rem; font-weight: bold;">+</div>
				<div>Add New Note</div>
			</div>
		</a>
	<?php endif; ?>

	<?php
	// Search & Filter Logic
	$where = [];

	// Default: Hide archived notes unless specifically filtering for them (logic can vary)
	// For now, let's say we hide archived notes by default.
	// $where[] = "n.is_archived = 0"; 
	
	// Actually, let's check if 'show_archived' parameter is present
	if (isset($_GET['archived']) && $_GET['archived'] == 1) {
		$where[] = "n.is_archived = 1";
	} else {
		// Important: To prevent breaking if column doesn't exist yet (in case user hasn't run SQL), 
		// we might skip this restriction for a split second, but ideally we add it. 
		// Assuming user runs SQL:
		$where[] = "n.is_archived = 0";
	}

	if (isset($_GET['q']) && !empty($_GET['q'])) {
		$q = mysqli_real_escape_string($conn, $_GET['q']);
		$where[] = "(n.title LIKE '%$q%' OR p.text LIKE '%$q%')";
	}
	if (isset($_GET['cat']) && !empty($_GET['cat'])) {
		$cat = mysqli_real_escape_string($conn, $_GET['cat']);
		$where[] = "n.category = '$cat'";
	}

	$where_sql = "";
	if (!empty($where)) {
		$where_sql = "WHERE " . implode(" AND ", $where);
	}

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
			$date_last = date("M j, H:i", strtotime($row['date_last']));
			$pin_icon = ($row['is_pinned'] == 1) ? "<span style='float: right; font-size: 1.2rem;'>📌</span>" : "";
			$date_created = date("M j, Y", strtotime($row['date_created']));

			// Determine Color
			$bg_color = isset($cat_colors[$category]) ? $cat_colors[$category] : '#ffffff';

			// Preview Text
			$raw_text = $row['text'] ?? '';
			// Visual Layout Update for Verticality
			$raw_text = str_replace(['</div>', '</p>', '<h1>', '<h2>', '<h3>', '<h4>', '</h5>', '<h6>'], '<br>', $raw_text);
			$raw_text = str_replace('<li>', '<br>&bull; ', $raw_text);
			$clean_text = strip_tags($raw_text, '<b><i><u><strong><em><br>');
			$dtxt = $clean_text;
			if (empty(trim(strip_tags($dtxt))))
				$dtxt = "<em>No content...</em>";

			echo "<a href='notepad.php?id=$nid' class='note-card' style='background-color: $bg_color;'>";
			echo "<div class='note-title'>$pin_icon" . $title . "</div>";
			echo "<div class='note-meta'>$category &bull; $date_last</div>";
			echo "<div class='note-preview'>$dtxt</div>";
			echo "<div class='note-footer'>";
			echo "<span>Created: $date_created</span>";
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

		echo "<div style='grid-column: 1 / -1; text-align: center; color: #777; padding: 40px;'>
                            <div style='font-size: 40px; margin-bottom: 10px; opacity: 0.5;'>📭</div>
                            <div style='font-size: 18px;'>$empty_msg</div>
                           </div>";
	}
	?>
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