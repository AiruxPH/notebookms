<?php
include 'includes/data_access.php';
// session_start(); is already in data_access via db.php (conditionally) or we can ensure it.
// db.php usually does session_start.

$msg = "";
$msg_type = "";
$nid = "";
$ntitle = "";
$ncat = 1; // Default category ID (1 = General)
$is_pinned_val = 0;
$is_archived_val = 0;
$content = "";

// 1. Handle POST (Save / Update / Archive)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save_note']) || isset($_POST['save_exit']) || (isset($_POST['action_type']) && $_POST['action_type'] == 'archive_redirect'))) {

	// Collect Data
	$save_data = [
		'id' => $_POST['note_id'] ?? '',
		'title' => $_POST['new_title'] ?? 'Untitled',
		'category' => $_POST['category'] ?? 1,
		'text' => $_POST['page'] ?? '',
		'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
		'is_archived' => $_POST['is_archived'] ?? 0
	];

	// SAVE
	$saved_id = save_note($save_data);

	if ($saved_id) {
		$nid = $saved_id; // Update ID if it was new
		$msg = "Note saved successfully!";
		$msg_type = "success";

		// Redirect logic
		if (isset($_POST['save_exit'])) {
			header("Location: index.php");
			exit();
		}
		if (isset($_POST['action_type']) && $_POST['action_type'] == 'archive_redirect') {
			$action_msg = $save_data['is_archived'] ? "Note Archived" : "Note Unarchived";
			$_SESSION['flash'] = ['message' => $action_msg, 'type' => 'success'];
			header("Location: index.php");
			exit();
		}
		// If just Save, stay here but reload clean to show updates/ID
		// We can just populate variables from POST to show valid state immediately
		$ntitle = $save_data['title'];
		$ncat = $save_data['category'];
		$content = $save_data['text'];
		$is_pinned_val = $save_data['is_pinned'];
		$is_archived_val = $save_data['is_archived'];
	} else {
		$msg = "Error saving note.";
		$msg_type = "error";
	}
}

// 2. Handle GET (Load Note)
if (isset($_GET['id'])) {
	$nid = $_GET['id'];
	$note = get_note($nid);
	if ($note) {
		$ntitle = $note['title'];
		$ncat = $note['category_id'];
		$content = $note['text'];
		$is_pinned_val = $note['is_pinned'];
		$is_archived_val = $note['is_archived'];
		$date_last_display = date("M j, Y, g:i A", strtotime($note['date_last']));
	} else {
		// Note not found or not owned
		header("Location: index.php");
		exit();
	}
}

