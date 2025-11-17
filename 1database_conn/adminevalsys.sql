-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 28, 2025 at 08:56 PM
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
-- Database: `adminevalsys`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_list`
--

CREATE TABLE `academic_list` (
  `id` int(11) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_list`
--

INSERT INTO `academic_list` (`id`, `semester`, `school_year`, `is_active`, `is_default`, `created_at`, `start_date`, `end_date`, `status`) VALUES
(3, '1', '2025-2026', 0, 1, '2025-08-25 17:25:22', '2025-08-26', '2026-01-20', 0);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `extra_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `target_id`, `extra_data`, `ip_address`, `user_agent`, `action`, `description`, `log_time`) VALUES
(1, 1, NULL, NULL, NULL, NULL, 'Update User', 'Updated user ID 1. New values - Name: owner, Email: owner@gmail.com, Role: Super Admin, Status: Active', '2025-07-26 09:54:13'),
(2, 1, NULL, NULL, NULL, NULL, 'Updated User', 'Name: \'owner\' → \'Owner\'', '2025-07-26 10:07:38'),
(3, 1, NULL, NULL, NULL, NULL, 'Login', 'User \'Owner\' (ID: 1) logged in.', '2025-08-15 16:08:47'),
(4, 1, NULL, NULL, NULL, NULL, 'Updated User', 'Status: \'Active\' → \'Inactive\'', '2025-08-15 16:53:39'),
(5, 1, NULL, NULL, NULL, NULL, 'Updated User', 'Status: \'Active\' → \'Pending\'', '2025-08-15 16:53:45'),
(6, 1, NULL, NULL, NULL, NULL, 'View Activity Logs', 'Edited by Owner (ID: 1) | Changes: No field changes recorded. | Notes: Viewed all activity logs page', '2025-08-15 17:45:48'),
(7, 1, NULL, NULL, NULL, NULL, 'View Activity Logs', 'Edited by Owner (ID: 1) | Changes: No field changes recorded. | Notes: Viewed all activity logs page', '2025-08-15 17:45:52'),
(8, 1, NULL, NULL, NULL, NULL, 'View Activity Logs', 'Edited by Owner (ID: 1) | Changes: No field changes recorded. | Notes: Viewed all activity logs page', '2025-08-15 17:45:53'),
(9, 1, NULL, NULL, NULL, NULL, 'View Activity Logs', 'Edited by Owner (ID: 1) | Changes: No field changes recorded. | Notes: Viewed all activity logs page', '2025-08-15 17:45:55'),
(10, 1, NULL, NULL, NULL, NULL, 'View Activity Logs', 'Edited by Owner (ID: 1) | Changes: No field changes recorded. | Notes: Viewed all activity logs page', '2025-08-15 17:46:14'),
(13, NULL, NULL, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Failed', 'Failed login attempt for email: owner@gmail.com (Incorrect password)', '2025-08-15 18:29:58'),
(14, NULL, NULL, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Failed', 'Failed login attempt for email: owner@gmail.com (Incorrect password)', '2025-08-15 18:30:10'),
(15, NULL, NULL, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Failed', 'Failed login attempt for email: owner@gmail.com (Incorrect password)', '2025-08-15 18:30:19'),
(16, 1, 1, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Success', 'User \'Owner\' (Email: owner@gmail.com) logged in successfully.', '2025-08-15 18:30:35'),
(17, 1, 14, '[\"Status: \'Pending\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', '', '2025-08-18 04:46:22'),
(18, 1, 13, '[\"Status: \'Inactive\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', '', '2025-08-18 04:46:30'),
(19, 1, 14, '[\"Status: \'Active\' → \'Inactive\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', '', '2025-08-18 04:46:44'),
(20, 1, 14, '[\"Status: \'Inactive\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', '', '2025-08-18 04:46:52'),
(21, 1, 14, '[\"Status: \'Active\' → \'Inactive\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', '', '2025-08-18 04:50:15'),
(22, 1, 14, '[\"Status: \'Inactive\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', '', '2025-08-18 04:50:33'),
(23, 1, 14, '[\"Status: \'Active\' → \'Inactive\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'Active\' → \'Inactive\'', '2025-08-18 14:32:42'),
(24, 1, 14, '[\"Status: \'Inactive\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'Inactive\' → \'Active\'', '2025-08-18 14:39:56'),
(25, 1, 15, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'CSV Upload User', 'Uploaded new user via CSV: John Doe (Administrator)', '2025-08-18 15:49:14'),
(26, 1, 16, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'CSV Upload User', 'Uploaded new user via CSV: Jane Smith (Faculty)', '2025-08-18 15:49:14'),
(27, 1, 17, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Create User', 'Created single user: Niel Patrick Callada (Faculty)', '2025-08-18 15:52:03'),
(28, 1, 17, '[\"Status: \'active\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'active\' → \'Active\'', '2025-08-18 15:53:15'),
(29, 1, 19, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Create User', 'Created single user: Joshua Balangue (Faculty)', '2025-08-18 15:58:49'),
(30, 1, 20, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Create User', 'Created single user: John Eudrei Daep (Faculty)', '2025-08-18 16:06:32'),
(31, 1, 21, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Create User', 'Created single user: Niel Patrick Callada (Faculty)', '2025-08-18 16:07:56'),
(32, 1, 22, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Create User', 'Created single user: Niel Patrick Callada (Faculty)', '2025-08-18 16:10:10'),
(33, 1, 22, '[\"Status: \'active\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'active\' → \'Active\'', '2025-08-18 16:13:51'),
(34, 1, 20, '[\"Status: \'active\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'active\' → \'Active\'', '2025-08-18 16:14:00'),
(35, 1, 19, '[\"Status: \'active\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'active\' → \'Active\'', '2025-08-18 16:14:15'),
(36, 1, 22, '[\"Role: \'Faculty\' → \'Administrator\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Faculty\' → \'Administrator\'', '2025-08-19 13:54:11'),
(37, 1, 22, '[\"Role: \'Administrator\' → \'Faculty\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Administrator\' → \'Faculty\'', '2025-08-19 13:55:34'),
(38, 1, 22, '[\"Role: \'Faculty\' → \'Administrator\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Faculty\' → \'Administrator\'', '2025-08-19 14:17:40'),
(39, 1, 22, '[\"Role: \'Administrator\' → \'Faculty\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Administrator\' → \'Faculty\'', '2025-08-19 14:26:54'),
(40, 1, 22, '[\"Role: \'Faculty\' → \'Administrator\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Faculty\' → \'Administrator\'', '2025-08-19 14:29:41'),
(41, 1, 22, '[\"Role: \'Administrator\' → \'Faculty\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Administrator\' → \'Faculty\'', '2025-08-19 14:33:06'),
(42, 1, 22, '[\"Role: \'Faculty\' → \'Administrator\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Faculty\' → \'Administrator\'', '2025-08-19 14:48:13'),
(43, 13, 13, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Success', 'User \'administrator\' (Email: admin@gmail.com) logged in successfully.', '2025-08-20 14:42:04'),
(44, 13, 13, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Success', 'User \'administrator\' (Email: admin@gmail.com) logged in successfully.', '2025-08-20 15:12:12'),
(45, 13, 13, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Success', 'User \'administrator\' (Email: admin@gmail.com) logged in successfully.', '2025-08-20 15:30:12'),
(46, 13, 13, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Success', 'User \'administrator\' (Email: admin@gmail.com) logged in successfully.', '2025-08-20 15:43:01'),
(47, NULL, NULL, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Failed', 'Failed login attempt for email: owner@gmail.com (Incorrect password)', '2025-08-20 16:23:19'),
(48, 1, 1, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Login Success', 'User \'Owner\' (Email: owner@gmail.com) logged in successfully.', '2025-08-20 16:23:37'),
(49, NULL, 22, '[\"Status: \'Active\' → \'Inactive\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'Active\' → \'Inactive\'', '2025-08-21 11:57:12'),
(50, NULL, 22, '[\"Status: \'Inactive\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'Inactive\' → \'Active\'', '2025-08-21 11:57:18'),
(51, NULL, 22, '[\"Role: \'Administrator\' → \'Faculty\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Administrator\' → \'Faculty\'', '2025-08-25 16:59:24'),
(52, NULL, 22, '[\"Role: \'Faculty\' → \'Super Admin\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Faculty\' → \'Super Admin\'', '2025-08-25 16:59:32'),
(53, 1, 23, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Create User', 'Created single user: test (Administrator)', '2025-08-25 17:01:11'),
(54, NULL, 23, '[\"Status: \'active\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'active\' → \'Active\'', '2025-08-25 19:59:09'),
(55, NULL, 22, '[\"Status: \'Active\' → \'Inactive\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'Active\' → \'Inactive\'', '2025-08-26 05:03:15'),
(56, NULL, 22, '[\"Status: \'Inactive\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Update Status', 'Status: \'Inactive\' → \'Active\'', '2025-08-26 05:03:33'),
(57, NULL, 20, '[\"Role: \'Faculty\' → \'Super Admin\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Faculty\' → \'Super Admin\'', '2025-08-26 05:05:05'),
(58, NULL, 20, '[\"Role: \'Super Admin\' → \'Administrator\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Role: \'Super Admin\' → \'Administrator\'', '2025-08-26 05:05:21'),
(59, NULL, 23, '[\"Full Name: \'test\' → \'test1\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Full Name: \'test\' → \'test1\'', '2025-08-26 05:23:27'),
(60, NULL, 23, '[\"Full Name: \'test1\' → \'test\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Full Name: \'test1\' → \'test\'', '2025-08-26 05:30:59'),
(61, NULL, 23, '[\"Full Name: \'test\' → \'test1\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Full Name: \'test\' → \'test1\'', '2025-08-26 05:35:30'),
(62, NULL, 23, '[\"Full Name: \'test1\' → \'test\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Full Name: \'test1\' → \'test\'', '2025-08-26 05:43:05'),
(63, NULL, 23, '[\"Full Name: \'test\' → \'test1\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'Updated User', 'Full Name: \'test\' → \'test1\'', '2025-08-26 06:12:20'),
(64, NULL, 23, '[\"Department changed to ID: 1\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Updated User', 'Department changed to ID: 1', '2025-09-22 09:29:46'),
(65, NULL, 23, '[\"Department changed to ID: 6\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Updated User', 'Department changed to ID: 6', '2025-09-23 01:30:21'),
(66, NULL, 23, '[\"Department changed to ID: 7\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Updated User', 'Department changed to ID: 7', '2025-09-23 01:30:56'),
(67, NULL, 23, '[\"Status: \'Active\' → \'Inactive\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Update Status', 'Status: \'Active\' → \'Inactive\'', '2025-09-23 01:50:08'),
(68, NULL, 23, '[\"Status: \'Inactive\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Update Status', 'Status: \'Inactive\' → \'Active\'', '2025-09-23 01:50:32'),
(69, NULL, 23, '[\"Department changed to ID: 1\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Updated User', 'Department changed to ID: 1', '2025-09-23 01:50:49'),
(70, NULL, 23, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Change Password', 'Password changed for user: test1 (test@gmail.com)', '2025-09-23 09:35:16'),
(71, 1, 24, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Create User', 'Created single user: 123 (Faculty)', '2025-09-27 04:45:53'),
(72, NULL, 24, '[\"Status: \'active\' → \'Active\'\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'Update Status', 'Status: \'active\' → \'Active\'', '2025-09-27 05:16:11');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`) VALUES
(4, 'College of Engineering'),
(3, 'College of Midwifery'),
(2, 'College of Nursing'),
(5, 'College of Psychology'),
(1, 'Institute of Mathematics and Computer Science (IMACS)'),
(7, 'Test 2'),
(6, 'Test1');

