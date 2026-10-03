-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 03, 2026 at 05:56 AM
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
-- Database: `iot_cabinet_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `project_id`, `action_type`, `description`, `created_at`) VALUES
(1, 1, 'CREATE_PROJECT', 'สร้างตู้ใหม่ \'โครงการ ก่อสร้างระบบกระจายน้ำด้วยพลังงานแสงอาทิตย์อ่างเก็บน้ำโศกตาดี\' รหัส 7 ลงระบบ', '2026-08-28 20:41:42'),
(2, 7, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'โครงการเซนเซอร์เตือนภัยน้ำท่วม หาดใหญ่\'', '2026-09-14 08:17:40'),
(3, 8, 'CREATE_PROJECT', '[admin] สร้างตู้ใหม่ \'wwwa\' รหัส 1/25 ลงระบบ', '2026-10-02 23:09:50'),
(4, 8, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'wwwa\'', '2026-10-02 23:20:31'),
(5, 8, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'wwwa\'', '2026-10-02 23:20:56'),
(6, 7, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'โครงการเซนเซอร์เตือนภัยน้ำท่วม หาดใหญ่\'', '2026-10-03 01:16:32'),
(7, 7, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'โครงการเซนเซอร์เตือนภัยน้ำท่วม หาดใหญ่\'', '2026-10-03 01:16:50'),
(8, 5, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'โครงการเฝ้าระวังระดับน้ำและสึนามิ ป่าตอง\'', '2026-10-03 02:08:58'),
(9, 7, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'โครงการเซนเซอร์เตือนภัยน้ำท่วม หาดใหญ่\'', '2026-10-03 02:16:44'),
(10, 7, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'โครงการเซนเซอร์เตือนภัยน้ำท่วม หาดใหญ่\'', '2026-10-03 02:22:01'),
(11, 7, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'โครงการเซนเซอร์เตือนภัยน้ำท่วม หาดใหญ่\'', '2026-10-03 02:25:05'),
(12, 8, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'wwwa\'', '2026-10-03 03:23:05'),
(13, 8, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'wwwa\'', '2026-10-03 03:28:15'),
(14, 8, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'wwwa\'', '2026-10-03 03:50:02'),
(15, 8, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'wwwa\'', '2026-10-03 03:52:57'),
(16, 8, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'wwwa\'', '2026-10-03 03:53:21'),
(17, 8, 'UPDATE_PROJECT', 'อัปเดตข้อมูลตู้โครงการ \'โครงการอนุรักษ์ฟื้นฟูแหล่งน้ำหนองโดก\'', '2026-10-03 03:54:47');

-- --------------------------------------------------------

--
-- Table structure for table `equipments`
--

CREATE TABLE `equipments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `cabinet_type` enum('control_iot','dc_iot') NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `is_configured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `requires_config` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipments`
--

