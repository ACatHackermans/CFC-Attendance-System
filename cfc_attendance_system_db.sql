-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2025 at 05:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cfc_attendance_system_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_log`
--

CREATE TABLE `attendance_log` (
  `log_id` int(11) NOT NULL,
  `student_number` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time NOT NULL,
  `status` enum('on_time','late') NOT NULL,
  `guardian_num` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_report`
--

CREATE TABLE `attendance_report` (
  `report_id` int(11) NOT NULL,
  `student_num` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `status_today` varchar(20) DEFAULT 'absent',
  `on_time` int(11) DEFAULT 0,
  `lates` int(11) DEFAULT 0,
  `absences` int(11) DEFAULT 0,
  `time_in` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_list`
--

CREATE TABLE `class_list` (
  `student_num` varchar(255) NOT NULL,
  `nfc_uid` varchar(32) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `birthday` date NOT NULL,
  `email` varchar(50) NOT NULL,
  `contact_num` varchar(10) NOT NULL,
  `guardian_name` varchar(255) NOT NULL,
  `guardian_num` varchar(10) NOT NULL,
  `date_of_enrollment` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_list`
--

INSERT INTO `class_list` (`student_num`, `nfc_uid`, `surname`, `first_name`, `birthday`, `email`, `contact_num`, `guardian_name`, `guardian_num`, `date_of_enrollment`) VALUES
('00231', 'C4C4E9F6', 'Dela Cruz', 'Juan', '2001-07-14', 'juandelacruz@gmail.com', '9612133520', 'Maria', '9612133520', '2025-01-18 06:46:39'),
('00274', '560FF703', 'Melgar', 'Felix Angelo', '2004-05-05', 'felixangelolmelgar@iskolarngbayan.pup.edu.ph', '9612135520', 'Felix', '9189072959', '2025-01-20 06:57:07'),
('12345', '1311F903', 'Bascon', 'James Cedric', '2004-06-08', 'jamescedricbascon@gmail.com', '9123407016', 'Cathy', '9612133520', '2025-01-18 06:10:43');

-- --------------------------------------------------------

--
-- Table structure for table `notification_queue`
--

CREATE TABLE `notification_queue` (
  `queue_id` int(11) NOT NULL,
  `student_num` varchar(255) NOT NULL,
  `guardian_phone` varchar(20) NOT NULL,
  `guardian_name` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_attempt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_of_signup` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `date_of_signup`) VALUES
(7, 'felix123', '$2y$10$ewjc3oxKGk4l6wDGks.k0eOYqbNX9ETr.ZVYxxaohxpBmEmEJ/O/i', '2025-01-18 06:20:45'),
(8, 'felixcfc', '$2y$10$7kpiASmwZHU7R9U.SEQlauH8BMY/m1xKRUx2AWaAhpWdg4T2d83CO', '2025-01-18 06:44:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_log`
--
ALTER TABLE `attendance_log`
  ADD PRIMARY KEY (`log_id`),
  ADD UNIQUE KEY `unique_daily_attendance` (`student_number`,`log_date`);

--
-- Indexes for table `attendance_report`
--
ALTER TABLE `attendance_report`
  ADD PRIMARY KEY (`report_id`),
  ADD UNIQUE KEY `unique_student` (`student_num`);

--
-- Indexes for table `class_list`
--
ALTER TABLE `class_list`
  ADD PRIMARY KEY (`student_num`),
  ADD UNIQUE KEY `nfc_uid` (`nfc_uid`),
  ADD KEY `nfc_uid_2` (`nfc_uid`);

--
-- Indexes for table `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD KEY `status_idx` (`status`),
  ADD KEY `student_num` (`student_num`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_log`
--
ALTER TABLE `attendance_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `attendance_report`
--
ALTER TABLE `attendance_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `notification_queue`
--
ALTER TABLE `notification_queue`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_report`
--
ALTER TABLE `attendance_report`
  ADD CONSTRAINT `attendance_report_ibfk_1` FOREIGN KEY (`student_num`) REFERENCES `class_list` (`student_num`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification_queue`
--
ALTER TABLE `notification_queue`
  ADD CONSTRAINT `notification_queue_ibfk_1` FOREIGN KEY (`student_num`) REFERENCES `class_list` (`student_num`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
