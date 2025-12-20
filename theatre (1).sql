-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2025 at 04:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `theatre`
--

-- --------------------------------------------------------

--
-- Table structure for table `can_add`
--

CREATE TABLE `can_add` (
  `Visitor1_ID` int(8) NOT NULL,
  `Visitor2_ID` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `can_explore`
--

CREATE TABLE `can_explore` (
  `Visitor_ID` int(8) NOT NULL,
  `ReleasedYear` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movie_loc`
--

CREATE TABLE `movie_loc` (
  `Released_year` date NOT NULL,
  `Locations` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `P_ID` int(11) NOT NULL,
  `P_date` date NOT NULL,
  `Pay_later` int(11) NOT NULL,
  `Amount` int(11) NOT NULL,
  `PreBook` int(11) NOT NULL,
  `Visitor_ID` int(8) NOT NULL,
  `Sn_flag` tinyint(1) NOT NULL,
  `Fr_flag` tinyint(1) NOT NULL,
  `Tick_flag` tinyint(1) NOT NULL,
  `GC_flag` tinyint(1) NOT NULL,
  `Food_name` varchar(100) NOT NULL,
  `Drinks_name` varchar(100) NOT NULL,
  `Food_quantity` int(11) NOT NULL,
  `Drinks_quantity` int(11) NOT NULL,
  `Ticket_num` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchased_items`
--

CREATE TABLE `purchased_items` (
  `P_ID` int(11) NOT NULL,
  `Items` varchar(100) NOT NULL,
  `Items_quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_method`
--

CREATE TABLE `purchase_method` (
  `P_ID` int(11) NOT NULL,
  `Payment_method` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription`
--

CREATE TABLE `subscription` (
  `S_ID` int(6) NOT NULL,
  `Subtitles` varchar(200) NOT NULL,
  `Wishlist` varchar(200) NOT NULL,
  `Visitor_ID` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subs_content`
--

CREATE TABLE `subs_content` (
  `S_ID` int(6) NOT NULL,
  `Contents` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `can_add`
--
ALTER TABLE `can_add`
  ADD PRIMARY KEY (`Visitor1_ID`,`Visitor2_ID`);

--
-- Indexes for table `can_explore`
--
ALTER TABLE `can_explore`
  ADD PRIMARY KEY (`Visitor_ID`,`ReleasedYear`);

--
-- Indexes for table `movie_loc`
--
ALTER TABLE `movie_loc`
  ADD PRIMARY KEY (`Released_year`,`Locations`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`P_ID`);

--
-- Indexes for table `purchased_items`
--
ALTER TABLE `purchased_items`
  ADD PRIMARY KEY (`P_ID`,`Items`);

--
-- Indexes for table `purchase_method`
--
ALTER TABLE `purchase_method`
  ADD PRIMARY KEY (`P_ID`,`Payment_method`);

--
-- Indexes for table `subscription`
--
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`S_ID`);

--
-- Indexes for table `subs_content`
--
ALTER TABLE `subs_content`
  ADD PRIMARY KEY (`S_ID`,`Contents`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchased_items`
--
ALTER TABLE `purchased_items`
  ADD CONSTRAINT `purchased_items_ibfk_1` FOREIGN KEY (`P_ID`) REFERENCES `purchase` (`P_ID`);

--
-- Constraints for table `purchase_method`
--
ALTER TABLE `purchase_method`
  ADD CONSTRAINT `purchase_method_ibfk_1` FOREIGN KEY (`P_ID`) REFERENCES `purchase` (`P_ID`);

--
-- Constraints for table `subs_content`
--
ALTER TABLE `subs_content`
  ADD CONSTRAINT `subs_content_ibfk_1` FOREIGN KEY (`S_ID`) REFERENCES `subscription` (`S_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
