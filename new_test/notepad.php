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
	$date = date('Y-m-d H:i:s');
	$action_type = isset($_POST['action_type']) ? $_POST['action_type'] : 'save';

	// *** SPECIAL HANDLING FOR ARCHIVE/UNARCHIVE ONLY ***
	if ($action_type == 'archive_redirect') {
		// Validation: Must have a valid ID
		if ($nid == "") {
			header("Location: index.php");
			exit();
		}

		$is_archived = isset($_POST['is_archived']) ? intval($_POST['is_archived']) : 0;

		// Simple Update Query - ONLY touches is_archived and date_last
		$stmt = $conn->prepare("UPDATE notes SET is_archived = ?, date_last = ? WHERE id = ?");
		$stmt->bind_param("isi", $is_archived, $date, $nid);

		if ($stmt->execute()) {
			$msg_text = ($is_archived == 1) ? "Note Archived" : "Note Unarchived"; // Short specific message
			$_SESSION['flash'] = ['message' => $msg_text, 'type' => 'success'];
		} else {
			$_SESSION['flash'] = ['message' => 'Database Error', 'type' => 'error'];
		}
		$stmt->close();

		header("Location: index.php");
		exit();
	}

	// *** STANDARD SAVE LOGIC (Insert/Update Content) ***
	$content_input = isset($_POST['page']) ? mysqli_real_escape_string($conn, $_POST['page']) : $content;

	// Use existing values ($ntitle, $ncat) if POST is missing (disabled inputs)
	$title_input = isset($_POST['new_title']) ? trim($_POST['new_title']) : $ntitle;
	$category_input = isset($_POST['category']) ? $_POST['category'] : $ncat;

	$new_title = mysqli_real_escape_string($conn, $title_input);
	$category = mysqli_real_escape_string($conn, $category_input);
	$is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
	// Archive status shouldn't change here usually, but we keep it sync
	$is_archived = isset($_POST['is_archived']) ? intval($_POST['is_archived']) : $is_archived_val;

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

		$_SESSION['flash'] = ['message' => 'Note Created', 'type' => 'success'];
	} else {
		// Update existing note
		$stmt = $conn->prepare("UPDATE notes SET title = ?, category = ?, is_pinned = ?, is_archived = ?, date_last = ? WHERE id = ?");
		$stmt->bind_param("ssiisi", $new_title, $category, $is_pinned, $is_archived, $date, $nid);
		$stmt->execute();
		$stmt->close();

		// Update existing page (ONLY if page content was sent - i.e. not disabled)
		if (isset($_POST['page'])) {
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
		}

		$_SESSION['flash'] = ['message' => 'Note Saved', 'type' => 'success'];
	}

	// Redirect based on action
	if (isset($_POST['save_exit'])) {
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

				<!-- Editor Section (WYSIWYG) -->
				<!-- Hidden input to store actual value for POST -->
				<input type="hidden" name="page" id="page_content" value="<?php echo htmlspecialchars($content); ?>">

				<div class="editor-div" id="editor" <?php echo $is_archived_val ? 'contenteditable="false"' : 'contenteditable="true"'; ?>>
					<?php
					// If content is empty, show placeholder-like behavior (handled via CSS/JS usually, or just empty)
					// If content has HTML, it renders. If plain text, it renders. 
					// We must NOT escape HTML here because we want it to render.
					// BUT we must be careful of XSS. Since this is a local app/test, we assume trust or basic sanitization.
					// For now, echo logic:
					echo $content;
					?>
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