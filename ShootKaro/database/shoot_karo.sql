-- Shoot Karo Database Schema
-- Database: shoot_karo_db
-- Designed for MySQL / MariaDB (XAMPP)

CREATE DATABASE IF NOT EXISTS `shoot_karo_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shoot_karo_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `creator_payouts`;
DROP TABLE IF EXISTS `commission_settings`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `booking_deliverables`;
DROP TABLE IF EXISTS `booking_services`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `creator_availability`;
DROP TABLE IF EXISTS `portfolio_items`;
DROP TABLE IF EXISTS `creator_service_areas`;
DROP TABLE IF EXISTS `creator_services`;
DROP TABLE IF EXISTS `creator_profiles`;
DROP TABLE IF EXISTS `areas`;
DROP TABLE IF EXISTS `cities`;
DROP TABLE IF EXISTS `site_settings`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role` ENUM('customer', 'creator', 'admin') NOT NULL DEFAULT 'customer',
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(25) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'pending', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Cities Table
CREATE TABLE `cities` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `state` VARCHAR(100) NOT NULL DEFAULT 'Maharashtra',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Areas Table
CREATE TABLE `areas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `city_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`) ON DELETE CASCADE,
  INDEX `idx_areas_city` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Creator Profiles Table
CREATE TABLE `creator_profiles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `studio_name` VARCHAR(150) DEFAULT NULL,
  `city_id` INT UNSIGNED DEFAULT NULL,
  `area_id` INT UNSIGNED DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `starting_price` DECIMAL(10,2) NOT NULL DEFAULT 3000.00,
  `experience_years` INT UNSIGNED DEFAULT 1,
  `portfolio_url` VARCHAR(255) DEFAULT NULL,
  `instagram_url` VARCHAR(255) DEFAULT NULL,
  `youtube_url` VARCHAR(255) DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE SET NULL,
  INDEX `idx_creator_approved` (`is_approved`),
  INDEX `idx_creator_available` (`is_available`),
  INDEX `idx_creator_price` (`starting_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Creator Services Table
CREATE TABLE `creator_services` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT UNSIGNED NOT NULL,
  `service_type` ENUM('camera', 'mobile', 'drone') NOT NULL,
  `service_title` VARCHAR(100) NOT NULL,
  `base_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`creator_id`) REFERENCES `creator_profiles`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_creator_service` (`creator_id`, `service_type`),
  INDEX `idx_service_type` (`service_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Creator Service Areas Mapping
CREATE TABLE `creator_service_areas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT UNSIGNED NOT NULL,
  `area_id` INT UNSIGNED NOT NULL,
  FOREIGN KEY (`creator_id`) REFERENCES `creator_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_creator_area` (`creator_id`, `area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Portfolio Items Table
CREATE TABLE `portfolio_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `media_type` ENUM('image', 'video_link') NOT NULL DEFAULT 'image',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `video_url` VARCHAR(255) DEFAULT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`creator_id`) REFERENCES `creator_profiles`(`id`) ON DELETE CASCADE,
  INDEX `idx_portfolio_creator` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Creator Availability Table
CREATE TABLE `creator_availability` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('available', 'booked', 'off') NOT NULL DEFAULT 'available',
  `notes` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`creator_id`) REFERENCES `creator_profiles`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_creator_date` (`creator_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bookings Table
CREATE TABLE `bookings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_reference` VARCHAR(40) NOT NULL UNIQUE,
  `customer_id` INT UNSIGNED NOT NULL,
  `creator_id` INT UNSIGNED NOT NULL,
  `city_id` INT UNSIGNED NOT NULL,
  `area_id` INT UNSIGNED NOT NULL,
  `customer_name` VARCHAR(120) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(25) NOT NULL,
  `shoot_venue` VARCHAR(255) NOT NULL,
  `shoot_date` DATE NOT NULL,
  `shoot_time` TIME NOT NULL,
  `occasion` VARCHAR(100) NOT NULL,
  `duration_hours` INT UNSIGNED NOT NULL DEFAULT 2,
  `deliverable_type` ENUM('photos', 'video', 'both') NOT NULL DEFAULT 'both',
  `photo_count_estimate` VARCHAR(50) DEFAULT '50-100 Photos',
  `video_duration_estimate` VARCHAR(50) DEFAULT '1-3 Minutes Highlights',
  `editing_requirements` TEXT DEFAULT NULL,
  `special_instructions` TEXT DEFAULT NULL,
  `starting_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `equipment_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `duration_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `advance_percentage` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
  `advance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'accepted', 'rejected', 'confirmed', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('pending', 'advance_paid', 'fully_paid', 'refunded') NOT NULL DEFAULT 'pending',
  `cancellation_reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`creator_id`) REFERENCES `creator_profiles`(`id`),
  FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`),
  FOREIGN KEY (`area_id`) REFERENCES `areas`(`id`),
  INDEX `idx_booking_status` (`status`),
  INDEX `idx_booking_date` (`shoot_date`),
  INDEX `idx_booking_ref` (`booking_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Booking Services Table
