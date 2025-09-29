-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2025 at 05:57 PM
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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
