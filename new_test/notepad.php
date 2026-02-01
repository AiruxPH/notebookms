<?php
include 'includes/db.php';

$msg = "";
$ntitle = "";
$content = "";

if (isset($_GET['t'])) {
	$ntitle = $_GET['t'];
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_note'])) {
	$new_title_input = isset($_POST['new_title']) ? trim($_POST['new_title']) : "";

	// Determine effective title
	if ($ntitle == "" && $new_title_input != "") {
		$ntitle = $new_title_input; // Set title for the first time
	}

	if ($ntitle == "") {
		$msg = "Error: Title cannot be empty.";
	} else {
		$content = mysqli_real_escape_string($conn, $_POST['page']);
		$safe_title = mysqli_real_escape_string($conn, $ntitle);
		$date = date('Y-m-d');

		// 1. Ensure Note exists in 'notes' table
		$check_note = mysqli_query($conn, "SELECT * FROM notes WHERE title = '$safe_title'");
		if (mysqli_num_rows($check_note) == 0) {
			$sql_note = "INSERT INTO notes (title, date_created, date_last, category, color) VALUES ('$safe_title', '$date', '$date', 'General', 0)";
			mysqli_query($conn, $sql_note);
		} else {
			$sql_note = "UPDATE notes SET date_last = '$date' WHERE title = '$safe_title'";
			mysqli_query($conn, $sql_note);
		}

		// 2. Update or Insert Page content
		$check_page = mysqli_query($conn, "SELECT * FROM pages WHERE owner = '$safe_title' AND page = 1");
		if (mysqli_num_rows($check_page) > 0) {
			$sql_page = "UPDATE pages SET text = '$content' WHERE owner = '$safe_title' AND page = 1";
		} else {
			$sql_page = "INSERT INTO pages (owner, page, text) VALUES ('$safe_title', 1, '$content')";
		}

		if (mysqli_query($conn, $sql_page)) {
			// Redirect to properly formatted URL to prevent resubmission issues
			header("Location: notepad.php?t=" . urlencode($ntitle));
			exit();
		} else {
			$msg = "Error saving note: " . mysqli_error($conn);
		}
	}
}

// Load Content
if ($ntitle != "") {
	$safe_title = mysqli_real_escape_string($conn, $ntitle);
	$query = mysqli_query($conn, "SELECT text FROM pages WHERE owner = '$safe_title' AND page = 1");
	if ($row = mysqli_fetch_assoc($query)) {
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
		<?php if ($msg && strpos($msg, 'Error') === false)
			echo "<p class='msg'>$msg</p>"; ?>
		<?php if ($msg && strpos($msg, 'Error') !== false)
			echo "<p class='error'>$msg</p>"; ?>

		<form method="post" class="notetext">
			<table>
				<colgroup>
					<col span="1" style="width: 50%" />
					<col span="1" style="width: 50%" />
				</colgroup>
				<tr>
					<td>
						<b class="note_cap"> Title:
							<?php if ($ntitle == ""): ?>
								<input type="text" name="new_title" class="title-input" placeholder="Enter Note Title"
									required>
							<?php else: ?>
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