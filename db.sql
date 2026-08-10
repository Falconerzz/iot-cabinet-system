CREATE DATABASE IF NOT EXISTS `iot_cabinet_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `iot_cabinet_db`;

-- ตารางผู้ใช้งาน
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- บัญชี admin : admin
INSERT INTO `users` (`username`, `password`, `role`) 
VALUES ('admin', '$2y$10$e.1s2G0C2K05Xq/8O84Bv.0A2zS0P4f8L1M5V9R0K2L0M2N0O0P0Q', 'admin')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- ตารางรายชื่ออุปกรณ์มาตรฐาน (Master Equipment List)
CREATE TABLE IF NOT EXISTS `master_equipments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `equipment_name` VARCHAR(255) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- เพิ่มชื่ออุปกรณ์เริ่มต้นในระบบ
INSERT INTO `master_equipments` (`equipment_name`) VALUES 
('IRT'), 
('irt_v2'),
('Power Supply 24V'), 
('Gateway'), 
('PLC Controller'), 
('4G Router'),
('Antenna 5dBi'),
('DC Converter')
ON DUPLICATE KEY UPDATE `equipment_name`=`equipment_name`;

-- ตารางโครงการ
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_name` VARCHAR(255) NOT NULL,
  `control_iot_id` VARCHAR(100) NOT NULL,
  `dc_iot_id` VARCHAR(100) NOT NULL,
  `reverse_ssh_name` VARCHAR(255) DEFAULT NULL,
  `gateway_name` VARCHAR(255) DEFAULT NULL,
  `gateway_code` VARCHAR(255) DEFAULT NULL,
  `installation_date` DATE DEFAULT NULL,
  `has_antenna` TINYINT(1) DEFAULT 0,
  `sim_status` ENUM('not_installed', 'installed') DEFAULT 'not_installed',
  `sim_number` VARCHAR(50) DEFAULT NULL,
  `sd_status` ENUM('not_installed', 'installed') DEFAULT 'not_installed',
  `sd_capacity_gb` VARCHAR(50) DEFAULT NULL,
  `camera_setup_status` ENUM('pending', 'completed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางอุปกรณ์ในตู้
CREATE TABLE IF NOT EXISTS `equipments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `cabinet_type` ENUM('control_iot', 'dc_iot') NOT NULL,
  `equipment_name` VARCHAR(255) NOT NULL,
  `serial_number` VARCHAR(255) NOT NULL,
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางแม่แบบอุปกรณ์
CREATE TABLE IF NOT EXISTS `equipment_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_name` VARCHAR(255) NOT NULL,
  `cabinet_type` ENUM('control_iot', 'dc_iot') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางรายการอุปกรณ์ในแม่แบบ
CREATE TABLE IF NOT EXISTS `template_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_id` INT NOT NULL,
  `equipment_name` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`template_id`) REFERENCES `equipment_templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางประวัติการทำงาน (Logs)
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT DEFAULT NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;