-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 01, 2026 at 02:03 PM
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
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `color` varchar(50) DEFAULT '#ffffff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `name`, `color`) VALUES
(1, 0, 'General', '#fff9c4'),
(2, 0, 'Personal', '#e8f5e9'),
(3, 0, 'Work', '#e3f2fd'),
(4, 0, 'Study', '#fce4ec'),
(5, 0, 'Ideas', '#f3e5f5');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `color` int(11) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_last` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `title`, `category`, `color`, `date_created`, `date_last`, `is_pinned`, `is_archived`) VALUES
(1, 0, 'this is the title', 'General', 0, '2026-02-01 10:05:02', '2026-02-01 13:39:30', 0, 0),
(2, 0, 'Yes', 'General', 0, '2026-02-01 10:20:25', '2026-02-01 10:20:25', 0, 0),
(3, 0, 'CSIT6 PRELIM', 'Study', 0, '2026-02-01 11:18:14', '2026-02-01 11:43:28', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `note_id` int(11) NOT NULL,
  `page_number` int(11) DEFAULT 1,
  `text` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `note_id`, `page_number`, `text`) VALUES
(1, 1, 1, '\r\n					\r\n					\r\n					\r\n					<b>This is a body</b><div><b><br></b></div><div>this <b><i>this is italic</i></b></div><div><b><i><br></i></b></div><div><b><i><u>this is iitalic underlined<br><br></u></i></b><h3><b><i><u>hey<br><br><ul><li><b><i><u>Thiss is a list</u></i></b></li><li><b><i><u>secon</u></i></b></li><li><b><i><u>third</u></i></b></li><li><b><i><u>fourth</u></i></b></li><li><b><i><u>fith</u></i></b></li><li><b><i><u>sixh</u></i></b></li><li><b><i><u>jhdfjwad</u></i></b></li><li><b><i><u>ad</u></i></b></li><li><b><i><u>awd</u></i></b></li><li><b><i><u>awd</u></i></b></li><li><b><i><u>awd</u></i></b></li><li><b><i><u>wd</u></i></b></li><li><b><i><u>wad</u></i></b></li><li><b><i><u><br></u></i></b></li></ul></u></i></b></h3></div>																'),
(2, 2, 1, 'Test'),
(3, 3, 1, 'This website is a preliminary examination requirement and yes there is a thing cc\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_guest` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `note_id` (`note_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
