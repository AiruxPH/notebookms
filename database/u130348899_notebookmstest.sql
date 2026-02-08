-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 08, 2026 at 03:46 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u130348899_notebookmstest`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `color` varchar(50) DEFAULT '#ffffff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `user_id`, `name`, `color`) VALUES
(1, 0, 'General', '#fff9c4'),
(2, 0, 'Personal', '#e8f5e9'),
(3, 0, 'Work', '#e3f2fd'),
(4, 0, 'Study', '#fce4ec'),
(5, 0, 'Ideas', '#f3e5f5'),
(6, 5, 'Green', '#75d94a'),
(7, 2, 'Supercalifragilisticexpialidocious', '#e0f7fa'),
(8, 3, 'novel', '#fff9c4'),
(9, 3, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '#d6f6d5'),
(10, 5, 'Novel', '#d5f1f6');

--
-- Triggers `categories`
--
DELIMITER $$
CREATE TRIGGER `limit_category_count` BEFORE INSERT ON `categories` FOR EACH ROW BEGIN
    DECLARE cat_count INT;
    SELECT COUNT(*) INTO cat_count FROM categories WHERE user_id = NEW.user_id;
    IF cat_count >= 20 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Limit reached: Maximum 20 categories per user allowed.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `note_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_last` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `category_id` int(11) DEFAULT 1,
  `reminder_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`note_id`, `user_id`, `title`, `date_created`, `date_last`, `is_pinned`, `is_archived`, `category_id`, `reminder_date`) VALUES
(1, 5, 'this is the title', '2026-02-01 10:05:02', '2026-02-03 10:59:48', 0, 0, 1, NULL),
(2, 5, 'Yes', '2026-02-01 10:20:25', '2026-02-03 10:59:48', 0, 0, 1, NULL),
(3, 5, 'CSIT6 PRELIM', '2026-02-01 11:18:14', '2026-02-03 10:59:48', 1, 0, 4, '2026-02-04 10:50:00'),
(4, 5, 'Green', '2026-02-01 15:18:13', '2026-02-03 10:59:48', 1, 1, 6, NULL),
(5, 2, 'Testing', '2026-02-02 01:45:53', '2026-02-02 02:03:09', 0, 0, 2, NULL),
(6, 2, 'Untitled', '2026-02-02 02:13:50', '2026-02-04 09:07:21', 0, 1, 1, NULL),
(7, 3, 'test', '2026-02-02 21:35:37', '2026-02-03 01:46:37', 0, 0, 1, '2026-02-25 01:46:00'),
(8, 3, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2026-02-03 00:00:48', '2026-02-03 01:46:16', 0, 0, 9, '2026-02-04 01:46:00'),
(9, 3, 'Novel title', '2026-02-03 00:16:13', '2026-02-03 01:44:34', 0, 0, 8, '2026-02-03 01:45:00'),
(10, 5, 'The Quiet Ink', '2026-02-03 02:58:40', '2026-02-03 10:59:48', 0, 0, 10, NULL),
(11, 6, 'Untitled', '2026-02-03 08:27:03', '2026-02-03 08:31:54', 0, 0, 1, NULL),
(12, 2, 'ASdf', '2026-02-03 10:53:53', '2026-02-03 10:53:53', 1, 0, 1, NULL),
(13, 2, 'Testing Stuff', '2026-02-04 08:49:34', '2026-02-04 09:18:07', 1, 0, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `note_id` int(11) NOT NULL,
  `page_number` int(11) DEFAULT 1,
  `text` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`note_id`, `page_number`, `text`) VALUES
(1, 1, '\r\n					\r\n					\r\n					\r\n					<b>This is a body</b><div><b><br></b></div><div>this <b><i>this is italic</i></b></div><div><b><i><br></i></b></div><div><b><i><u>this is iitalic underlined<br><br></u></i></b><h3><b><i><u>hey<br><br><ul><li><b><i><u>Thiss is a list</u></i></b></li><li><b><i><u>secon</u></i></b></li><li><b><i><u>third</u></i></b></li><li><b><i><u>fourth</u></i></b></li><li><b><i><u>fith</u></i></b></li><li><b><i><u>sixh</u></i></b></li><li><b><i><u>jhdfjwad</u></i></b></li><li><b><i><u>ad</u></i></b></li><li><b><i><u>awd</u></i></b></li><li><b><i><u>awd</u></i></b></li><li><b><i><u>awd</u></i></b></li><li><b><i><u>wd</u></i></b></li><li><b><i><u>wad</u></i></b></li><li><b><i><u><br></u></i></b></li></ul></u></i></b></h3></div>																'),
(2, 1, 'Test'),
(4, 1, '<h3>Green</h3><div>This is green</div>'),
(5, 1, '\r\n					\r\n					123								'),
(6, 1, '\r\n					Aasdfasdawasd				'),
(7, 1, '\r\n					\r\n					\r\n					\r\n					\r\n					\r\n					test																								'),
(8, 1, '\r\n					\r\n					\r\n					\r\n					\r\n					aaaaaaaaaaaaaaaaaaa aaaaaaaaaa&nbsp; &nbsp; &nbsp; aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa<div><br></div>																				'),
(9, 1, '\r\n					Narration: This is a narration				'),
(10, 1, '<p>On the morning the notebooks arrived, the town woke to a softer sound than usual. No engines. No shouting vendors. Just paper sliding against paper as if the air itself had learned how to turn a page.</p><p>Milo found the stack on his doorstep, wrapped in twine, each cover blank and warm to the touch. He counted nine notebooks. Inside the top one, a single line waited for him.</p><p>Write carefully. The ink remembers.</p><p>Milo laughed, because that is what you do when something strange pretends to be serious. He brought the notebooks inside, set them beside the kettle, and told himself this was just another prank from the university kids who passed through town.</p><p>He was wrong.</p>'),
(10, 2, '\n						This is page 2					'),
(10, 3, 'This is page 3'),
(10, 4, ''),
(10, 5, ''),
(11, 1, 'Hello world. Jsjajaj'),
(3, 1, 'This website is a preliminary examination requirement and yes there is a thing cc\n'),
(12, 1, 'ASdfasdasd'),
(13, 1, 'Ass'),
(13, 2, '<blockquote><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, \nsed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut \nenim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut \naliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit \nin voluptate velit esse cillum dolore eu fugiat nulla pariatur. \nExcepteur sint occaecat cupidatat non proident, sunt in culpa qui \nofficia deserunt mollit anim id est laborum.</p></blockquote>');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `security_word` varchar(255) DEFAULT NULL,
  `security_word_set` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `is_active`, `date_created`, `date_modified`, `security_word`, `security_word_set`) VALUES