// Check for Flash Message (from redirection)
if (isset($_SESSION['flash'])) {
	$msg = $_SESSION['flash']['message'];
	$msg_type = $_SESSION['flash']['type'];
	unset($_SESSION['flash']);
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
			<h1><a href="dashboard.php">Notebook-BAR</a></h1>
			<nav>
				<a href="dashboard.php">Dashboard</a>
				<a href="index.php">Notes</a>
				<a href="categories.php">Categories</a>
				<?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0): ?>
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

		<div class="editor-layout">
			<form method="post">
				<input type="hidden" name="action_type" id="action_type" value="save">
				<!-- Meta Section -->
				<div class="editor-metadata-bar">
					<select name="category" class="cat-select" <?php echo $is_archived_val ? 'disabled' : ''; ?>>
						<?php
						$all_cats = get_categories();

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
								// VALUE should be ID now
								$cname = htmlspecialchars($c['name']);
								$cid = $c['id'];
								$sel = ($ncat == $cid) ? "selected" : "";
								echo "<option value='$cid' $sel>$cname</option>";
							}
							echo "</optgroup>";
						}

						if (!empty($custom)) {
							echo "<optgroup label='My Categories'>";
							foreach ($custom as $c) {
								$cname = htmlspecialchars($c['name']);
								$cid = $c['id'];
								$sel = ($ncat == $cid) ? "selected" : "";
								echo "<option value='$cid' $sel>$cname</option>";
							}
							echo "</optgroup>";
						}
						?>
					</select>

					<label class="pin-label">
						<input type="checkbox" name="is_pinned" value="1" <?php if ($is_pinned_val)
							echo "checked"; ?>
							<?php echo $is_archived_val ? 'disabled' : ''; ?>>
						Pin Note
					</label>

					<!-- Archive button moved to toolbar -->
					<input type="hidden" name="is_archived" id="is_archived_input"
						value="<?php echo isset($is_archived_val) ? $is_archived_val : 0; ?>">
				</div>

				<div class="title-row">
					<div class="title-container">
						<textarea name="new_title" class="title-input" placeholder="Note Title" required maxlength="100"
							style="width: 100%; resize: none; overflow: hidden; min-height: 32px; height: auto;"
							oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px';" <?php echo $is_archived_val ? 'disabled' : ''; ?>><?php echo htmlspecialchars($ntitle); ?></textarea>
						<span id="title-char-counter" class="title-char-counter">0/100</span>
					</div>
				</div>

				<!-- Stats Bar Below Title -->
				<div class="editor-stats-bar">
					<span id="word-count-top">0</span> words |
					<span id="body-char-count">0</span> characters |
					<?php if ($date_last_display): ?>
						Last Modified: <?php echo $date_last_display; ?>
					<?php else: ?>
						New Note
					<?php endif; ?>
				</div>

				<!-- Formatting Toolbar -->
				<div
					style="background: #eee; padding: 5px; border: 1px solid #ccc; border-bottom: none; display: flex; gap: 5px;">
					<button type="button" onclick="formatText('b')" style="font-weight: bold; width: 30px;"
						title="Bold">B</button>
					<button type="button" onclick="formatText('i')" style="font-style: italic; width: 30px;"
						title="Italic">I</button>
					<button type="button" onclick="formatText('u')" style="text-decoration: underline; width: 30px;"
						title="Underline">U</button>
					<span style="border-left: 1px solid #ccc; margin: 0 5px;"></span>
					<button type="button" onclick="formatText('h3')" style="font-weight: bold; width: 30px;"
						title="Heading">H</button>
					<button type="button" onclick="formatText('li')" style="width: 30px;" title="List Item">•</button>
				</div>

				<input type="hidden" name="note_id" value="<?php echo htmlspecialchars($nid); ?>">

				<!-- Editor Section (WYSIWYG) -->
				<!-- Hidden input to store actual value for POST -->
				<input type="hidden" name="page" id="page_content" value="<?php echo htmlspecialchars($content); ?>">

				<div class="editor-div" id="editor" <?php echo $is_archived_val ? 'contenteditable="false"' : 'contenteditable="true"'; ?>>
					<?php echo $content; ?>
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
		const editor = document.getElementById('editor');
		const hiddenInput = document.getElementById('page_content');
		const wordCountTop = document.getElementById('word-count-top');
		const bodyCharCount = document.getElementById('body-char-count');
		const charCountTitle = document.getElementById('title-char-counter');
		const titleInput = document.querySelector('textarea[name="new_title"]');
		const form = document.querySelector('form');

		// Initial Auto-grow for title
		if (titleInput) {
			titleInput.style.height = titleInput.scrollHeight + 'px';
		}

		// Prevent Enter key in title
		if (titleInput) {
			titleInput.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') {
					e.preventDefault();
				}
			});
		}

		// Toolbar Action
		function formatText(command) {
			if (!editor) return;
			editor.focus();

			// Map our custom args to execCommand args
			if (command === 'h3') {
				document.execCommand('formatBlock', false, '<h3>');
			} else if (command === 'li') {
				document.execCommand('insertUnorderedList', false, null);
			} else {
				// b, i, u
				let cmd = 'bold';
				if (command === 'i') cmd = 'italic';
				if (command === 'u') cmd = 'underline';
				document.execCommand(cmd, false, null);
			}
			updateStats();
		}

		function confirmArchive() {
			if (confirm("Are you sure you want to ARCHIVE ?")) {
				document.getElementById('is_archived_input').value = 1;
				document.getElementById('action_type').value = 'archive_redirect';
				// Sync content before submit
				hiddenInput.value = editor.innerHTML;
				form.submit();
			}
		}

		function confirmUnarchive() {
			if (confirm("Are you sure you want to UNARCHIVE ?")) {
				document.getElementById('is_archived_input').value = 0;
				document.getElementById('action_type').value = 'archive_redirect';

				// Enable all disabled inputs so their values are submitted
				const disabled = form.querySelectorAll('[disabled]');
				disabled.forEach(el => el.disabled = false);

				// Sync content before submit
				hiddenInput.value = editor.innerHTML;
				form.submit();
			}
		}

		function updateStats() {
			if (!editor) return;
			const text = editor.innerText || ""; // innerText gives plain text
			const words = text.trim().split(/\s+/).filter(word => word.length > 0);

			if (wordCountTop) wordCountTop.textContent = words.length;
			if (bodyCharCount) bodyCharCount.textContent = text.length;

			if (titleInput) {
				if (charCountTitle) charCountTitle.textContent = titleInput.value.length + "/100";
			}

			// Sync to hidden input on every change (or at least on submit)
			hiddenInput.value = editor.innerHTML;
		}

		if (titleInput) {
			titleInput.addEventListener('input', updateStats);
		}

		if (editor) {
			editor.addEventListener('input', updateStats);
			// Also sync on blur just in case
			editor.addEventListener('blur', updateStats);

			// Handle Form Submit
			form.addEventListener('submit', function () {
				hiddenInput.value = editor.innerHTML;
			});

			updateStats();
		}

		// Toast Logic
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

		// Trigger from PHP
		<?php if ($msg): ?>
			showToast("<?php echo addslashes($msg); ?>", "<?php echo $msg_type; ?>");
		<?php endif; ?>
	</script>
</body>

</html>