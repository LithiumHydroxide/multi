-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2026 at 09:47 PM
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
-- Database: `multi`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `term` tinyint(3) UNSIGNED NOT NULL CHECK (`term` in (1,2,3)),
  `academic_year` year(4) NOT NULL,
  `assessment_type` enum('formative','summative','kpsea','kjssea') NOT NULL,
  `title` varchar(150) NOT NULL,
  `max_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `date_conducted` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `school_id`, `class_id`, `subject_id`, `term`, `academic_year`, `assessment_type`, `title`, `max_marks`, `date_conducted`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 2, '2026', 'formative', 'Mathematics CAT 2', 50.00, '2026-05-10', '2026-05-05 12:59:14', '2026-05-05 12:59:14');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `marked_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `school_id`, `student_id`, `class_id`, `date`, `status`, `marked_by`, `created_at`) VALUES
(1, 1, 4, 3, '2026-05-26', 'present', 3, '2026-05-26 17:16:32'),
(2, 1, 1, 1, '2026-05-26', 'late', 3, '2026-05-26 17:53:35'),
(3, 1, 2, 1, '2026-05-26', 'late', 3, '2026-05-26 17:53:35');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(10) UNSIGNED DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `school_id`, `user_id`, `action`, `table_name`, `record_id`, `old_value`, `new_value`, `ip_address`, `created_at`) VALUES
(1, 2, 1, 'impersonate_start', 'schools', 2, NULL, '{\"impersonated_by\":1,\"at\":\"2026-05-21T13:16:47+02:00\"}', NULL, '2026-05-21 11:16:47'),
(2, 2, 1, 'status_change', 'schools', 2, NULL, '{\"new_status\":\"suspended\"}', NULL, '2026-05-22 12:19:24'),
(3, 2, 1, 'status_change', 'schools', 2, NULL, '{\"new_status\":\"active\"}', NULL, '2026-05-22 12:20:30'),
(4, 3, 1, 'status_change', 'schools', 3, NULL, '{\"new_status\":\"active\"}', NULL, '2026-05-22 12:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED DEFAULT NULL CHECK (`grade_level` between 0 and 12 or `grade_level` is null),
  `stream_code` varchar(20) DEFAULT NULL,
  `level` enum('kindergarten','preschool','1','2','3','4','5','6','7','8','9','10','11','12') DEFAULT NULL,
  `capacity` int(10) UNSIGNED DEFAULT 45,
  `class_teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `assistant_teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `prefect_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `school_id`, `name`, `grade_level`, `stream_code`, `level`, `capacity`, `class_teacher_id`, `assistant_teacher_id`, `prefect_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'Grade 7 West', 7, '7W', NULL, 40, 1, 2, 1, '2026-05-05 12:59:14', '2026-05-21 10:55:11'),
(2, 1, 'Grade 8 East', 8, '8E', NULL, 40, 1, 2, 2, '2026-05-05 12:59:14', '2026-05-26 18:46:12'),
(3, 1, 'Grade 9 North', 9, '9N', NULL, 40, 6, 2, 4, '2026-05-05 12:59:14', '2026-05-23 19:21:19'),
(4, 2, 'Grade 7 East', 7, 'EAST', NULL, 40, 3, 4, 112, '2026-05-08 15:09:45', '2026-05-21 12:25:41'),
(5, 2, 'Grade 1 East', 1, 'EAST', NULL, 40, NULL, NULL, NULL, '2026-05-08 15:15:05', '2026-05-08 15:15:05'),
(6, 2, 'Grade 2 East', 2, 'EAST', NULL, 40, NULL, NULL, NULL, '2026-05-08 15:15:17', '2026-05-08 15:15:17'),
(7, 2, 'Grade 3 East', 3, 'EAST', NULL, 40, NULL, NULL, NULL, '2026-05-08 15:16:04', '2026-05-08 15:16:04'),
(8, 2, 'Grade 4 East', 4, 'EAST', NULL, 40, NULL, NULL, NULL, '2026-05-08 15:16:24', '2026-05-08 15:16:24'),
(9, 2, 'Grade 5 East', 5, 'EAST', NULL, 40, NULL, NULL, NULL, '2026-05-08 15:16:38', '2026-05-08 15:16:38'),
(10, 2, 'Grade 6 East', 6, 'EAST', NULL, 40, NULL, NULL, NULL, '2026-05-08 15:16:49', '2026-05-08 15:16:49'),
(11, 2, 'Grade 8 East', 8, 'EAST', NULL, 40, NULL, NULL, NULL, '2026-05-08 15:17:11', '2026-05-08 15:17:11'),
(12, 2, 'Grade 9', 9, 'EAST', NULL, 40, NULL, NULL, NULL, '2026-05-08 15:17:21', '2026-05-08 15:17:21'),
(13, 1, 'Grade 7 East', 7, '7E', NULL, 45, NULL, NULL, NULL, '2026-05-21 15:54:37', '2026-05-21 15:54:37'),
(14, 3, 'Grade 7 East', 7, '7E', NULL, 40, NULL, NULL, NULL, '2026-05-22 12:30:35', '2026-05-22 12:30:35'),
(15, 3, 'Grade 8 East', 8, '8E', NULL, 40, NULL, NULL, NULL, '2026-05-22 12:30:35', '2026-05-22 12:30:35'),
(16, 3, 'Grade 9 East', 9, '9E', NULL, 40, NULL, NULL, NULL, '2026-05-22 12:30:35', '2026-05-22 12:30:35'),
(21, 3, 'Grade 7 East', 7, '7E', NULL, 45, NULL, NULL, NULL, '2026-05-22 13:19:12', '2026-05-22 13:19:12');

