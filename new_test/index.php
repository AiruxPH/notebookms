<?php
include 'includes/db.php';

// Flash message check (optional if we want to show messages on index too)
session_start();
?>
<!DOCTYPE html>
<html>

<head>
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<title>My Notes - Notebook</title>
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
		<div class="note-grid">
			<!-- Add New Note Card -->
			<a href="notepad.php" class="note-card note-add-card">
				<div style="text-align: center;">
					<div style="font-size: 3rem; font-weight: bold;">+</div>
					<div>Add New Note</div>
				</div>
			</a>

			<?php
			// Fetch Notes with Content Preview
			$sql = "SELECT n.id, n.title, n.category, n.date_created, n.date_last, p.text 
				FROM notes n 
				LEFT JOIN pages p ON n.id = p.note_id AND p.page_number = 1
				ORDER BY n.date_last DESC";

			$result = mysqli_query($conn, $sql);

			if ($result) {
				while ($row = mysqli_fetch_assoc($result)) {
					$nid = $row['id'];
					$dtitle = $row['title'];
					$dcat = $row['category'];
					// Format Date nicely
					$ddatc = date("M j, Y", strtotime($row['date_created']));
					$ddatl = date("M j, H:i", strtotime($row['date_last']));

					// Truncate text
					$dtxt = htmlspecialchars(substr($row['text'] ?? '', 0, 120));
					if (strlen($row['text'] ?? '') > 120)
						$dtxt .= "...";
					if (empty($dtxt))
						$dtxt = "<em>No content...</em>";

					// Render Card
					echo "<a href='notepad.php?id=$nid' class='note-card'>";
					echo "<div class='note-title'>" . htmlspecialchars($dtitle) . "</div>";
					echo "<div class='note-meta'>$dcat &bull; $ddatl</div>";
					echo "<div class='note-preview'>$dtxt</div>";
					echo "<div class='note-footer'>";
					echo "<span>Created: $ddatc</span>";
					echo "</div>";
					echo "</a>";
				}
			} else {
				echo "<p>Error fetching notes: " . mysqli_error($conn) . "</p>";
			}
			?>
		</div>
	</div>

</body>

</html>