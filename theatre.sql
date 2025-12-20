-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2025 at 03:58 PM
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
-- Table structure for table `add_remove`
--

CREATE TABLE `add_remove` (
  `ID` int(30) NOT NULL,
  `released_year` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `add_subtitles`
--

CREATE TABLE `add_subtitles` (
  `admin_id` int(30) NOT NULL,
  `released_year` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `ID` int(30) NOT NULL,
  `Add polls` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `Games_id` int(30) NOT NULL,
  `games_name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `movie_confirmation` varchar(30) NOT NULL,
  `add-members` varchar(30) NOT NULL,
  `ID` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movie_catalogue`
--

CREATE TABLE `movie_catalogue` (
  `celeb_blog` varchar(200) NOT NULL,
  `released_year` date NOT NULL,
  `reviews` varchar(200) NOT NULL,
  `up_movie` varchar(200) NOT NULL,
  `on_movie` varchar(200) NOT NULL,
  `duration` int(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `name_of_games`
--

CREATE TABLE `name_of_games` (
  `Games_id` int(30) NOT NULL,
  `games_type` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport`
--

CREATE TABLE `transport` (
  `T_ID` int(30) NOT NULL,
  `Location` varchar(300) NOT NULL,
  `ID` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_type`
--

CREATE TABLE `transport_type` (
  `T_ID` int(30) NOT NULL,
  `Vehicle_type` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `ID` int(30) NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `Phone` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_games`
--

CREATE TABLE `user_games` (
  `Games_id` int(30) NOT NULL,
  `visitor_id` int(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor`
--

CREATE TABLE `visitor` (
  `ID` int(30) NOT NULL,
  `Reward_points` int(200) NOT NULL,
  `Privacy` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_remove`
--
ALTER TABLE `add_remove`
  ADD PRIMARY KEY (`ID`,`released_year`),
  ADD KEY `released_year` (`released_year`);

--
-- Indexes for table `add_subtitles`
--
ALTER TABLE `add_subtitles`
  ADD PRIMARY KEY (`admin_id`,`released_year`),
  ADD KEY `released_year` (`released_year`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`Games_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `movie_catalogue`
--
ALTER TABLE `movie_catalogue`
  ADD PRIMARY KEY (`released_year`);

--
-- Indexes for table `name_of_games`
--
ALTER TABLE `name_of_games`
  ADD PRIMARY KEY (`Games_id`,`games_type`);

--
-- Indexes for table `transport`
--
ALTER TABLE `transport`
  ADD PRIMARY KEY (`T_ID`,`ID`);

--
-- Indexes for table `transport_type`
--
ALTER TABLE `transport_type`
  ADD PRIMARY KEY (`T_ID`,`Vehicle_type`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `user_games`
--
ALTER TABLE `user_games`
  ADD PRIMARY KEY (`Games_id`,`visitor_id`),
  ADD KEY `visitor_id` (`visitor_id`);

--
-- Indexes for table `visitor`
--
ALTER TABLE `visitor`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `ID` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `Games_id` int(30) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `ID` int(30) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `add_remove`
--
ALTER TABLE `add_remove`
  ADD CONSTRAINT `add_remove_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `admin` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `add_remove_ibfk_2` FOREIGN KEY (`released_year`) REFERENCES `movie_catalogue` (`released_year`) ON DELETE CASCADE;

--
-- Constraints for table `add_subtitles`
--
ALTER TABLE `add_subtitles`
  ADD CONSTRAINT `add_subtitles_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `add_subtitles_ibfk_2` FOREIGN KEY (`released_year`) REFERENCES `movie_catalogue` (`released_year`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `user` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `visitor` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `name_of_games`
--
ALTER TABLE `name_of_games`
  ADD CONSTRAINT `name_of_games_ibfk_1` FOREIGN KEY (`Games_id`) REFERENCES `games` (`Games_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transport`
--
ALTER TABLE `transport`
  ADD CONSTRAINT `transport_ibfk_1` FOREIGN KEY (`T_ID`) REFERENCES `transport_type` (`T_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transport_type`
--
ALTER TABLE `transport_type`
  ADD CONSTRAINT `transport_type_ibfk_1` FOREIGN KEY (`T_ID`) REFERENCES `transport` (`T_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_games`
--
ALTER TABLE `user_games`
  ADD CONSTRAINT `user_games_ibfk_1` FOREIGN KEY (`Games_id`) REFERENCES `games` (`Games_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_games_ibfk_2` FOREIGN KEY (`visitor_id`) REFERENCES `visitor` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
