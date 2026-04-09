-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 03, 2026 at 06:20 PM
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
-- Database: `hostel_assist`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password`, `created_at`) VALUES
(2, 'admin', 'admin@gmail.com', 'admin123', '2026-03-31 13:11:34');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `hod_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `hod_review` text DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `submission_date` date DEFAULT curdate(),
  `show_name` enum('yes','no') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`complaint_id`, `student_id`, `title`, `description`, `status`, `hod_status`, `hod_review`, `remark`, `photo`, `completion_date`, `submission_date`, `show_name`) VALUES
(1, 1, 'WiFi', 'wifi is not working.', 'In Progress', 'Pending', NULL, 'Will be fixed in next 2 days.', '', NULL, '2026-03-31', 'yes'),
(2, 1, 'Cleanliness', 'Floor is dirty from past 2 days.', 'Resolved', 'Pending', NULL, NULL, '1774960628_dirt-and-dust-on-wooden-floor-photo.jpg', '2026-03-31', '2026-03-31', 'no'),
(3, 2, 'Plumbing', 'Wash Basin tap is broken.', 'Assigned', 'Pending', NULL, 'Workers will come tomorrow to fix it.', '', NULL, '2026-03-31', 'yes'),
(4, 3, 'Food', 'Plates are not cleaned properly', 'Pending', 'Pending', NULL, NULL, '', NULL, '2026-03-31', 'yes'),
(5, 4, 'Electricity', 'Fan Capacitor not working.', 'Pending', 'Pending', NULL, NULL, '', NULL, '2026-03-31', 'yes'),
(6, 4, 'Other', 'Window glass broken.', 'Pending', 'Pending', NULL, NULL, '1774966933_broken window.jpg', NULL, '2026-03-31', 'no'),
(7, 5, 'WiFi', 'wi-fi is not working in room no.18.. ', 'Assigned', 'Pending', NULL, NULL, '', NULL, '2026-04-01', 'yes'),
(8, 1, 'Electricity', 'Fan capacitor not working.', 'Pending', 'Pending', NULL, NULL, '', NULL, '2026-04-01', 'yes'),
(9, 4, 'Plumbing', 'Fam capacitor', 'Resolved', 'Pending', NULL, NULL, '', '2026-04-01', '2026-04-01', 'no');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `staff_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`staff_id`, `email`, `mobile`, `department`, `name`, `password`, `photo`, `signature`, `role`) VALUES
(1, 'hodaiml@gmail.com', '9877665242', 'AIML', 'Sachin Latkar', 'aiml123', NULL, '1774959156_hod_aiml-removebg-preview.png', 'HOD'),
(2, 'warden@gmail.com', '9765171034', 'AIML', 'Tejaswini Shinge', 'warden123', NULL, '1774959847_Untitled_design-removebg-preview.png', 'Warden'),
(3, 'hodchem@gmail.com', '4632537283', 'CHEM', 'Amit Sawant', 'chem123', NULL, '1775020012_hod_aiml.jpeg', 'HOD');

-- --------------------------------------------------------

--
-- Table structure for table `gate_pass`
--

CREATE TABLE `gate_pass` (
  `gatepass_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `date_going` date DEFAULT NULL,
  `date_return` date DEFAULT NULL,
  `status` enum('Pending HOD','Pending Warden','Approved','Rejected') DEFAULT 'Pending HOD',
  `hod_approved` tinyint(1) NOT NULL DEFAULT 0,
  `warden_approved` tinyint(1) NOT NULL DEFAULT 0,
  `time_going` varchar(10) DEFAULT NULL,
  `time_return` varchar(10) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `issue_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gate_pass`
--

