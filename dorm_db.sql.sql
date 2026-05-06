-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: May 06, 2026 at 08:15 AM
-- Server version: 8.0.46
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dorm_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `billing_records`
--

CREATE TABLE `billing_records` (
  `id` int NOT NULL,
  `room_number` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `tenant_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `billing_month` date NOT NULL,
  `num_people` int NOT NULL,
  `water_prev` int NOT NULL,
  `water_new` int NOT NULL,
  `water_units` int NOT NULL,
  `water_cost` decimal(10,2) NOT NULL,
  `elec_prev` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elec_new` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elec_units` int NOT NULL,
  `elec_cost` decimal(10,2) NOT NULL,
  `room_rent` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `record_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `elec_image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `elec_image_prev_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_records`
--

INSERT INTO `billing_records` (`id`, `room_number`, `tenant_name`, `billing_month`, `num_people`, `water_prev`, `water_new`, `water_units`, `water_cost`, `elec_prev`, `elec_new`, `elec_units`, `elec_cost`, `room_rent`, `total_cost`, `payment_date`, `status`, `record_date`, `elec_image_path`, `elec_image_prev_path`) VALUES
(86, '101', 'แม้ว', '2026-01-01', 2, 0, 0, 0, 200.00, '3244', '3486', 242, 1936.00, 3500.00, 5636.00, '2026-04-22', 'paid', '2026-04-22 14:32:33', 'meter_101_2026-01_1776868343.png', ''),
(87, '102', 'แมว', '2026-01-01', 1, 0, 0, 0, 100.00, '3144', '3486', 342, 2736.00, 3500.00, 6336.00, '2026-04-22', 'paid', '2026-04-22 14:33:46', 'meter_102_2026-01_1776868416.png', ''),
(88, '103', 'ชินจัง', '2026-01-01', 1, 0, 0, 0, 100.00, '3342', '3486', 144, 1152.00, 3499.98, 4751.98, '2026-04-22', 'paid', '2026-04-22 14:34:44', 'meter_103_2026-01_1776868460.png', ''),
(89, '104', 'ต๋องแต๋ง', '2026-01-01', 1, 0, 0, 0, 100.00, '3287', '3486', 199, 1592.00, 3500.00, 5192.00, '2026-04-22', 'paid', '2026-04-22 14:35:19', 'meter_104_2026-01_1776868516.png', ''),
(90, '105', 'โชค', '2026-01-01', 2, 0, 0, 0, 200.00, '3354', '3486', 132, 1056.00, 3499.98, 4755.98, '2026-04-22', 'paid', '2026-04-22 14:43:15', 'meter_105_2026-01_1776868987.png', ''),
(91, '101', 'แม้ว', '2026-02-01', 2, 0, 0, 0, 200.00, '3340', '3486', 146, 1168.00, 3500.00, 4868.00, '2026-04-22', 'paid', '2026-04-22 14:44:21', 'meter_101_2026-02_1776869052.png', ''),
(92, '102', 'แมว', '2026-02-01', 1, 0, 0, 0, 100.00, '3288', '3486', 198, 1584.00, 3500.00, 5184.00, '2026-04-22', 'paid', '2026-04-22 14:45:01', 'meter_102_2026-02_1776869097.png', ''),
(93, '103', 'ชินจัง', '2026-02-01', 1, 0, 0, 0, 100.00, '3186', '3486', 300, 2400.00, 3500.00, 6000.00, '2026-04-22', 'paid', '2026-04-22 14:45:22', 'meter_103_2026-02_1776869116.png', ''),
(94, '104', 'ต๋องแต๋ง', '2026-02-01', 1, 0, 0, 0, 100.00, '3336', '3486', 150, 1200.00, 3500.00, 4800.00, '2026-04-22', 'paid', '2026-04-22 14:45:52', 'meter_104_2026-02_1776869148.png', ''),
(95, '105', 'โชค', '2026-02-01', 2, 0, 0, 0, 200.00, '3086', '3486', 400, 3200.00, 3500.00, 6900.00, '2026-04-22', 'paid', '2026-04-22 14:46:14', 'meter_105_2026-02_1776869168.png', ''),
(96, '101', 'แม้ว', '2026-03-01', 2, 0, 0, 0, 200.00, '3099', '3486', 387, 3096.00, 3500.00, 6796.00, '2026-04-22', 'paid', '2026-04-22 14:47:15', 'meter_101_2026-03_1776869229.png', ''),
(97, '102', 'แมว', '2026-03-01', 1, 0, 0, 0, 100.00, '3326', '3403', 77, 616.00, 3500.00, 4216.00, '2026-04-22', 'paid', '2026-04-22 14:47:48', 'meter_102_2026-03_1776869253.png', ''),
(98, '103', 'ชินจัง', '2026-03-01', 1, 0, 0, 0, 100.00, '1158', '1544', 386, 3088.00, 3500.00, 6688.00, '2026-04-22', 'paid', '2026-04-22 14:48:18', 'meter_103_2026-03_1776869285.png', ''),
(99, '104', 'ต๋องแต๋ง', '2026-03-01', 1, 0, 0, 0, 100.00, '0158', '0276', 118, 944.00, 3500.00, 4544.00, '2026-04-22', 'paid', '2026-04-22 14:49:08', 'meter_104_2026-03_1776869329.png', ''),
(101, '105', 'โชค', '2026-03-01', 2, 0, 0, 0, 200.00, '0156', '0276', 120, 960.00, 3500.00, 4660.00, '2026-04-22', 'paid', '2026-04-22 14:52:44', 'meter_105_2026-03_1776869542.png', ''),
(102, '101', 'แม้ว', '2026-04-01', 2, 0, 0, 0, 200.00, '3101', '3403', 302, 2416.00, 3500.00, 6116.00, NULL, 'pending', '2026-04-22 14:54:20', 'meter_101_2026-04_1776869644.png', ''),
(103, '102', 'แมว', '2026-04-01', 1, 0, 0, 0, 100.00, '3300', '3486', 186, 1488.00, 3500.00, 5088.00, NULL, 'pending', '2026-04-22 14:54:45', 'meter_102_2026-04_1776869681.png', ''),
(104, '103', 'ชินจัง', '2026-04-01', 2, 0, 0, 0, 200.00, '1144', '1544', 400, 3200.00, 3499.98, 6899.98, '2026-04-22', 'paid', '2026-04-22 14:55:07', 'meter_103_2026-04_1776869703.png', ''),
(105, '104', 'ต๋องแต๋ง', '2026-04-01', 2, 0, 0, 0, 200.00, '1154', '1544', 390, 3120.00, 3499.99, 6819.99, '2026-04-22', 'paid', '2026-04-22 14:55:45', 'meter_104_2026-04_1776869733.png', ''),
(106, '105', 'โชค', '2026-04-01', 2, 0, 0, 0, 200.00, '3165', '3486', 321, 2568.00, 3499.98, 6267.98, '2026-04-26', 'paid', '2026-04-22 14:56:10', 'meter_105_2026-04_1776869767.png', ''),
(107, '105', 'เด', '2026-05-01', 2, 0, 0, 0, 200.00, '3186', '3486', 300, 2400.00, 3500.00, 6100.00, '2026-04-23', 'paid', '2026-04-22 14:58:20', 'meter_105_2026-05_1776869896.png', ''),
(108, '104', 'ต๋องแต๋ง', '2026-05-01', 1, 0, 0, 0, 100.00, '1146', '1554', 408, 3264.00, 3500.00, 6864.00, '2026-04-23', 'paid', '2026-04-23 09:44:22', 'meter_readings/elec_104_20260423121208.jpg', ''),
(110, '101', 'แม้ว', '2026-05-01', 2, 0, 0, 0, 200.00, '3400', '3486', 86, 688.00, 3500.00, 4388.00, '2026-04-23', 'paid', '2026-04-23 10:11:17', 'meter_101_2026-05_1776939057.png', ''),
(112, '102', 'แมว', '2026-05-01', 1, 0, 0, 0, 100.00, '3286', '3486', 200, 1600.00, 3500.00, 5200.00, '2026-04-23', 'paid', '2026-04-23 13:23:41', 'meter_102_2026-05_1776950615.png', ''),
(113, '106', 'ออ', '2026-04-01', 2, 0, 0, 0, 200.00, '3111', '3486', 375, 3000.00, 3500.00, 6700.00, '2026-04-23', 'paid', '2026-04-23 14:50:52', 'meter_106_2026-04_1776955847.png', ''),
(114, '102', 'แมว', '2026-06-01', 1, 0, 0, 0, 100.00, '1186', '1544', 358, 2864.00, 3500.00, 6464.00, '2026-04-24', 'paid', '2026-04-24 07:20:31', 'meter_102_2026-06_1777015216.png', ''),
(115, '104', 'ต๋องแต๋ง', '2026-06-01', 1, 0, 0, 0, 100.00, '1112', '1544', 432, 3456.00, 3500.00, 7056.00, '2026-04-26', 'paid', '2026-04-26 13:12:59', 'meter_104_2026-06_1777209166.png', ''),
(117, '106', 'เก', '2026-06-01', 1, 0, 0, 0, 100.00, '3125', '3486', 361, 2888.00, 3500.00, 6488.00, '2026-04-27', 'paid', '2026-04-27 13:57:25', 'meter_106_2026-06_1777298218.png', '');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `room_number` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `room_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Standard',
  `price` decimal(10,2) NOT NULL,
  `occupant_count` int DEFAULT '1',
  `meter_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type`, `price`, `occupant_count`, `meter_no`) VALUES