CREATE TABLE `booking_services` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `service_type` ENUM('camera', 'mobile', 'drone') NOT NULL,
  `service_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Booking Deliverables Table
CREATE TABLE `booking_deliverables` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `download_url` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Payments Table
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `payment_reference` VARCHAR(50) NOT NULL UNIQUE,
  `payment_type` ENUM('advance', 'balance', 'full') NOT NULL DEFAULT 'advance',
  `payment_method` ENUM('upi', 'card', 'cash_pay_later') NOT NULL DEFAULT 'upi',
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'successful', 'failed') NOT NULL DEFAULT 'pending',
  `transaction_data` TEXT DEFAULT NULL,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  INDEX `idx_payment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Reviews Table
CREATE TABLE `reviews` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL UNIQUE,
  `creator_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NOT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`creator_id`) REFERENCES `creator_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_reviews_creator` (`creator_id`),
  INDEX `idx_reviews_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Complaints Table
CREATE TABLE `complaints` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED DEFAULT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
  `resolution_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL,
  INDEX `idx_complaints_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Creator Payouts Table
CREATE TABLE `creator_payouts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `creator_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED NOT NULL UNIQUE,
  `total_booking_amount` DECIMAL(10,2) NOT NULL,
  `platform_commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `platform_commission_amount` DECIMAL(10,2) NOT NULL,
  `creator_net_amount` DECIMAL(10,2) NOT NULL,
  `payout_status` ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
  `payout_date` TIMESTAMP NULL DEFAULT NULL,
  `payout_reference` VARCHAR(60) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`creator_id`) REFERENCES `creator_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  INDEX `idx_payouts_status` (`payout_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Site Settings Table (Also serves as commission_settings)
CREATE TABLE `site_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(60) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Notifications Table
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- SEED DATA
-- ==========================================================

-- 1. Site Settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `description`) VALUES
('platform_commission_percent', '10.00', 'Default platform commission deducted from creator bookings'),
('default_advance_percent', '20.00', 'Mandatory advance booking payment percentage'),
('support_phone', '+91 98765 43210', 'Official customer support phone number'),
('support_email', 'support@shootkaro.local', 'Official customer support email'),
('site_name', 'SHOOT KARO', 'Brand Name of Platform');

-- 2. Cities
INSERT INTO `cities` (`id`, `name`, `state`, `is_active`) VALUES
(1, 'Pune', 'Maharashtra', 1);

-- 3. Areas in Pune
INSERT INTO `areas` (`id`, `city_id`, `name`, `postal_code`, `is_active`) VALUES
(1, 1, 'Baner', '411045', 1),
(2, 1, 'Kothrud', '411038', 1),
(3, 1, 'Hinjawadi', '411057', 1),
(4, 1, 'Wakad', '411057', 1),
(5, 1, 'Hadapsar', '411028', 1),
(6, 1, 'Shivajinagar', '411005', 1);

