<?php
include 'includes/db.php';

//echo "Test" . $_SESSION['passnote'];
if (isset($_GET['t'])) {
	$ntitle = $_GET['t'];
	echo "Test  " . $ntitle;
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
	<div id="nwrap">
		<table>
			<colgroup>
				<col span="1" style="width: 50%" />
				<col span="1" style="width: 50%" />
			</colgroup>
			<tr>
				<td>
					<b class="note_cap"> Title: </b> <br />
					<?php
					if (isset($_GET['t'])) {
						$ntitle = $_GET['t'];
						echo "Test  " . $ntitle;
					}
					/*
						if (is_null($_SESSION['passnote'])){
							echo "<input type='text' style='width: 90%;'>";
						} else{
							echo "<b>" . $_SESSION['passnote'] . "</b>";
						}*/

					//
					?>
				</td>
				<td>
					<div id="detwrap">
						Words: XXX <br />
						Characters: XXX <br />
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<form method="post" class="notetext">
						<textarea name="page" row="15" cols="10">
					<?php
					if (isset($_GET['t'])) {
						$ntitle = $_GET['t'];
						echo "Test  " . $ntitle;
					}

					?>
					</textarea>
					</form>
				</td>
			</tr>
		</table>
		<!--
		<div id="pagenav" style="width: 100%; text-align: center;">
			<a href="notepad.html"> < </a>
			<a href="notepad.html"> 1 </a>
			<a href="notepad.html"> 2 </a>
			<a href="notepad.html"> 3 </a>
			<a href="notepad.html"> > </a>
		</div>
		-->
	</div>
</body>

</html>