INSERT INTO `gate_pass` (`gatepass_id`, `student_id`, `location`, `reason`, `date_going`, `date_return`, `status`, `hod_approved`, `warden_approved`, `time_going`, `time_return`, `issue_date`, `issue_time`) VALUES
(1, 1, 'ratnagiri', 'family function', '2026-04-01', '2026-04-05', 'Pending HOD', 0, 0, '10:16', '15:16', '2026-04-01', '10:16:51'),
(2, 1, 'rajapur', 'other', '2026-04-03', '2026-04-05', 'Approved', 1, 1, '09:18', '10:21', '2026-04-02', '10:28:18'),
(3, 5, 'Roha', 'medical ', '2026-04-02', '2026-04-10', 'Approved', 0, 1, '10:00', '18:52', '2026-04-01', '00:24:23'),
(4, 4, 'ratnagiri', 'other ', '2026-04-03', '2026-04-07', 'Pending Warden', 0, 0, '12:33', '16:34', '2026-04-01', '00:34:06'),
(5, 3, 'lanja', 'for function ', '2026-04-01', '2026-04-06', 'Pending Warden', 0, 0, '03:35', '13:35', '2026-04-01', '00:36:02'),
(6, 3, 'lanja', 'for function ', '2026-04-01', '2026-04-06', 'Pending Warden', 0, 0, '03:35', '13:35', '2026-04-01', '00:36:03'),
(7, 6, 'Mahableshwar', 'festival', '2026-04-03', '2026-04-06', 'Approved', 0, 1, '08:47', '13:47', '2026-04-01', '00:48:02'),
(8, 4, 'Ratnagiri', 'Vacation', '2026-04-02', '2026-04-06', 'Pending HOD', 0, 0, '17:00', '10:00', '2026-04-01', '10:07:41'),
(9, 6, 'Khed', 'Hospital Visit', '2026-04-03', '2026-04-04', 'Rejected', 0, 0, '20:39', '12:41', '2026-04-01', '10:37:59'),
(10, 4, 'ratnagiri', 'vacation', '2026-04-03', '2026-04-04', 'Approved', 1, 1, '10:00', '13:00', '2026-04-01', '14:41:25');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `recipient_role` varchar(50) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `actor_role` varchar(50) DEFAULT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `target_url` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `recipient_role`, `recipient_id`, `actor_role`, `actor_id`, `title`, `message`, `entity_type`, `entity_id`, `target_url`, `is_read`, `created_at`) VALUES
(4, 'student', 1, 'warden', 2, 'Gatepass approved by Warden', 'Your gatepass GP#1 has been approved by the warden.', 'gatepass', 1, 'view_gatepass.php?id=1', 0, '2026-03-31 12:39:35'),
(5, 'student', 1, 'warden', 2, 'Complaint status updated', 'Complaint #2 status changed to Resolved.', 'complaint', 2, 'complaint_info.php?id=2', 0, '2026-03-31 12:40:24'),
(6, 'student', 1, 'warden', 2, 'Complaint status updated', 'Complaint #1 status changed to In Progress.', 'complaint', 1, 'complaint_info.php?id=1', 0, '2026-03-31 12:40:35'),
(7, 'student', 1, 'warden', 2, 'New remark on complaint', 'Warden added a remark on complaint #1: Will be fixed in next 2 days.', 'complaint', 1, 'complaint_info.php?id=1', 0, '2026-03-31 12:41:08'),
(13, 'student', 3, 'warden', 2, 'Gatepass rejected by Warden', 'Your gatepass GP#4 has been rejected by the warden.', 'gatepass', 4, 'view_gatepass.php?id=4', 0, '2026-03-31 13:09:14'),
(16, 'student', 2, 'warden', 2, 'New remark on complaint', 'Warden added a remark on complaint #3: Workers will come tomorrow to fix it.', 'complaint', 3, 'complaint_info.php?id=3', 0, '2026-03-31 14:18:19'),
(17, 'student', 2, 'warden', 2, 'Complaint status updated', 'Complaint #3 status changed to Pending.', 'complaint', 3, 'complaint_info.php?id=3', 0, '2026-03-31 14:18:22'),
(18, 'student', 2, 'warden', 2, 'Complaint status updated', 'Complaint #3 status changed to Assigned.', 'complaint', 3, 'complaint_info.php?id=3', 0, '2026-03-31 14:18:31'),
(20, 'student', 2, 'hod', 1, 'Gatepass approved by HOD', 'Your gatepass GP#2 has been approved by HOD and forwarded to the warden.', 'gatepass', 2, 'view_gatepass.php?id=2', 0, '2026-03-31 14:36:10'),
(22, 'student', 1, 'hod', 1, 'Gatepass approved by HOD', 'Your gatepass GP#7 has been approved by HOD and forwarded to the warden.', 'gatepass', 7, 'view_gatepass.php?id=7', 0, '2026-03-31 18:21:09'),
(24, 'student', 1, 'warden', 2, 'Gatepass approved by Warden', 'Your gatepass GP#7 has been approved by the warden.', 'gatepass', 7, 'view_gatepass.php?id=7', 0, '2026-03-31 18:27:19'),
(27, 'warden', 2, 'student', 1, 'New gatepass request', 'Swara Gurunath  Bhogate submitted gatepass GP#2 for warden approval.', 'gatepass', 2, 'view_gatepass.php?id=2', 1, '2026-03-31 18:48:36'),
(28, 'warden', 2, 'student', 5, 'New gatepass request', 'Sanika  Suresh  Rasal submitted gatepass GP#3 for warden approval.', 'gatepass', 3, 'view_gatepass.php?id=3', 0, '2026-03-31 18:54:24'),
(29, 'warden', 2, 'student', 5, 'New complaint submitted', 'Sanika  Suresh  Rasal submitted complaint #7 under WiFi.', 'complaint', 7, 'complaint_info.php?id=7', 0, '2026-03-31 18:56:01'),
(30, 'student', 5, 'warden', 2, 'Complaint status updated', 'Complaint #7 status changed to Assigned.', 'complaint', 7, 'complaint_info.php?id=7', 1, '2026-03-31 18:56:47'),
(31, 'student', 5, 'warden', 2, 'Gatepass approved by Warden', 'Your gatepass GP#3 has been approved by the warden.', 'gatepass', 3, 'view_gatepass.php?id=3', 0, '2026-03-31 18:57:31'),
(32, 'warden', 2, 'student', 4, 'New gatepass request', 'Liza Intikhab Hodekar submitted gatepass GP#4 for warden approval.', 'gatepass', 4, 'view_gatepass.php?id=4', 0, '2026-03-31 19:04:08'),
(33, 'student', 1, 'warden', 2, 'Gatepass rejected by Warden', 'Your gatepass GP#1 has been rejected by the warden.', 'gatepass', 1, 'view_gatepass.php?id=1', 0, '2026-03-31 19:04:58'),
(34, 'warden', 2, 'student', 3, 'New gatepass request', 'Vinanti  Vinod Raut submitted gatepass GP#5 for warden approval.', 'gatepass', 5, 'view_gatepass.php?id=5', 0, '2026-03-31 19:06:03'),
(35, 'warden', 2, 'student', 3, 'New gatepass request', 'Vinanti  Vinod Raut submitted gatepass GP#6 for warden approval.', 'gatepass', 6, 'view_gatepass.php?id=6', 0, '2026-03-31 19:06:04'),
(36, 'warden', 2, 'student', 6, 'New gatepass request', 'sonali chandrakant padale submitted gatepass GP#7 for warden approval.', 'gatepass', 7, 'view_gatepass.php?id=7', 0, '2026-03-31 19:18:02'),
(37, 'student', 1, 'hod', 1, 'Gatepass approved by HOD', 'Your gatepass GP#2 has been approved by HOD and forwarded to the warden.', 'gatepass', 2, 'view_gatepass.php?id=2', 0, '2026-03-31 19:21:48'),
(38, 'warden', 2, 'hod', 1, 'Gatepass forwarded by HOD', 'HOD approved gatepass GP#2 for Swara Gurunath  Bhogate.', 'gatepass', 2, 'view_gatepass.php?id=2', 0, '2026-03-31 19:21:48'),
(39, 'student', 1, 'warden', 2, 'Gatepass approved by Warden', 'Your gatepass GP#2 has been approved by the warden.', 'gatepass', 2, 'view_gatepass.php?id=2', 0, '2026-03-31 19:22:38'),
(40, 'hod', 1, 'student', 4, 'New gatepass request', 'Liza Intikhab Hodekar submitted gatepass GP#8 for HOD approval.', 'gatepass', 8, 'view_gatepass.php?id=8', 0, '2026-04-01 04:37:42'),
(41, 'warden', 2, 'student', 4, 'New gatepass request', 'Liza Intikhab Hodekar submitted gatepass GP#8. It is currently waiting for HOD approval.', 'gatepass', 8, 'view_gatepass.php?id=8', 0, '2026-04-01 04:37:42'),
(42, 'hod', 3, 'student', 6, 'New gatepass request', 'sonali chandrakant padale submitted gatepass GP#9 for HOD approval.', 'gatepass', 9, 'view_gatepass.php?id=9', 0, '2026-04-01 05:08:00'),
(43, 'warden', 2, 'student', 6, 'New gatepass request', 'sonali chandrakant padale submitted gatepass GP#9. It is currently waiting for HOD approval.', 'gatepass', 9, 'view_gatepass.php?id=9', 0, '2026-04-01 05:08:00'),
(44, 'student', 6, 'hod', 3, 'Gatepass rejected by HOD', 'Your gatepass GP#9 has been rejected by HOD.', 'gatepass', 9, 'view_gatepass.php?id=9', 0, '2026-04-01 05:09:17'),
(45, 'warden', 2, 'hod', 3, 'Gatepass rejected by HOD', 'HOD rejected gatepass GP#9 for sonali chandrakant padale.', 'gatepass', 9, 'view_gatepass.php?id=9', 0, '2026-04-01 05:09:17'),
(46, 'student', 5, 'warden', 2, 'Complaint status updated', 'Complaint #7 status changed to In Progress.', 'complaint', 7, 'complaint_info.php?id=7', 0, '2026-04-01 05:46:39'),
(47, 'student', 5, 'warden', 2, 'Complaint status updated', 'Complaint #7 status changed to Assigned.', 'complaint', 7, 'complaint_info.php?id=7', 0, '2026-04-01 05:46:51'),
(48, 'student', 6, 'warden', 2, 'Gatepass approved by Warden', 'Your gatepass GP#7 has been approved by the warden.', 'gatepass', 7, 'view_gatepass.php?id=7', 0, '2026-04-01 05:48:07'),
(49, 'warden', 2, 'student', 1, 'New complaint submitted', 'Swara Gurunath  Bhogate submitted complaint #8 under Electricity.', 'complaint', 8, 'complaint_info.php?id=8', 0, '2026-04-01 06:03:02'),
(50, 'hod', 1, 'student', 4, 'New gatepass request', 'Liza Intikhab Hodekar submitted gatepass GP#10 for HOD approval.', 'gatepass', 10, 'view_gatepass.php?id=10', 1, '2026-04-01 09:11:25'),
(51, 'warden', 2, 'student', 4, 'New gatepass request', 'Liza Intikhab Hodekar submitted gatepass GP#10. It is currently waiting for HOD approval.', 'gatepass', 10, 'view_gatepass.php?id=10', 0, '2026-04-01 09:11:25'),
(52, 'warden', 2, 'student', 4, 'New complaint submitted', 'Liza Intikhab Hodekar submitted complaint #9 under Plumbing.', 'complaint', 9, 'complaint_info.php?id=9', 0, '2026-04-01 09:12:16'),
(53, 'student', 4, 'hod', 1, 'Gatepass approved by HOD', 'Your gatepass GP#10 has been approved by HOD and forwarded to the warden.', 'gatepass', 10, 'view_gatepass.php?id=10', 0, '2026-04-01 09:13:01'),
(54, 'warden', 2, 'hod', 1, 'Gatepass forwarded by HOD', 'HOD approved gatepass GP#10 for Liza Intikhab Hodekar.', 'gatepass', 10, 'view_gatepass.php?id=10', 0, '2026-04-01 09:13:01'),
(55, 'student', 4, 'warden', 2, 'Gatepass approved by Warden', 'Your gatepass GP#10 has been approved by the warden.', 'gatepass', 10, 'view_gatepass.php?id=10', 0, '2026-04-01 09:14:42'),
(56, 'student', 4, 'warden', 2, 'Complaint status updated', 'Complaint #9 status changed to In Progress.', 'complaint', 9, 'complaint_info.php?id=9', 0, '2026-04-01 09:15:25'),
(57, 'student', 4, 'warden', 2, 'Complaint status updated', 'Complaint #9 status changed to Resolved.', 'complaint', 9, 'complaint_info.php?id=9', 0, '2026-04-01 09:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `parent_mobile1` varchar(15) NOT NULL,
  `parent_mobile2` varchar(15) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `email`, `mobile`, `parent_mobile1`, `parent_mobile2`, `department`, `year`, `semester`, `password`, `photo`, `name`, `room_number`) VALUES