(1, '101', 'Standard', 0.00, 1, NULL),
(2, '102', 'Standard', 0.00, 1, NULL),
(3, '103', 'Standard', 0.00, 1, NULL),
(4, '104', 'Standard', 0.00, 1, NULL),
(5, '105', 'Standard', 0.00, 1, NULL),
(6, '106', 'Standard', 0.00, 1, NULL),
(7, '107', 'Standard', 0.00, 1, NULL),
(8, '108', 'Standard', 0.00, 1, NULL),
(9, '109', 'Standard', 0.00, 1, NULL),
(10, '110', 'Standard', 0.00, 1, NULL),
(11, '111', 'Standard', 0.00, 1, NULL),
(12, '112', 'Standard', 0.00, 1, NULL),
(13, '113', 'Standard', 0.00, 1, NULL),
(14, '114', 'Standard', 0.00, 1, NULL),
(15, '115', 'Standard', 0.00, 1, NULL),
(16, '116', 'Standard', 0.00, 1, NULL),
(17, '117', 'Standard', 0.00, 1, NULL),
(18, '118', 'Standard', 0.00, 1, NULL),
(19, '119', 'Standard', 0.00, 1, NULL),
(20, '120', 'Standard', 0.00, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'WATER_RATE_PER_PERSON', '100.00'),
(2, 'ELECTRICITY_RATE_PER_UNIT', '8.00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `plain_password` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'tenant',
  `room_price` decimal(10,2) DEFAULT '2500.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `password`, `plain_password`, `role`, `room_price`) VALUES
(1, 'admin', NULL, 'password1234', NULL, 'admin', 2500.00),
(7, 'admin2', NULL, '$2y$10$CLkbgXpumsrfuDROvC8PjOxiv1L6xfXXwKX24LCRkZr7TRGM9kYqC', NULL, 'admin', 2500.00),
(40, '101', 'แม้ว', '$2y$10$Gw9kXKKfbYPPc0t.htoliuPWJMKyStnjsYsinoomtTecOIQWFaaMq', 'fenbeq', 'tenant', 2500.00),
(41, '102', 'แมว', '$2y$10$nvaX0PMLsqDNg6nvUD2a8.0WOTiEKBuhN3gUJRBbMymQdbKB.OqLu', 'aihxzn', 'tenant', 2500.00),
(42, '103', 'ชินจัง', '$2y$10$D0vlZ1e7mGDPeuXEwhlSiuF0g1aWJdxcBJuarHIni5J6Pf83oTHrK', 'fmjufc', 'tenant', 2500.00),
(43, '104', 'ต๋องแต๋ง', '$2y$10$PdO7PD1iLW6Ar96E9RTnr.gfnEx4fyIPd9QeNkAsLampJboWO2Nzq', 'e5zr34', 'tenant', 2500.00),
(56, '105', 'เด', '$2y$10$JuLSd2Ltwicyk68cMi.BdOrRmMg5Cp0YjJ8Bl1TqUO63l1eAPh4Sq', 'sz6a0d', 'tenant', 2500.00),
(64, '106', 'เก', '$2y$10$DpQmQskgaYYVkbzTE0Ng4.Y4GwStWg7dwZLUxmjFWkfH6N.Uf8C6S', 'y2aarx', 'tenant', 2500.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billing_records`
--
ALTER TABLE `billing_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `billing_records`
--
ALTER TABLE `billing_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
