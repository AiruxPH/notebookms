<?php
include 'includes/db.php';

// Turn off foreign key checks to allow dropping tables freely
mysqli_query($conn, "SET foreign_key_checks = 0");

$queries = [
  "DROP TABLE IF EXISTS `pages`",
  "DROP TABLE IF EXISTS `notes`",
  "DROP TABLE IF EXISTS `categories`",
  "DROP TABLE IF EXISTS `users`",

  "CREATE TABLE `users` (
      `user_id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(255) NOT NULL,
      `password` VARCHAR(255) NOT NULL,
      `role` ENUM('user', 'admin') DEFAULT 'user',
      `security_word` VARCHAR(255) DEFAULT NULL,
      `security_word_set` TINYINT(1) DEFAULT 0,
      `is_active` TINYINT(1) DEFAULT 1,
      `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `is_guest` BOOLEAN DEFAULT FALSE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "CREATE TABLE `categories` (
      `category_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT DEFAULT 0,
      `name` VARCHAR(100) NOT NULL,
      `color` VARCHAR(50) DEFAULT '#ffffff'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "CREATE TABLE `notes` (
      `note_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT DEFAULT 0,
      `title` VARCHAR(255) NOT NULL,
      `category_id` INT DEFAULT 1,
      `is_pinned` TINYINT(1) DEFAULT 0,
      `is_archived` TINYINT(1) DEFAULT 0,
      `reminder_date` DATETIME DEFAULT NULL,
      `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `date_last` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  "INSERT INTO `categories` (user_id, name, color) VALUES
    (0, 'General', '#fff9c4'),
    (0, 'Personal', '#e8f5e9'),
    (0, 'Work', '#e3f2fd'),
    (0, 'Study', '#fce4ec'),
    (0, 'Ideas', '#f3e5f5')",

  "CREATE TABLE `pages` (
      `note_id` INT NOT NULL,
      `page_number` INT DEFAULT 1,
      `text` LONGTEXT,
      PRIMARY KEY (`note_id`, `page_number`),
      FOREIGN KEY (`note_id`) REFERENCES `notes`(`note_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($queries as $sql) {
  if (mysqli_query($conn, $sql)) {
    echo "Success: " . substr($sql, 0, 30) . "...\n";
  } else {
    echo "Error: " . mysqli_error($conn) . "\n";
  }
}

mysqli_query($conn, "SET foreign_key_checks = 1");
echo "Database schema updated successfully.\n";
?>