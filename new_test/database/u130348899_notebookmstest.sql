-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 01, 2026 at 09:30 AM
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
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `title` varchar(64) NOT NULL,
  `date_created` date NOT NULL,
  `date_last` date NOT NULL,
  `category` varchar(40) NOT NULL,
  `color` int(2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`title`, `date_created`, `date_last`, `category`, `color`) VALUES
('Testing 123', '2024-02-21', '2024-02-21', 'Random Category', 0),
('Shoppinglist', '2024-02-22', '2024-02-22', 'gear', 11);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `owner` varchar(64) NOT NULL,
  `page` int(2) NOT NULL,
  `text` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`owner`, `page`, `text`) VALUES
('Testing 123', 1, 'This is a sample block of text that amounts to two hundred fifty five (255)characters long. Given how the text fields in HTML are limited to only 255 characters, instead of trying to find a way around it Ive decided to implement it instead as a feature.'),
('Shoppinglist', 1, '3 cans of sardines\r\n1 can of corned beef\r\n1 pack of misua\r\n9 packs of pancit (lemon)');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`title`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD KEY `owner` (`owner`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
