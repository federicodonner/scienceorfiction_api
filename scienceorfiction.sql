-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Oct 05, 2020 at 04:26 PM
-- Server version: 5.7.23
-- PHP Version: 7.2.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `scienceorfiction`
--
CREATE DATABASE IF NOT EXISTS `scienceorfiction` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `scienceorfiction`;

-- --------------------------------------------------------

--
-- Table structure for table `episode`
--

DROP TABLE IF EXISTS `episode`;
CREATE TABLE `episode` (
  `id` int(11) NOT NULL,
  `broadcastDate` int(11) NOT NULL,
  `episodeNumber` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `game`
--

DROP TABLE IF EXISTS `game`;
CREATE TABLE `game` (
  `id` int(11) NOT NULL,
  `hostId` int(11) NOT NULL,
  `episodeId` int(11) NOT NULL,
  `Theme` varchar(500) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

DROP TABLE IF EXISTS `item`;
CREATE TABLE `item` (
  `id` int(11) NOT NULL,
  `itemText` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `link` varchar(500) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `sortOrder` int(11) NOT NULL,
  `fiction` tinyint(4) NOT NULL,
  `gameId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `response`
--

DROP TABLE IF EXISTS `response`;
CREATE TABLE `response` (
  `id` int(11) NOT NULL,
  `rogueId` int(11) NOT NULL,
  `responseOrder` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `response_x_item`
--

DROP TABLE IF EXISTS `response_x_item`;
CREATE TABLE `response_x_item` (
  `id` int(11) NOT NULL,
  `responseId` int(11) NOT NULL,
  `itemId` int(11) NOT NULL,
  `fiction` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rogue`
--

DROP TABLE IF EXISTS `rogue`;
CREATE TABLE `rogue` (
  `id` int(11) NOT NULL,
  `firstName` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `lastName` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `picture` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `episode`
--
ALTER TABLE `episode`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game`
--
ALTER TABLE `game`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_rogue` (`hostId`),
  ADD KEY `game_episode` (`episodeId`);

--
-- Indexes for table `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_game` (`gameId`);

--
-- Indexes for table `response`
--
ALTER TABLE `response`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_rogue` (`rogueId`);

--
-- Indexes for table `response_x_item`
--
ALTER TABLE `response_x_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_x_item_response` (`responseId`),
  ADD KEY `response_x_item_item` (`itemId`);

--
-- Indexes for table `rogue`
--
ALTER TABLE `rogue`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `episode`
--
ALTER TABLE `episode`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game`
--
ALTER TABLE `game`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item`
--
ALTER TABLE `item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `response`
--
ALTER TABLE `response`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `response_x_item`
--
ALTER TABLE `response_x_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rogue`
--
ALTER TABLE `rogue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `game`
--
ALTER TABLE `game`
  ADD CONSTRAINT `game_episode` FOREIGN KEY (`episodeId`) REFERENCES `episode` (`id`),
  ADD CONSTRAINT `game_rogue` FOREIGN KEY (`hostId`) REFERENCES `rogue` (`id`);

--
-- Constraints for table `item`
--
ALTER TABLE `item`
  ADD CONSTRAINT `item_game` FOREIGN KEY (`gameId`) REFERENCES `game` (`id`);

--
-- Constraints for table `response`
--
ALTER TABLE `response`
  ADD CONSTRAINT `response_rogue` FOREIGN KEY (`rogueId`) REFERENCES `rogue` (`id`);

--
-- Constraints for table `response_x_item`
--
ALTER TABLE `response_x_item`
  ADD CONSTRAINT `response_x_item_item` FOREIGN KEY (`itemId`) REFERENCES `item` (`id`),
  ADD CONSTRAINT `response_x_item_response` FOREIGN KEY (`responseId`) REFERENCES `response` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