INSERT INTO `equipments` (`id`, `project_id`, `cabinet_type`, `equipment_name`, `serial_number`, `photo_path`, `is_configured`, `created_at`, `requires_config`) VALUES
(134, 8, 'control_iot', 'Industrial Grade PC (3ONEDATA/IPC6000-1136LR)', 'YBE0713002452', 'uploads/control_iot_6ac0764fd14712.24354964.jpg', 1, '2026-10-03 03:54:47', 1),
(135, 8, 'control_iot', 'Industrial 4G (3ONEDATA/IRT5300L-5T2D-2P12_36)', 'YBE0919001473', 'uploads/control_iot_6ac07b6a153266.83476151.jpg', 1, '2026-10-03 03:54:47', 1),
(136, 8, 'control_iot', 'Industrial Monitor จอแสดงผล ขนาด 10 นิ้ว (PELCO/DS-IND-LCD10)', 'GD322606090311G1045026', 'uploads/control_iot_6ac07b6a15d791.07993043.jpg', 0, '2026-10-03 03:54:47', 0),
(137, 8, 'control_iot', '3MP Fixed Micro Bullet Imager for Sarix Modular Camera (PELCO/IDL302-FXI)', '-', NULL, 1, '2026-10-03 03:54:47', 1),
(138, 8, 'control_iot', 'Sarix Modular Camera 2 Port Processor Unit (PELCO/IDL0-PU2)', '382604203853', 'uploads/control_iot_6ac07b6a16df36.99021390.jpg', 0, '2026-10-03 03:54:47', 0),
(139, 8, 'control_iot', 'เครื่องวัดอัตราการไหลน้ำเข้า แบบอัลตราโซนิก (Portable Ultrasonic Flow Meter) พร้อมชุดประกอบสำหรับจัดเก็บ (IUFM-701)', '260601-Fn-21111014', 'uploads/control_iot_6ac07b6a177218.05186476.jpg', 1, '2026-10-03 03:54:47', 1),
(140, 8, 'control_iot', 'เครื่องวัดอัตราการไหลน้ำออก แบบอัลตราโซนิก (Portable Ultrasonic Flow Meter) พร้อมชุดประกอบสำหรับจัดเก็บ (IUFM-701)', '260601-Fn-21111001', 'uploads/control_iot_6ac07b6a181ee6.39509546.jpg', 1, '2026-10-03 03:54:47', 1),
(141, 8, 'control_iot', 'ทรานสมิตเตอร์ pH (pH Transmitter) (DIGICON/PPH-601-10)', 'APH06C26A068', 'uploads/control_iot_6ac07b6a18d282.47322203.jpg', 1, '2026-10-03 03:54:47', 1),
(142, 8, 'dc_iot', 'IVH-RAPD-HFP-3.3Kw24V', '86HFP3300260410127650-146', 'uploads/dc_iot_6ac07c195a4345.20233219.jpg', 1, '2026-10-03 03:54:47', 1),
(143, 8, 'dc_iot', 'เครื่องวิเคราะห์กำลังไฟฟ้า DM-1 (Power Meter) (ELECNOVA/Sfere720C)', '2651094396', 'uploads/dc_iot_6ac07c195ab9f8.10226208.jpg', 1, '2026-10-03 03:54:47', 1);

-- --------------------------------------------------------

--
-- Table structure for table `equipment_templates`
--

CREATE TABLE `equipment_templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `cabinet_type` enum('control_iot','dc_iot') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_templates`
--

INSERT INTO `equipment_templates` (`id`, `template_name`, `cabinet_type`, `created_at`) VALUES
(4, 'โครงการปี 2568', 'control_iot', '2026-10-03 01:50:04'),
(5, 'โครงการปี 2568', 'dc_iot', '2026-10-03 02:03:12');

-- --------------------------------------------------------

--
-- Table structure for table `master_equipments`
--