-- 4. Initial Users
-- Password for all seed users:
-- Admin: Admin@12345
-- Creators: Creator@12345
-- Customer: Customer@12345
INSERT INTO `users` (`id`, `role`, `name`, `email`, `phone`, `password_hash`, `status`) VALUES
(1, 'admin', 'System Administrator', 'admin@shootkaro.local', '9900000001', '$2y$10$rox4zqDInZHfsAlvcUE8nexQHiuw6M8z9M74S2sT0MfkN7OKXs9a6', 'active'),
(2, 'customer', 'Rohan Patil', 'customer@shootkaro.local', '9822011223', '$2y$10$rayPOJNAHoRZ35MfYxy9CuBLgiKcor9IUd7qcxGU22ldht4xN1Apy', 'active'),
(3, 'creator', 'Aditya Kulkarni', 'aditya@shootkaro.local', '9822055661', '$2y$10$oZblii5dBAV03Szt0nj0TemqfVro0K85aOFkWrycRrJqGru7pZAHq', 'active'),
(4, 'creator', 'Priya Nair', 'priya@shootkaro.local', '9822055662', '$2y$10$oZblii5dBAV03Szt0nj0TemqfVro0K85aOFkWrycRrJqGru7pZAHq', 'active'),
(5, 'creator', 'Vikram Joshi', 'vikram@shootkaro.local', '9822055663', '$2y$10$oZblii5dBAV03Szt0nj0TemqfVro0K85aOFkWrycRrJqGru7pZAHq', 'active'),
(6, 'creator', 'Sneha Deshmukh', 'sneha@shootkaro.local', '9822055664', '$2y$10$oZblii5dBAV03Szt0nj0TemqfVro0K85aOFkWrycRrJqGru7pZAHq', 'active'),
(7, 'creator', 'Amit Shinde', 'amit@shootkaro.local', '9822055665', '$2y$10$oZblii5dBAV03Szt0nj0TemqfVro0K85aOFkWrycRrJqGru7pZAHq', 'pending');

-- 5. Creator Profiles
INSERT INTO `creator_profiles` (`id`, `user_id`, `studio_name`, `city_id`, `area_id`, `bio`, `profile_image`, `starting_price`, `experience_years`, `portfolio_url`, `instagram_url`, `is_approved`, `is_available`) VALUES
(1, 3, 'Aditya Visuals & Films', 1, 1, 'Cinematic wedding and commercial storyteller with Sony Alpha 7 IV and DJI Air 3. Over 150+ high-profile shoots completed across Maharashtra.', 'creator_1.svg', 4500.00, 6, 'https://adityavisuals.example.com', 'https://instagram.com/adityavisuals', 1, 1),
(2, 4, 'AeroVision Drone Labs', 1, 3, 'Certified DGCA drone pilot and aerial videographer specializing in real estate, festivals, pre-weddings, and corporate events with 4K HDR delivery.', 'creator_2.svg', 5500.00, 4, 'https://aerovision.example.com', 'https://instagram.com/aerovisiondrone', 1, 1),
(3, 5, 'Reel Craft Productions', 1, 2, 'Next-gen viral reel creators and dynamic event videographers. Fast 24-hour turnaround for Instagram reels, YouTube shorts, and influencer shoots.', 'creator_3.svg', 3000.00, 3, 'https://reelcraft.example.com', 'https://instagram.com/reelcraft_pune', 1, 1),
(4, 6, 'Shivajinagar Lens Studio', 1, 6, 'Specialist in candid portraiture, maternity, birthdays, and product photography. High-end primes, softbox lighting, and studio-grade color grading.', 'creator_4.svg', 4000.00, 5, 'https://lensstudio.example.com', 'https://instagram.com/lensstudio_pune', 1, 1),
(5, 7, 'Wakad Frame Works', 1, 4, 'Upcoming candid photographer and drone enthusiast. Fresh aesthetic and modern framing.', 'creator_5.svg', 2500.00, 2, 'https://wakadframes.example.com', 'https://instagram.com/wakadframes', 0, 1);

