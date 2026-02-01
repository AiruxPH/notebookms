<?php include 'includes/data_access.php'; ?>
<!DOCTYPE html>
<html>

<head>
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<title>About - Notebook-BAR</title>
</head>

<body>

	<header>
		<div class="header-inner">
			<h1><a href="dashboard.php">Notebook-BAR</a></h1>
			<nav>
				<a href="dashboard.php">Dashboard</a>
				<a href="index.php">Notes</a>
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
		<div
			style="background: var(--card-bg); padding: 40px; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
			<h2>About Notebook Bar</h2>
			<p>This app was made as a rather novel way to write digital notes. It uses elements from many of the popular
				note-taking apps and adds in a small twist that tries to emulate actually writing on a notebook. This is
				also reflected in the code where instead of a note being stored in one text file they are stored in a
				database.</p>
			<hr style="border: 0; border-top: 1px dashed #999; margin: 20px 0;">
			<h2>About the Dev</h2>
			<p>Verne Mhel Calledo is an amateur programmer who is currently enrolled in Misamis University. He majors in
				Computer Science, following the footsteps of his father Verne Ed Calledo who was a Computer Science
				graduate
				and the first student governor of the College of Computer Studies. Verne Mhel has had several forays
				into
				web development, having been a two-time champion of the Provincial Technolympics in Webdesign of the
				years
				2016 and 2017, and the second runner-up in the Regional Technolympics of 2016.</p>
		</div>
	</div>

</body>

</html>