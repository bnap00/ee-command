-- phpMyAdmin SQL Dump
-- version 4.6.6
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 26, 2017 at 06:27 PM
-- Server version: 10.1.25-MariaDB-1~xenial
-- PHP Version: 5.6.31-1~ubuntu16.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ee`
--

-- --------------------------------------------------------

--
-- Table structure for table `ee_site_data`
--

CREATE TABLE `ee_site_data` (
  `ID` int(11) NOT NULL,
  `site_name` varchar(40) NOT NULL,
  `site_type_code` varchar(40) NOT NULL,
  `site_type` varchar(40) NOT NULL,
  `cache_type` varchar(40) DEFAULT NULL,
  `php` varchar(40) DEFAULT NULL,
  `letsencrypt` varchar(40) NOT NULL DEFAULT 'disabled',
  `mysql` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `ee_site_data`
--

INSERT INTO `ee_site_data` (`ID`, `site_name`, `site_type_code`, `site_type`, `cache_type`, `php`, `letsencrypt`, `mysql`) VALUES
(76, 'wp.org', 'wpredis', 'WordPress', 'nginx redis_cache', '5.6', '', 'yes'),
(77, 'one.com', '', '', '', '7.0', '', ''),
(78, 'two.com', '', '', '', '7.0', '', ''),
(79, 'three.com', 'php7', 'PHP', '', '7.0', '', ''),
(80, 'four.com', 'wp', 'WordPress', '', '7.0', '', 'yes'),
(81, 'five.com', 'wp', 'WordPress', '', '7.0', '', 'yes'),
(82, 'six.com', 'wp', 'WordPress', '', '5.6', '', 'yes'),
(83, 'seven.com', 'php7', 'php', '', '7.0', 'enabled', ''),
(84, 'eight.com', 'mysql', 'PHP', '', '5.6', '', 'yes'),
(85, 'nine.com', 'mysql', 'PHP', '', '5.6', 'enabled', 'yes'),
(88, 'ten.com', 'mysql', 'PHP', '', '5.6', 'enabled', 'yes'),
(89, 'eleven.com', 'wp', 'WordPress', '', '5.6', '', 'yes'),
(90, 'twelve.com', 'wp', 'WordPress', '', '7.0', '', 'yes');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ee_site_data`
--
ALTER TABLE `ee_site_data`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `site_name` (`site_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ee_site_data`
--
ALTER TABLE `ee_site_data`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