CREATE TABLE `master_equipments` (
  `id` int(11) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_equipments`
--

INSERT INTO `master_equipments` (`id`, `equipment_name`, `created_at`) VALUES
(31, 'Industrial Grade PC (3ONEDATA/IPC6000-1136LR)', '2026-09-02 04:03:10'),
(32, 'Industrial 4G (3ONEDATA/IRT5300L-5T2D-2P12_36)', '2026-09-02 04:03:24'),
(33, 'Industrial Monitor จอแสดงผล ขนาด 10 นิ้ว (PELCO/DS-IND-LCD10)', '2026-09-02 04:03:39'),
(34, '3MP Fixed Micro Bullet Imager for Sarix Modular Camera (PELCO/IDL302-FXI)', '2026-09-02 04:03:52'),
(35, 'Sarix Modular Camera 2 Port Processor Unit (PELCO/IDL0-PU2)', '2026-09-02 04:04:07'),
(36, 'IVH-RAPD-HFP-3.3Kw24V\"3.3 KW (Full Power) Hybrid Offgrid InverterSingle Phase 220-240Vac / 24 Vdc 1 String\"', '2026-09-02 04:04:36'),
(37, 'IoT SIM', '2026-09-02 04:04:44'),
(38, 'เครื่องวิเคราะห์กำลังไฟฟ้า DM-1 (Power Meter) (ELECNOVA/Sfere720C)', '2026-09-02 04:04:50'),
(40, 'เครื่องวัดอัตราการไหล แบบอัลตราโซนิก (Portable Ultrasonic Flow Meter) พร้อมชุดประกอบสำหรับจัดเก็บ (IMARI/)', '2026-09-02 04:05:09'),
(41, 'ทรานสมิตเตอร์ pH (pH Transmitter) (DIGICON/PPH-601-10)', '2026-09-02 04:05:14'),
(42, 'เซ็นเซอร์วัดระดับ (Level sensors) (QDY70F-GSF)', '2026-09-02 04:05:23'),
(46, 'Power Supply 24V (HDR-30-24)', '2026-09-14 08:17:40'),
(86, 'เครื่องวัดอัตราการไหลน้ำเข้า แบบอัลตราโซนิก (Portable Ultrasonic Flow Meter) พร้อมชุดประกอบสำหรับจัดเก็บ (IUFM-701)', '2026-10-03 01:54:35'),
(87, 'เครื่องวัดอัตราการไหลน้ำออก แบบอัลตราโซนิก (Portable Ultrasonic Flow Meter) พร้อมชุดประกอบสำหรับจัดเก็บ (IUFM-701)', '2026-10-03 01:54:40'),
(122, 'เครื่องวิเคราะห์กำลังไฟฟ้า DM-2 (Power Meter) (ELECNOVA/Sfere720C)', '2026-10-03 02:02:45'),
(123, 'IVH-RAPD-HFP-3.3Kw24V', '2026-10-03 02:03:12'),
(143, '4G Router', '2026-10-03 02:08:58'),
(144, 'PLC Controller', '2026-10-03 02:08:58'),
(145, 'DC Converter', '2026-10-03 02:08:58'),
(146, 'Power Supply 24V', '2026-10-03 02:08:58'),
(153, 'เครื่องวิเคราะห์กำลังไฟฟ้า (Power Meter) (ELECNOVA/Sfere720C)', '2026-10-03 02:16:44');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_tags` varchar(255) DEFAULT NULL,
  `cabinet_id` varchar(100) NOT NULL,
  `region` varchar(50) DEFAULT NULL,
  `is_programmed` tinyint(1) DEFAULT 0,
  `programmer_name` varchar(100) DEFAULT NULL,
  `reverse_ssh_name` varchar(255) DEFAULT NULL,
  `gateway_name` varchar(255) DEFAULT NULL,
  `gateway_code` varchar(255) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `amphure` varchar(100) DEFAULT NULL,
  `tambon` varchar(100) DEFAULT NULL,
  `project_notes` text DEFAULT NULL,
  `has_antenna` tinyint(1) DEFAULT 0,
  `sim_status` enum('not_installed','installed') DEFAULT 'not_installed',
  `sim_number` varchar(50) DEFAULT NULL,
  `sim_serial` varchar(100) DEFAULT NULL,
  `sim_photo_path` varchar(255) DEFAULT NULL,
  `sd_status` enum('not_installed','installed') DEFAULT 'not_installed',
  `sd_capacity_gb` varchar(50) DEFAULT NULL,
  `camera_setup_status` enum('pending','completed') DEFAULT 'pending',
  `is_delivered` tinyint(1) DEFAULT 0,
  `delivery_photo_path` varchar(255) DEFAULT NULL,
  `delivery_timestamp` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `project_tags`, `cabinet_id`, `region`, `is_programmed`, `programmer_name`, `reverse_ssh_name`, `gateway_name`, `gateway_code`, `installation_date`, `latitude`, `longitude`, `address`, `province`, `amphure`, `tambon`, `project_notes`, `has_antenna`, `sim_status`, `sim_number`, `sim_serial`, `sim_photo_path`, `sd_status`, `sd_capacity_gb`, `camera_setup_status`, `is_delivered`, `delivery_photo_path`, `delivery_timestamp`, `created_by`, `created_at`) VALUES
