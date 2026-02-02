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
$reminder_date_val = "";
$content = "";
$current_page = 1;
$total_pages = 1;

// 1. Handle POST (Save / Update / Archive / Delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// HANDLE DELETE PERMANENTLY
	if (isset($_POST['delete_permanent'])) {
		$delete_id = $_POST['note_id'];
		if (delete_note_permanently($delete_id)) {
			$_SESSION['flash'] = ['message' => "Note deleted permanently.", 'type' => 'success'];
			header("Location: index.php?archived=1");
			exit();
		} else {
			$msg = "Error deleting note.";
			$msg_type = "error";
		}
	}

	// HANDLE SAVE / ARCHIVE
	elseif (isset($_POST['save_note']) || isset($_POST['save_exit']) || (isset($_POST['action_type']) && $_POST['action_type'] == 'archive_redirect')) {

		// Collect Data
		$save_data = [
			'id' => $_POST['note_id'] ?? '',
			'title' => $_POST['new_title'] ?? 'Untitled',
			'category' => $_POST['category'] ?? 1,
			'text' => $_POST['page'] ?? '',
			'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
			'is_archived' => $_POST['is_archived'] ?? 0,
			'reminder_date' => !empty($_POST['reminder_date']) ? str_replace('T', ' ', $_POST['reminder_date']) : null,
			'page_number' => $_POST['page_number'] ?? 1
		];

		// DEBUG LOGGING
		// DEBUG LOGGING (Absolute Path)
		$log_file = 'C:/Users/ASUS/Documents/notebookms/notebookms/debug_log.txt';
		file_put_contents($log_file, date('Y-m-d H:i:s') . " - Saving Note: ID=" . $save_data['id'] . ", Page=" . $save_data['page_number'] . ", POST_PAGE=" . ($_POST['page_number'] ?? 'NULL') . "\n", FILE_APPEND);

		// SAVE
		$saved_id = save_note($save_data);

		if ($saved_id) {
			$nid = $saved_id;
			$current_page = $save_data['page_number'];

			// Redirect logic
			// if (isset($_POST['save_exit'])) {
			// 	$_SESSION['flash'] = ['message' => "Note saved.", 'type' => 'success'];
			// 	header("Location: index.php");
			// 	exit();
			// }

			// DEBUG MODE: NO REDIRECT
			// if (isset($_POST['action_type']) && $_POST['action_type'] == 'archive_redirect') {
			// 	// ...
			// }

			// PRG DISABLED FOR DEBUGGING
			// $_SESSION['flash'] = ['message' => "Note saved successfully!", 'type' => 'success'];
			// header("Location: notepad.php?id=$nid&page=$current_page&mode=edit");
			// exit();

			$msg = "DEBUG MODE: Saved ID=$saved_id. Posted Page=" . ($_POST['page_number'] ?? 'MISSING');
			$msg_type = "success";

		} else {
			$msg = "Error saving note.";
			$msg_type = "error";
		}
	}
}

// 2. Handle GET (Load Note)
if (isset($_GET['id'])) {
	// ...

	$nid = $_GET['id'];
	$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

	$note = get_note($nid);
	if ($note) {
		$ntitle = $note['title'];
		$ncat = $note['category_id'];
		$is_pinned_val = $note['is_pinned'];
		$is_archived_val = $note['is_archived'];
		$reminder_date_val = $note['reminder_date'];

		$total_pages = get_note_page_count($nid);

		// Fetch Content
		$content = get_note_page($nid, $current_page);

		// Fallback for Page 1 if empty (Preview works, so $note['text'] should be reliable)
		if (empty($content) && $current_page == 1 && !empty($note['text'])) {
			$content = $note['text'];
		}
	} else {
		header("Location: index.php");
		exit();
	}
}

