-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2026 at 05:12 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED DEFAULT NULL CHECK (`grade_level` BETWEEN 0 AND 12 OR `grade_level` IS NULL),
  `stream_code` varchar(20) DEFAULT NULL,
  `level` enum('kindergarten','preschool','1','2','3','4','5','6','7','8','9','10','11','12') DEFAULT NULL,
  `capacity` int(10) UNSIGNED DEFAULT 45,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `school_id`, `name`, `grade_level`, `stream_code`, `level`, `capacity`, `created_at`, `updated_at`) VALUES
(1, 1, 'Grade 7 West', 7, '7W', NULL, 40, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(2, 1, 'Grade 8 East', 8, '8E', NULL, 40, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(3, 1, 'Grade 9 North', 9, '9N', NULL, 40, '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(4, 2, 'Grade 7 East', 7, 'EAST', NULL, 40, '2026-05-08 15:09:45', '2026-05-08 15:09:45');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `code`, `county`, `contact_email`, `contact_phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Greenfield CBC Academy', 'GFA-DEMO', 'Nairobi', 'admin@greenfield-demo.ke', '+254700000001', 'active', '2026-05-05 12:59:13', '2026-05-05 12:59:13'),
(2, 'Visionary School', NULL, 'NAIROBI', 'holt@gmail.com', NULL, 'trial', '2026-05-05 16:51:19', '2026-05-05 16:51:19');

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
(2, 2, 1, 'active', '2026-05-05', '2026-06-04', 0, 0, '2026-05-05 16:51:20', '2026-05-05 16:51:20');

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
(1, 1, 1, 1, 45.00, 'EE1', NULL, 2, '2026-05-05 13:00:03', '2026-05-05 13:00:03'),
(2, 1, 2, 1, 34.00, 'ME2', NULL, 2, '2026-05-05 13:00:03', '2026-05-05 13:00:03');

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
(4, 1, 'David Kimani', 'ADM/2025/004', 3, 'Male', '2009-01-30', 'active', '2026-05-05 12:59:14', '2026-05-05 12:59:14');

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

INSERT INTO `subjects` (`id`, `school_id`, `name`, `code`, `learning_area`, `created_at`, `updated_at`) VALUES
(1, 1, 'Mathematics', 'MAT', 'Mathematics', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(2, 1, 'English', 'ENG', 'Languages', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(3, 1, 'Kiswahili', 'KIS', 'Languages', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(4, 1, 'Integrated Science', 'SCI', 'Science & Technology', '2026-05-05 12:59:14', '2026-05-05 12:59:14'),
(5, 1, 'Social Studies', 'SST', 'Social Studies', '2026-05-05 12:59:14', '2026-05-05 12:59:14');

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
(2, 4, 1, 'TSC/98765/43', 'Languages', '2026-05-05 12:59:14', '2026-05-05 12:59:14');

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
(105, 1, 'Friday', 5, '32:00:00', '33:00:00');

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
  `role` enum('super_admin','school_admin','teacher') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `password_hash`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Super Admin', 'admin@platform.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active', '2026-05-07 10:06:12', '2026-05-05 12:59:14', '2026-05-07 10:06:12'),
(2, 1, 'Demo Admin', 'admin@greenfield-demo.ke', '$2y$10$/poC6lhT/yhT2s/jcNHXvuM5v5vqFomRurooULSj37Z1rZPER1WrG', 'school_admin', 'active', '2026-05-07 10:06:42', '2026-05-05 12:59:14', '2026-05-07 10:06:42'),
(3, 1, 'Jane Teacher', 'jane@greenfield-demo.ke', '$2y$10$22xMpTSWMUK.zrsBXujygud0.2bnY44oov3xK.sbc.0e162/VggbK', 'teacher', 'active', '2026-05-07 10:02:33', '2026-05-05 12:59:14', '2026-05-07 10:02:33'),
(4, 1, 'John Teacher', 'john@greenfield-demo.ke', '$2y$10$kCVnMusCGY8mBqaicozow.IkAPZvLUK2eYsz8AbvIvJLVF9A7gxV6', 'teacher', 'active', NULL, '2026-05-05 12:59:14', '2026-05-05 16:25:32'),
(5, 2, 'John Holt', 'holt@gmail.com', '$2y$10$1219BZGGrsNLpLIt3QJsxub8EPr6wEs9f4RDP2QLNKdx/R5.sa5MC', 'school_admin', 'active', '2026-05-08 14:38:12', '2026-05-05 16:51:20', '2026-05-08 14:38:12');

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
  ADD UNIQUE KEY `code` (`code`),
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `classes_new`
--
ALTER TABLE `classes_new`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_subject_teacher`
--
ALTER TABLE `class_subject_teacher`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grade_definitions`
--
ALTER TABLE `grade_definitions`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `school_subscriptions`
--
ALTER TABLE `school_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timetable_slots`
--
ALTER TABLE `timetable_slots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