-- --------------------------------------------------------

--
-- Table structure for table `classes_new`
--

CREATE TABLE `classes_new` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED DEFAULT NULL COMMENT '0=PP1/Kindergarten, 1-6=Primary, 7-9=JSS, 10-12=SSS, NULL=Custom',
  `stream_code` varchar(20) DEFAULT NULL,
  `capacity` int(10) UNSIGNED DEFAULT 45,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes_new`
--

INSERT INTO `classes_new` (`id`, `school_id`, `name`, `grade_level`, `stream_code`, `capacity`, `created_at`, `updated_at`) VALUES
(1, 1, 'Grade 7 West', 7, '7W', 40, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(2, 1, 'Grade 8 East', 8, '8E', 40, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(3, 1, 'Grade 9 North', 9, '9N', 40, '2026-05-05 12:59:14', '2026-05-05 12:59:14');

-- --------------------------------------------------------

--
-- Table structure for table `class_subject_teacher`
--

CREATE TABLE `class_subject_teacher` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_subject_teacher`
--

INSERT INTO `class_subject_teacher` (`id`, `school_id`, `class_id`, `subject_id`, `teacher_id`) VALUES
(2, 1, 1, 2, 1),
(3, 2, 4, 31, 4),
(4, 1, 3, 34, 6);

-- --------------------------------------------------------

--
-- Table structure for table `grade_definitions`
--

CREATE TABLE `grade_definitions` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `level_id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `label` varchar(50) NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_definitions`
--

INSERT INTO `grade_definitions` (`id`, `level_id`, `code`, `label`, `sort_order`) VALUES
(1, 1, 'PP1', 'PP 1', 1),
(2, 1, 'PP2', 'PP 2', 2),
(3, 2, 'G1', 'Grade 1', 3),
(4, 2, 'G2', 'Grade 2', 4),
(5, 2, 'G3', 'Grade 3', 5),
(6, 2, 'G4', 'Grade 4', 6),
(7, 2, 'G5', 'Grade 5', 7),
(8, 2, 'G6', 'Grade 6', 8),
(9, 3, 'G7', 'Grade 7', 9),
(10, 3, 'G8', 'Grade 8', 10),
(11, 3, 'G9', 'Grade 9', 11),
(12, 4, 'G10', 'Grade 10', 12),
(13, 4, 'G11', 'Grade 11', 13),
(14, 4, 'G12', 'Grade 12', 14),
(15, 5, 'F1', 'Form 1', 15),
(16, 5, 'F2', 'Form 2', 16),
(17, 5, 'F3', 'Form 3', 17),
(18, 5, 'F4', 'Form 4', 18);

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `county` varchar(50) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `status` enum('active','suspended','trial') DEFAULT 'trial',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `onboarding_step` tinyint(1) UNSIGNED DEFAULT 0 COMMENT '0=not started, 1=school info, 2=academics, 3=staff, 4=students, 5=complete',
  `onboarding_completed` tinyint(1) DEFAULT 0 COMMENT '0=pending, 1=done'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `code`, `county`, `contact_email`, `contact_phone`, `status`, `created_at`, `updated_at`, `onboarding_step`, `onboarding_completed`) VALUES