(0, 'system_default', 'N/A', 'admin', 1, '2026-02-08 15:41:43', '2026-02-08 15:41:43', NULL, 0),
(1, 'AiruxPH', 'RandyBOY999999@@@', 'user', 1, '2026-02-02 16:50:04', '2026-02-03 02:30:04', 'randy', 1),
(2, 'archer', '123', 'user', 1, '2026-02-02 16:50:04', '2026-02-03 10:53:35', 'Arc', 1),
(3, 'admin', 'admin', 'admin', 1, '2026-02-02 16:56:16', '2026-02-02 16:56:16', NULL, 0),
(4, 'admin2', 'admin2', 'admin', 1, '2026-02-03 02:50:15', '2026-02-03 02:50:15', NULL, 0),
(5, 'novelist', 'novelist', 'user', 1, '2026-02-03 04:42:08', '2026-02-03 04:42:08', 'novelist', 1),
(6, 'otocchrys@gmail.com', '123456', 'user', 1, '2026-02-03 08:25:26', '2026-02-03 08:25:26', '111111', 1);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `prevent_last_admin_delete` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
    DECLARE admin_count INT;

    -- Only check if the row being deleted is an admin
    IF OLD.role = 'admin' THEN
        SELECT COUNT(*) INTO admin_count
        FROM users
        WHERE role = 'admin';

        IF admin_count <= 1 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot delete the last admin account.';
        END IF;
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `fk_category_user` (`user_id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `fk_note_user` (`user_id`),
  ADD KEY `fk_note_category` (`category_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD KEY `note_id` (`note_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_category_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `fk_note_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_note_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `fk_page_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`note_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
