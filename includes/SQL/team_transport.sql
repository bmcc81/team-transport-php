-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2025 at 10:18 PM
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
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT curtime(),
  `users_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `username`, `comment_text`, `created_at`, `users_id`) VALUES
(1, 'Brandon', 'This is a brandon comment', '2025-09-15 18:38:09', NULL);

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
(3, 8, 'Best Buy', 'Melissa', 'Tom', 'McCarthy', 'tom@gmail.com', '16 Kelloway', 'Harrington', 'Quebec', 'J7f3t6', 'Canada', '514-999-7777', '514-999-7771', 'www.tom.com', '2025-09-22 00:36:27'),
(4, 8, 'Canadian Tire', 'Brandon', 'John', 'Doe', 'john@ct.com', '999 Lake Road', 'Ottawa', 'Ontario', 'y8ys4s', 'Canada', '416-888-9999', '416-888-9991', 'www.canadientire.ca', '2025-09-22 01:07:22');

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
(10, 'admin', '$2y$10$XY0c05j4qtNQbhgKrq02iOnu3.9.Sk1BCky/WhJmQvWJ76TVp38VG', 'bmcc81@gmail.com', '2025-09-18 16:00:22', 'admin');

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
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `users_id` (`users_id`);

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
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
