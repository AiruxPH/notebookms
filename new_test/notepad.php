<?php
include 'includes/db.php';
session_start(); // Start session for Flash messages

$msg = "";
$msg_type = "";
$nid = "";
$ntitle = "";
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
	$res = mysqli_query($conn, "SELECT title FROM notes WHERE id = $nid");
	if ($row = mysqli_fetch_assoc($res)) {
		$ntitle = $row['title'];
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
			$sql_note = "INSERT INTO notes (user_id, title, category, date_created, date_last) VALUES (0, '$safe_title', 'General', '$date', '$date')";
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
		mysqli_query($conn, "UPDATE notes SET date_last = '$date' WHERE id = $nid");

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
	<link rel="stylesheet" href="css/style.css">
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
				<!-- Title Section -->
				<?php if ($nid == ""): ?>
					<input type="text" name="new_title" class="title-input" placeholder="Enter Note Title Here..." required
						value="<?php echo htmlspecialchars($ntitle); ?>" autofocus>
				<?php else: ?>
					<div class="note-title"
						style="font-size: 1.8rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1rem;">
						<?php echo htmlspecialchars($ntitle); ?>
					</div>
				<?php endif; ?>

				<!-- Editor Section -->
				<textarea name="page" class="editor-textarea"
					placeholder="Start writing your note..."><?php echo htmlspecialchars($content); ?></textarea>

				<!-- Toolbar -->
				<div class="toolbar">
					<a href="index.php" class="btn btn-secondary">Back to List</a>
					<button type="submit" name="save_note" class="btn btn-primary">Save Note</button>
				</div>
			</form>
		</div>
	</div>

</body>

</html>