-- --------------------------------------------------------

--
-- Table structure for table `edit_requests`
--

CREATE TABLE `edit_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requested_data` text DEFAULT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `request_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_assignments`
--

CREATE TABLE `evaluation_assignments` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `evaluatee_id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `academic_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_assignments`
--

INSERT INTO `evaluation_assignments` (`id`, `department_id`, `evaluator_id`, `evaluatee_id`, `form_id`, `academic_id`, `assigned_at`, `status`) VALUES
(1, 1, 24, 13, 9, 3, '2025-09-28 15:48:22', 'completed'),
(2, 1, 24, 15, 9, 3, '2025-09-28 15:48:22', 'pending'),
(3, 1, 24, 20, 9, 3, '2025-09-28 15:48:22', 'pending'),
(4, 1, 24, 23, 9, 3, '2025-09-28 15:48:22', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_categories`
--

CREATE TABLE `evaluation_categories` (
  `id` int(11) NOT NULL,
  `form_id` int(11) DEFAULT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_percentage` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_categories`
--

INSERT INTO `evaluation_categories` (`id`, `form_id`, `category_name`, `category_percentage`) VALUES
(15, 9, 'Strategic Functions', 45.00),
(16, 9, 'Core Functions', 45.00),
(17, 9, 'Support Functions', 10.00),
(18, 10, '', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_forms`
--

CREATE TABLE `evaluation_forms` (
  `id` int(11) NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `academic_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_forms`
--

INSERT INTO `evaluation_forms` (`id`, `form_name`, `version`, `is_active`, `academic_id`, `created_at`) VALUES
(9, 'Work Performance Review Form 2025', 1, 1, 3, '2025-08-25 18:55:21'),
(10, 'Work Performance Review Form 1', 1, 1, 3, '2025-09-28 06:06:43');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_form_details`
--

CREATE TABLE `evaluation_form_details` (
  `id` int(11) NOT NULL,
  `form_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `output_name` varchar(255) NOT NULL,
  `success_indicator` varchar(255) NOT NULL,
  `accomplishment` varchar(255) NOT NULL,
  `q_rating` decimal(3,2) DEFAULT NULL,
  `e_rating` decimal(3,2) DEFAULT NULL,
  `t_rating` decimal(3,2) DEFAULT NULL,
  `a_rating` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_form_details`
--

INSERT INTO `evaluation_form_details` (`id`, `form_id`, `category_id`, `output_name`, `success_indicator`, `accomplishment`, `q_rating`, `e_rating`, `t_rating`, `a_rating`) VALUES
(47, 9, 15, '', '', '', 0.00, 0.00, 0.00, 0.00),
(48, 9, 15, '', '', '', 0.00, 0.00, 0.00, 0.00),
(49, 9, 15, '', '', '', 0.00, 0.00, 0.00, 0.00),
(50, 9, 16, '', '', '', 0.00, 0.00, 0.00, 0.00),
(51, 9, 16, '', '', '', 0.00, 0.00, 0.00, 0.00),
(52, 9, 16, '', '', '', 0.00, 0.00, 0.00, 0.00),
(53, 9, 17, '', '', '', 0.00, 0.00, 0.00, 0.00),
(54, 9, 17, '', '', '', 0.00, 0.00, 0.00, 0.00),
(55, 9, 17, '', '', '', 0.00, 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_results`
--

CREATE TABLE `evaluation_results` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `form_id` int(11) NOT NULL,
  `academic_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `q` int(11) NOT NULL DEFAULT 0,
  `e` int(11) NOT NULL DEFAULT 0,
  `t` int(11) NOT NULL DEFAULT 0,
  `a` int(11) NOT NULL DEFAULT 0,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_results`
--

INSERT INTO `evaluation_results` (`id`, `assignment_id`, `form_id`, `academic_id`, `admin_id`, `evaluator_id`, `q`, `e`, `t`, `a`, `submitted_at`) VALUES
(1, 1, 9, 3, 13, 24, 1, 2, 2, 2, '2025-09-28 23:50:32'),
(2, 1, 9, 3, 13, 24, 1, 2, 5, 3, '2025-09-28 23:50:32'),
(3, 1, 9, 3, 13, 24, 4, 2, 1, 2, '2025-09-28 23:50:32'),
(4, 1, 9, 3, 13, 24, 2, 3, 1, 2, '2025-09-28 23:50:32'),
(5, 1, 9, 3, 13, 24, 2, 2, 2, 2, '2025-09-28 23:50:32'),
(6, 1, 9, 3, 13, 24, 4, 1, 1, 2, '2025-09-28 23:50:32'),
(7, 1, 9, 3, 13, 24, 3, 4, 3, 3, '2025-09-28 23:50:32'),
(8, 1, 9, 3, 13, 24, 2, 2, 1, 2, '2025-09-28 23:50:32'),
(9, 1, 9, 3, 13, 24, 4, 5, 1, 3, '2025-09-28 23:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `user_id`, `status`, `ip_address`, `user_agent`, `attempt_time`, `reason`) VALUES
(1, 'admin@gmail.com', 13, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 17:11:08', 'Login successful'),
(2, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-20 17:11:26', 'Login successful'),
(3, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 16:56:59', 'Login successful'),
(4, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 16:57:12', 'Login successful'),
(5, 'test@gmail.com', 23, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:02:45', 'Login successful'),
(6, 'admin@admin.com', NULL, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:03:22', 'Email not found'),
(7, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:03:25', 'Login successful'),
(8, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:03:26', 'Login successful'),
(9, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:03:27', 'Login successful'),
(10, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:05:16', 'Login successful'),
(11, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:06:58', 'Login successful'),
(12, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:11:20', 'Login successful'),
(13, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:12:08', 'Login successful'),
(14, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:29:38', 'Login successful'),
(15, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:34:47', 'Login successful'),
(16, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:47:47', 'Login successful'),
(17, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 17:48:46', 'Login successful'),
(18, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-25 18:36:28', 'Login successful'),
(19, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 04:58:30', 'Login successful'),
(20, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 05:30:50', 'Login successful'),
(21, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 05:42:56', 'Login successful'),
(22, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-10 10:46:41', 'Login successful'),
(23, 'owner@gmail.com', 1, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-13 01:16:29', 'Incorrect password'),
(24, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-13 01:16:39', 'Login successful'),
(25, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-15 05:18:51', 'Login successful'),
(26, 'owner@gmail.com', 1, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 09:28:14', 'Incorrect password'),
(27, 'owner@gmail.com', 1, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 09:28:29', 'Incorrect password'),
(28, 'owner@gmail.com', 1, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 09:28:38', 'Incorrect password'),
(29, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-22 09:28:53', 'Login successful'),
(30, 'test@gmail.com', 23, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-23 09:35:38', 'Login successful'),
(31, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-23 09:37:02', 'Login successful'),
(32, 'admin@gmail.com', 13, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:43:38', 'Incorrect password'),
(33, 'test@gmail.com', 23, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:43:43', 'Incorrect password'),
(34, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:43:46', 'Login successful'),
(35, 'test@gmail.com', 23, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:45:07', 'Incorrect password'),
(36, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:45:12', 'Login successful'),
(37, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:46:09', 'Login successful'),
(38, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 04:52:31', 'Login successful'),
(39, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:05:15', 'Login successful'),
(40, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:07:03', 'Login successful'),
(41, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:13:37', 'Login successful'),
(42, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:17:21', 'Login successful'),
(43, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:23:50', 'Login successful'),
(44, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:24:33', 'Login successful'),
(45, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:24:54', 'Login successful'),
(46, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:26:47', 'Login successful'),
(47, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:26:58', 'Login successful'),
(48, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:27:26', 'Login successful'),
(49, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:58:55', 'Login successful'),
(50, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 05:59:49', 'Login successful'),
(51, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:01:05', 'Login successful'),
(52, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:02:24', 'Login successful'),
(53, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:05:26', 'Login successful'),
(54, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:05:52', 'Login successful'),
(55, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:06:00', 'Login successful'),
(56, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:06:22', 'Login successful'),
(57, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:06:48', 'Login successful'),
(58, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:07:01', 'Login successful'),
(59, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:07:41', 'Login successful'),
(60, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:08:11', 'Login successful'),
(61, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:08:18', 'Login successful'),
(62, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:10:17', 'Login successful'),
(63, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:10:26', 'Login successful'),
(64, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 06:10:52', 'Login successful'),
(65, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 07:04:27', 'Login successful'),
(66, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 07:26:21', 'Login successful'),
(67, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 05:36:29', 'Login successful'),
(68, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 05:40:32', 'Login successful'),
(69, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 05:41:08', 'Login successful'),
(70, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 05:41:13', 'Login successful'),
(71, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 05:41:25', 'Login successful'),
(72, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 06:01:07', 'Login successful'),
(73, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 06:02:43', 'Login successful'),
(74, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 06:06:20', 'Login successful'),
(75, 'faculty@gmail.com', 14, 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 06:10:30', 'Incorrect password'),
(76, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 06:13:29', 'Login successful'),
(77, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 06:19:33', 'Login successful'),
(78, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 10:40:23', 'Login successful'),
(79, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 13:55:27', 'Login successful'),
(80, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 15:48:05', 'Login successful'),
(81, '123@gmail.com', 24, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 15:48:33', 'Login successful'),
(82, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 15:50:54', 'Login successful'),
(83, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 16:25:48', 'Login successful'),
(84, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 17:05:14', 'Login successful'),
(85, 'owner@gmail.com', 1, 'success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-28 17:36:09', 'Login successful');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `success` tinyint(1) DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullName` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `can_be_evaluated` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `account_locked` tinyint(1) DEFAULT 0,
  `can_edit_profile` tinyint(1) DEFAULT 0,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullName`, `email`, `password`, `role`, `status`, `can_be_evaluated`, `created_at`, `updated_at`, `last_login`, `failed_attempts`, `account_locked`, `can_edit_profile`, `archived`, `department_id`) VALUES
(1, 'Owner', 'owner@gmail.com', '$2y$10$/Jx7FctYZil6UPZec/sI4.d1cIQtmdRq.HrS/QOh88hz0Hh6H9/cu', 'Super Admin', 'Active', 1, '2025-07-23 12:12:19', '2025-09-28 06:09:21', NULL, 0, 0, 0, 0, 3),
(13, 'administrator', 'admin@gmail.com', '$2y$10$gYDxzj4CIFbajok.ZefjcOu8MXnOzgjivvWN2w4ldBDJ/mF6KMLhi', 'Administrator', 'Active', 1, '2025-07-23 15:18:26', '2025-09-13 01:21:00', NULL, 0, 0, 1, 0, 1),
(14, 'faculty', 'faculty@gmail.com', '$2y$10$TmtOSlScYKZ4qs72sJIFeeDyj4FgLlsJ6jb6i.CZnSYCtfHuDcf/.', 'Faculty', 'Active', 1, '2025-07-23 15:18:26', '2025-09-13 01:21:29', NULL, 0, 0, 0, 0, 2),
(15, 'John Doe', 'johndoe@gmail.com', '$2y$10$i/lQtQHm2oSA/Ni/MzisjeoF2Sakk1NXhY/XpUZXLaTTcFoxNXRO6', 'Administrator', 'Active', 1, '2025-08-18 15:49:14', '2025-09-13 01:21:00', NULL, 0, 0, 0, 0, 1),
(16, 'Jane Smith', 'janesmith@gmail.com', '$2y$10$mrxxECF0lvw4fRuCTaTe6..x/HsYUH.6RDamPzNZkI0fbjmbo54TK', 'Faculty', 'Active', 1, '2025-08-18 15:49:14', '2025-09-28 06:09:25', NULL, 0, 0, 0, 0, 2),
(19, 'Joshua Balangue', 'jbalangue@gmail.com', '$2y$10$3nt.1bUPEUVvF1.q/q9q5OamFOHi4jpDJjiswvWcKvrzrg6hKN8mm', 'Faculty', 'Active', 1, '2025-08-18 15:58:49', '2025-09-28 06:09:28', NULL, 0, 0, 0, 0, 2),
(20, 'John Eudrei Daep', 'jemdaep@kld.edu.ph', '$2y$10$5THMibaifsKQ3XbGNSk2wuk5NWNRFamT7HbDVSqYJ/Yg55XRgb0Qe', 'Administrator', 'Active', 1, '2025-08-18 16:06:32', '2025-09-28 06:09:32', NULL, 0, 0, 0, 0, 1),
(22, 'Niel Patrick Callada', 'npcallada@gmail.com', '$2y$10$gT2OmL3eXFQ7uoIVkNrxceySwqmrN0EV332HPtoiUsVBDMYum9bla', 'Super Admin', 'Active', 1, '2025-08-18 16:10:10', '2025-09-28 06:09:35', NULL, 0, 0, 0, 0, 3),
(23, 'test1', 'test@gmail.com', '$2y$10$qEwvhFo4TzwuUVwvMY5ThuvY4ILqNPHn5qrGJSYiwJLT6a0Lg4NPK', 'Administrator', 'Active', 1, '2025-08-25 17:01:11', '2025-09-23 09:35:16', NULL, 0, 0, 0, 0, 1),
(24, '123', '123@gmail.com', '$2y$10$ic5afILoqHVIfKwLimgzPepFysTpYEadniVH88ErSfgs.u0uGVjhe', 'Faculty', 'Active', 1, '2025-09-27 04:45:53', '2025-09-27 05:16:50', NULL, 0, 0, 0, 0, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_list`
--
ALTER TABLE `academic_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `edit_requests`
--
ALTER TABLE `edit_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `evaluation_assignments`
--
ALTER TABLE `evaluation_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`department_id`,`evaluator_id`,`evaluatee_id`,`form_id`,`academic_id`);

--
-- Indexes for table `evaluation_categories`
--
ALTER TABLE `evaluation_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

--
-- Indexes for table `evaluation_forms`
--
ALTER TABLE `evaluation_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_evaluation_forms_academic` (`academic_id`);

--
-- Indexes for table `evaluation_form_details`
--
ALTER TABLE `evaluation_form_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `evaluation_results`
--
ALTER TABLE `evaluation_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`),
  ADD KEY `academic_id` (`academic_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `evaluator_id` (`evaluator_id`),
  ADD KEY `fk_assignment` (`assignment_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_department` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_list`
--
ALTER TABLE `academic_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `edit_requests`
--
ALTER TABLE `edit_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_assignments`
--
ALTER TABLE `evaluation_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `evaluation_categories`
--
ALTER TABLE `evaluation_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `evaluation_forms`
--
ALTER TABLE `evaluation_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `evaluation_form_details`
--
ALTER TABLE `evaluation_form_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `evaluation_results`
--
ALTER TABLE `evaluation_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `edit_requests`
--
ALTER TABLE `edit_requests`
  ADD CONSTRAINT `edit_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `edit_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `evaluation_categories`
--
ALTER TABLE `evaluation_categories`
  ADD CONSTRAINT `evaluation_categories_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `evaluation_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_forms`
--
ALTER TABLE `evaluation_forms`
  ADD CONSTRAINT `fk_evaluation_forms_academic` FOREIGN KEY (`academic_id`) REFERENCES `academic_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_form_details`
--
ALTER TABLE `evaluation_form_details`
  ADD CONSTRAINT `evaluation_form_details_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `evaluation_forms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluation_form_details_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `evaluation_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_results`
--
ALTER TABLE `evaluation_results`
  ADD CONSTRAINT `evaluation_results_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `evaluation_forms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluation_results_ibfk_2` FOREIGN KEY (`academic_id`) REFERENCES `academic_list` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluation_results_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluation_results_ibfk_4` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `evaluation_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
