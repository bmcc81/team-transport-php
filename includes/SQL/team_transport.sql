-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2025 at 02:37 AM
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
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_company_name` varchar(255) NOT NULL,
  `customer_internal_handler_name` varchar(255) NOT NULL,
  `customer_contact_first_name` varchar(100) NOT NULL,
  `customer_contact_last_name` varchar(100) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_contact_address` varchar(255) NOT NULL,
  `customer_contact_city` varchar(100) NOT NULL,
  `customer_contact_state_or_province` varchar(100) NOT NULL,
  `customer_contact_zip_or_postal_code` varchar(50) NOT NULL,
  `customer_contact_country` varchar(100) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_fax` varchar(50) DEFAULT NULL,
  `customer_website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `customer_company_name`, `customer_internal_handler_name`, `customer_contact_first_name`, `customer_contact_last_name`, `customer_email`, `customer_contact_address`, `customer_contact_city`, `customer_contact_state_or_province`, `customer_contact_zip_or_postal_code`, `customer_contact_country`, `customer_phone`, `customer_fax`, `customer_website`, `created_at`) VALUES
(2, 8, 'Walmart', 'Brandon', 'Tim', 'Shoniker', 'tim@gmail.com', '123 street', 'Toronto', 'Ontario', 'h7g5a9', 'Canada', '514-555-9999', '555-999-9991', 'www.tim.com', '2025-09-21 21:44:08'),
(3, 8, 'Best Buy', 'Melissa', 'Tom', 'McCarthy', 'tom@gmail.com', '16 Kelloway', 'Harrington', 'Quebec', 'J7f3t6', 'Canada', '514-999-7777', '514-999-7771', 'www.tom.com', '2025-09-22 00:36:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