-- 6. Creator Services
-- Creator 1 (Aditya Visuals): Camera, Mobile, Drone
INSERT INTO `creator_services` (`creator_id`, `service_type`, `service_title`, `base_price`, `description`, `is_active`) VALUES
(1, 'camera', 'Professional Full-Frame Camera Shoot', 4500.00, 'Sony A7 IV with prime lenses, 4K 60fps recording, on-camera lighting', 1),
(1, 'mobile', 'Mobile Reels & Social Highlights', 2500.00, 'iPhone 15 Pro Max 4K ProRes with DJI Osmo Mobile gimbal stabilization', 1),
(1, 'drone', '4K Aerial Drone Coverage', 3500.00, 'DJI Air 3 dual-camera system with high wind resistance and cinematic fly-bys', 1);

-- Creator 2 (AeroVision): Drone, Camera
INSERT INTO `creator_services` (`creator_id`, `service_type`, `service_title`, `base_price`, `description`, `is_active`) VALUES
(2, 'drone', 'Licensed 4K HDR Drone Cinematography', 5500.00, 'DGCA certified pilot, DJI Mavic 3 Pro Cine with master shots', 1),
(2, 'camera', 'Ground B-Roll & 4K Camera Coverage', 3500.00, 'Mirrorless ground support footage synced with aerial angles', 1);

-- Creator 3 (Reel Craft): Mobile, Camera
INSERT INTO `creator_services` (`creator_id`, `service_type`, `service_title`, `base_price`, `description`, `is_active`) VALUES
(3, 'mobile', 'Viral High-Energy Mobile Reels', 3000.00, 'Vertical 9:16 shoots with trending audio, transitions, and same-day cut', 1),
(3, 'camera', 'Mirrorless 4K Storytelling', 4000.00, 'Lumix S5 II cinematic profiles for events and interview shoots', 1);

-- Creator 4 (Shivajinagar Lens): Camera
INSERT INTO `creator_services` (`creator_id`, `service_type`, `service_title`, `base_price`, `description`, `is_active`) VALUES
(4, 'camera', 'Artisan Portrait & Candid Camera Session', 4000.00, 'Canon EOS R6 Mark II with 85mm f/1.2 L lens for creamy bokeh', 1);

-- Creator 5 (Amit Shinde - Pending): Mobile, Drone
INSERT INTO `creator_services` (`creator_id`, `service_type`, `service_title`, `base_price`, `description`, `is_active`) VALUES
(5, 'mobile', 'Mobile Social Media Coverage', 2500.00, 'Fast social media coverage for small events', 1),
(5, 'drone', 'Mini Drone Aerial Shots', 3000.00, 'DJI Mini 4 Pro scenic fly-bys', 1);

