-- =========================================================
-- IMS Database (Beginner Friendly)
-- =========================================================
--
-- What is this file?
-- - This file creates the database tables for the Inventory Management System
--   and inserts some sample data.
--
-- How to import (phpMyAdmin):
-- 1) Open http://localhost/phpmyadmin
-- 2) Click "Import"
-- 3) Choose this file: connection/database.sql
-- 4) Click "Go"
--
-- How to import (Terminal / MySQL):
-- 1) Open Terminal
-- 2) Run:
--    mysql -u root -p < /path/to/ims/connection/database.sql
--    (On XAMPP default, password is often empty, so just press Enter.)
--
-- If you get "database doesn't exist":
-- - This file includes CREATE DATABASE + USE statements below.
--
-- IMPORTANT:
-- - Importing will create tables and sample rows.
-- - If you already have data you care about, BACK IT UP first.
--
-- Optional: Reset (DANGER)
-- - If you want a fresh start, you can UNCOMMENT the DROP TABLE lines
--   below. This will permanently delete existing tables + data.
--
-- -- DROP TABLE IF EXISTS users;
-- -- DROP TABLE IF EXISTS refunds;
-- -- DROP TABLE IF EXISTS bill_items;
-- -- DROP TABLE IF EXISTS bills;
-- -- DROP TABLE IF EXISTS supply_items;
-- -- DROP TABLE IF EXISTS supply_orders;
-- -- DROP TABLE IF EXISTS products;
--
-- ---------------------------------------------------------
-- Create and select database
-- ---------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `ims`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `ims`;

-- ---------------------------------------------------------
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 16, 2025 at 04:23 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ims`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT 'Walk-in',
  `total_amount` decimal(10,2) NOT NULL,
  `bill_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `requested_by` varchar(50) NOT NULL,
  `requested_role` varchar(20) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` varchar(50) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Notes:
-- - The app will automatically seed default users (admin/staff)
--   the first time you open the login page, if this table is empty.
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_plain` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supply_items`
--

CREATE TABLE `supply_items` (
  `id` int(11) NOT NULL,
  `supply_order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `buying_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supply_orders`
--

CREATE TABLE `supply_orders` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bill_id` (`bill_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_username` (`username`);

--
-- Indexes for table `supply_items`
--
ALTER TABLE `supply_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supply_order_id` (`supply_order_id`);

--
-- Indexes for table `supply_orders`
--
ALTER TABLE `supply_orders`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supply_items`
--
ALTER TABLE `supply_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `supply_orders`
--
ALTER TABLE `supply_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `fk_refunds_bill_id` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_items`
--
ALTER TABLE `supply_items`
  ADD CONSTRAINT `supply_items_ibfk_1` FOREIGN KEY (`supply_order_id`) REFERENCES `supply_orders` (`id`) ON DELETE CASCADE;

-- ---------------------------------------------------------
-- Sample data (seed)
-- Notes:
-- - We intentionally do NOT insert into `users` here.
--   The app auto-seeds default users on first login if `users` is empty.
-- ---------------------------------------------------------

-- Products
INSERT INTO `products` (`id`, `name`, `sku`, `price`, `quantity`, `created_at`) VALUES
  (1, 'USB-C Cable 1m', 'USB-C-1M', 6.50, 120, '2025-12-01 08:00:00'),
  (2, 'Wireless Mouse', 'MOUSE-WL-01', 18.90, 45, '2025-12-01 08:00:00'),
  (3, 'Notebook A5', 'NOTE-A5-100', 2.20, 300, '2025-12-01 08:00:00'),
  (4, 'LED Bulb 9W', 'BULB-LED-9W', 3.75, 80, '2025-12-01 08:00:00');

-- Supply (purchases)
INSERT INTO `supply_orders` (`id`, `supplier_name`, `total_cost`, `order_date`) VALUES
  (1, 'TechSource Pvt Ltd', 519.95, '2025-12-01 10:15:00'),
  (2, 'OfficeMart', 220.00, '2025-12-05 14:30:00'),
  (3, 'BrightLite Distributors', 180.00, '2025-12-10 09:05:00'),
  (4, 'TechSource Pvt Ltd', 360.00, '2025-12-18 16:20:00');

INSERT INTO `supply_items` (`id`, `supply_order_id`, `product_id`, `product_name`, `quantity`, `buying_price`) VALUES
  (1, 1, 1, 'USB-C Cable 1m', 100, 3.20),
  (2, 1, 2, 'Wireless Mouse', 15, 13.33),
  (3, 2, 3, 'Notebook A5', 200, 1.10),
  (4, 3, 4, 'LED Bulb 9W', 50, 2.90),
  (5, 3, 1, 'USB-C Cable 1m', 10, 3.50),
  (6, 4, 2, 'Wireless Mouse', 30, 12.00);

-- Sales
INSERT INTO `bills` (`id`, `customer_name`, `total_amount`, `bill_date`) VALUES
  (1, 'Walk-in', 31.90, '2025-12-20 11:05:00'),
  (2, 'Nimal Perera', 22.00, '2025-12-20 13:40:00'),
  (3, 'Walk-in', 15.00, '2025-12-21 09:15:00'),
  (4, 'Samanthi Silva', 25.50, '2025-12-21 17:25:00'),
  (5, 'Walk-in', 14.00, '2025-12-22 10:50:00'),
  (6, 'KGP Retail', 94.50, '2025-12-23 15:10:00');

INSERT INTO `bill_items` (`id`, `bill_id`, `product_id`, `product_name`, `quantity`, `price`) VALUES
  (1, 1, 1, 'USB-C Cable 1m', 2, 6.50),
  (2, 1, 2, 'Wireless Mouse', 1, 18.90),
  (3, 2, 3, 'Notebook A5', 10, 2.20),
  (4, 3, 4, 'LED Bulb 9W', 4, 3.75),
  (5, 4, 2, 'Wireless Mouse', 1, 18.90),
  (6, 4, 3, 'Notebook A5', 3, 2.20),
  (7, 5, 1, 'USB-C Cable 1m', 1, 6.50),
  (8, 5, 4, 'LED Bulb 9W', 2, 3.75),
  (9, 6, 2, 'Wireless Mouse', 5, 18.90);

-- Refund requests
INSERT INTO `refunds` (
  `id`, `bill_id`, `refund_amount`, `reason`, `status`,
  `requested_by`, `requested_role`, `requested_at`,
  `reviewed_by`, `reviewed_at`, `review_note`
) VALUES
  (1, 4, 18.90, 'Mouse stopped working', 'approved', 'staff', 'staff', '2025-12-22 09:00:00', 'admin', '2025-12-22 10:15:00', 'Approved after inspection'),
  (2, 2, 2.20, 'Returned 1 notebook (unopened)', 'pending', 'staff', 'staff', '2025-12-23 10:00:00', NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
