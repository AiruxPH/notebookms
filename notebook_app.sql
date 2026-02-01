-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 22, 2024 at 11:13 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `notebook_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE IF NOT EXISTS `notes` (
  `title` varchar(64) NOT NULL,
  `date_created` date NOT NULL,
  `date_last` date NOT NULL,
  `category` varchar(40) NOT NULL,
  `color` int(2) NOT NULL,
  PRIMARY KEY (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

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

CREATE TABLE IF NOT EXISTS `pages` (
  `owner` varchar(64) NOT NULL,
  `page` int(2) NOT NULL,
  `text` text NOT NULL,
  KEY `owner` (`owner`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`owner`, `page`, `text`) VALUES
('Testing 123', 1, 'This is a sample block of text that amounts to two hundred fifty five (255)characters long. Given how the text fields in HTML are limited to only 255 characters, instead of trying to find a way around it Ive decided to implement it instead as a feature.'),
('Shoppinglist', 1, '3 cans of sardines\r\n1 can of corned beef\r\n1 pack of misua\r\n9 packs of pancit (lemon)');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
