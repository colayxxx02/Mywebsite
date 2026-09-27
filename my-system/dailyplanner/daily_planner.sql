-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2026 at 03:37 AM
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
-- Database: `daily_planner`
--

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('personal','academic','chores','health','work') NOT NULL,
  `schedule_datetime` datetime NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'low',
  `status` enum('pending','in_progress','completed','canceled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `task_name`, `description`, `category`, `schedule_datetime`, `priority`, `status`, `created_at`, `notified`) VALUES
(5, 2, 'buy new socks', '', 'personal', '2026-09-24 21:22:00', 'low', 'completed', '2026-09-24 13:22:06', 1),
(6, 2, 'cut my hair', '', 'personal', '2026-09-24 17:00:00', 'medium', 'completed', '2026-09-24 13:22:57', 1),
(7, 2, 'watch aot', 'ep 45', 'personal', '2026-09-24 09:23:00', 'high', 'completed', '2026-09-24 13:24:22', 1),
(8, 2, 'pay my bill', 'wifi', 'personal', '2026-09-25 11:30:00', 'high', 'completed', '2026-09-24 13:26:29', 1),
(9, 2, 'read book', 'chapter 1', 'academic', '2026-09-25 13:30:00', 'low', 'canceled', '2026-09-24 13:27:48', 1),
(10, 2, 'Print', 'study reviewer', 'academic', '2026-09-25 16:30:00', 'medium', 'canceled', '2026-09-24 13:28:55', 1),
(11, 2, 'buy weekly groceries', '', 'chores', '2026-10-24 07:30:00', 'high', 'pending', '2026-09-24 13:30:07', 0),
(12, 2, 'check up', 'dental', 'health', '2026-10-24 09:30:00', 'high', 'pending', '2026-09-24 13:31:11', 0),
(13, 2, 'time to sleep', '', 'health', '2026-09-24 22:49:00', 'high', 'completed', '2026-09-24 14:48:49', 1),
(14, 2, 'prepare to school', '', 'academic', '2026-09-25 07:32:00', 'high', 'completed', '2026-09-24 23:30:24', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `avatar`, `created_at`) VALUES
(1, 'admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe18q/Wp4t5JvD9M4kU6a8Nl7c5x2xY standard_hash_example', 'Nica Polinar', 'default.png', '2026-09-22 07:37:14'),
(2, 'janedoe', '$2y$10$nmwP1M18mRZcwIVyFA0Q1OMd5k0WYPAejNpNBSeCVRKq9ANrGLaj2', 'Jane Doe', 'woman1', '2026-09-22 07:45:41'),
(3, 'nics', '$2y$10$BjEZJApPA7JZ0QatsbZ9GeCoCM8iecGbz4bkgLvyHR7ypkYYgsqD.', 'Nica Polinar', 'man1', '2026-09-24 13:12:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
