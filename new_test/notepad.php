<?php
include 'includes/db.php';
session_start(); // Start session for Flash messages

$msg = "";
$msg_type = "";
$nid = "";
$ntitle = "";
$ncat = "General"; // Default category
$is_pinned_val = 0;
$is_archived_val = 0;
$content = "";

// Check for Flash Message
if (isset($_SESSION['flash'])) {
	$msg = $_SESSION['flash']['message'];
	$msg_type = $_SESSION['flash']['type'];
	unset($_SESSION['flash']); // Clear immediately
}

// 1. Check for ID and Fetch Existing Data FIRST
if (isset($_GET['id'])) {
	$nid = intval($_GET['id']);
	$res = mysqli_query($conn, "SELECT * FROM notes WHERE id = $nid");
	if ($row = mysqli_fetch_assoc($res)) {
		$ntitle = $row['title'];
		$ncat = $row['category'];
		$is_pinned_val = $row['is_pinned'];
		$is_archived_val = $row['is_archived'];
	} else {
		$nid = ""; // Invalid ID
	}
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save_note']) || isset($_POST['save_exit']) || isset($_POST['action_type']))) {
	$content_input = isset($_POST['page']) ? mysqli_real_escape_string($conn, $_POST['page']) : $content;
	$date = date('Y-m-d H:i:s');

	// Use existing values ($ntitle, $ncat) if POST is missing (disabled inputs)
	$title_input = isset($_POST['new_title']) ? trim($_POST['new_title']) : $ntitle;
	$category_input = isset($_POST['category']) ? $_POST['category'] : $ncat;

	$new_title = mysqli_real_escape_string($conn, $title_input);
	$category = mysqli_real_escape_string($conn, $category_input);
	$is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
	// Use POST value if set, otherwise keep existing (important for unarchive which sends via hidden input)
	$is_archived = isset($_POST['is_archived']) ? intval($_POST['is_archived']) : $is_archived_val;

	$action_type = isset($_POST['action_type']) ? $_POST['action_type'] : 'save';

	if ($nid == "") {
		// Insert new note
		$stmt = $conn->prepare("INSERT INTO notes (title, category, is_pinned, is_archived, date_created, date_last) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("ssiiss", $new_title, $category, $is_pinned, $is_archived, $date, $date);
		$stmt->execute();
		$nid = $stmt->insert_id;
		$stmt->close();

		// Insert initial page
		$stmt = $conn->prepare("INSERT INTO pages (note_id, page_number, text) VALUES (?, 1, ?)");
		$stmt->bind_param("is", $nid, $content_input);
		$stmt->execute();
		$stmt->close();

		$_SESSION['flash'] = ['message' => 'Note created successfully!', 'type' => 'success'];
	} else {
		// Update existing note
		$stmt = $conn->prepare("UPDATE notes SET title = ?, category = ?, is_pinned = ?, is_archived = ?, date_last = ? WHERE id = ?");
		$stmt->bind_param("ssiisi", $new_title, $category, $is_pinned, $is_archived, $date, $nid);
		$stmt->execute();
		$stmt->close();

		// Update existing page
		$check = mysqli_query($conn, "SELECT id FROM pages WHERE note_id = $nid AND page_number = 1");
		if (mysqli_num_rows($check) > 0) {
			$stmt = $conn->prepare("UPDATE pages SET text = ? WHERE note_id = ? AND page_number = 1");
			$stmt->bind_param("si", $content_input, $nid);
			$stmt->execute();
			$stmt->close();
		} else {
			$stmt = $conn->prepare("INSERT INTO pages (note_id, page_number, text) VALUES (?, 1, ?)");
			$stmt->bind_param("is", $nid, $content_input);
			$stmt->execute();
			$stmt->close();
		}

		$_SESSION['flash'] = ['message' => 'Note updated successfully!', 'type' => 'success'];
	}

	// Redirect based on action
	if ($action_type == 'archive_redirect' || isset($_POST['save_exit'])) {
		header("Location: index.php");
		exit();
	} else {
		header("Location: notepad.php?id=$nid");
		exit();
	}
}

// 3. Load Content (if not already loaded/handled)
if ($nid != "" && $content == "") {
	$res = mysqli_query($conn, "SELECT text FROM pages WHERE note_id = $nid AND page_number = 1");
	if ($page_row = mysqli_fetch_assoc($res)) {
		$content = $page_row['text'];
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
		<!-- Popup Container -->
		<div id="popup-overlay" class="popup-overlay">
			<div class="popup-content">
				<div id="popup-message" class="popup-message"></div>
				<button class="popup-btn" onclick="closePopup()">OK</button>
			</div>
		</div>

		<div class="editor-layout">
			<form method="post">
				<input type="hidden" name="action_type" id="action_type" value="save">
				<!-- Meta Section -->
				<div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
					<select name="category" class="title-input"
						style="width: auto; font-size: 16px; border-bottom: 2px solid #999;" <?php echo $is_archived_val ? 'disabled' : ''; ?>>
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
							echo "checked"; ?>
							<?php echo $is_archived_val ? 'disabled' : ''; ?>>
						Pin
					</label>

					<?php if ($nid != ""): ?>
						<!-- Archive button moved to toolbar -->
						<input type="hidden" name="is_archived" id="is_archived_input"
							value="<?php echo isset($is_archived_val) ? $is_archived_val : 0; ?>">
					<?php endif; ?>

					<input type="text" name="new_title" class="title-input" placeholder="Note Title" required
						value="<?php echo htmlspecialchars($ntitle); ?>" style="flex-grow: 1;" <?php echo $is_archived_val ? 'disabled' : ''; ?>>
				</div>

				<!-- Editor Section -->
				<textarea name="page" class="editor-textarea" placeholder="Start writing your note..." <?php echo $is_archived_val ? 'disabled' : ''; ?>><?php echo htmlspecialchars($content); ?></textarea>

				<!-- Stats Bar -->
				<div style="font-size: 12px; color: #777; margin-top: 5px; text-align: right;">
					<span id="word-count">0</span> Words | <span id="char-count">0</span> Characters
				</div>

				<!-- Toolbar -->
				<div class="toolbar">
					<a href="index.php" class="btn btn-secondary">Back to List</a>

					<?php if ($nid != ""): ?>
						<?php if (isset($is_archived_val) && $is_archived_val): ?>
							<button type="button" onclick="confirmUnarchive()" class="btn"
								style="background: #e1f5fe; border-color: #039be5; color: #0277bd;">Unarchive Note</button>
						<?php else: ?>
							<button type="button" onclick="confirmArchive()" class="btn"
								style="background: #ffebee; border-color: #ef5350; color: #c62828;">Archive Note</button>
						<?php endif; ?>
					<?php endif; ?>

					<?php if (!$is_archived_val): ?>
						<button type="submit" name="save_note" class="btn btn-primary"
							style="margin-left: auto; margin-right: 10px;">Save</button>
						<button type="submit" name="save_exit" class="btn btn-primary">Save & Exit</button>
					<?php endif; ?>
				</div>
			</form>
		</div>
	</div>

	<script>
		const textarea = document.querySelector('.editor-textarea');
		const wordCount = document.getElementById('word-count');
		const charCount = document.getElementById('char-count');

		function confirmArchive() {
			if (confirm("Are you sure you want to ARCHIVE this note?\nIt will be hidden from the main list.")) {
				document.getElementById('is_archived_input').value = 1;
				document.getElementById('action_type').value = 'archive_redirect';
				// Submit form
				document.querySelector('form').submit();
			}
		}

		function confirmUnarchive() {
			if (confirm("Are you sure you want to UNARCHIVE this note?\nIt will return to the main list.")) {
				document.getElementById('is_archived_input').value = 0;
				document.getElementById('action_type').value = 'archive_redirect';
				// Submit form
				document.querySelector('form').submit();
			}
		}

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

		// Popup Logic
		const popupOverlay = document.getElementById('popup-overlay');
		const popupMessage = document.getElementById('popup-message');

		function showPopup(msg, type) {
			popupMessage.textContent = msg;
			popupMessage.className = "popup-message " + (type === 'error' ? 'flash-error' : 'flash-success');
			// We reuse flash-error/success for text color if we want, or just leave it standard.
			// Let's reset color to black just in case, or use specific colors.
			popupMessage.style.color = (type === 'error') ? '#c62828' : '#2e7d32';

			popupOverlay.style.display = 'flex';
		}

		function closePopup() {
			popupOverlay.style.display = 'none';
		}

		// Trigger from PHP
		<?php if ($msg): ?>
			showPopup("<?php echo addslashes($msg); ?>", "<?php echo $msg_type; ?>");
		<?php endif; ?>
	</script>

</body>

</html>