-- 7. Creator Service Areas
INSERT INTO `creator_service_areas` (`creator_id`, `area_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), -- Aditya serves Baner, Kothrud, Hinjawadi, Wakad
(2, 3), (2, 1), (2, 4),         -- AeroVision serves Hinjawadi, Baner, Wakad
(3, 2), (3, 1), (3, 6),         -- Reel Craft serves Kothrud, Baner, Shivajinagar
(4, 6), (4, 2), (4, 5),         -- Shivajinagar Lens serves Shivajinagar, Kothrud, Hadapsar
(5, 4), (5, 1);                 -- Amit serves Wakad, Baner

-- 8. Portfolio Items
INSERT INTO `portfolio_items` (`creator_id`, `title`, `media_type`, `file_path`, `video_url`, `caption`, `sort_order`) VALUES
(1, 'Baner Pre-Wedding Twilight', 'image', 'portfolio_1.svg', NULL, 'Golden hour pre-wedding shoot captured with 85mm f/1.4', 1),
(1, 'Balewadi Corporate Conclave', 'image', 'portfolio_2.svg', NULL, 'Keynote conference photography and speaker highlights', 2),
(1, 'Hinjawadi Tech Fest Aerial Reel', 'video_link', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Cinematic drone flyover over corporate tech campus', 3),
(2, 'Western Ghats Scenic Aerials', 'image', 'portfolio_3.svg', NULL, 'Sunrise mountain range capture from 120m altitude', 1),
(2, 'Pune Metro Infrastructure Survey', 'image', 'portfolio_4.svg', NULL, 'High precision 48MP aerial orthomosaic sample', 2),
(3, 'FC Road Street Fashion Reel', 'image', 'portfolio_5.svg', NULL, 'Streetwear brand social campaign shot entirely on iPhone 15 Pro', 1),
(4, 'Classical Dance Recital Candid', 'image', 'portfolio_6.svg', NULL, 'Bharatanatyam stage performance with dual rim lighting', 1);

-- 9. Sample Bookings
-- Booking 1: Completed shoot with Aditya Visuals
INSERT INTO `bookings` (`id`, `booking_reference`, `customer_id`, `creator_id`, `city_id`, `area_id`, `customer_name`, `customer_email`, `customer_phone`, `shoot_venue`, `shoot_date`, `shoot_time`, `occasion`, `duration_hours`, `deliverable_type`, `photo_count_estimate`, `video_duration_estimate`, `editing_requirements`, `special_instructions`, `starting_price`, `equipment_charges`, `duration_charges`, `total_amount`, `advance_percentage`, `advance_amount`, `balance_amount`, `status`, `payment_status`, `created_at`) VALUES
(1, 'SK-BK-20261010-1001', 2, 1, 1, 1, 'Rohan Patil', 'customer@shootkaro.local', '9822011223', 'Oxford Golf Resort, Baner', '2026-10-10', '16:00:00', 'Pre-Wedding', 4, 'both', '80 Photos', '2-3 Min Highlight Video', 'Warm cinematic film look, subtle film grain', 'Include drone flyover during sunset at the 18th hole', 4500.00, 3500.00, 2000.00, 10000.00, 20.00, 2000.00, 8000.00, 'completed', 'fully_paid', '2026-10-05 10:00:00');

INSERT INTO `booking_services` (`booking_id`, `service_type`, `service_price`) VALUES
(1, 'camera', 4500.00),
(1, 'drone', 3500.00);

INSERT INTO `payments` (`booking_id`, `payment_reference`, `payment_type`, `payment_method`, `amount`, `status`, `transaction_data`, `paid_at`) VALUES
(1, 'PAY-MOCK-20261005-ADV', 'advance', 'upi', 2000.00, 'successful', '{"gateway":"mock_upi","vpa":"rohan@okhdfcbank","txn_id":"TXN9928172"}', '2026-10-05 10:05:00'),
(1, 'PAY-MOCK-20261010-BAL', 'balance', 'cash_pay_later', 8000.00, 'successful', '{"notes":"Cash paid directly on shoot completion"}', '2026-10-10 20:30:00');

INSERT INTO `booking_deliverables` (`booking_id`, `title`, `file_path`, `download_url`, `notes`, `uploaded_at`) VALUES
(1, 'Final Color Graded High-Res Photos (ZIP)', NULL, 'https://drive.google.com/drive/folders/sample_shoot_karo_1001', '85 High-resolution corrected JPG files', '2026-10-12 14:00:00'),
(1, 'Cinematic 4K Highlight Reel (MP4)', NULL, 'https://vimeo.com/sample_reel_1001', 'Master 4K 60fps deliverable with licensed audio', '2026-10-12 14:05:00');

INSERT INTO `reviews` (`booking_id`, `creator_id`, `customer_id`, `rating`, `comment`, `is_approved`, `created_at`) VALUES
(1, 1, 2, 5, 'Aditya and his team were phenomenal! The drone angles during sunset at Oxford Golf Resort were breathtaking, and the turnaround time was faster than expected. Highly recommended!', 1, '2026-10-13 11:20:00');

INSERT INTO `creator_payouts` (`creator_id`, `booking_id`, `total_booking_amount`, `platform_commission_rate`, `platform_commission_amount`, `creator_net_amount`, `payout_status`, `payout_date`, `payout_reference`, `notes`) VALUES
(1, 1, 10000.00, 10.00, 1000.00, 9000.00, 'paid', '2026-10-15 12:00:00', 'PAYOUT-NEFT-99120', 'Settled via direct bank transfer');

-- Booking 2: Confirmed shoot with Priya Nair (AeroVision)
INSERT INTO `bookings` (`id`, `booking_reference`, `customer_id`, `creator_id`, `city_id`, `area_id`, `customer_name`, `customer_email`, `customer_phone`, `shoot_venue`, `shoot_date`, `shoot_time`, `occasion`, `duration_hours`, `deliverable_type`, `photo_count_estimate`, `video_duration_estimate`, `editing_requirements`, `special_instructions`, `starting_price`, `equipment_charges`, `duration_charges`, `total_amount`, `advance_percentage`, `advance_amount`, `balance_amount`, `status`, `payment_status`, `created_at`) VALUES
(2, 'SK-BK-20261025-1002', 2, 2, 1, 3, 'Rohan Patil', 'customer@shootkaro.local', '9822011223', 'Tech Centre Phase 1, Hinjawadi', '2026-10-25', '10:00:00', 'Corporate Event', 3, 'video', 'N/A', '3-5 Min Corporate Video', 'Corporate brand colors and clean lower thirds', 'Security clearance for drone flight in Phase 1 already obtained', 5500.00, 0.00, 1500.00, 7000.00, 20.00, 1400.00, 5600.00, 'confirmed', 'advance_paid', '2026-10-14 09:30:00');

INSERT INTO `booking_services` (`booking_id`, `service_type`, `service_price`) VALUES
(2, 'drone', 5500.00);

INSERT INTO `payments` (`booking_id`, `payment_reference`, `payment_type`, `payment_method`, `amount`, `status`, `transaction_data`, `paid_at`) VALUES
(2, 'PAY-MOCK-20261014-ADV', 'advance', 'card', 1400.00, 'successful', '{"gateway":"mock_card","card_last4":"4242","txn_id":"TXN881239"}', '2026-10-14 09:35:00');

INSERT INTO `creator_payouts` (`creator_id`, `booking_id`, `total_booking_amount`, `platform_commission_rate`, `platform_commission_amount`, `creator_net_amount`, `payout_status`, `notes`) VALUES
(2, 2, 7000.00, 10.00, 700.00, 6300.00, 'pending', 'Awaiting shoot completion and balance settlement');

-- Booking 3: Pending review with Vikram Joshi (Reel Craft)
INSERT INTO `bookings` (`id`, `booking_reference`, `customer_id`, `creator_id`, `city_id`, `area_id`, `customer_name`, `customer_email`, `customer_phone`, `shoot_venue`, `shoot_date`, `shoot_time`, `occasion`, `duration_hours`, `deliverable_type`, `photo_count_estimate`, `video_duration_estimate`, `editing_requirements`, `special_instructions`, `starting_price`, `equipment_charges`, `duration_charges`, `total_amount`, `advance_percentage`, `advance_amount`, `balance_amount`, `status`, `payment_status`, `created_at`) VALUES
(3, 'SK-BK-20261101-1003', 2, 3, 1, 2, 'Rohan Patil', 'customer@shootkaro.local', '9822011223', 'City Pride Hub, Kothrud', '2026-11-01', '14:00:00', 'Reels', 2, 'video', 'N/A', '5x 30s Viral Reels', 'High energy fast cuts with sound effects', 'Focus on product unboxing and reaction cuts', 3000.00, 0.00, 0.00, 3000.00, 20.00, 600.00, 2400.00, 'pending', 'pending', '2026-10-16 15:45:00');

INSERT INTO `booking_services` (`booking_id`, `service_type`, `service_price`) VALUES
(3, 'mobile', 3000.00);

-- 10. Sample In-App Notification
INSERT INTO `notifications` (`user_id`, `title`, `message`, `link`, `is_read`) VALUES
(3, 'Booking Completed', 'Booking SK-BK-20261010-1001 was completed and review received.', 'dashboard/creator.php', 1),
(4, 'Advance Received', 'Customer paid advance for booking SK-BK-20261025-1002. Status: Confirmed.', 'dashboard/creator.php', 0),
(5, 'New Booking Request', 'New booking request SK-BK-20261101-1003 received for Kothrud on 01-Nov-2026.', 'dashboard/creator.php', 0);
