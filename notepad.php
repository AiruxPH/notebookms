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
			'page_number' => $_POST['page_number'] ?? 1,
			'pages_json' => $_POST['pages_json'] ?? null
		];



		// SAVE
		$saved_id = save_note($save_data);

		if ($saved_id) {
			$nid = $saved_id;
			$current_page = $save_data['page_number'];

			// Redirect logic
			// Redirect logic
			if (isset($_POST['save_exit'])) {
				$_SESSION['flash'] = ['message' => "Note saved.", 'type' => 'success'];
				header("Location: index.php");
				exit();
			}

			if (isset($_POST['action_type']) && $_POST['action_type'] == 'archive_redirect') {
				$action_msg = $save_data['is_archived'] ? "Note Archived" : "Note Unarchived";
				$_SESSION['flash'] = ['message' => $action_msg, 'type' => 'success'];
				header("Location: index.php");
				exit();
			}

			// PRG: Redirect to self to show saved state and avoid resubmission
			$_SESSION['flash'] = ['message' => "Note saved successfully!", 'type' => 'success'];
			header("Location: notepad.php?id=$nid&page=$current_page&mode=edit");
			exit();

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
		$date_last = $note['date_last'];
		$date_created = $note['date_created'];

		$total_pages = get_note_page_count($nid);

		// Fetch ALL Pages for Client-Side Switching
		$all_pages = get_all_note_pages($nid);
		// Inject into JS
		$json_pages = json_encode($all_pages);

		// Content for initial load (Page 1 usually, or requested page)
		$content = isset($all_pages[$current_page]) ? $all_pages[$current_page] : "";
	} else {
		header("Location: index.php");
		exit();
	}
}
?>
<script>
	// Inject Pages from PHP
	window.initialPages = <?php echo isset($json_pages) ? $json_pages : '{}'; ?>;
	window.currentPage = <?php echo $current_page; ?>;
	window.totalPages = <?php echo $total_pages; ?>;
</script>

