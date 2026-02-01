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

// 1. Check for ID in URL
// 1. Check for ID in URL
if (isset($_GET['id'])) {
	$nid = intval($_GET['id']);
	// Fetch Title (and verify note exists)
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

// ... (logic skipped) ...

// 3. Load Content
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
						<!-- Archive button moved to toolbar -->
						<input type="hidden" name="is_archived" id="is_archived_input"
							value="<?php echo isset($is_archived_val) ? $is_archived_val : 0; ?>">
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

					<?php if ($nid != ""): ?>
						<?php if (isset($is_archived_val) && $is_archived_val): ?>
							<button type="button" onclick="confirmUnarchive()" class="btn"
								style="background: #e1f5fe; border-color: #039be5; color: #0277bd;">Unarchive Note</button>
						<?php else: ?>
							<button type="button" onclick="confirmArchive()" class="btn"
								style="background: #ffebee; border-color: #ef5350; color: #c62828;">Archive Note</button>
						<?php endif; ?>
					<?php endif; ?>

					<button type="submit" name="save_note" class="btn btn-primary">Save Note</button>
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
				// Submit form
				document.querySelector('form').submit();
			}
		}

		function confirmUnarchive() {
			if (confirm("Are you sure you want to UNARCHIVE this note?\nIt will return to the main list.")) {
				document.getElementById('is_archived_input').value = 0;
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