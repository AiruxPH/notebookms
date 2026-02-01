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
// End of header logic
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
				<a href="about.html">About</a>
				<a href="index.php">Notes</a>
				<a href="contact.html">Contact Us</a>
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
				<div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
					<select name="category" class="title-input"
						style="width: auto; font-size: 16px; border-bottom: 2px solid #999;" <?php echo $is_archived_val ? 'disabled' : ''; ?>>
						<?php
						$all_cats = get_categories();
						foreach ($all_cats as $c) {
							$cname = $c['name'];
							$sel = ($ncat == $cname) ? "selected" : "";
							echo "<option value='$cname' $sel>$cname</option>";
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
				<input type="hidden" name="is_archived" value="<?php echo $is_archived_val; ?>">

				<!-- Editor Section (WYSIWYG) -->
				<!-- Hidden input to store actual value for POST -->
				<input type="hidden" name="page" id="page_content" value="<?php echo htmlspecialchars($content); ?>">

				<div class="editor-div" id="editor" <?php echo $is_archived_val ? 'contenteditable="false"' : 'contenteditable="true"'; ?>>
					<?php echo $content; ?>
				</div>

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
		const editor = document.getElementById('editor');
		const hiddenInput = document.getElementById('page_content');
		const wordCount = document.getElementById('word-count');
		const charCount = document.getElementById('char-count');
		const form = document.querySelector('form');

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
				// Sync content before submit
				hiddenInput.value = editor.innerHTML;
				form.submit();
			}
		}

		function updateStats() {
			if (!editor) return;
			const text = editor.innerText || ""; // innerText gives plain text
			charCount.textContent = text.length;
			const words = text.trim().split(/\s+/).filter(word => word.length > 0);
			wordCount.textContent = words.length;

			// Sync to hidden input on every change (or at least on submit)
			hiddenInput.value = editor.innerHTML;
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