-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 02, 2025 at 09:15 PM
-- Server version: 8.0.30
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `irrigacao`
--

-- --------------------------------------------------------

--
-- Table structure for table `controle`
--

CREATE TABLE `controle` (
  `id` int NOT NULL,
  `estado` enum('ON','OFF') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OFF',
  `modo` enum('MANUAL','AUTO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dados_umidade`
--

CREATE TABLE `dados_umidade` (
  `id` int NOT NULL,
  `umidade` decimal(5,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `data_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leituras`
--

CREATE TABLE `leituras` (
  `id` int NOT NULL,
  `data_hora` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `temperatura` decimal(5,2) NOT NULL,
  `umidade_ar` decimal(5,2) NOT NULL,
  `umidade_solo` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `leituras_by_date`
-- (See below for the actual view)
--
CREATE TABLE `leituras_by_date` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `leituras_view`
-- (See below for the actual view)
--
CREATE TABLE `leituras_view` (
);

-- --------------------------------------------------------

--
-- Table structure for table `sensores`
--

CREATE TABLE `sensores` (
  `id` int NOT NULL,
  `data_hora` datetime DEFAULT NULL,
  `soil1` int DEFAULT NULL,
  `soil2` int DEFAULT NULL,
  `soil3` int DEFAULT NULL,
  `temp` float DEFAULT NULL,
  `hum` float DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure for view `leituras_by_date`
--
DROP TABLE IF EXISTS `leituras_by_date`;

CREATE ALGORITHM=UNDEFINED DEFINER=`softdesignercom`@`localhost` SQL SECURITY DEFINER VIEW `leituras_by_date`  AS SELECT `leituras_view`.`id` AS `id`, `leituras_view`.`temperatura` AS `temperatura`, `leituras_view`.`umidade_ar` AS `umidade_ar`, `leituras_view`.`umidade_solo` AS `umidade_solo`, `leituras_view`.`created_at` AS `created_at`, cast(`leituras_view`.`created_at` as date) AS `data_apenas` FROM `leituras_view``leituras_view`  ;

-- --------------------------------------------------------

--
-- Structure for view `leituras_view`
--
DROP TABLE IF EXISTS `leituras_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`softdesignercom`@`localhost` SQL SECURITY DEFINER VIEW `leituras_view`  AS SELECT `leituras`.`id` AS `id`, `leituras`.`temperatura` AS `temperatura`, `leituras`.`umidade_ar` AS `umidade_ar`, `leituras`.`umidade_solo` AS `umidade_solo`, `leituras`.`created_at` AS `created_at` FROM `leituras``leituras`  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `controle`
--
ALTER TABLE `controle`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dados_umidade`
--
ALTER TABLE `dados_umidade`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leituras`
--
ALTER TABLE `leituras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leituras_created_at` (`created_at`);

--
-- Indexes for table `sensores`
--
ALTER TABLE `sensores`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dados_umidade`
--
ALTER TABLE `dados_umidade`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leituras`
--
ALTER TABLE `leituras`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensores`
--
ALTER TABLE `sensores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