// Check for Flash Message
if (isset($_SESSION['flash'])) {
	$msg = $_SESSION['flash']['message'];
	$msg_type = $_SESSION['flash']['type'];
	unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<link rel="icon" href="favicon.png" type="image/png">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<title><?php echo $ntitle ? htmlspecialchars($ntitle) : "New Note"; ?> - Notebook</title>
</head>

<body>

	<header>
		<div class="header-inner">
			<h1><a href="dashboard.php">Notebook-BAR</a> <small style="font-size: 12px; color: #555;">(Page:
					<?php echo $current_page; ?>)</small></h1>
			<nav>
				<a href="dashboard.php">Dashboard</a>
				<a href="index.php">Notes</a>
				<a href="categories.php">Categories</a>
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
		<div id="toast-overlay" class="toast-overlay">
			<div id="toast-message" class="toast-message"></div>
		</div>

		<div class="editor-layout">
			<form method="post" id="note-form">
				<input type="hidden" name="action_type" id="action_type" value="save">

				<?php
				$mode = 'edit';
				if ($nid != "") {
					$mode = (isset($_GET['mode']) && $_GET['mode'] == 'edit') ? 'edit' : 'view';
				}
				if ($is_archived_val)
					$mode = 'view';
				$is_view_mode = ($mode == 'view');
				?>

				<!-- VIEW MODE HEADER (Clean Look) -->
				<?php if ($is_view_mode): ?>
					<div class="editor-metadata-bar"
						style="justify-content: space-between; border-bottom: 2px solid #ddd; padding-bottom: 15px;">

						<!-- Left: Title & Category -->
						<div>
							<h1
								style="margin: 0; font-size: 24px; font-family: 'Courier New', monospace; font-weight: bold;">
								<?php echo htmlspecialchars($ntitle); ?>
							</h1>
							<div style="font-size: 13px; color: #666; margin-top: 5px;">
								<span style="background: #eee; padding: 2px 6px; border-radius: 4px;">
									<?php
									// Get Category Name
									$cat_name = "General";
									foreach (get_categories() as $c) {
										if ($c['id'] == $ncat) {
											$cat_name = $c['name'];
											break;
										}
									}
									echo htmlspecialchars($cat_name);
									?>
								</span>
								<?php if ($reminder_date_val): ?>
									<span style="color: #c62828; margin-left: 10px;"><i class="fa-regular fa-clock"></i>
										<?php echo date("M j, g:i A", strtotime($reminder_date_val)); ?></span>
								<?php endif; ?>
								<span style="margin-left: 10px; color: #888;">Page <?php echo $current_page; ?> of
									<?php echo $total_pages; ?></span>
							</div>
						</div>

						<!-- Right: Status Icons Only (Clean) -->
						<div style="display: flex; gap: 10px; align-items: center;">
							<?php if ($is_pinned_val): ?>
								<span title="Pinned" style="font-size: 18px; color: #555;"><i
										class="fa-solid fa-thumbtack"></i></span>
							<?php endif; ?>
							<?php if ($is_archived_val): ?>
								<span title="Archived" style="font-size: 18px; color: #888;"><i
										class="fa-solid fa-box-archive"></i></span>
							<?php endif; ?>
						</div>
					</div>

					<div class="notebook-container" style="margin-top: 20px;">
						<div class="notebook-paper" id="view-content">
							<?php echo $content; ?>
						</div>

						<!-- Pagination -->
						<?php if ($total_pages > 1): ?>
							<div class="pagination-bar">
								<?php if ($current_page > 1): ?>
									<a href="?id=<?php echo $nid; ?>&page=<?php echo $current_page - 1; ?>&mode=view"
										class="page-btn"><i class="fa-solid fa-chevron-left"></i> Prev</a>
								<?php else: ?>
									<span class="page-btn disabled"><i class="fa-solid fa-chevron-left"></i> Prev</span>
								<?php endif; ?>

								<span class="page-indicator">Page <?php echo $current_page; ?></span>

								<?php if ($current_page < $total_pages): ?>
									<a href="?id=<?php echo $nid; ?>&page=<?php echo $current_page + 1; ?>&mode=view"
										class="page-btn">Next <i class="fa-solid fa-chevron-right"></i></a>
								<?php else: ?>
									<span class="page-btn disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<!-- EDIT MODE (Classic Editor) -->
				<?php else: ?>

					<div class="editor-metadata-bar">
						<select name="category" class="cat-select" <?php echo $is_archived_val ? 'disabled' : ''; ?>>
							<?php
							$all_cats = get_categories();
							$defaults = [];
							$custom = [];
							$default_names = ['General', 'Personal', 'Work', 'Study', 'Ideas'];
							foreach ($all_cats as $c) {
								if (in_array($c['name'], $default_names))
									$defaults[] = $c;
								else
									$custom[] = $c;
							}
							if (!empty($defaults)) {
								echo "<optgroup label='Defaults'>";
								foreach ($defaults as $c) {
									$sel = ($ncat == $c['id']) ? "selected" : "";
									echo "<option value='{$c['id']}' $sel>" . htmlspecialchars($c['name']) . "</option>";
								}
								echo "</optgroup>";
							}
							if (!empty($custom)) {
								echo "<optgroup label='My Categories'>";
								foreach ($custom as $c) {
									$sel = ($ncat == $c['id']) ? "selected" : "";
									echo "<option value='{$c['id']}' $sel>" . htmlspecialchars($c['name']) . "</option>";
								}
								echo "</optgroup>";
							}
							?>
						</select>

						<!-- PIN CHECKBOX RESTORED -->
						<label class="pin-label">
							<input type="checkbox" name="is_pinned" value="1" <?php if ($is_pinned_val)
								echo "checked"; ?>
								<?php echo $is_archived_val ? 'disabled' : ''; ?>>
							<i class="fa-solid fa-thumbtack" style="font-size: 12px;"></i> Pin
						</label>

						<div style="margin-left: auto; display: flex; align-items: center; gap: 5px;">
							<label><i class="fa-regular fa-clock"></i></label>
							<input type="datetime-local" name="reminder_date"
								value="<?php echo $reminder_date_val ? date('Y-m-d\TH:i', strtotime($reminder_date_val)) : ''; ?>"
								style="font-size: 13px;">
						</div>
					</div>

					<div class="title-row">
						<textarea name="new_title" class="title-input" placeholder="Note Title" required maxlength="100"
							style="width: 100%; resize: none; overflow: hidden; min-height: 32px;"
							oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px';" <?php echo $is_archived_val ? 'disabled' : ''; ?>><?php echo htmlspecialchars($ntitle); ?></textarea>
					</div>

					<!-- Formatting Toolbar -->
					<div
						style="background: #eee; padding: 5px; border: 1px solid #ccc; border-bottom: none; display: flex; gap: 5px;">
						<button type="button" onclick="formatText('b')" style="font-weight: bold; width: 30px;"><i
								class="fa-solid fa-bold"></i></button>
						<button type="button" onclick="formatText('i')" style="font-style: italic; width: 30px;"><i
								class="fa-solid fa-italic"></i></button>
						<button type="button" onclick="formatText('u')" style="text-decoration: underline; width: 30px;"><i
								class="fa-solid fa-underline"></i></button>
						<span style="border-left: 1px solid #ccc; margin: 0 5px;"></span>
						<button type="button" onclick="formatText('h3')" style="font-weight: bold; width: 30px;">H3</button>
						<button type="button" onclick="formatText('li')" style="width: 30px;"><i
								class="fa-solid fa-list-ul"></i></button>
					</div>

					<input type="hidden" name="note_id" value="<?php echo htmlspecialchars($nid); ?>">
					<input type="hidden" name="page" id="page_content" value="<?php echo htmlspecialchars($content); ?>">
					<input type="hidden" name="is_archived" id="is_archived_input"
						value="<?php echo isset($is_archived_val) ? $is_archived_val : 0; ?>">

					<div style="background:red; color:white; padding:5px; font-weight:bold;">
						DEBUG PAGE: <input type="text" name="page_number" value="<?php echo $current_page; ?>">
					</div>

					<div class="editor-div" id="editor" contenteditable="true">
						<?php echo $content; ?>
					</div>

					<!-- Edit Mode Pagination -->
					<?php if ($nid != ""): ?>
						<div class="pagination-bar" style="background: #f0f0f0;">
							<?php if ($current_page > 1): ?>
								<a href="?id=<?php echo $nid; ?>&page=<?php echo $current_page - 1; ?>&mode=edit"
									onclick="return confirmNavigation()" class="page-btn"><i class="fa-solid fa-chevron-left"></i>
									Prev</a>
							<?php endif; ?>

							<span class="page-indicator">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>

							<?php if ($current_page < $total_pages): ?>
								<a href="?id=<?php echo $nid; ?>&page=<?php echo $current_page + 1; ?>&mode=edit"
									onclick="return confirmNavigation()" class="page-btn">Next <i
										class="fa-solid fa-chevron-right"></i></a>
							<?php endif; ?>

							<!-- Add Page -->
							<a href="?id=<?php echo $nid; ?>&page=<?php echo $total_pages + 1; ?>&mode=edit"
								onclick="return confirmNavigation()" class="page-btn add-page-btn"><i
									class="fa-solid fa-plus"></i> New Page</a>
						</div>
					<?php endif; ?>

				<?php endif; ?>

				<!-- Floating Action Buttons (FAB) -->
				<div class="fab-container">

					<!-- SAVE / EDIT Actions -->
					<?php if (!$is_archived_val && !$is_view_mode): ?>
						<button type="submit" name="save_exit" class="fab-btn fab-save" title="Save & Exit">
							<i class="fa-solid fa-floppy-disk"></i>
							<span class="fab-label">Save & Exit</span>
						</button>
						<button type="submit" name="save_note" class="fab-btn fab-secondary" title="Save">
							<i class="fa-solid fa-check"></i>
							<span class="fab-label">Save</span>
						</button>
					<?php endif; ?>

					<!-- EDIT Toggle -->
					<?php if ($is_view_mode && !$is_archived_val): ?>
						<a href="notepad.php?id=<?php echo $nid; ?>&mode=edit&page=<?php echo $current_page; ?>"
							class="fab-btn fab-primary" title="Edit Note">
							<i class="fa-solid fa-pen"></i>
							<span class="fab-label">Edit</span>
						</a>
					<?php elseif (!$is_view_mode && $nid != ""): ?>
						<a href="notepad.php?id=<?php echo $nid; ?>&mode=view&page=<?php echo $current_page; ?>"
							class="fab-btn fab-secondary" onclick="return confirmNavigation()" title="View Mode">
							<i class="fa-solid fa-eye"></i>
							<span class="fab-label">View</span>
						</a>
					<?php endif; ?>

					<!-- Archive / Delete Actions -->
					<?php if ($nid != ""): ?>
						<?php if (isset($is_archived_val) && $is_archived_val): ?>
							<!-- DELETE PERMANENT -->
							<button type="button" onclick="confirmDeletePermanent()" class="fab-btn"
								style="background: #ffebee; color: #c62828;">
								<i class="fa-solid fa-trash-can"></i>
								<span class="fab-label">Delete Forever</span>
							</button>
							<!-- UNARCHIVE -->
							<button type="button" onclick="confirmUnarchive()" class="fab-btn"
								style="background: #e1f5fe; color: #0277bd;">
								<i class="fa-solid fa-box-open"></i>
								<span class="fab-label">Unarchive</span>
							</button>
						<?php elseif (!$is_view_mode): ?>
							<!-- Archive (Edit Mode) -->
							<button type="button" onclick="confirmArchive()" class="fab-btn"
								style="background: #ffebee; color: #c62828;">
								<i class="fa-solid fa-box-archive"></i>
								<span class="fab-label">Archive</span>
							</button>
						<?php else: ?>
							<!-- Archive (View Mode) -->
							<button type="button" onclick="confirmArchive()" class="fab-btn"
								style="background: #ffebee; color: #c62828;">
								<i class="fa-solid fa-box-archive"></i>
								<span class="fab-label">Archive</span>
							</button>
						<?php endif; ?>
					<?php endif; ?>

					<!-- Removed Home Button as requested -->
				</div>
			</form>
		</div>
	</div>

	<script>
		// Common JS
		const editor = document.getElementById('editor');
		const hiddenInput = document.getElementById('page_content');
		const form = document.getElementById('note-form');

		if (editor) {
			form.addEventListener('submit', function () {
				hiddenInput.value = editor.innerHTML;
			});
		}

		function formatText(command) {
			if (!editor) return;
			editor.focus();
			if (command === 'h3') document.execCommand('formatBlock', false, '<h3>');
			else if (command === 'li') document.execCommand('insertUnorderedList', false, null);
			else {
				let cmd = (command === 'i') ? 'italic' : (command === 'u' ? 'underline' : 'bold');
				document.execCommand(cmd, false, null);
			}
		}

		function confirmArchive() {
			if (confirm("Archive this note?")) {
				document.getElementById('is_archived_input').value = 1;
				document.getElementById('action_type').value = 'archive_redirect';
				if (editor) hiddenInput.value = editor.innerHTML;
				form.submit();
			}
		}

		function confirmUnarchive() {
			if (confirm("Unarchive this note?")) {
				// We can use the main form to handle this to ensure data is saved
				document.getElementById('is_archived_input').value = 0;
				document.getElementById('action_type').value = 'archive_redirect';

				// Sync content if editor exists
				if (editor) hiddenInput.value = editor.innerHTML;

				// Enable disabled fields for submission
				const disabled = form.querySelectorAll('[disabled]');
				disabled.forEach(el => el.disabled = false);

				form.submit();
			}
		}

		function confirmNavigation() {
			return true;
		}

		function updateStats() {
			if (!editor) return;

			// SYNC CONTENT ON EVERY UPDATE (Fixes missing body issue)
			if (hiddenInput) hiddenInput.value = editor.innerHTML;

			const text = editor.innerText || "";
			const words = text.trim().split(/\s+/).filter(word => word.length > 0);
			const wordCountTop = document.getElementById('word-count-top');
			const bodyCharCount = document.getElementById('body-char-count');
			const titleInput = document.querySelector('textarea[name="new_title"]');
			const charCountTitle = document.getElementById('title-char-counter');

			if (wordCountTop) wordCountTop.textContent = words.length;
			if (bodyCharCount) bodyCharCount.textContent = text.length;

			if (titleInput && charCountTitle) {
				charCountTitle.textContent = titleInput.value.length + "/100";
			}
		}

		const titleInput = document.querySelector('textarea[name="new_title"]');
		if (titleInput) {
			titleInput.addEventListener('input', function () {
				this.style.height = '';
				this.style.height = this.scrollHeight + 'px';
				updateStats();
			});
			// Initial height
			titleInput.style.height = titleInput.scrollHeight + 'px';

			titleInput.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') e.preventDefault();
			});
		}

		if (editor) {
			editor.addEventListener('input', updateStats);
			editor.addEventListener('blur', updateStats);
			// Initial sync and stats update
			updateStats();
		}

		// Toast Logic
		const toastOverlay = document.getElementById('toast-overlay');
		const toastMessage = document.getElementById('toast-message');

		function showToast(msg, type) {
			if (!toastMessage) return;
			toastMessage.textContent = msg;
			toastMessage.className = "toast-message " + (type === 'error' ? 'toast-error' : 'toast-success');
			// Force reflow
			void toastMessage.offsetWidth;
			toastOverlay.style.display = 'flex';
			requestAnimationFrame(() => {
				toastMessage.classList.add('show');
			});
			setTimeout(() => {
				toastMessage.classList.remove('show');
				setTimeout(() => { toastOverlay.style.display = 'none'; }, 300);
			}, 3000);
		}

		<?php if ($msg): ?>
			showToast("<?php echo addslashes($msg); ?>", "<?php echo $msg_type; ?>");
		<?php endif; ?>
	</script>
</body>

</html>