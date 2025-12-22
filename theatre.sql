-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 04:21 PM
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
  `ID` int(8) NOT NULL,
  `released_year` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_remove`
--

INSERT INTO `add_remove` (`ID`, `released_year`) VALUES
(1, '2020-01-21'),
(1, '2026-06-21'),
(2, '2021-03-12'),
(4, '2022-01-14'),
(6, '2022-11-22'),
(10, '2024-01-25'),
(15, '2025-05-23');

-- --------------------------------------------------------

--
-- Table structure for table `add_subtitles`
--

CREATE TABLE `add_subtitles` (
  `admin_id` int(30) NOT NULL,
  `released_year` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_subtitles`
--

INSERT INTO `add_subtitles` (`admin_id`, `released_year`) VALUES
(2, '2021-03-12'),
(4, '2022-01-14'),
(6, '2022-11-22'),
(10, '2024-01-25'),
(15, '2025-05-23');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `ID` int(30) NOT NULL,
  `Add polls` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`ID`, `Add polls`) VALUES
(1, 'Choose Snacks | Fries | Burger'),
(2, 'Enable weekend promo poll'),
(4, 'Customer satisfaction poll'),
(6, 'Preferred showtime poll'),
(10, 'New releases poll'),
(15, 'Game corner poll');

-- --------------------------------------------------------

--
-- Table structure for table `can_add`
--