(1, 'dse25109417@git-india.edu.in', '9272045071', '7666598265', '9767045983', 'AIML', 'Second Year', 4, 'Swara@14', NULL, 'Swara Gurunath  Bhogate', 'A14'),
(2, 'dse25131944@git-india.edu.in', '9987734199', '7447568555', '1223456785', 'AIML', 'Second Year', 4, 'Riya@123', NULL, 'Riya  Sachin Phaware', 'A14'),
(3, 'dse25112660@git-india.edu.in', '8767596226', '9689737318', '9309821772', 'AIML', 'Second Year', 4, 'Vinanti@12345', NULL, 'Vinanti  Vinod Raut', '57'),
(4, 'dse25100211@git-india.edu.in', '8208576452', '9766321652', '9860626883', 'AIML', 'Second Year', 4, 'Liza@1612', NULL, 'Liza Intikhab Hodekar', 'C52'),
(5, 'dse25149647@git-india.edu.in', '7768038836', '7499539783', '8010256955', 'AIML', 'Second Year', 4, 'Sanoo@23', NULL, 'Sanika  Suresh  Rasal', 'A18'),
(6, 'dse25119688@git-india.edu.in', '7447568674', '9405543420', '8896450321', 'CHEM', 'Second Year', 4, 'Sonali@123', NULL, 'sonali chandrakant padale', 'A11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `gate_pass`
--
ALTER TABLE `gate_pass`
  ADD PRIMARY KEY (`gatepass_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_recipient` (`recipient_role`,`recipient_id`,`is_read`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gate_pass`
--
ALTER TABLE `gate_pass`
  MODIFY `gatepass_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `gate_pass`
--
ALTER TABLE `gate_pass`
  ADD CONSTRAINT `gate_pass_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
