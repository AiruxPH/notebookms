<?php
include 'includes/db.php';

if (isset($_SESSION['passnote']) && !is_null($_SESSION['passnote'])) {
	$_SESSION['passnote'] = null;
}

?><!DOCTYPE html>

<html>

<head>
	<link rel="stylesheet" href="css/style.css">
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
	<div id="bwrap">
		<br>
		<figure>
			<a href="notepad.php">
				<h2 class="new_note"> Add New Note </h2>
				<b style="font-size: 120px; text-align: center; overflow:  hidden; vertical-align: center;"> + </b>
			</a>
		</figure>

		<?php
		// Use LEFT JOIN to get note details and page 1 content in one query
		$sql = "SELECT n.title, n.category, n.date_created, n.date_last, p.text 
				FROM notes n 
				LEFT JOIN pages p ON n.title = p.owner AND p.page = 1
				ORDER BY n.date_last DESC"; // Optional: sort by last modified
		
		$result = mysqli_query($conn, $sql);

		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$dtitle = $row['title'];
				$dcat = $row['category'];
				$ddatc = $row['date_created'];
				$ddatl = $row['date_last'];
				// Truncate text for preview (e.g., first 100 chars)
				$dtxt = htmlspecialchars(substr($row['text'] ?? '', 0, 100)) . (strlen($row['text'] ?? '') > 100 ? '...' : '');

				echo "<figure>";
				echo "<a href='notepad.php?t=" . urlencode($dtitle) . "'>"; // Use urlencode for safety
				echo "<h2>" . htmlspecialchars($dtitle) . "</h2>";
				echo "<h5>" . htmlspecialchars($dcat) . "</h5>";
				echo "<p>" . $dtxt . "</p>";
				echo "<table class='tbldetails'>";
				echo "<colgroup><col span='1' class='dtc1'><col span='1' class='dtc2'></colgroup>";
				echo "<tr><td>Date Created</td><td>" . htmlspecialchars($ddatc) . "</td></tr>";
				echo "<tr><td>Last Modified</td><td>" . htmlspecialchars($ddatl) . "</td></tr>";
				echo "</table>";
				echo "</a>";
				echo "</figure>";
			}
		} else {
			echo "<p>Error fetching notes: " . mysqli_error($conn) . "</p>";
		}
		?>
	</div>
</body>

</html>