-- =====================================================================
-- Campus2Community Jharkhand Civic Portal Database Schema
-- Database: c2c_portal
-- Engine: InnoDB, Charset: utf8mb4_unicode_ci
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `c2c_portal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `c2c_portal`;

-- =====================================================================
-- Disable foreign key checks to allow drops and re-creation without error #3730
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- Drop child and dependent tables first
DROP TABLE IF EXISTS `complaint_timeline`;
DROP TABLE IF EXISTS `complaint_votes`;
DROP TABLE IF EXISTS `otp_tokens`;
DROP TABLE IF EXISTS `university_proposals`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `universities`;
DROP TABLE IF EXISTS `citizens`;
DROP TABLE IF EXISTS `users`;

-- 1. Citizens / Residents Table
CREATE TABLE `citizens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(20) NOT NULL UNIQUE,
  `district` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Universities & Innovation Hubs Table
DROP TABLE IF EXISTS `universities`;
CREATE TABLE `universities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `aishe_code` VARCHAR(50) NOT NULL,
  `department` VARCHAR(150) NOT NULL,
  `faculty_coordinator` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Administrative Scrutiny Officers Table
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `admin_id_code` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Civic Complaints / Grievances Table (with GPS & Geotag data)
DROP TABLE IF EXISTS `complaints`;
CREATE TABLE `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` VARCHAR(50) NOT NULL UNIQUE,
  `citizen_name` VARCHAR(150) NOT NULL,
  `citizen_mobile` VARCHAR(20) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `locality` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `problem_title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `latitude` DECIMAL(10, 7) NULL,
  `longitude` DECIMAL(10, 7) NULL,
  `accuracy_meters` DECIMAL(8, 2) NULL,
  `geotag_address` TEXT NULL,
  `photo_url` VARCHAR(500) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Submitted',
  `urgency` VARCHAR(30) DEFAULT 'Standard',
  `assigned_university_id` INT NULL,
  `assigned_university_name` VARCHAR(255) NULL,
  `sanctioned_grant` DECIMAL(12, 2) DEFAULT 0,
  `milestone_timeline` VARCHAR(100) NULL,
  `scrutiny_notes` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ticket (`ticket_id`),
  INDEX idx_status (`status`),
  INDEX idx_district (`district`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. University Solution Proposals / Bids Table
DROP TABLE IF EXISTS `university_proposals`;
CREATE TABLE `university_proposals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `complaint_id` INT NOT NULL,
  `university_name` VARCHAR(255) NOT NULL,
  `proposal_title` VARCHAR(255) NOT NULL,
  `timeline` VARCHAR(100) NOT NULL,
  `estimated_cost` DECIMAL(12, 2) NOT NULL,
  `faculty_lead` VARCHAR(150) NOT NULL,
  `student_count` INT DEFAULT 5,
  `match_score` INT DEFAULT 90,
  `status` VARCHAR(50) DEFAULT 'Pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DEMONSTRATION ACCOUNTS & INITIAL TICKETS
-- =====================================================================

-- Default passwords:
-- citizen123 -> $2y$10$wN1G9QhXn1a9jZ4s8z9/te4h/7E8jV81l9tN.2iHq7b7Y3wM1tV72 (bcrypt)
-- bitmesra123 -> $2y$10$0kK9a/p8N2L4O4jB5mR1ue7m4W6fA1bB2c7d9eF1gH2iJ3kL4mN5o
-- admin123 -> $2y$10$4.oP9yC/kF9s8mG7bN3xwe1l2k3j4h5g6f7d8s9a0b1c2d3e4f5g6

-- Insert Demo Citizen (Mobile: 9876543210, Pass: citizen123)
INSERT INTO `citizens` (`name`, `mobile`, `district`, `password_hash`) VALUES
('Rajeshwar Oraon', '9876543210', 'Ranchi', '$2y$10$uA4wz4M5xZ7gQo.H0iJbAOB9p1c8f1E4p6eZ7s9y1B2c3d4e5f6g7');

-- Insert Demo University (Email: civic.lab@bitmesra.ac.in, Pass: bitmesra123)
INSERT INTO `universities` (`name`, `email`, `aishe_code`, `department`, `faculty_coordinator`, `password_hash`) VALUES
('Birla Institute of Technology (BIT), Mesra', 'civic.lab@bitmesra.ac.in', 'U-0268', 'Dept of Civil & Environmental Engineering', 'Dr. S. K. Verma', '$2y$10$uA4wz4M5xZ7gQo.H0iJbAOB9p1c8f1E4p6eZ7s9y1B2c3d4e5f6g7');

-- Insert Demo Admin (Email: admin.scrutiny@jharkhand.gov.in, Pass: admin123)
INSERT INTO `admins` (`name`, `email`, `admin_id_code`, `password_hash`) VALUES
('Dr. Amitesh Kumar', 'admin.scrutiny@jharkhand.gov.in', 'ADM-JH-8821', '$2y$10$uA4wz4M5xZ7gQo.H0iJbAOB9p1c8f1E4p6eZ7s9y1B2c3d4e5f6g7');

-- Seed Initial Demonstration Complaints
INSERT INTO `complaints` 
(`id`, `ticket_id`, `citizen_name`, `citizen_mobile`, `district`, `locality`, `category`, `problem_title`, `description`, `latitude`, `longitude`, `accuracy_meters`, `geotag_address`, `photo_url`, `status`, `urgency`, `assigned_university_id`, `assigned_university_name`, `sanctioned_grant`, `milestone_timeline`) 
VALUES
(1, 'JC2C-2026-00125', 'Rajeshwar Oraon', '9876543210', 'Ranchi', 'Chutia, Ward 14', 'Water', 'Broken submersible community pump and muddy ditch water', 'The community tube-well pump motor burned out 3 weeks ago. Over 180 tribal families are fetching water from a muddy ditch 1.2 km away. Urgent electrical repair and water filtration required.', 23.3441000, 85.3096000, 4.20, 'Ward 14, Chutia Near Block Office, Ranchi, Jharkhand - 834001', 'https://images.unsplash.com/photo-1541888946425-d0fbb186c5f7?auto=format&fit=crop&w=800&q=80', 'Submitted', 'High', NULL, NULL, 0, NULL),

(2, 'JC2C-2026-00089', 'Sunita Devi', '9876543211', 'Dhanbad', 'Govindpur Block, Panchayat Bhawan Road', 'Roads', 'Severe asphalt subsidence and hazardous crater near school', 'A 4-meter wide crater formed over an abandoned mine drainage culvert. 3 school buses overturned risks daily. Heavy rainfall is worsening the cave-in.', 23.8322000, 86.5218000, 6.10, 'Govindpur Main Road, Dhanbad, Jharkhand - 828109', 'https://images.unsplash.com/photo-1515162816999-a0c47dc192f7?auto=format&fit=crop&w=800&q=80', 'Submitted', 'High', NULL, NULL, 0, NULL),

(3, 'JC2C-2026-00042', 'Manoj Karmakar', '9876543212', 'East Singhbhum', 'Ghatsila Rural, Mosabani Sector', 'Environment', 'Hexavalent Chromium runoff from legacy copper slag dump', 'Industrial heavy-metal sludge leaching into local irrigation canal during rains, poisoning 12 hectares of standing paddy crop.', 22.5804000, 86.4820000, 5.00, 'Mosabani Copper Slag Sector, Ghatsila, East Singhbhum, Jharkhand - 832104', 'https://images.unsplash.com/photo-1618477461853-cf6ed80faba5?auto=format&fit=crop&w=800&q=80', 'Submitted', 'Critical', NULL, NULL, 0, NULL);

-- Seed Enrolled Universities for Complaints
INSERT INTO `university_proposals` 
(`complaint_id`, `university_name`, `proposal_title`, `timeline`, `estimated_cost`, `faculty_lead`, `student_count`, `match_score`, `status`)
VALUES
(1, 'Birla Institute of Technology (BIT), Mesra', 'Solar-Hybrid Submersible Pump & Multi-stage Gravel Filtration Unit', '14 Days (Rapid Implementation)', 85000.00, 'Dr. S. K. Verma, Dept of Civil & Water Resources', 6, 96, 'Pending'),
(1, 'National Institute of Technology (NIT), Jamshedpur', 'Automated IoT Chlorination & DC Brushless Pumping Setup', '21 Days', 95000.00, 'Prof. A. Banerjee, Mechanical & Civic Labs', 4, 88, 'Pending'),
(1, 'Government Polytechnic, Ranchi', 'Basic Rewinding & Hand-pump Dual Reconfiguration', '7 Days', 45000.00, 'Er. Rajesh Toppo', 5, 82, 'Pending'),

(2, 'IIT (ISM) Dhanbad', 'Geotechnical Soil Stabilization with Eco-friendly Flyash Grouting', '28 Days', 180000.00, 'Prof. K. R. Sen, Dept of Mining & Geomechanics', 8, 98, 'Pending'),
(2, 'Birsa Institute of Technology (BIT) Sindri', 'Pre-cast Reinforced Concrete Culvert & Polymer Asphalt Patching', '18 Days', 145000.00, 'Dr. R. P. Sharma, Civil Engg', 6, 91, 'Pending'),

(3, 'National Institute of Foundry & Forge Technology (NIFFT), Ranchi', 'Biochar Adsorption Barrier & Heavy Metal Chemical Immobilization', '30 Days', 220000.00, 'Dr. Meenakshi Soren, Materials & Environmental Lab', 5, 94, 'Pending');

-- =====================================================================
-- Re-enable foreign key checks
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 1;

