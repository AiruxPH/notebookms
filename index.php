<?php
$servername = "srv1858.hstgr.io";
$username = "u130348899_notebookms";
$password = "Note999@";
$dbname = "u130348899_notebook";

if (!isset($_SESSION)) {
    session_start();
} else {
	header("Location: index.php");
	exit;
}

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
} else {
	echo "[Connected successfully]";
}

if (!is_null($_SESSION['passnote'])){
	$_SESSION['passnote'] = null;
}

?><!DOCTYPE html>

<html>
<head>
<link rel="stylesheet" href="styles/style.css">
</head>

<header>
	<br/>
	<h1> <a href="index.php"> Notebook-BAR </a> </h1>
	<nav>
		<a href="about.html"> About </a>
		<a href="index.php"> Notes </a>
		<a href="contact.html"> Contact Us </a>
	</nav>
	<br/>
</header>

<body>
	<div id="bwrap">
	<br>
		<!-- Template
		<figure>
		<a href="notepad.html">
			<h2> This is a Title </h2>
			<h5> Placeholder Category </h5>
			<p> This is a sample block of text that amounts to two hundred fifty five (255)characters long. Given how the text fields in HTML are limited to only 255 characters, instead of trying to find a way around it Ive decided to implement it instead as a feature. </p>
			<table class="tbldetails">
				<colgroup>
					<col span="1" style="width: 40%;">
					<col span="1" style="width: 60%;">
				</colgroup>
				<tr>
					<td>
						Date Created
					</td>
					<td>
						2024-02-20 2220
					</td>
				</tr>
				<tr>
					<td>
						Last Modified
					</td>
					<td>
						2024-02-20 2220
					</td>
				</tr>
			</table>
		</a>
		</figure>
		-->

		<figure>
		<a href="notepad.php">
			<h2 clas="new_note"> Add New Note </h2>
		  	<b style="font-size: 120px; text-align: center; overflow:  hidden; vertical-align: center;"> + </b>
			</a>
		</figure>
		
		<?php
			$sql = "SELECT title, date_created, date_last, category, color FROM notes";
			$result = mysqli_query($conn, $sql);
			$notesql = "SELECT text FROM pages WHERE owner LIKE ? AND page LIKE 1";
			
			$stmt = $conn->prepare($notesql);
			$stmt->bind_param("s", $dtitle);
			
			/*
			$notesql = "SELECT text FROM pages WHERE owner LIKE $result[title] AND page LIKE 0";
			$noteres = mysqli_query($conn, $sql);*/
			
			while ($row = mysqli_fetch_assoc($result)){
				$dtitle = $row['title'];
				$stmt->execute();
				
				$dcat = $row['category'];
				$ddatc = $row['date_created'];
				$ddatl = $row['date_last'];
				
				$stmt->bind_result($dtxt);
				$stmt->fetch();
				
				/* don't work either :(
				//$noteres = $stmt->mysqli_fetch();;
				//$noterow = mysqli_fetch_assoc($noteres);
				*/
				
				/* apparently needs some sort of extra lib or smthn installed
				$noteres = $stmt->get_result();;
				$noterow = mysqli_fetch_assoc($noteres);
				*/
				
				/* doesn't work :/
				$notesql = "SELECT text FROM pages WHERE owner LIKE" . $dtitle . " AND page LIKE '1'";
				$noteres = mysqli_query($conn, $notesql);
				$noterow = mysqli_fetch_assoc($noteres);
				*/
				
				//$dtxt = $noteres;
				
				
				echo "<figure><a href='notepad.php?t=".$dtitle."'><h2>" . $dtitle . "</h2><h5>" . $dcat . "</h5><p>" . $dtxt . "</p>	<table class='tbldetails'><colgroup><col span='1' class='dtc1'><col span='1' class='dtc2'></colgroup><tr><td>Date Created</td><td>". $ddatc . "</td></tr><tr><td>Last Modified</td><td>" . $ddatl . "</td></tr></table></a></figure>";
			}
		?>
	</div>
</body>

</html>