<?php
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
			<h1><a href="dashboard.php">Notebook-BAR</a></h1>
			<input type="checkbox" id="menu-toggle" class="menu-toggle">
			<label for="menu-toggle" class="hamburger">
				<span></span>
				<span></span>
				<span></span>
			</label>
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
				<input type="hidden" name="note_id" value="<?php echo htmlspecialchars($nid); ?>">
				<input type="hidden" name="is_archived" id="is_archived_input" value="<?php echo isset($is_archived_val) ? $is_archived_val : 0; ?>">
				<input type="hidden" name="page_number" value="<?php echo $current_page; ?>">
				<input type="hidden" name="page" id="page_content" value="<?php echo htmlspecialchars($content); ?>">

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

						<!-- Left: Title & Category/Dates -->
						<div>
							<h1
								style="margin: 0; font-size: 24px; font-family: 'Courier New', monospace; font-weight: bold;">
								<?php echo htmlspecialchars($ntitle); ?>
							</h1>
							<div
								style="font-size: 13px; color: #666; margin-top: 5px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
								<span style="background: #eee; padding: 2px 6px; border-radius: 4px;">
									<?php
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

								<span style="color: #888;" title="Created Date">
									<i class="fa-solid fa-calendar-plus"></i>
									<?php echo date("M j, Y", strtotime($date_created)); ?>
								</span>

								<span style="color: #888;" title="Last Updated">
									<i class="fa-solid fa-clock-rotate-left"></i>
									<?php echo date("M j, g:i A", strtotime($date_last)); ?>
								</span>

								<?php if ($reminder_date_val): ?>
									<span style="color: #c62828;"><i class="fa-regular fa-clock"></i>
										<?php echo date("M j, g:i A", strtotime($reminder_date_val)); ?></span>
								<?php endif; ?>
							</div>
						</div>

						<!-- Right: Pin & Archive Icons -->
						<div style="display: flex; gap: 15px; align-items: center;">
							<label class="pin-label"
								style="display: flex; align-items: center; gap: 5px; cursor: pointer; font-size: 14px; color: #555;">
								<input type="checkbox" id="view-pin-checkbox" onchange="togglePin(this.checked)" <?php echo $is_pinned_val ? 'checked' : ''; ?>>
								<i class="fa-solid fa-thumbtack"></i> Pin
							</label>
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

						<!-- View Mode Pagination (Client-Side) -->
						<?php if ($nid != ""): ?>
							<div class="pagination-bar"
								style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 20px;">

								<button type="button" onclick="goToPage(1)" class="page-btn" id="btn-first-v"
									title="First Page">
									<i class="fa-solid fa-backward-step"></i>
								</button>

								<button type="button" onclick="goToPage(window.currentPage - 1)" class="page-btn"
									id="btn-prev-v" title="Previous Page">
									<i class="fa-solid fa-chevron-left"></i> <span class="btn-text">Prev</span>
								</button>

								<span class="page-indicator" style="display: flex; align-items: center; gap: 5px;">
									Page
									<input type="number" id="jump-page-input-v" value="<?php echo $current_page; ?>" min="1"
										style="width: 50px; text-align: center; border: 1px solid #ccc; border-radius: 4px; padding: 2px;"
										onchange="goToPage(parseInt(this.value))">
									of <span id="total-pages-display-v"><?php echo $total_pages; ?></span>
								</span>

								<button type="button" onclick="goToPage(window.currentPage + 1)" class="page-btn"
									id="btn-next-v" title="Next Page">
									<span class="btn-text">Next</span> <i class="fa-solid fa-chevron-right"></i>
								</button>

								<button type="button" onclick="goToPage(window.totalPages)" class="page-btn" id="btn-last-v"
									title="Last Page">
									<i class="fa-solid fa-forward-step"></i>
								</button>
							</div>
						<?php endif; ?>
					</div>

					<!-- EDIT MODE (Classic Editor) -->
				<?php else: ?>
					<div class="title-row" style="position: relative;">
						<textarea name="new_title" class="title-input" placeholder="Note Title" required maxlength="100"
							style="width: 100%; resize: none; overflow: hidden; min-height: 32px;"
							oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px';" <?php echo $is_archived_val ? 'disabled' : ''; ?>><?php echo htmlspecialchars($ntitle); ?></textarea>
						<span id="title-char-counter"
							style="position: absolute; right: 5px; bottom: 5px; font-size: 11px; color: #aaa; pointer-events: none;">0/100</span>
					</div>

					<div
						style="font-size: 11px; color: #888; margin-bottom: 10px; padding-left: 2px; font-family: sans-serif;">
						<span id="word-count-top">0</span> words <span style="margin: 0 4px; color: #ccc;">|</span>
						<span id="body-char-count">0</span> / 1800 characters <span
							style="margin: 0 4px; color: #ccc;">|</span>
						Updated: <?php echo date("M j, g:i A", strtotime($date_last)); ?>
					</div>

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

						<div style="margin-left: auto; display: flex; align-items: center; gap: 5px;">
							<label><i class="fa-regular fa-clock"></i></label>
							<input type="datetime-local" name="reminder_date"
								value="<?php echo $reminder_date_val ? date('Y-m-d\TH:i', strtotime($reminder_date_val)) : ''; ?>"
								style="font-size: 13px;">
						</div>
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



					<div class="editor-div" id="editor" contenteditable="true">
						<?php echo $content; ?>
					</div>

					<!-- Edit Mode Pagination (Client-Side) -->
					<?php if ($nid != ""): ?>
						<div class="pagination-bar"
							style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; gap: 8px;">

							<!-- First -->
							<button type="button" onclick="goToPage(1)" class="page-btn" id="btn-first" title="First Page">
								<i class="fa-solid fa-backward-step"></i>
							</button>

							<!-- Prev -->
							<button type="button" onclick="goToPage(window.currentPage - 1)" class="page-btn" id="btn-prev"
								title="Previous Page">
								<i class="fa-solid fa-chevron-left"></i> <span class="btn-text">Prev</span>
							</button>

							<span class="page-indicator" style="display: flex; align-items: center; gap: 5px;">
								Page
								<input type="number" id="jump-page-input" value="<?php echo $current_page; ?>" min="1"
									style="width: 50px; text-align: center; border: 1px solid #ccc; border-radius: 4px; padding: 2px;"
									onchange="goToPage(parseInt(this.value))">
								of <span id="total-pages-display"><?php echo $total_pages; ?></span>
							</span>

							<!-- Next -->
							<button type="button" onclick="goToPage(window.currentPage + 1)" class="page-btn" id="btn-next"
								title="Next Page">
								<span class="btn-text">Next</span> <i class="fa-solid fa-chevron-right"></i>
							</button>

							<!-- Last -->
							<button type="button" onclick="goToPage(window.totalPages)" class="page-btn" id="btn-last"
								title="Last Page">
								<i class="fa-solid fa-forward-step"></i>
							</button>

							<!-- Add Page -->
							<button type="button" onclick="addNewPage()" class="page-btn add-page-btn" title="Add New Page">
								<i class="fa-solid fa-plus"></i>
							</button>
						</div>

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
					<!-- Archive REMOVED from Edit Mode -->
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
		// ===============================
		// STATE MANAGEMENT
		// ===============================
		// Note: window.initialPages, window.currentPage, window.totalPages injected by PHP
		let allPages = window.initialPages || {};
		let currentPage = window.currentPage || 1;
		let totalPages = window.totalPages || 1;

		const editor = document.getElementById('editor');
		const form = document.getElementById('note-form');
		const hiddenInput = document.getElementById('page_content'); // Stores CURRENT page text (legacy/fallback)
		// Hidden input for BULK save
		const bulkInput = document.createElement('input');
		bulkInput.type = 'hidden';
		bulkInput.name = 'pages_json';
		form.appendChild(bulkInput);

		const MAX_CHARS = 1800;

		// ===============================
		// INITIALIZATION
		// ===============================
		if (editor) {
			// Restore current page state if exists
			if (allPages[currentPage] !== undefined) {
				editor.innerHTML = allPages[currentPage];
			} else {
				// If new page or empty
				allPages[currentPage] = editor.innerHTML;
			}
			updateCharCount();

			// Events
			editor.addEventListener('input', handleInput);
			editor.addEventListener('keydown', handleKeyDown);
			// Sync on blur too just in case
			editor.addEventListener('blur', syncCurrentPage);
		}
		const viewContent = document.getElementById('view-content');

		// ===============================
		// CORE LOGIC
		// ===============================

		function syncCurrentPage() {
			// Edit Mode Sync
			if (editor) {
				allPages[currentPage] = editor.innerHTML;
				if (hiddenInput) hiddenInput.value = editor.innerHTML;
			}
		}

		function goToPage(pageNum) {
			// 1. Sync current page before leaving (if editing)
			syncCurrentPage();

			// Validate
			if (pageNum < 1 || pageNum > totalPages) return;

			// 2. Switch State
			currentPage = pageNum;
			window.currentPage = pageNum; // Sync for global use (onclick)

			// 3. Render New Content
			if (allPages[currentPage] === undefined) {
				allPages[currentPage] = "";
			}

			if (editor) {
				editor.innerHTML = allPages[currentPage];
			} else if (viewContent) {
				viewContent.innerHTML = allPages[currentPage];
			}

			// 4. Update UI
			updateUI();
		}

		function addNewPage() {
			if (!editor) return; // Only in edit mode
			syncCurrentPage();
			totalPages++;
			window.totalPages = totalPages;
			currentPage = totalPages;
			window.currentPage = totalPages;
			allPages[currentPage] = ""; // Init empty
			editor.innerHTML = "";
			updateUI();
		}

		function updateUI() {
			// Edit Mode UI
			const jumpInput = document.getElementById('jump-page-input');
			const totalDisplay = document.getElementById('total-pages-display');
			if (jumpInput) jumpInput.value = currentPage;
			if (totalDisplay) totalDisplay.innerText = totalPages;

			// View Mode UI
			const jumpInputV = document.getElementById('jump-page-input-v');
			const totalDisplayV = document.getElementById('total-pages-display-v');
			if (jumpInputV) jumpInputV.value = currentPage;
			if (totalDisplayV) totalDisplayV.innerText = totalPages;

			updateCharCount();
		}

		// ===============================
		// CONSTRAINTS & FORMATTING
		// ===============================

		function handleInput(e) {
			syncCurrentPage();
			updateCharCount();
		}

		function updateCharCount() {
			if (!editor) return;
			// 1. Body Count
			const text = editor.innerText || "";
			const wordCountTop = document.getElementById('word-count-top');
			const bodyCharCount = document.getElementById('body-char-count');
			const words = text.trim().split(/\s+/).filter(word => word.length > 0);

			if (wordCountTop) wordCountTop.innerText = (text.trim() === "") ? 0 : words.length;

			if (bodyCharCount) {
				bodyCharCount.innerText = text.length;
				if (text.length > MAX_CHARS) {
					bodyCharCount.style.color = 'red';
					bodyCharCount.style.fontWeight = 'bold';
				} else {
					bodyCharCount.style.color = '#777';
					bodyCharCount.style.fontWeight = 'normal';
				}
			}

			// 2. Title Count
			const titleInput = document.querySelector('textarea[name="new_title"]');
			const titleCounter = document.getElementById('title-char-counter');
			if (titleInput && titleCounter) {
				titleCounter.innerText = titleInput.value.length + "/100";
			}
		}

		function handleKeyDown(e) {
			if (e.key === 'Tab') {
				e.preventDefault();
				document.execCommand('insertText', false, '    ');
			}
			const text = editor.innerText || "";
			const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'];
			if (text.length >= MAX_CHARS && !allowed.includes(e.key) && !e.ctrlKey && !e.metaKey && e.key.length === 1) {
				e.preventDefault();
			}
		}

		function togglePin(isPinned) {
			// Update hidden input if it exists (not strictly needed since we submit immediately)
			// But we need to make sure the form is ready.
			const pinInput = document.querySelector('input[name="is_pinned"]');

			// If we are in view mode, we might not have the pin checkbox in the main form.
			// Let's create an ad-hoc submission or use the existing fabric logic.

			// The main form handles save. We want an instant save for pin.
			if (form) {
				// Ensure the is_pinned checkbox in the FORM matches the View Mode checkbox
				// Actually, the view mode checkbox isn't inside the form (or is it? No, it's outside in my current replace).
				// Let's check the form structure.

				// If it's outside the form, we can just submit a special action.
				document.getElementById('action_type').value = 'save_redirect'; // Stay on page

				// Create or update a hidden input in the form
				let hiddenPin = form.querySelector('input[name="is_pinned"][type="hidden"]');
				if (!hiddenPin) {
					hiddenPin = document.createElement('input');
					hiddenPin.type = 'hidden';
					hiddenPin.name = 'is_pinned';
					form.appendChild(hiddenPin);
				}
				hiddenPin.value = isPinned ? "1" : "0";

				form.submit();
			}
		}

		// ===============================
		// SAVING & FORMS
		// ===============================

		if (form) {
			form.addEventListener('submit', function (e) {
				syncCurrentPage(); // Ensure latest edits are captured

				// SERIALIZE ALL PAGES
				bulkInput.value = JSON.stringify(allPages);

				// Update legacy hidden input too
				hiddenInput.value = editor.innerHTML;

				// Also update archive/action fields if needed (handled by onclicks usually)
			});
		}

		// Helper Utils
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
				form.submit(); // submit handler will do the syncing
			}
		}

		function confirmUnarchive() {
			if (confirm("Unarchive this note?")) {
				document.getElementById('is_archived_input').value = 0;
				document.getElementById('action_type').value = 'archive_redirect';
				const disabled = form.querySelectorAll('[disabled]');
				disabled.forEach(el => el.disabled = false);
				form.submit();
			}
		}

		// Renamed to avoid using the old confirmNavigation
		function confirmNavigation() {
			return true;
		}

		// Title Resizer
		const titleInput = document.querySelector('textarea[name="new_title"]');
		if (titleInput) {
			titleInput.addEventListener('input', function () {
				this.style.height = '';
				this.style.height = this.scrollHeight + 'px';
			});
			titleInput.style.height = titleInput.scrollHeight + 'px';
			titleInput.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
		}

		// Toast Logic
		const toastOverlay = document.getElementById('toast-overlay');
		const toastMessage = document.getElementById('toast-message');

		function showToast(msg, type) {
			if (!toastMessage) return;
			toastMessage.textContent = msg;
			toastMessage.className = "toast-message " + (type === 'error' ? 'toast-error' : 'toast-success');
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