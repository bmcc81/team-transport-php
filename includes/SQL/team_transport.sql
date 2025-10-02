-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2025 at 09:30 PM
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
(2, 8, 'Walmart', 'Brandon', 'Tim', 'Shoniker', 'tim@gmail.com', '123 street', 'Toronto', 'Ontario', 'h7g5a9', 'Canada', '514-555-9999', '555-999-9991', 'www.tim.com', '2025-09-22 01:44:08'),
(3, 8, 'Best Buy', 'Melissa', 'Tom', 'McCarthy', 'tom@gmail.com', '16 Kelloway', 'Harrington', 'Quebec', 'J7f3t6', 'Canada', '514-999-7777', '514-999-7771', 'www.tom.com', '2025-09-22 04:36:27'),
(4, 8, 'Canadian Tire', 'Brandon', 'John', 'Doe', 'john@ct.com', '999 Lake Road', 'Ottawa', 'Ontario', 'y8ys4s', 'Canada', '416-888-9999', '416-888-9991', 'www.canadientire.ca', '2025-09-22 05:07:22'),
(5, 11, 'Super C', 'Tom', 'Mike', 'Furlotte', 'mike@gmail.com', '46 Super-C Street', 'Vaudreuil', 'Quebec', 'h7h5at', 'Canada', '555-666-9999', '555-666-9999', 'www.supec.com', '2025-09-29 19:30:04'),
(6, 11, 'Tim Hortons', 'Tom', 'Bob', 'Horton', 'bob_horton@timhortons.ca', '44 Tims Street', 'Edmonton', 'Alberta', '4d59i8', 'Canada', '514-833-9991', '514-833-9992', 'www.timhortons.ca', '2025-09-29 19:58:33'),
(7, 13, 'McDonalds', 'Bob', 'Ronald', 'McDonald', 'ron@mcdonalds.com', '123 Big Mac Street', 'Burger Town', 'Quebec', 'j8h6f7', 'Canada', '555-666-9999', '555-666-9991', 'www.mcdonalds.com', '2025-10-01 12:48:34'),
(8, 13, 'Pharmaprix', 'Bob', 'JOhn', 'Doe', 'johndoe@gmail.com', '333 DrugsStore Blvd.', 'New York', 'New York', '99802', 'United States', '555-777-8888', '555-777-8888', 'www.pharmaprix.com', '2025-10-01 12:51:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `pwd` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT curtime(),
  `role` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `pwd`, `email`, `created_at`, `role`) VALUES
(7, 'testuser', '$2y$10$SQ1BvSmj0k/iaQmDR1CpUOJXF4bsXlfUfsbgmqj/6H66Cwh0S5WOy', 'some_email@gmail.com', '2025-09-16 12:01:46', 'user'),
(8, 'Brandon', '$2y$10$FiBy0DEl3IFhWHmwWpwVOe32DpwwZPs6SF2vi89shnu3awKDkFYrC', 'bmcc81@gmail.com', '2025-09-16 12:02:23', 'user'),
(9, 'Melissa', '$2y$10$plfozzDp.TmhBVTVgkxxJuBbfr44JhjDOt/Icyd3lVWX27R0/bN4u', 'melissa@gmail.com', '2025-09-16 12:02:55', 'user'),
(10, 'admin', '$2y$10$XY0c05j4qtNQbhgKrq02iOnu3.9.Sk1BCky/WhJmQvWJ76TVp38VG', 'bmcc81@gmail.com', '2025-09-18 16:00:22', 'admin'),
(11, 'Tom', '$2y$10$X89422LRfjCFYMHaXE4Nr.TIJto4NC60.WV2/HMX3.Jx/ZMvi9esS', 'tom@gmail.com', '2025-09-29 12:01:51', 'user'),
(13, 'Bob', '$2y$10$vHkFzQZoV6qbJRJ/9XEry.p.cIj13vgbjNJD1cQ7LDktmcpdDAIea', 'bob@gmail.com', '2025-09-30 14:16:27', 'user'),
(14, 'Charlie', '$2y$10$OSZUk/PkoWLUpy3ENSX64OcPFM.v8wb3rXD1hoOUMd.1ou.fVdFXa', 'charlie@gmail.com', '2025-09-30 15:36:56', 'user');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `reassign_customers_before_user_delete` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
    -- Reassign all customers of this user to admin (id=10)
    UPDATE customers
    SET user_id = 10
    WHERE user_id = OLD.id;
END
$$
DELIMITER ;

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
