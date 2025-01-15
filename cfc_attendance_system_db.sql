-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2025 at 10:37 PM
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

--
-- Dumping data for table `attendance_log`
--

INSERT INTO `attendance_log` (`log_id`, `student_number`, `surname`, `name`, `log_date`, `time_in`, `status`, `guardian_num`) VALUES
(25, '00273', 'Melgar', 'Felix Angelo', '2025-01-09', '11:46:17', 'late', '9189072959'),
(26, '123', 'bugs', 'natnat', '2025-01-09', '16:08:26', 'late', '123456789'),
(27, '514423', 'melgar', 'felix', '2025-01-09', '16:12:45', 'late', '1233424365'),
(28, '1234', 'bascon', 'james', '2025-01-09', '16:13:03', 'late', '1234789'),
(29, '123', 'bugs', 'natnat', '2025-01-10', '04:16:39', '', '123456789'),
(31, '1234', 'bascon', 'james', '2025-01-12', '05:37:59', '', '9123407016');

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
('1234', '1311F903', 'bascon', 'james', '2010-02-05', 'jc@gmail.com', '123456789', 'cecil', '9123407016', '2025-01-11 09:28:05');

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
(1, 'felix', '$2y$10$hLQx5A02cgqKcCRYrhE3aO9nWT79SYlv5cYZXmGN2WN5GQpDPU2Nu', '2025-01-08 05:17:15'),
(2, 'bugna', '$2y$10$jnUsIvnnhvlsBejZZc8pK.OOd2HJUFGA2cYg10v3zSf2nbe8s6e6S', '2025-01-09 08:02:51');

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `attendance_report`
--
ALTER TABLE `attendance_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_report`
--
ALTER TABLE `attendance_report`
  ADD CONSTRAINT `attendance_report_ibfk_1` FOREIGN KEY (`student_num`) REFERENCES `class_list` (`student_num`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
