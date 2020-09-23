-- phpMyAdmin SQL Dump
-- version 4.9.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Aug 18, 2020 at 11:40 PM
-- Server version: 5.7.26
-- PHP Version: 7.4.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `midosis`
--
CREATE DATABASE IF NOT EXISTS `midosis` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `midosis`;

-- --------------------------------------------------------

--
-- Table structure for table `dosis`
--

DROP TABLE IF EXISTS `dosis`;
CREATE TABLE `dosis` (
  `id` int(11) NOT NULL,
  `horario` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `pastillero_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `droga`
--

DROP TABLE IF EXISTS `droga`;
CREATE TABLE `droga` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `pastillero_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `droga_x_dosis`
--

DROP TABLE IF EXISTS `droga_x_dosis`;
CREATE TABLE `droga_x_dosis` (
  `id` int(11) NOT NULL,
  `droga_id` int(11) NOT NULL,
  `dosis_id` int(11) NOT NULL,
  `cantidad_mg` float NOT NULL,
  `notas` varchar(500) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

DROP TABLE IF EXISTS `login`;
CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `login_dttm` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pastillero`
--

DROP TABLE IF EXISTS `pastillero`;
CREATE TABLE `pastillero` (
  `id` int(11) NOT NULL,
  `dia_actualizacion` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `droga_id` int(11) NOT NULL,
  `comprimido` int(11) NOT NULL,
  `cantidad_doceavos` int(11) NOT NULL,
  `ingreso_dttm` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `apellido` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `email` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `pass_hash` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `pendiente_cambio_pass` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usuario_x_pastillero`
--

DROP TABLE IF EXISTS `usuario_x_pastillero`;
CREATE TABLE `usuario_x_pastillero` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pastillero_id` int(11) NOT NULL,
  `admin` tinyint(4) NOT NULL,
  `activo` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dosis`
--
ALTER TABLE `dosis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pastillero_id_2` (`pastillero_id`);

--
-- Indexes for table `droga`
--
ALTER TABLE `droga`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pastillero_id_1` (`pastillero_id`);

--
-- Indexes for table `droga_x_dosis`
--
ALTER TABLE `droga_x_dosis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `droga_id_1` (`droga_id`),
  ADD KEY `dosis_id_1` (`dosis_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id_1` (`usuario_id`);

--
-- Indexes for table `pastillero`
--
ALTER TABLE `pastillero`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id_1` (`paciente_id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `droga_id_2` (`droga_id`);

--
-- Indexes for table `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usuario_x_pastillero`
--
ALTER TABLE `usuario_x_pastillero`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id_2` (`usuario_id`),
  ADD KEY `pastillero_id_3` (`pastillero_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dosis`
--
ALTER TABLE `dosis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `droga`
--
ALTER TABLE `droga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `droga_x_dosis`
--
ALTER TABLE `droga_x_dosis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pastillero`
--
ALTER TABLE `pastillero`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuario_x_pastillero`
--
ALTER TABLE `usuario_x_pastillero`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dosis`
--
ALTER TABLE `dosis`
  ADD CONSTRAINT `pastillero_id_2` FOREIGN KEY (`pastillero_id`) REFERENCES `pastillero` (`id`);

--
-- Constraints for table `droga`
--
ALTER TABLE `droga`
  ADD CONSTRAINT `pastillero_id_1` FOREIGN KEY (`pastillero_id`) REFERENCES `pastillero` (`id`);

--
-- Constraints for table `droga_x_dosis`
--
ALTER TABLE `droga_x_dosis`
  ADD CONSTRAINT `dosis_id_1` FOREIGN KEY (`dosis_id`) REFERENCES `dosis` (`id`),
  ADD CONSTRAINT `droga_id_1` FOREIGN KEY (`droga_id`) REFERENCES `droga` (`id`);

--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `usuario_id_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Constraints for table `pastillero`
--
ALTER TABLE `pastillero`
  ADD CONSTRAINT `paciente_id_1` FOREIGN KEY (`paciente_id`) REFERENCES `usuario` (`id`);

--
-- Constraints for table `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `droga_id_2` FOREIGN KEY (`droga_id`) REFERENCES `droga` (`id`);

--
-- Constraints for table `usuario_x_pastillero`
--
ALTER TABLE `usuario_x_pastillero`
  ADD CONSTRAINT `pastillero_id_3` FOREIGN KEY (`pastillero_id`) REFERENCES `pastillero` (`id`),
  ADD CONSTRAINT `usuario_id_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
