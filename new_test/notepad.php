<?php
include 'includes/db.php';

$msg = "";
$nid = "";
$ntitle = "";
$content = "";

// 1. Check for ID in URL
if (isset($_GET['id'])) {
	$nid = intval($_GET['id']);
	// Fetch Title (and verify note exists)
	$res = mysqli_query($conn, "SELECT title FROM notes WHERE id = $nid");
	if ($row = mysqli_fetch_assoc($res)) {
		$ntitle = $row['title'];
	} else {
		// Invalid ID
		$nid = "";
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
			$msg = "Error: Title is required for a new note.";
		} else {
			$safe_title = mysqli_real_escape_string($conn, $title_input);
			// Insert into notes (User ID 0 for now)
			$sql_note = "INSERT INTO notes (user_id, title, category, date_created, date_last) VALUES (0, '$safe_title', 'General', '$date', '$date')";
			if (mysqli_query($conn, $sql_note)) {
				$nid = mysqli_insert_id($conn); // Get the new ID

				// Insert Page 1
				$sql_page = "INSERT INTO pages (note_id, page_number, text) VALUES ($nid, 1, '$content_input')";
				mysqli_query($conn, $sql_page);

				// Redirect to the new Note ID
				header("Location: notepad.php?id=$nid");
				exit();
			} else {
				$msg = "Database Error: " . mysqli_error($conn);
			}
		}
	} else {
		// --- UPDATE NOTE ---
		// Update Timestamp
		mysqli_query($conn, "UPDATE notes SET date_last = '$date' WHERE id = $nid");

		// Update or Insert Content
		$check = mysqli_query($conn, "SELECT id FROM pages WHERE note_id = $nid AND page_number = 1");
		if (mysqli_num_rows($check) > 0) {
			$sql_page = "UPDATE pages SET text = '$content_input' WHERE note_id = $nid AND page_number = 1";
		} else {
			$sql_page = "INSERT INTO pages (note_id, page_number, text) VALUES ($nid, 1, '$content_input')";
		}

		if (mysqli_query($conn, $sql_page)) {
			$msg = "Note saved successfully!";
		} else {
			$msg = "Error saving content: " . mysqli_error($conn);
		}
	}
}

// 3. Load Content (if viewing an existing note)
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
	<style>
		.msg {
			color: green;
			font-weight: bold;
			text-align: center;
		}

		.error {
			color: red;
			font-weight: bold;
			text-align: center;
		}

		.save-btn {
			margin-top: 10px;
			padding: 10px 20px;
			font-size: 16px;
			cursor: pointer;
		}

		.title-input {
			font-size: 16px;
			padding: 5px;
			width: 300px;
		}
	</style>
</head>
<header>
	<br />
	<h1> <a href="index.php"> Notebook-BAR </a> </h1>
	<nav>
		<a href="about.html"> About </a>
		<a href="index.php"> Notes </a>
		<a href="contact.html"> Contact Us </a>
	</nav>
	<br />
</header>

<body>
	<div id="nwrap">
		<?php if ($msg)
			echo "<p class='msg'>$msg</p>"; ?>

		<form method="post" class="notetext">
			<table>
				<colgroup>
					<col span="1" style="width: 50%" />
					<col span="1" style="width: 50%" />
				</colgroup>
				<tr>
					<td>
						<b class="note_cap"> Title:
							<?php if ($nid == ""): ?>
								<!-- New Note: Show Input -->
								<input type="text" name="new_title" class="title-input" placeholder="Enter Note Title"
									required value="<?php echo htmlspecialchars($ntitle); ?>">
							<?php else: ?>
								<!-- Existing Note: Show Text -->
								<?php echo htmlspecialchars($ntitle); ?>
							<?php endif; ?>
						</b>
					</td>
					<td>
						<div id="detwrap">
							<!-- Stats placeholders -->
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea name="page" rows="20"
							style="width: 100%;"><?php echo htmlspecialchars($content); ?></textarea>
						<div style="text-align: right;">
							<button type="submit" name="save_note" class="save-btn">Save Note</button>
						</div>
					</td>
				</tr>
			</table>
		</form>
	</div>
</body>

</html>