(1, 'Greenfield CBC Academy', 'GFA-DEMO', 'Nairobi', 'admin@greenfield-demo.ke', '0713234567', 'active', '2026-05-05 12:59:13', '2026-05-21 15:56:02', 5, 1),
(2, 'Visionary School', NULL, 'NAIROBI', 'holt@gmail.com', NULL, 'active', '2026-05-05 16:51:19', '2026-05-22 12:20:30', 0, 0),
(3, 'Tabseera Internationa School', 'GFA-003', 'NAIROBI', 'fernandes@gmail.com', '0713234567', 'active', '2026-05-22 12:30:35', '2026-05-22 13:22:17', 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `school_levels`
--

CREATE TABLE `school_levels` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `label` varchar(50) NOT NULL,
  `description` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_levels`
--

INSERT INTO `school_levels` (`id`, `code`, `label`, `description`) VALUES
(1, 'PP', 'Pre-Primary', 'Kindergarten / PP1–PP2 (Ages 4–6)'),
(2, 'PRIMARY', 'Primary', 'Grade 1–6 (CBC Lower & Upper Primary)'),
(3, 'JSS', 'Junior Secondary', 'Grade 7–9 (CBC Junior Secondary)'),
(4, 'SSS', 'Senior Secondary', 'Grade 10–12 (CBC Senior Secondary)'),
(5, 'FORM', 'Secondary (8-4-4)', 'Form 1–4 (Legacy 8-4-4 curriculum)');

-- --------------------------------------------------------

--
-- Table structure for table `school_level_config`
--

CREATE TABLE `school_level_config` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `level_id` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_level_config`
--

INSERT INTO `school_level_config` (`id`, `school_id`, `level_id`) VALUES
(1, 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `school_settings`
--

CREATE TABLE `school_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `system_type` enum('CBC','8-4-4') DEFAULT 'CBC',
  `logo_path` varchar(255) DEFAULT NULL,
  `primary_color` varchar(7) DEFAULT '#1e40af',
  `secondary_color` varchar(7) DEFAULT '#f8fafc',
  `motto` varchar(200) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `dashboard_layout` enum('default','compact','analytics') NOT NULL DEFAULT 'default',
  `default_term` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `academic_year_start` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_settings`
--

INSERT INTO `school_settings` (`id`, `school_id`, `system_type`, `logo_path`, `primary_color`, `secondary_color`, `motto`, `address`, `contact_phone`, `dashboard_layout`, `default_term`, `academic_year_start`) VALUES
(1, 1, 'CBC', '/multi/storage/logos/school_1_logo.jpg', '#39db4c', '#e6e6eb', 'Education is the Key', 'Kibera, Nairobi County', '0713234567', 'analytics', 1, '2026-01-01'),
(7, 2, 'CBC', NULL, '#36ae1e', '#f8fafc', '', '', '', 'default', 1, NULL),
(11, 3, 'CBC', NULL, '#50ae1e', '#f8fafc', 'Education is the Key', 'Kibera, Nairobi', '0713234567', 'default', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `school_subscriptions`
--

CREATE TABLE `school_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `status` enum('active','expired','trial','suspended') DEFAULT 'active',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 0,
  `current_student_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_subscriptions`
--

INSERT INTO `school_subscriptions` (`id`, `school_id`, `plan_id`, `status`, `start_date`, `end_date`, `auto_renew`, `current_student_count`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'active', '2026-01-01', '2026-12-31', 1, 45, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(2, 2, 1, 'active', '2026-05-05', '2026-06-04', 0, 0, '2026-05-05 16:51:20', '2026-05-05 16:51:20'),
(3, 3, 3, 'active', '2026-05-22', '2027-05-22', 0, 0, '2026-05-22 12:30:35', '2026-05-22 12:30:35');

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `assessment_id` int(10) UNSIGNED NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `achievement_level` enum('EE1','EE2','ME1','ME2','AE1','AE2','BE1','BE2') NOT NULL,
  `remarks` text DEFAULT NULL,
  `graded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scores`
--

INSERT INTO `scores` (`id`, `school_id`, `student_id`, `assessment_id`, `marks_obtained`, `achievement_level`, `remarks`, `graded_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 50.00, 'EE1', NULL, 3, '2026-05-05 13:00:03', '2026-05-21 15:29:36'),
(2, 1, 2, 1, 45.00, 'EE1', NULL, 3, '2026-05-05 13:00:03', '2026-05-21 15:29:36');

-- --------------------------------------------------------

--
-- Table structure for table `staff_directory`
--

CREATE TABLE `staff_directory` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `job_role` varchar(100) NOT NULL COMMENT 'e.g. Driver, Head Cook, Electrician',
  `phone` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `license_no` varchar(50) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `contract_type` enum('permanent','contract','part-time') DEFAULT 'contract',
  `start_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `staff_type` enum('teaching','non_teaching') NOT NULL,
  `job_role` varchar(100) NOT NULL COMMENT 'e.g. Driver, Nurse, Cook, Electrician, Bursar',
  `tsc_number` varchar(50) DEFAULT NULL COMMENT 'Required only for teaching staff',
  `details_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Role-specific metadata (bus info, licenses, certs, etc.)' CHECK (json_valid(`details_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT INTO `staff_profiles` (`id`, `user_id`, `school_id`, `staff_type`, `job_role`, `tsc_number`, `details_json`, `created_at`) VALUES
(1, 3, 1, 'teaching', 'Teacher', 'TSC/12345/67', NULL, '2026-05-23 19:28:55'),
(2, 4, 1, 'teaching', 'Teacher', 'TSC/98765/43', NULL, '2026-05-23 19:28:55'),
(3, 6, 2, 'teaching', 'Teacher', 'TCH001', NULL, '2026-05-23 19:28:55'),
(4, 7, 2, 'teaching', 'Teacher', 'TCH002', NULL, '2026-05-23 19:28:55'),
(5, 8, 2, 'teaching', 'Teacher', 'TCH003', NULL, '2026-05-23 19:28:55'),
(6, 9, 1, 'teaching', 'Teacher', 'TSC 00123', NULL, '2026-05-23 19:28:55'),
(7, 11, 3, 'teaching', 'Teacher', 'Mathematics, Science and Swahili', NULL, '2026-05-23 19:28:55');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `status` enum('active','transferred','graduated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `school_id`, `name`, `admission_number`, `class_id`, `gender`, `dob`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Alice Wanjiku', 'ADM/2024/001', 1, 'Female', '2011-03-15', 'active', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(2, 1, 'Brian Omondi', 'ADM/2024/002', 1, 'Male', '2011-07-22', 'active', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(3, 1, 'Chloe Achieng', 'ADM/2025/003', 2, 'Female', '2010-11-05', 'active', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(4, 1, 'David Kimani', 'ADM/2025/004', 3, 'Male', '2009-01-30', 'active', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(5, 2, 'Akinyi Otieno', '1001E1', 5, 'Female', '2010-01-06', 'active', '2026-05-08 15:20:33', '2026-05-08 15:20:33'),
(86, 2, 'Akinyi Otieno', 'G1E01', 5, 'Female', NULL, 'active', '2026-05-08 15:58:40', '2026-05-08 15:58:40'),
(87, 2, 'Brian Mwangi', 'G1E02', 5, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(88, 2, 'Cynthia Wanjiku', 'G1E03', 5, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(89, 2, 'David Omondi', 'G1E04', 5, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(90, 2, 'Esther Muthoni', 'G1E05', 5, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(91, 2, 'Fredrick Kiprop', 'G2E01', 6, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(92, 2, 'Grace Adhiambo', 'G2E02', 6, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(93, 2, 'Hassan Ali', 'G2E03', 6, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(94, 2, 'Irene Chepkoech', 'G2E04', 6, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(95, 2, 'James Otieno', 'G2E05', 6, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(96, 2, 'Kelly Jepchirchir', 'G3E01', 7, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(97, 2, 'Leon Odhiambo', 'G3E02', 7, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(98, 2, 'Mercy Chebet', 'G3E03', 7, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(99, 2, 'Newton Ochieng', 'G3E04', 7, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(100, 2, 'Olivia Achieng', 'G3E05', 7, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(101, 2, 'Peter Kariuki', 'G4E01', 8, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(102, 2, 'Quinter Akoth', 'G4E02', 8, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(103, 2, 'Ronald Kibet', 'G4E03', 8, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(104, 2, 'Stella Wairimu', 'G4E04', 8, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(105, 2, 'Timothy Mwita', 'G4E05', 8, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(106, 2, 'Umi Hassan', 'G5E01', 9, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(107, 2, 'Vincent Onyango', 'G5E02', 9, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(108, 2, 'Winnie Nduku', 'G5E03', 9, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(109, 2, 'Xavier Kipchoge', 'G5E04', 9, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(110, 2, 'Yvonne Nkatha', 'G5E05', 9, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(111, 2, 'Zayn Musa', 'G6E01', 10, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(112, 2, 'Angela Mueni', 'G6E02', 10, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(113, 2, 'Brian Kimutai', 'G6E03', 10, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(114, 2, 'Catherine Muthoni', 'G6E04', 10, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(115, 2, 'Dennis Njoroge', 'G6E05', 10, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(116, 2, 'Eva Naliaka', 'G7E01', 4, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(117, 2, 'Francis Muli', 'G7E02', 4, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(118, 2, 'Grace Wacera', 'G7E03', 4, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(119, 2, 'Henry Njeru', 'G7E04', 4, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(120, 2, 'Ivy Kavira', 'G7E05', 4, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(121, 2, 'John Ndung\'u', 'G8E01', 11, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(122, 2, 'Kevin Otieno', 'G8E02', 11, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(123, 2, 'Linda Achieng', 'G8E03', 11, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(124, 2, 'Moses Kipruto', 'G8E04', 11, 'Male', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(125, 2, 'Nancy Muthoni', 'G8E05', 11, 'Female', NULL, 'active', '2026-05-08 15:58:41', '2026-05-08 15:58:41'),
(136, 3, 'Oliver Mwangi', 'G9E01', 16, 'Male', NULL, 'active', '2026-05-22 13:22:31', '2026-05-22 13:22:31'),
(137, 3, 'Pauline Chepchumba', 'G9E02', 16, 'Female', NULL, 'active', '2026-05-22 13:22:31', '2026-05-22 13:22:31'),
(138, 3, 'Quincy Omondi', 'G9E03', 16, 'Male', NULL, 'active', '2026-05-22 13:22:31', '2026-05-22 13:22:31'),
(139, 3, 'Rose Adhiambo', 'G9E04', 16, 'Female', NULL, 'active', '2026-05-22 13:22:31', '2026-05-22 13:22:31'),
(140, 3, 'Samuel Kariuki', 'G9E05', 16, 'Male', NULL, 'active', '2026-05-22 13:22:31', '2026-05-22 13:22:31'),
(141, 3, 'Eva Naliaka', '7007E1', 14, 'Female', '2012-02-09', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(142, 3, 'Francis Muli', '7007E2', 14, 'Male', '2011-09-12', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(143, 3, 'Grace Wacera', '7007E3', 14, 'Female', '2012-04-18', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(144, 3, 'Henry Njeru', '7007E4', 14, 'Male', '2011-12-05', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(145, 3, 'Ivy Kavira', '7007E5', 14, 'Female', '2012-06-22', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(146, 3, 'John Ndung\'u', '8008E1', 15, 'Male', '2011-03-14', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(147, 3, 'Kevin Otieno', '8008E2', 15, 'Male', '2010-07-30', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(148, 3, 'Linda Achieng', '8008E3', 15, 'Female', '2011-01-25', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(149, 3, 'Moses Kipruto', '8008E4', 15, 'Male', '2010-11-16', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(150, 3, 'Nancy Muthoni', '8008E5', 15, 'Female', '2011-05-03', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(151, 3, 'Oliver Mwangi', '9009E1', 16, 'Male', '2010-02-28', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(152, 3, 'Pauline Chepchumba', '9009E2', 16, 'Female', '2009-10-11', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(153, 3, 'Quincy Omondi', '9009E3', 16, 'Male', '2010-06-17', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(154, 3, 'Rose Adhiambo', '9009E4', 16, 'Female', '2009-12-09', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51'),
(155, 3, 'Samuel Kariuki', '9009E5', 16, 'Male', '2010-08-02', 'active', '2026-05-22 13:22:51', '2026-05-22 13:22:51');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `learning_area` varchar(100) DEFAULT NULL,
  `phase` enum('lower_primary','upper_primary','junior_secondary','senior_secondary') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `school_id`, `name`, `code`, `learning_area`, `phase`, `created_at`, `updated_at`) VALUES
(1, 1, 'Mathematics', 'MAT', 'Mathematics', 'junior_secondary', '2026-05-05 12:59:14', '2026-05-08 16:06:01'),
(2, 1, 'English', 'ENG', 'Languages', NULL, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(3, 1, 'Kiswahili', 'KIS', 'Languages', NULL, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(4, 1, 'Integrated Science', 'SCI', 'Science & Technology', NULL, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(5, 1, 'Social Studies', 'SST', 'Social Studies', 'junior_secondary', '2026-05-05 12:59:14', '2026-05-08 16:06:01'),
(17, 2, 'Mathematics', 'MAT001', 'Mathematics Activities', 'lower_primary', '2026-05-08 16:08:00', '2026-05-08 16:08:00'),
(18, 2, 'English', 'ENG002', 'English Activities', 'lower_primary', '2026-05-08 16:08:21', '2026-05-08 16:08:21'),
(19, 2, 'kiswahili', 'KISW003', 'Kiswahili Activities', 'lower_primary', '2026-05-08 16:09:15', '2026-05-08 16:09:15'),
(20, 2, 'Environmental', 'ENV004', 'Environmental Activities', 'lower_primary', '2026-05-08 16:10:54', '2026-05-08 16:10:54'),
(21, 2, 'CRE', 'CRE005', 'Religious Education Activities', 'lower_primary', '2026-05-08 16:11:09', '2026-05-08 16:11:09'),
(22, 2, 'Art & Craft', 'ART006', 'Creative Activities', 'lower_primary', '2026-05-08 16:11:29', '2026-05-08 16:11:29'),
(23, 2, 'Indigenous', 'INDI007', 'Indigenous Language Activities', 'lower_primary', '2026-05-08 16:12:14', '2026-05-08 16:12:14'),
(24, 2, 'English', 'ENG100', 'English', 'upper_primary', '2026-05-08 16:12:49', '2026-05-08 16:12:49'),
(25, 2, 'kiswahili', 'KISW101', 'Kiswahili', 'upper_primary', '2026-05-08 16:13:21', '2026-05-08 16:13:21'),
(26, 2, 'Mathematics', 'MAT103', 'Mathematics', 'upper_primary', '2026-05-08 16:13:35', '2026-05-08 16:13:35'),
(27, 2, 'Science', 'SCI104', 'Science and Technology', 'upper_primary', '2026-05-08 16:13:47', '2026-05-08 16:13:47'),
(28, 2, 'Agriculture', 'AGRI105', 'Agriculture and Nutrition', 'upper_primary', '2026-05-08 16:14:02', '2026-05-08 16:14:02'),
(29, 2, 'Social Studies', 'SST106', 'Social Studies', 'upper_primary', '2026-05-08 16:14:20', '2026-05-08 16:14:20'),
(30, 2, 'Art', 'ART107', 'Creative Arts', 'upper_primary', '2026-05-08 16:14:36', '2026-05-08 16:14:36'),
(31, 2, 'CRE', 'CRE108', 'Religious Education', 'upper_primary', '2026-05-08 16:14:48', '2026-05-08 16:14:48'),
(32, 1, 'Mathematics', 'MAT1', NULL, NULL, '2026-05-21 15:54:37', '2026-05-21 15:54:37'),
(33, 1, 'English', 'ENG2', NULL, NULL, '2026-05-21 15:54:37', '2026-05-21 15:54:37'),
(34, 1, 'Kiswahili', 'KIS3', NULL, NULL, '2026-05-21 15:54:37', '2026-05-21 15:54:37'),
(35, 1, 'Science', 'SCI4', NULL, NULL, '2026-05-21 15:54:37', '2026-05-21 15:54:37'),
(36, 1, 'Physical Education', 'PHY5', NULL, NULL, '2026-05-21 15:54:37', '2026-05-21 15:54:37'),
(37, 1, 'CRE', 'CRE6', NULL, NULL, '2026-05-21 15:54:37', '2026-05-21 15:54:37'),
(38, 1, 'Civic studies', 'CIV7', NULL, NULL, '2026-05-21 15:54:37', '2026-05-21 15:54:37'),
(43, 3, 'Mathematics', 'MAT1', NULL, NULL, '2026-05-22 13:19:12', '2026-05-22 13:19:12'),
(44, 3, 'English', 'ENG2', NULL, NULL, '2026-05-22 13:19:12', '2026-05-22 13:19:12'),
(45, 3, 'Kiswahili', 'KIS3', NULL, NULL, '2026-05-22 13:19:12', '2026-05-22 13:19:12'),
(46, 3, 'Science', 'SCI4', NULL, NULL, '2026-05-22 13:19:12', '2026-05-22 13:19:12'),
(47, 3, 'Physical Education', 'PHY5', NULL, NULL, '2026-05-22 13:19:12', '2026-05-22 13:19:12'),
(48, 3, 'CRE', 'CRE6', NULL, NULL, '2026-05-22 13:19:12', '2026-05-22 13:19:12'),
(49, 3, 'Civic studies', 'CIV7', NULL, NULL, '2026-05-22 13:19:12', '2026-05-22 13:19:12');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `billing_cycle` enum('monthly','termly','annually') DEFAULT 'termly',
  `max_students` int(10) UNSIGNED DEFAULT 50,
  `features_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`features_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `name`, `price`, `billing_cycle`, `max_students`, `features_json`, `created_at`) VALUES
(1, 'Free', 0.00, 'termly', 50, '{\"manual_timetable\": true, \"ai_timetable\": false, \"parent_portal\": false, \"advanced_analytics\": false}', '2026-05-05 12:59:13'),
(2, 'Standard', 5000.00, 'termly', 200, '{\"manual_timetable\": true, \"ai_timetable\": false, \"parent_portal\": false, \"advanced_analytics\": false}', '2026-05-05 12:59:13'),
(3, 'Premium', 15000.00, 'termly', 500, '{\"manual_timetable\": true, \"ai_timetable\": true, \"advanced_analytics\": true, \"parent_portal\": true, \"payment_gateway\": false}', '2026-05-05 12:59:13');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `tsc_number` varchar(50) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `school_id`, `tsc_number`, `specialization`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'TSC/12345/67', 'Mathematics & Science', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(2, 4, 1, 'TSC/98765/43', 'Languages', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(3, 6, 2, 'TCH001', 'English, Kiswahili, Mathematics, Environmental, RE, Creative, Indigenous Language', '2026-05-08 16:19:09', '2026-05-08 16:19:09'),
(4, 7, 2, 'TCH002', 'English, Kiswahili, Mathematics, Environmental, RE, Creative, Indigenous Language', '2026-05-08 16:25:21', '2026-05-08 16:25:21'),
(5, 8, 2, 'TCH003', 'English, Kiswahili, Mathematics, Science & Technology, Agriculture & Nutrition, Social Studies, Crea', '2026-05-08 16:25:57', '2026-05-08 16:25:57'),
(6, 9, 1, 'TSC 00123', 'TSC 00123', '2026-05-21 15:55:25', '2026-05-21 15:55:25'),
(7, 11, 3, 'Mathematics, Science and Swahili', 'Mathematics, Science and Swahili', '2026-05-22 13:21:21', '2026-05-22 13:21:21');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_entries`
--

CREATE TABLE `timetable_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `slot_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_entries`
--

INSERT INTO `timetable_entries` (`id`, `school_id`, `class_id`, `slot_id`, `subject_id`, `teacher_id`, `room_number`, `created_at`, `updated_at`) VALUES
(2, 2, 5, 106, 26, 3, 'Room 12', '2026-05-08 16:20:47', '2026-05-08 16:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_slots`
--

CREATE TABLE `timetable_slots` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `period_number` tinyint(3) UNSIGNED NOT NULL CHECK (`period_number` between 1 and 10),
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_slots`
--

INSERT INTO `timetable_slots` (`id`, `school_id`, `day_of_week`, `period_number`, `start_time`, `end_time`) VALUES
(81, 1, 'Monday', 1, '08:00:00', '09:00:00'),
(82, 1, 'Monday', 2, '09:00:00', '10:00:00'),
(83, 1, 'Monday', 3, '10:00:00', '11:00:00'),
(84, 1, 'Monday', 4, '11:00:00', '12:00:00'),
(85, 1, 'Monday', 5, '12:00:00', '13:00:00'),
(86, 1, 'Tuesday', 1, '13:00:00', '14:00:00'),
(87, 1, 'Tuesday', 2, '14:00:00', '15:00:00'),
(88, 1, 'Tuesday', 3, '15:00:00', '16:00:00'),
(89, 1, 'Tuesday', 4, '16:00:00', '17:00:00'),
(90, 1, 'Tuesday', 5, '17:00:00', '18:00:00'),
(91, 1, 'Wednesday', 1, '18:00:00', '19:00:00'),
(92, 1, 'Wednesday', 2, '19:00:00', '20:00:00'),
(93, 1, 'Wednesday', 3, '20:00:00', '21:00:00'),
(94, 1, 'Wednesday', 4, '21:00:00', '22:00:00'),
(95, 1, 'Wednesday', 5, '22:00:00', '23:00:00'),
(96, 1, 'Thursday', 1, '23:00:00', '24:00:00'),
(97, 1, 'Thursday', 2, '24:00:00', '25:00:00'),
(98, 1, 'Thursday', 3, '25:00:00', '26:00:00'),
(99, 1, 'Thursday', 4, '26:00:00', '27:00:00'),
(100, 1, 'Thursday', 5, '27:00:00', '28:00:00'),
(101, 1, 'Friday', 1, '28:00:00', '29:00:00'),
(102, 1, 'Friday', 2, '29:00:00', '30:00:00'),
(103, 1, 'Friday', 3, '30:00:00', '31:00:00'),
(104, 1, 'Friday', 4, '31:00:00', '32:00:00'),
(105, 1, 'Friday', 5, '32:00:00', '33:00:00'),
(106, 2, 'Monday', 1, '08:00:00', '09:00:00'),
(107, 2, 'Monday', 2, '09:00:00', '10:00:00'),
(108, 2, 'Monday', 3, '10:00:00', '11:00:00'),
(109, 2, 'Monday', 4, '11:00:00', '12:00:00'),
(110, 2, 'Monday', 5, '12:00:00', '13:00:00'),
(111, 2, 'Tuesday', 1, '13:00:00', '14:00:00'),
(112, 2, 'Tuesday', 2, '14:00:00', '15:00:00'),
(113, 2, 'Tuesday', 3, '15:00:00', '16:00:00'),
(114, 2, 'Tuesday', 4, '16:00:00', '17:00:00'),
(115, 2, 'Tuesday', 5, '17:00:00', '18:00:00'),
(116, 2, 'Wednesday', 1, '18:00:00', '19:00:00'),
(117, 2, 'Wednesday', 2, '19:00:00', '20:00:00'),
(118, 2, 'Wednesday', 3, '20:00:00', '21:00:00'),
(119, 2, 'Wednesday', 4, '21:00:00', '22:00:00'),
(120, 2, 'Wednesday', 5, '22:00:00', '23:00:00'),
(121, 2, 'Thursday', 1, '23:00:00', '24:00:00'),
(122, 2, 'Thursday', 2, '24:00:00', '25:00:00'),
(123, 2, 'Thursday', 3, '25:00:00', '26:00:00'),
(124, 2, 'Thursday', 4, '26:00:00', '27:00:00'),
(125, 2, 'Thursday', 5, '27:00:00', '28:00:00'),
(126, 2, 'Friday', 1, '28:00:00', '29:00:00'),
(127, 2, 'Friday', 2, '29:00:00', '30:00:00'),
(128, 2, 'Friday', 3, '30:00:00', '31:00:00'),
(129, 2, 'Friday', 4, '31:00:00', '32:00:00'),
(130, 2, 'Friday', 5, '32:00:00', '33:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','school_admin','teacher','staff') NOT NULL,
  `staff_type` enum('teaching','non_teaching','administrative','support') DEFAULT 'teaching',
  `job_role` varchar(100) DEFAULT NULL,
  `details_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_json`)),
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `password_hash`, `role`, `staff_type`, `job_role`, `details_json`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Super Admin', 'admin@platform.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'teaching', NULL, NULL, 'active', '2026-05-23 19:00:24', '2026-05-05 12:59:14', '2026-05-23 19:00:24'),
(2, 1, 'Demo Admin', 'admin@greenfield-demo.ke', '$2y$10$/poC6lhT/yhT2s/jcNHXvuM5v5vqFomRurooULSj37Z1rZPER1WrG', 'school_admin', 'teaching', NULL, NULL, 'active', '2026-05-26 19:32:09', '2026-05-05 12:59:14', '2026-05-26 19:32:09'),
(3, 1, 'Jane Teacher', 'jane@greenfield-demo.ke', '$2y$10$22xMpTSWMUK.zrsBXujygud0.2bnY44oov3xK.sbc.0e162/VggbK', 'teacher', 'teaching', NULL, NULL, 'active', '2026-05-26 19:30:01', '2026-05-05 12:59:14', '2026-05-26 19:30:01'),
(4, 1, 'John Teacher', 'john@greenfield-demo.ke', '$2y$10$kCVnMusCGY8mBqaicozow.IkAPZvLUK2eYsz8AbvIvJLVF9A7gxV6', 'teacher', 'teaching', NULL, NULL, 'active', NULL, '2026-05-05 12:59:14', '2026-05-05 16:25:32'),
(5, 2, 'John Holt', 'holt@gmail.com', '$2y$10$1219BZGGrsNLpLIt3QJsxub8EPr6wEs9f4RDP2QLNKdx/R5.sa5MC', 'school_admin', 'teaching', NULL, NULL, 'active', '2026-05-21 12:24:43', '2026-05-05 16:51:20', '2026-05-21 12:24:43'),
(6, 2, 'Grace Auma', 'auma@gmail.com', '$2y$10$HrAm1Sz/snel0bJd9AoM/uE.luHF7pUTGobHod6HZvROKSrbj7fJC', 'teacher', 'teaching', NULL, NULL, 'active', NULL, '2026-05-08 16:19:09', '2026-05-08 16:19:09'),
(7, 2, 'James Odhiambo', 'jamoh@gmail.com', '$2y$10$iiDTWjhmBwIxGvvUiV.2SuTrAan1WrxjufoqruKyMTTd2EQNuVrNC', 'teacher', 'teaching', NULL, NULL, 'active', NULL, '2026-05-08 16:25:21', '2026-05-08 16:25:21'),
(8, 2, 'Mary Wanjiku', 'mary@gmail.com', '$2y$10$w/Ey.vEDASxbkK/BL0dsmep3TKKEKs5MwP/6ZpWZRv7bWldkUDLDi', 'teacher', 'teaching', NULL, NULL, 'active', '2026-05-11 13:03:32', '2026-05-08 16:25:57', '2026-05-11 13:03:32'),
(9, 1, 'Kylian Mbappe', 'kylian1@gmail.com', '$2y$10$sHph7ugzj533Y5a/VdZOz.PYzGImNRHybYh.P9H6AB22QmX1Bp0cS', 'teacher', 'teaching', NULL, NULL, 'active', '2026-05-23 19:22:10', '2026-05-21 15:55:25', '2026-05-23 19:22:10'),
(10, 3, 'Bruno Wachira', 'fernandes@gmail.com', '$2y$10$Fq.14cF1/nQTzDcFx4yjKOajwkVK/nATlILtcdIg0t6XcxlsDQQBG', 'school_admin', 'teaching', NULL, NULL, 'active', '2026-05-22 13:05:48', '2026-05-22 12:30:35', '2026-05-22 13:05:48'),
(11, 3, 'Marcus Rashford', 'marcus@gmail.com', '$2y$10$XUFyzkc.BfLSNsxJ2YCjUOVghQCEzpMvNseh67W.07KCIjfxz3baS', 'teacher', 'teaching', NULL, NULL, 'active', NULL, '2026-05-22 13:21:21', '2026-05-22 13:21:21'),
(17, 1, 'John Amollo', 'amollo@gmail.com', '$2y$10$Uaad46kOZstfwtjZU8/DIe0D7.Nkv/CmtXoU.OXImc.nj4NQMdME6', 'staff', 'non_teaching', 'Driver', '[]', 'active', NULL, '2026-05-26 18:53:40', '2026-05-26 18:53:40'),
(18, 1, 'John Maina', 'mainaissue@gmail.com', '$2y$10$wMcZ5mZCidNJaMUD21SXF.rCQE0wBgb82sR75qiQSM9fef0uVuAk.', 'staff', 'non_teaching', 'Driver', '[]', 'active', NULL, '2026-05-26 19:36:40', '2026-05-26 19:36:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`date`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_school` (`school_id`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `classes_new`
--
ALTER TABLE `classes_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `class_subject_teacher`
--
ALTER TABLE `class_subject_teacher`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`class_id`,`subject_id`,`teacher_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `grade_definitions`
--
ALTER TABLE `grade_definitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `level_id` (`level_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `school_levels`
--
ALTER TABLE `school_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `school_level_config`
--
ALTER TABLE `school_level_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_level` (`school_id`,`level_id`),
  ADD KEY `level_id` (`level_id`);

--
-- Indexes for table `school_settings`
--
ALTER TABLE `school_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`);

--
-- Indexes for table `school_subscriptions`
--
ALTER TABLE `school_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_assessment` (`student_id`,`assessment_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `graded_by` (`graded_by`);

--
-- Indexes for table `staff_directory`
--
ALTER TABLE `staff_directory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_number` (`admission_number`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_subject` (`school_id`,`code`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tsc_number` (`tsc_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `slot_id` (`slot_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot` (`school_id`,`day_of_week`,`period_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `school_id` (`school_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `classes_new`
--
ALTER TABLE `classes_new`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_subject_teacher`
--
ALTER TABLE `class_subject_teacher`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `grade_definitions`
--
ALTER TABLE `grade_definitions`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `school_levels`
--
ALTER TABLE `school_levels`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school_level_config`
--
ALTER TABLE `school_level_config`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `school_settings`
--
ALTER TABLE `school_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `school_subscriptions`
--
ALTER TABLE `school_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `staff_directory`
--
ALTER TABLE `staff_directory`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessments_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes_new`
--
ALTER TABLE `classes_new`
  ADD CONSTRAINT `classes_new_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_subject_teacher`
--
ALTER TABLE `class_subject_teacher`
  ADD CONSTRAINT `class_subject_teacher_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subject_teacher_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subject_teacher_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subject_teacher_ibfk_4` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_settings`
--
ALTER TABLE `school_settings`
  ADD CONSTRAINT `school_settings_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_subscriptions`
--
ALTER TABLE `school_subscriptions`
  ADD CONSTRAINT `school_subscriptions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `school_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`);

--
-- Constraints for table `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scores_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scores_ibfk_3` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scores_ibfk_4` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_directory`
--
ALTER TABLE `staff_directory`
  ADD CONSTRAINT `staff_directory_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_profiles_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD CONSTRAINT `timetable_entries_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_ibfk_3` FOREIGN KEY (`slot_id`) REFERENCES `timetable_slots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_ibfk_4` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_entries_ibfk_5` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  ADD CONSTRAINT `timetable_slots_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