(8, 'โครงการอนุรักษ์ฟื้นฟูแหล่งน้ำหนองโดก', 'ปี68', '1/25', 'ภาคอีสาน', 1, 'เวฟ', NULL, 'gw-thung-fai', 'GV4F5', '2026-05-20', 13.50344161, 101.00993156, '124', 'กาฬสินธุ์', 'ดอนจาน', 'นาจำปา', '', 1, 'installed', '0625594486', '2611846302289', 'uploads/sim_6ac075190bba44.31879311.jpg', 'installed', '256', 'completed', 0, NULL, NULL, 'admin', '2026-10-02 23:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `template_equipments`
--

CREATE TABLE `template_equipments` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `cabinet_type` varchar(50) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `requires_config` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `template_items`
--

CREATE TABLE `template_items` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `requires_config` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `template_items`
--

INSERT INTO `template_items` (`id`, `template_id`, `equipment_name`, `requires_config`) VALUES
(51, 5, 'IVH-RAPD-HFP-3.3Kw24V', 1),
(52, 5, 'เครื่องวิเคราะห์กำลังไฟฟ้า DM-1 (Power Meter) (ELECNOVA/Sfere720C)', 1),
(53, 5, 'เครื่องวิเคราะห์กำลังไฟฟ้า DM-2 (Power Meter) (ELECNOVA/Sfere720C)', 1),
(63, 4, 'Industrial Grade PC (3ONEDATA/IPC6000-1136LR)', 1),
(64, 4, 'Industrial 4G (3ONEDATA/IRT5300L-5T2D-2P12_36)', 1),
(65, 4, 'Industrial Monitor จอแสดงผล ขนาด 10 นิ้ว (PELCO/DS-IND-LCD10)', 0),
(66, 4, '3MP Fixed Micro Bullet Imager for Sarix Modular Camera (PELCO/IDL302-FXI)', 1),
(67, 4, 'Sarix Modular Camera 2 Port Processor Unit (PELCO/IDL0-PU2)', 0),
(68, 4, 'เครื่องวัดอัตราการไหลน้ำเข้า แบบอัลตราโซนิก (Portable Ultrasonic Flow Meter) พร้อมชุดประกอบสำหรับจัดเก็บ (IUFM-701)', 1),
(69, 4, 'เครื่องวัดอัตราการไหลน้ำออก แบบอัลตราโซนิก (Portable Ultrasonic Flow Meter) พร้อมชุดประกอบสำหรับจัดเก็บ (IUFM-701)', 1),
(70, 4, 'ทรานสมิตเตอร์ pH (pH Transmitter) (DIGICON/PPH-601-10)', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `email` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `full_name`, `phone`, `profile_image`, `created_at`) VALUES
(1, 'admin', '$2y$10$e.1s2G0C2K05Xq/8O84Bv.0A2zS0P4f8L1M5V9R0K2L0M2N0O0P0Q', 'admin', NULL, NULL, NULL, NULL, '2026-08-28 20:00:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `equipments`
--
ALTER TABLE `equipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `equipment_templates`
--
ALTER TABLE `equipment_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_equipments`
--
ALTER TABLE `master_equipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `equipment_name` (`equipment_name`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `template_equipments`
--
ALTER TABLE `template_equipments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `template_items`
--
ALTER TABLE `template_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `equipments`
--
ALTER TABLE `equipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `equipment_templates`
--
ALTER TABLE `equipment_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `master_equipments`
--
ALTER TABLE `master_equipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `template_equipments`
--
ALTER TABLE `template_equipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `template_items`
--
ALTER TABLE `template_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `equipments`
--
ALTER TABLE `equipments`
  ADD CONSTRAINT `equipments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template_items`
--
ALTER TABLE `template_items`
  ADD CONSTRAINT `template_items_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `equipment_templates` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
