<?php include 'includes/data_access.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<link rel="icon" href="favicon.png" type="image/png">
	<title>Contact - Notebook-BAR</title>
</head>

<body>

	<header>
		<div class="header-inner">
			<h1><a href="dashboard.php">Notebook-BAR</a></h1>
			<input type="checkbox" id="menu-toggle" class="menu-toggle">
			<label for="menu-toggle" class="hamburger">
				<span></span>
				<span></span>
				<span></span>
			</label>
			<nav>
				<a href="dashboard.php">Dashboard</a>
				<a href="index.php">Notes</a>
				<a href="categories.php">Categories</a>
				<?php if (is_logged_in()): ?>
					<a href="profile.php">Profile</a>
					<a href="logout.php" style="color: #c62828;">Logout</a>
				<?php else: ?>
					<a href="login.php" style="color: #2e7d32;">Login</a>
				<?php endif; ?>
				<a href="about.php">About</a>
				<a href="contact.php" style="background: white;">Contact Us</a>
			</nav>
		</div>
	</header>

	<div class="container">
		<div
			style="background: var(--card-bg); padding: 40px; border: 1px solid var(--border-color); box-shadow: var(--shadow); width: 100%; max-width: 100%; overflow: hidden;">

			<div style="text-align: center; margin-bottom: 40px;">
				<h2 style="margin-top: 0; margin-bottom: 10px;">Get in Touch</h2>
				<p style="color: #666;">We'd love to hear from you! Reach out to our development team below.</p>
			</div>

			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">

				<!-- Lead Developer Card -->
				<div class="contact-card"
					style="background: #fff; border: 1px solid #ccc; padding: 25px; box-shadow: 2px 2px 0 rgba(0,0,0,0.05);">
					<h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #333;">Verne Mhel N.
						Calledo</h3>
					<div
						style="font-size: 13px; font-weight: bold; color: #555; margin-bottom: 15px; text-transform: uppercase;">
						Lead Developer</div>

					<ul style="list-style: none; padding: 0; font-size: 14px; line-height: 1.8;">
						<li><strong>📧 Email:</strong> vcalledo@gmail.com</li>
						<li><strong>🐙 Github:</strong> <a href="https://github.com/Cole-Studios" target="_blank"
								style="color: blue;">Cole-Studios</a></li>
						<li><strong>📘 Facebook:</strong> Coll Studios</li>
						<li><strong>🐦 Twitter:</strong> Archer Five</li>
					</ul>
				</div>

				<!-- Developer Card -->
				<div class="contact-card"
					style="background: #fff; border: 1px solid #ccc; padding: 25px; box-shadow: 2px 2px 0 rgba(0,0,0,0.05);">
					<h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #333;">Anecito Randy E.
						Calunod Jr.</h3>
					<div
						style="font-size: 13px; font-weight: bold; color: #555; margin-bottom: 15px; text-transform: uppercase;">
						Developer</div>

					<ul style="list-style: none; padding: 0; font-size: 14px; line-height: 1.8;">
						<li><strong>📧 Email:</strong> randythegreat000@gmail.com</li>
						<li><strong>🐙 Github:</strong> <a href="https://github.com/AiruxPH" target="_blank"
								style="color: blue;">AiruxPH</a></li>
						<li><strong>📘 Facebook:</strong> Randy Calunod Jr.</li>
						<li><strong>📸 Instagram:</strong> itsmerandythegreat</li>
					</ul>
				</div>

			</div>
		</div>
	</div>
</body>

</html>