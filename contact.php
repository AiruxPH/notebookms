<?php include 'includes/data_access.php'; ?>
<!DOCTYPE html>
<html>

<head>
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<title>Contact - Notebook-BAR</title>
</head>

<body>

	<header>
		<div class="header-inner">
			<h1><a href="dashboard.php">Notebook-BAR</a></h1>
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
		<div
			style="background: var(--card-bg); padding: 40px; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
			<h2>Contact Me</h2>
			<p>You can reach me through the following mediums:</p>
			<ul style="list-style: none; padding: 0; font-size: 1.1em;">
				<li style="margin-bottom: 10px;">
					<strong>Facebook:</strong> Coll Studios
				</li>
				<li style="margin-bottom: 10px;">
					<strong>Twitter:</strong> Archer Five
				</li>
				<li style="margin-bottom: 10px;">
					<strong>Email:</strong> vcalledo@gmail.com
				</li>
			</ul>
		</div>
	</div>

</body>

</html>