<?php include 'includes/data_access.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
	<link rel="icon" href="favicon.png" type="image/png">
	<title>About - Notebook-BAR</title>
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
				<a href="about.php" style="background: white;">About</a>
				<a href="contact.php">Contact Us</a>
			</nav>
		</div>
	</header>

	<div class="container">
		<div
			style="background: var(--card-bg); padding: 40px; border: 1px solid var(--border-color); box-shadow: var(--shadow); width: 100%; max-width: 100%; overflow: hidden;">

			<div style="text-align: center; margin-bottom: 30px;">
				<h2 style="font-size: 28px; margin-bottom: 10px;">About Notebook-BAR</h2>
				<p style="font-size: 16px; color: #555; max-width: 800px; margin: 0 auto;">
					A classic, typewriter-inspired digital notebook designed for focus and simplicity.
					Notebook-BAR combines the tactile feel of an old-school journal with the power of modern
					database storage.
				</p>
			</div>

			<hr style="border: 0; border-top: 1px dashed #999; margin: 30px 0;">

			<div style="margin-bottom: 40px;">
				<h3 style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">👨‍💻 The
					Development Team</h3>
				<div style="display: flex; gap: 20px; flex-wrap: wrap;">
					<div class="dev-card"
						style="flex: 1; min-width: 250px; background: #fff; padding: 20px; border: 1px solid #ccc; box-shadow: 2px 2px 0 rgba(0,0,0,0.1);">
						<div style="font-weight: bold; font-size: 18px; margin-bottom: 5px;">Verne Mhel N. Calledo
						</div>
						<div style="color: #666; font-size: 14px;">Lead Developer</div>
					</div>
					<div class="dev-card"
						style="flex: 1; min-width: 250px; background: #fff; padding: 20px; border: 1px solid #ccc; box-shadow: 2px 2px 0 rgba(0,0,0,0.1);">
						<div style="font-weight: bold; font-size: 18px; margin-bottom: 5px;">Anecito Randy E.
							Calunod Jr.</div>
						<div style="color: #666; font-size: 14px;">Developer</div>
					</div>
				</div>
			</div>

			<div>
				<h3 style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px;">✨ Key Features
				</h3>
				<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>📝 Secure Text Storage</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Notes are
							securely stored in a relational database, not just loose text files.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>🎨 Dynamic Categories</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Organize with
							custom, color-coded categories for visual sorting.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>📊 Real-time Stats</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Live word and
							character counts as you type.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>📌 Pinning & Archiving</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Keep important
							notes at the top and tuck away old ones.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>🔍 Search & Filter</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Instantly find
							notes by keyword or category.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>📱 Fully Responsive</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Optimized
							experience for Desktop, Tablet, and Mobile.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>📅 Auto-Timestamp</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Tracks creation
							and last modification times automatically.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>⚡ Instant Pagination</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Multi-page
							navigation is now instant. Flip through notes without waiting for page reloads.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>💾 Bulk Page Saving</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Edit multiple
							pages and save them all at once with a single click.</p>
					</div>

					<div style="background: #fff; padding: 15px; border: 1px solid #eee;">
						<strong>⌨️ Developer Shortcuts</strong>
						<p style="font-size: 13px; color: #666; margin-top: 5px; margin-bottom: 0;">Tab key support
							for indentation and 1,800-character limits per page.</p>
					</div>

				</div>
			</div>

		</div>
	</div>
</body>

</html>