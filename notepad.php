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

		// SAVE
		$saved_id = save_note($save_data);

		if ($saved_id) {
			$nid = $saved_id;
			$msg = "Note saved successfully!";
			$msg_type = "success";
			$current_page = $save_data['page_number']; // Stay on same page

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

			// Refresh Data to ensure consistency
			// We redirect to self to avoid form resubmission and reload fresh data
			// header("Location: notepad.php?id=$nid&page=$current_page&mode=edit");
			// exit; 
			// User requested "stay here" mostly so let's just populate variables

			$ntitle = $save_data['title'];
			$ncat = $save_data['category'];
			$content = $save_data['text'];
			$is_pinned_val = $save_data['is_pinned'];
			$is_archived_val = $save_data['is_archived'];
			$reminder_date_val = $save_data['reminder_date'];

			$total_pages = get_note_page_count($nid); // Update total pages logic

		} else {
			$msg = "Error saving note.";
			$msg_type = "error";
		}
	}
}

// 2. Handle GET (Load Note) - OR Fallback from POST
// If $nid is set from POST, we already have some data, but let's re-fetch if empty to be safe or sync
if (isset($_GET['id']) || ($nid != "" && $_SERVER['REQUEST_METHOD'] == 'POST')) {
	if (isset($_GET['id']))
		$nid = $_GET['id'];
	$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : $current_page;

	$note = get_note($nid);
	if ($note) {
		$ntitle = $note['title'];
		$ncat = $note['category_id'];
		$is_pinned_val = $note['is_pinned'];
		$is_archived_val = $note['is_archived'];
		$reminder_date_val = $note['reminder_date'];
		$date_last_display = date("M j, Y, g:i A", strtotime($note['date_last']));

		// Page Logic
		$total_pages = get_note_page_count($nid);
		if ($current_page > $total_pages && $total_pages > 0) {
			// If requested page doesn't exist (e.g. creating new page logic handled by edit mode)
			// For view mode, clamp?
			// For Edit mode, if we want to create page, we allow it if = total+1
		}

		$content = get_note_page($nid, $current_page);

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

				<!-- VIEW MODE HEADER (New Look) -->
				<?php if ($is_view_mode): ?>
					<div class="editor-metadata-bar"
						style="justify-content: space-between; border-bottom: 2px solid #ddd; padding-bottom: 15px;">

						<!-- Left: Title & Category -->
						<div>
							<h1 style="margin: 0; font-size: 28px; font-family: 'Courier New', monospace;">
								<?php echo htmlspecialchars($ntitle); ?></h1>
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
									<span style="color: #c62828; margin-left: 10px;">⏰
										<?php echo date("M j, g:i A", strtotime($reminder_date_val)); ?></span>
								<?php endif; ?>
								<span style="margin-left: 10px; color: #888;">Page <?php echo $current_page; ?> of
									<?php echo $total_pages; ?></span>
							</div>
						</div>

						<!-- Right: Actions (Pin, Archive, Edit) -->
						<div style="display: flex; gap: 10px; align-items: center;">
							<?php if ($is_pinned_val): ?>
								<span title="Pinned" style="font-size: 20px;">📌</span>
							<?php endif; ?>

							<?php if ($is_archived_val): ?>
								<form method="post"
									onsubmit="return confirm('Permanently delete this note? This cannot be undone.');">
									<input type="hidden" name="note_id" value="<?php echo $nid; ?>">
									<button type="submit" name="delete_permanent" class="btn"
										style="background: #ffebee; color: #c62828; border-color: #ef9a9a; padding: 8px 15px; font-size: 12px;">DELETE
										FOREVER</button>
								</form>
								<button type="button" onclick="confirmUnarchive()" class="btn"
									style="background: #e1f5fe; color: #0277bd; border-color: #039be5; padding: 8px 15px; font-size: 12px;">UNARCHIVE</button>
							<?php else: ?>
								<a href="notepad.php?id=<?php echo $nid; ?>&mode=edit&page=<?php echo $current_page; ?>"
									class="btn btn-primary"
									style="background: #2196f3; border-color: #1976d2; padding: 8px 15px;">✏️ Edit</a>
								<button type="button" onclick="confirmArchive()" class="btn"
									style="padding: 8px 15px; font-size: 12px;">Archive</button>
							<?php endif; ?>
						</div>
					</div>

					<div class="notebook-container" style="margin-top: 20px;">
						<div class="notebook-paper" id="view-content">
							<?php echo $content; ?>
						</div>

						<!-- View Pagination -->
						<?php if ($total_pages > 1): ?>
							<div class="pagination-bar">
								<?php if ($current_page > 1): ?>
									<a href="?id=<?php echo $nid; ?>&page=<?php echo $current_page - 1; ?>&mode=view"
										class="page-btn">&laquo; Prev</a>
								<?php else: ?>
									<span class="page-btn disabled">&laquo; Prev</span>
								<?php endif; ?>

								<span class="page-indicator">Page <?php echo $current_page; ?></span>

								<?php if ($current_page < $total_pages): ?>
									<a href="?id=<?php echo $nid; ?>&page=<?php echo $current_page + 1; ?>&mode=view"
										class="page-btn">Next &raquo;</a>
								<?php else: ?>
									<span class="page-btn disabled">Next &raquo;</span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<div style="margin-top: 10px;">
						<a href="index.php" class="btn btn-secondary">Back to List</a>
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

						<label class="pin-label">
							<input type="checkbox" name="is_pinned" value="1" <?php if ($is_pinned_val)
								echo "checked"; ?>
								<?php echo $is_archived_val ? 'disabled' : ''; ?>>
							Pin
						</label>

						<div style="margin-left: auto; display: flex; align-items: center; gap: 5px;">
							<label>⏰</label>
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
						<button type="button" onclick="formatText('b')" style="font-weight: bold; width: 30px;">B</button>
						<button type="button" onclick="formatText('i')" style="font-style: italic; width: 30px;">I</button>
						<button type="button" onclick="formatText('u')"
							style="text-decoration: underline; width: 30px;">U</button>
						<span style="border-left: 1px solid #ccc; margin: 0 5px;"></span>
						<button type="button" onclick="formatText('h3')" style="font-weight: bold; width: 30px;">H</button>
						<button type="button" onclick="formatText('li')" style="width: 30px;">•</button>
					</div>

					<input type="hidden" name="note_id" value="<?php echo htmlspecialchars($nid); ?>">
					<input type="hidden" name="page" id="page_content" value="<?php echo htmlspecialchars($content); ?>">
					<input type="hidden" name="is_archived" id="is_archived_input"
						value="<?php echo isset($is_archived_val) ? $is_archived_val : 0; ?>">
					<input type="hidden" name="page_number" value="<?php echo $current_page; ?>">

					<div class="editor-div" id="editor" contenteditable="true">
						<?php echo $content; ?>
					</div>

					<!-- Edit Mode Pagination -->
					<?php if ($nid != ""): ?>
						<div class="pagination-bar" style="background: #f0f0f0;">
							<?php if ($current_page > 1): ?>
								<!-- WARNING: Navigation without save logic might lose data. Ideally JS warns. For now, we assume user saves. -->
								<a href="?id=<?php echo $nid; ?>&page=<?php echo $current_page - 1; ?>&mode=edit"
									onclick="return confirmNavigation()" class="page-btn">&laquo; Prev</a>
							<?php endif; ?>

							<span class="page-indicator">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>

							<?php if ($current_page < $total_pages): ?>
								<a href="?id=<?php echo $nid; ?>&page=<?php echo $current_page + 1; ?>&mode=edit"
									onclick="return confirmNavigation()" class="page-btn">Next &raquo;</a>
							<?php endif; ?>

							<!-- Add Page -->
							<a href="?id=<?php echo $nid; ?>&page=<?php echo $total_pages + 1; ?>&mode=edit"
								onclick="return confirmNavigation()" class="page-btn add-page-btn">+ New Page</a>
						</div>
					<?php endif; ?>

					<!-- Toolbar -->
					<div class="toolbar">
						<a href="index.php" class="btn btn-secondary">Back to List</a>

						<?php if ($nid != ""): ?>
							<a href="notepad.php?id=<?php echo $nid; ?>&mode=view&page=<?php echo $current_page; ?>"
								class="btn btn-secondary" onclick="return confirmNavigation()">👁️ View Mode</a>
						<?php endif; ?>

						<button type="submit" name="save_note" class="btn btn-primary"
							style="margin-left: auto; margin-right: 10px;">Save Page</button>
						<button type="submit" name="save_exit" class="btn btn-primary">Save & Exit</button>
					</div>

				<?php endif; ?>
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
				const formUn = document.createElement('form');
				formUn.method = 'POST';
				formUn.innerHTML = `<input type="hidden" name="note_id" value="<?php echo $nid; ?>">
									<input type="hidden" name="action_type" value="archive_redirect">
									<input type="hidden" name="is_archived" value="0">
									<input type="hidden" name="save_note" value="1">`; // Trigger generic save/update status
				document.body.appendChild(formUn);
				formUn.submit();
			}
		}

		function confirmNavigation() {
			// Simple check: In a real app we'd track dirty state.
			return true;
			// return confirm("Unsaved changes on this page will be lost. Continue?");
		}

		function updateStats() {
			if (!editor) return;
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
			titleInput.addEventListener('input', function() {
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