CREATE TABLE `can_add` (
  `Visitor1_ID` int(8) NOT NULL,
  `Visitor2_ID` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `can_add`
--

INSERT INTO `can_add` (`Visitor1_ID`, `Visitor2_ID`) VALUES
(1, 2),
(1, 3),
(1, 20),
(2, 1),
(2, 3),
(3, 1),
(3, 2),
(20, 1);

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
-- Table structure for table `friend_requests`
--

CREATE TABLE `friend_requests` (
  `ID` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friend_requests`
--

INSERT INTO `friend_requests` (`ID`, `requester_id`, `receiver_id`, `status`, `created_at`, `responded_at`) VALUES
(1, 1, 20, 'accepted', '2025-12-22 11:43:12', '2025-12-22 11:43:41'),
(2, 20, 2, 'pending', '2025-12-22 12:33:42', NULL),
(3, 21, 20, 'rejected', '2025-12-22 12:39:43', '2025-12-22 12:40:56'),
(4, 21, 1, 'pending', '2025-12-22 12:40:01', NULL);

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

--
-- Dumping data for table `movie_catalogue`
--

INSERT INTO `movie_catalogue` (`celeb_blog`, `released_year`, `reviews`, `up_movie`, `on_movie`, `duration`) VALUES
('', '2020-01-21', '4.7', '', 'Stranger Things', 120),
('Behind the Scenes: Dhaka Premiere', '2021-03-12', '4.3', 'The Silent Stage', '', 112),
('Star Interview: Lead Actor', '2021-08-27', '4.5', 'Midnight Metro', '', 128),
('Director Notes', '2022-01-14', '4.1', 'Crimson Curtain', '', 105),
('Festival Buzz', '2022-06-09', '4.6', 'Neon Skyline', '', 137),
('Critics Corner', '2022-11-22', '4.0', 'River of Lights', '', 98),
('Cast Spotlight', '2023-02-18', '4.4', 'Echoes of Tomorrow', '', 122),
('Local Hit Review', '2023-05-30', '4.2', 'Tea Stall Tales', '', 110),
('Audience Reactions', '2023-09-08', '4.7', 'Storm Over Sundarbans', '', 140),
('Soundtrack Breakdown', '2024-01-25', '4.5', 'City of Kites', '', 119),
('Premiere Night Recap', '2024-04-19', '4.1', 'The Last Ticket', '', 101),
('Weekend Picks', '2024-07-06', '4.6', 'Golden Frame', '', 132),
('Hidden Gems', '2024-10-13', '4.0', 'Monsoon Melody', '', 116),
('Box Office Watch', '2025-01-17', '4.8', 'Doomsday Avenue', '', 145),
('Top 10 Romance', '2025-05-23', '4.2', 'Letters in Rain', '', 108),
('Year-End Wrap', '2025-09-29', '4.4', 'Sky Harbor Nights', '', 126),
('', '2026-06-21', '5.00', 'Friends', '', 220);

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
-- Table structure for table `name_of_games`
--

CREATE TABLE `name_of_games` (
  `Games_id` int(30) NOT NULL,
  `games_type` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `poll_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `polls`
--

INSERT INTO `polls` (`poll_id`, `question`, `is_active`, `created_by_admin_id`, `created_at`) VALUES
(1, 'Which genre do you like most?', 0, 1, '2025-12-22 12:06:19'),
(2, 'Which fastfood should we bring?', 1, 1, '2025-12-22 12:07:01');

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE `poll_options` (
  `option_id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poll_options`
--

INSERT INTO `poll_options` (`option_id`, `poll_id`, `option_text`) VALUES
(1, 1, 'Action'),
(2, 1, 'Comedy'),
(3, 1, 'Horror'),
(4, 1, 'Romance'),
(5, 2, 'Burger'),
(6, 2, 'Pizza'),
(7, 2, 'FrenchFry'),
(8, 2, 'Popcorn');

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE `poll_votes` (
  `poll_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poll_votes`
--

INSERT INTO `poll_votes` (`poll_id`, `user_id`, `option_id`, `voted_at`) VALUES
(1, 20, 2, '2025-12-22 12:07:38'),
(1, 21, 3, '2025-12-22 12:40:13');

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

--
-- Dumping data for table `subscription`
--

INSERT INTO `subscription` (`S_ID`, `Subtitles`, `Wishlist`, `Visitor_ID`) VALUES
(250415, '', '', 21),
(383931, '', 'Stranger Things, The Silent Stage, Midnight Metro, Letters in Rain', 20),
(572960, '', 'Stranger Things, barbie', 2),
(598593, '', '', 1),
(704594, '', '', 3),
(710383, '', 'Stranger Things', 0),
(987835, '', 'Avengers : Doomsday', 4);

-- --------------------------------------------------------

--
-- Table structure for table `subs_content`
--

CREATE TABLE `subs_content` (
  `S_ID` int(6) NOT NULL,
  `Contents` varchar(200) NOT NULL
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
  `ID` int(8) NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `Phone` varchar(30) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ID`, `Name`, `Email`, `Phone`, `PasswordHash`) VALUES
(1, 'Labiba', 'labiba@gmail.com', '017117263732', '$2y$10$3VjdSDm3FhV3azZczxbpzedaMtL3H9i8P33i6MOohq/qSwcfcrpqe'),
(2, 'momo', 'momo@gmail.com', '01911283214', '$2y$10$5J3p6K1DT9ULP9.l7fuemOIasmsHs1VRaxXnEBBujpOQODitsrL8G'),
(3, 'mashiyat', 'mashiyat@gmail.com', '12345678', '$2y$10$B6DKyi5SBniyJzUIcFA0YeAI6Nvgf3hVloS46Z7asRXjTH6gmd8aq'),
(4, 'Sufiyan Arif', 'labibagadha@gmail.com', '01540175700', '$2y$10$vyAllM6Sz1chl/XgQuzo2OoM3p9zjUEB6PnTX9M2.WZTu16cFOwBa'),
(5, 'Ayesha Rahman', 'ayesha.rahman@gmail.com', '01711000005', '$2y$10$uR8Q3oKq8FhQ2x9z1yT8eOa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(6, 'Tanvir Ahmed', 'tanvir.ahmed@gmail.com', '01711000006', '$2y$10$kP7D2sLm9NqW1x3z5yT7eOa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(7, 'Nusrat Jahan', 'nusrat.jahan@gmail.com', '01711000007', '$2y$10$Zx1C2vBn3mQw4eRt5yUi6Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(8, 'Sabbir Hossain', 'sabbir.hossain@gmail.com', '01711000008', '$2y$10$Aa2Bb3Cc4Dd5Ee6Ff7Gg8Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(9, 'Mim Sultana', 'mim.sultana@gmail.com', '01711000009', '$2y$10$Qq1Ww2Ee3Rr4Tt5Yy6Uu7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(10, 'Rakib Hasan', 'rakib.hasan@gmail.com', '01711000010', '$2y$10$Hh1Jj2Kk3Ll4Mm5Nn6Oo7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(11, 'Farzana Akter', 'farzana.akter@gmail.com', '01711000011', '$2y$10$Vv1Bb2Nn3Mm4Cc5Xx6Zz7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(12, 'Mehedi Hasan', 'mehedi.hasan@gmail.com', '01711000012', '$2y$10$Ss1Dd2Ff3Gg4Hh5Jj6Kk7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(13, 'Sadia Islam', 'sadia.islam@gmail.com', '01711000013', '$2y$10$Pp1Oo2Ii3Uu4Yy5Tt6Rr7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(14, 'Arif Chowdhury', 'arif.chowdhury@gmail.com', '01711000014', '$2y$10$Nn1Mm2Bb3Vv4Cc5Xx6Zz7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(15, 'Samira Karim', 'samira.karim@gmail.com', '01711000015', '$2y$10$Tt1Rr2Ee3Ww4Qq5Aa6Ss7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(16, 'Imran Kabir', 'imran.kabir@gmail.com', '01711000016', '$2y$10$Yy1Uu2Ii3Oo4Pp5Ll6Kk7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(17, 'Jannat Ferdous', 'jannat.ferdous@gmail.com', '01711000017', '$2y$10$Cc1Xx2Zz3Vv4Bb5Nn6Mm7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(18, 'Nazmul Huda', 'nazmul.huda@gmail.com', '01711000018', '$2y$10$Ee1Rr2Tt3Yy4Uu5Ii6Oo7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(19, 'Priya Saha', 'priya.saha@gmail.com', '01711000019', '$2y$10$Mm1Nn2Bb3Vv4Cc5Xx6Zz7Oa1b2c3d4e5f6g7h8i9j0kLmNoPq'),
(20, 'faiyaz', 'faiyaz@gmail.com', '12345', '$2y$10$Q8Z5jjwjQ6PIdxaljwZahOOF8F0KS9p9UVTokZE5Cprth2Vz3244C'),
(21, 'imran', 'imran@gmail.com', '12345678', '$2y$10$F8ZFX9C6NhiwEwue2wjnk.Z879QYbYtuHSkUSuMNHbRcP9EQKf1qy');

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
-- Dumping data for table `visitor`
--

INSERT INTO `visitor` (`ID`, `Reward_points`, `Privacy`) VALUES
(0, 0, 0),
(1, 0, 1),
(2, 0, 1),
(3, 0, 1),
(4, 0, 0),
(5, 120, 0),
(6, 40, 1),
(7, 300, 0),
(8, 75, 0),
(9, 15, 1),
(10, 560, 0),
(11, 210, 0),
(12, 90, 1),
(13, 430, 0),
(14, 25, 0),
(15, 150, 1),
(16, 610, 0),
(17, 80, 0),
(18, 260, 1),
(19, 35, 0),
(20, 0, 1),
(21, 0, 0);

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
-- Indexes for table `can_add`
--
ALTER TABLE `can_add`
  ADD PRIMARY KEY (`Visitor1_ID`,`Visitor2_ID`),
  ADD UNIQUE KEY `uniq_friend_pair` (`Visitor1_ID`,`Visitor2_ID`);

--
-- Indexes for table `can_explore`
--
ALTER TABLE `can_explore`
  ADD PRIMARY KEY (`Visitor_ID`,`ReleasedYear`),
  ADD KEY `ReleasedYear` (`ReleasedYear`);

--
-- Indexes for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uniq_pair` (`requester_id`,`receiver_id`),
  ADD KEY `idx_receiver_status` (`receiver_id`,`status`);

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
-- Indexes for table `movie_loc`
--
ALTER TABLE `movie_loc`
  ADD PRIMARY KEY (`Released_year`,`Locations`);

--
-- Indexes for table `name_of_games`
--
ALTER TABLE `name_of_games`
  ADD PRIMARY KEY (`Games_id`,`games_type`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`poll_id`);

--
-- Indexes for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `poll_id` (`poll_id`);

--
-- Indexes for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD PRIMARY KEY (`poll_id`,`user_id`),
  ADD KEY `option_id` (`option_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`P_ID`),
  ADD KEY `Visitor_ID` (`Visitor_ID`);

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
  ADD PRIMARY KEY (`S_ID`),
  ADD KEY `Visitor_ID` (`Visitor_ID`);

--
-- Indexes for table `subs_content`
--
ALTER TABLE `subs_content`
  ADD PRIMARY KEY (`S_ID`,`Contents`);

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
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uniq_user_email` (`Email`);

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
  MODIFY `ID` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `Games_id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `poll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `ID` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
-- Constraints for table `can_explore`
--
ALTER TABLE `can_explore`
  ADD CONSTRAINT `can_explore_ibfk_1` FOREIGN KEY (`ReleasedYear`) REFERENCES `movie_catalogue` (`released_year`),
  ADD CONSTRAINT `can_explore_ibfk_2` FOREIGN KEY (`Visitor_ID`) REFERENCES `visitor` (`ID`);

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `visitor` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `movie_loc`
--
ALTER TABLE `movie_loc`
  ADD CONSTRAINT `movie_loc_ibfk_1` FOREIGN KEY (`Released_year`) REFERENCES `movie_catalogue` (`released_year`);

--
-- Constraints for table `name_of_games`
--
ALTER TABLE `name_of_games`
  ADD CONSTRAINT `name_of_games_ibfk_1` FOREIGN KEY (`Games_id`) REFERENCES `games` (`Games_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`poll_id`) ON DELETE CASCADE;

--
-- Constraints for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD CONSTRAINT `poll_votes_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`poll_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poll_votes_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`option_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poll_votes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `purchase`
--
ALTER TABLE `purchase`
  ADD CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`Visitor_ID`) REFERENCES `visitor` (`ID`);

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
-- Constraints for table `subscription`
--
ALTER TABLE `subscription`
  ADD CONSTRAINT `subscription_ibfk_1` FOREIGN KEY (`Visitor_ID`) REFERENCES `visitor` (`ID`);

--
-- Constraints for table `subs_content`
--
ALTER TABLE `subs_content`
  ADD CONSTRAINT `subs_content_ibfk_1` FOREIGN KEY (`S_ID`) REFERENCES `subscription` (`S_ID`);

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
