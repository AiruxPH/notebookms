<?php
include 'includes/db.php';
session_start(); // Start session for Flash messages

$msg = "";
$msg_type = "";
$nid = "";
$ntitle = "";
$ncat = "General"; // Default category
$content = "";

// Check for Flash Message
if (isset($_SESSION['flash'])) {
	$msg = $_SESSION['flash']['message'];
	$msg_type = $_SESSION['flash']['type'];
	unset($_SESSION['flash']); // Clear immediately
}

// 1. Check for ID in URL
if (isset($_GET['id'])) {
	$nid = intval($_GET['id']);
	// Fetch Title (and verify note exists)
	$res = mysqli_query($conn, "SELECT * FROM notes WHERE id = $nid");
	if ($row = mysqli_fetch_assoc($res)) {
		$ntitle = $row['title'];
		$ncat = $row['category'];
		// Fetch ALL row data to access later
		// $row is already available
	} else {
		$nid = ""; // Invalid ID
	}
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_note'])) {
	$content_input = mysqli_real_escape_string($conn, $_POST['page']);
	$date = date('Y-m-d H:i:s');

	if ($nid == "") {
		// --- NEW NOTE ---
		$title_input = isset($_POST['new_title']) ? trim($_POST['new_title']) : "";
		if ($title_input == "") {
			$msg = "Title is required for a new note.";
			$msg_type = "error";
		} else {
			$safe_title = mysqli_real_escape_string($conn, $title_input);
			$cat_input = isset($_POST['category']) ? $_POST['category'] : 'General';
			$safe_cat = mysqli_real_escape_string($conn, $cat_input);

			$is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
			// New notes are generally not archived immediately, but we stick to defaults (0) or explicit input if provided.

			$sql_note = "INSERT INTO notes (user_id, title, category, is_pinned, is_archived, date_created, date_last) VALUES (0, '$safe_title', '$safe_cat', $is_pinned, 0, '$date', '$date')";
			if (mysqli_query($conn, $sql_note)) {
				$nid = mysqli_insert_id($conn);
				$sql_page = "INSERT INTO pages (note_id, page_number, text) VALUES ($nid, 1, '$content_input')";
				mysqli_query($conn, $sql_page);

				// Set Flash Message and Redirect
				$_SESSION['flash'] = ['message' => 'Note created successfully!', 'type' => 'success'];
				header("Location: notepad.php?id=$nid");
				exit();
			} else {
				$msg = "Database Error: " . mysqli_error($conn);
				$msg_type = "error";
			}
		}
	} else {
		// --- UPDATE NOTE ---
		$title_input = isset($_POST['new_title']) ? trim($_POST['new_title']) : $ntitle;
		$cat_input = isset($_POST['category']) ? $_POST['category'] : 'General';

		$safe_title = mysqli_real_escape_string($conn, $title_input);
		$safe_cat = mysqli_real_escape_string($conn, $cat_input);

		$is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
		$is_archived = isset($_POST['is_archived']) ? 1 : 0;

		mysqli_query($conn, "UPDATE notes SET title = '$safe_title', category = '$safe_cat', is_pinned = $is_pinned, is_archived = $is_archived, date_last = '$date' WHERE id = $nid");

		$check = mysqli_query($conn, "SELECT id FROM pages WHERE note_id = $nid AND page_number = 1");
		if (mysqli_num_rows($check) > 0) {
			$sql_page = "UPDATE pages SET text = '$content_input' WHERE note_id = $nid AND page_number = 1";
		} else {
			$sql_page = "INSERT INTO pages (note_id, page_number, text) VALUES ($nid, 1, '$content_input')";
		}

		if (mysqli_query($conn, $sql_page)) {
			// Set Flash Message and Redirect (Self-redirect prevents form resubmission)
			$_SESSION['flash'] = ['message' => 'Note saved successfully!', 'type' => 'success'];
			header("Location: notepad.php?id=$nid");
			exit();
		} else {
			$msg = "Error saving content: " . mysqli_error($conn);
			$msg_type = "error";
		}
	}
}

// 3. Load Content
if ($nid != "" && $content == "") {
	$res = mysqli_query($conn, "SELECT text FROM pages WHERE note_id = $nid AND page_number = 1");
	if ($row = mysqli_fetch_assoc($res)) {
		$content = $row['text'];
	}
}
?>
<!DOCTYPE html>
<html>

<head>
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<title><?php echo $ntitle ? htmlspecialchars($ntitle) : "New Note"; ?> - Notebook</title>
</head>

<body>

	<header>
		<div class="header-inner">
			<h1><a href="index.php">Notebook-BAR</a></h1>
			<nav>
				<a href="about.html">About</a>
				<a href="index.php">Notes</a>
				<a href="contact.html">Contact Us</a>
			</nav>
		</div>
	</header>

	<div class="container">
		<?php if ($msg): ?>
			<div class="flash-message flash-<?php echo $msg_type; ?>">
				<?php echo htmlspecialchars($msg); ?>
			</div>
		<?php endif; ?>

		<div class="editor-layout">
			<form method="post">
				<!-- Meta Section -->
				<div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
					<select name="category" class="title-input"
						style="width: auto; font-size: 16px; border-bottom: 2px solid #999;">
						<?php
						$cats = ["General", "Personal", "Work", "Study", "Ideas"];
						foreach ($cats as $c) {
							$sel = ($ncat == $c) ? "selected" : "";
							echo "<option value='$c' $sel>$c</option>";
						}
						?>
					</select>

					<label style="font-size: 14px; display: flex; align-items: center; gap: 5px;">
						<input type="checkbox" name="is_pinned" value="1" <?php if ($is_pinned_val)
							echo "checked"; ?>>
						Pin
					</label>

					<?php if ($nid != ""): ?>
						<label style="font-size: 14px; display: flex; align-items: center; gap: 5px;">
							<input type="checkbox" name="is_archived" value="1" <?php if ($is_archived_val)
								echo "checked"; ?>> Archive
						</label>
					<?php endif; ?>

					<input type="text" name="new_title" class="title-input" placeholder="Note Title" required
						value="<?php echo htmlspecialchars($ntitle); ?>" style="flex-grow: 1;">
				</div>

				<!-- Editor Section -->
				<textarea name="page" class="editor-textarea"
					placeholder="Start writing your note..."><?php echo htmlspecialchars($content); ?></textarea>

				<!-- Stats Bar -->
				<div style="font-size: 12px; color: #777; margin-top: 5px; text-align: right;">
					<span id="word-count">0</span> Words | <span id="char-count">0</span> Characters
				</div>

				<!-- Toolbar -->
				<div class="toolbar">
					<a href="index.php" class="btn btn-secondary">Back to List</a>
					<button type="submit" name="save_note" class="btn btn-primary">Save Note</button>
				</div>
			</form>
		</div>
	</div>

	<script>
		const textarea = document.querySelector('.editor-textarea');
		const wordCount = document.getElementById('word-count');
		const charCount = document.getElementById('char-count');

		function updateStats() {
			const text = textarea.value;
			charCount.textContent = text.length;

			// Basic word count (split by spaces/newlines)
			const words = text.trim().split(/\s+/).filter(word => word.length > 0);
			wordCount.textContent = words.length;
		}

		textarea.addEventListener('input', updateStats);
		// Initial call
		updateStats();
	</script>

</body>

</html>