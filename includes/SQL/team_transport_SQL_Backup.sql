-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 13, 2025 at 05:38 PM
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
-- Database: `team_transport`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `trip_id` int(10) UNSIGNED NOT NULL,
  `booking_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `trip_id`, `booking_date`, `status`) VALUES
(4, 3, 1, '2025-11-13 11:04:48', 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_company_name` varchar(100) NOT NULL,
  `customer_internal_handler_name` varchar(100) DEFAULT NULL,
  `customer_contact_first_name` varchar(100) DEFAULT NULL,
  `customer_contact_last_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_contact_address` varchar(255) DEFAULT NULL,
  `customer_contact_city` varchar(100) DEFAULT NULL,
  `customer_contact_state_or_province` varchar(100) DEFAULT NULL,
  `customer_contact_country` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_fax` varchar(50) DEFAULT NULL,
  `customer_website` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `customer_company_name`, `customer_internal_handler_name`, `customer_contact_first_name`, `customer_contact_last_name`, `customer_email`, `customer_contact_address`, `customer_contact_city`, `customer_contact_state_or_province`, `customer_contact_country`, `customer_phone`, `customer_fax`, `customer_website`, `created_at`) VALUES
(1, 1, 'Trans-Logix Freight', 'Admin', 'John', 'Smith', 'john.smith@translogix.ca', '1200 Boulevard René-Lévesque Ouest', 'Montreal', 'QC', 'Canada', '(514) 555-2199', NULL, 'https://www.translogix.ca', '2025-11-12 19:03:13'),
(2, 1, 'North Star Logistics', 'Admin', 'Sophie', 'Turner', 'sophie.turner@northstarlogistics.com', '45 Wellington St W', 'Toronto', 'ON', 'Canada', '(416) 555-6620', NULL, 'https://www.northstarlogistics.com', '2025-11-12 19:03:13'),
(3, 1, 'Express Cargo Solutions', 'Admin', 'Eric', 'Dubois', 'eric.dubois@expresscargo.ca', '245 Industrial Blvd', 'Laval', 'QC', 'Canada', '(450) 555-8933', NULL, 'https://www.expresscargo.ca', '2025-11-12 19:03:13'),
(4, 2, 'Maple Freight Lines', 'Driver1', 'Lucas', 'Reynolds', 'lucas.reynolds@maplefreight.ca', '9900 Autoroute 40 Ouest', 'Vaudreuil-Dorion', 'QC', 'Canada', '(514) 555-7790', NULL, 'https://www.maplefreight.ca', '2025-11-12 19:06:10');

-- --------------------------------------------------------

--
-- Table structure for table `customers_backup`
--

CREATE TABLE `customers_backup` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_activity_log`
--

CREATE TABLE `customer_activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `action` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `id` int(10) UNSIGNED NOT NULL,
  `trip_name` varchar(100) NOT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `trip_date` date NOT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`id`, `trip_name`, `origin`, `destination`, `trip_date`, `driver_name`, `vehicle_plate`, `created_at`) VALUES
(1, 'Montreal to Ottawa', 'Montreal', 'Ottawa', '2025-11-15', 'John Doe', 'ABC123', '2025-11-13 15:55:03'),
(2, 'Ottawa to Toronto', 'Ottawa', 'Toronto', '2025-11-16', 'Jane Smith', 'XYZ789', '2025-11-13 15:55:03'),
(3, 'Toronto to Montreal', 'Toronto', 'Montreal', '2025-11-17', 'Alex White', 'TRK245', '2025-11-13 15:55:03'),
(4, 'Montreal to Ottawa', 'Montreal', 'Ottawa', '2025-11-15', 'John Doe', 'ABC123', '2025-11-13 15:57:19'),
(5, 'Ottawa to Toronto', 'Ottawa', 'Toronto', '2025-11-16', 'Jane Smith', 'XYZ789', '2025-11-13 15:57:19'),
(6, 'Toronto to Montreal', 'Toronto', 'Montreal', '2025-11-17', 'Alex White', 'TRK245', '2025-11-13 15:57:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `pwd` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','dispatcher','driver','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `must_change_password` tinyint(1) DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `pwd`, `email`, `full_name`, `role`, `created_at`, `must_change_password`, `created_by`) VALUES
(1, 'admin', '$2y$10$NUdTxQDRB.0x15/PslE1Ou8FzzgPl4w0iOZPFL/H.KzeLW9NDRBv2', 'admin@teamtransport.ca', 'System Admin', 'admin', '2025-11-12 15:00:08', 1, NULL),
(2, 'driver1', '$2y$10$EXAMPLE_HASH', 'driver1@teamtransport.ca', 'John Doe', 'driver', '2025-11-12 15:00:08', 0, 1),
(3, 'Mikey123', '$2y$10$VZgeAyoyPL3N9CyBio7EAOgshnvtVPUhhtoVNIl/OeDk0k9NffffW', 'mikey123@gmail.com', NULL, 'admin', '2025-11-13 16:10:04', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(10) UNSIGNED NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `make` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `license_plate` varchar(20) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `status` enum('available','in_service','maintenance','retired') DEFAULT 'available',
  `assigned_driver_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `vehicle_number`, `make`, `model`, `year`, `license_plate`, `capacity`, `status`, `assigned_driver_id`, `created_at`) VALUES
(1, 'VH-001', 'Ford', 'Transit', '2022', 'ABC123', NULL, 'available', 2, '2025-11-12 15:00:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `trip_id` (`trip_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_Customers_Users` (`user_id`);

--
-- Indexes for table `customer_activity_log`
--
ALTER TABLE `customer_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `FK_Users_CreatedBy` (`created_by`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_number` (`vehicle_number`),
  ADD KEY `assigned_driver_id` (`assigned_driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_activity_log`
--
ALTER TABLE `customer_activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `FK_Customers_Users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `customer_activity_log`
--
ALTER TABLE `customer_activity_log`
  ADD CONSTRAINT `customer_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `customer_activity_log_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_Users_CreatedBy` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`assigned_driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
