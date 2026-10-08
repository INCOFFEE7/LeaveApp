-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026 at 03:58 AM
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
-- Database: `sijeda`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `activity_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `related_table` varchar(255) NOT NULL,
  `related_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'Finance Accounting and Tax', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(2, 'Human Resource General Affairs Document', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(3, 'Nasional Sales and Marketing', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(4, 'Part and Service Operation', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(5, 'Regional Manager ', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(6, 'Project and Engineering Service', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(7, 'Fabrication and Workshop', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(8, 'UNASSIGNED', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(9, 'Manager', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(10, 'Director', 1, 1, 1, '2026-03-08 02:57:33', '2026-03-08 02:57:33');

-- --------------------------------------------------------

--
-- Table structure for table `department_approvers`
--

CREATE TABLE `department_approvers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL,
  `approver_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extra_holidays`
--

CREATE TABLE `extra_holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leader_assignments`
--

CREATE TABLE `leader_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `leader_id` bigint(20) UNSIGNED NOT NULL,
  `worker_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `effective_date` date NOT NULL DEFAULT '2026-03-08',
  `ended_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_approvals`
--

CREATE TABLE `leave_approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `leave_permission_id` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED NOT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL,
  `status` enum('pending','in_progress','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `worker_id` bigint(20) UNSIGNED NOT NULL,
  `year` int(11) NOT NULL,
  `hak_cuti` tinyint(3) UNSIGNED NOT NULL,
  `sisa_cuti` tinyint(4) NOT NULL,
  `utang_cuti` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `reset_at` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_balances`
--

INSERT INTO `leave_balances` (`id`, `worker_id`, `year`, `hak_cuti`, `sisa_cuti`, `utang_cuti`, `reset_at`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 2011, 12, 12, 0, '2011-01-01', NULL, '2026-03-08 02:57:34', '2026-03-08 02:57:34'),
(2, 2, 2016, 12, 12, 0, '2016-09-01', NULL, '2026-03-08 02:57:36', '2026-03-08 02:57:36'),
(3, 3, 2017, 12, 12, 0, '2017-02-20', NULL, '2026-03-08 02:57:36', '2026-03-08 02:57:36'),
(4, 4, 2021, 12, 12, 0, '2021-01-01', NULL, '2026-03-08 02:57:36', '2026-03-08 02:57:36'),
(5, 5, 2020, 12, 12, 0, '2020-05-01', NULL, '2026-03-08 02:57:37', '2026-03-08 02:57:37'),
(6, 6, 2019, 12, 12, 0, '2019-03-10', NULL, '2026-03-08 02:57:37', '2026-03-08 02:57:37'),
(7, 7, 2018, 12, 12, 0, '2018-06-15', NULL, '2026-03-08 02:57:37', '2026-03-08 02:57:37'),
(8, 8, 2022, 12, 12, 0, '2022-04-01', NULL, '2026-03-08 02:57:37', '2026-03-08 02:57:37'),
(9, 9, 2019, 12, 12, 0, '2019-02-15', NULL, '2026-03-08 02:57:38', '2026-03-08 02:57:38'),
(10, 10, 2020, 12, 12, 0, '2020-01-10', NULL, '2026-03-08 02:57:38', '2026-03-08 02:57:38'),
(11, 11, 2022, 12, 12, 0, '2022-05-10', NULL, '2026-03-08 02:57:38', '2026-03-08 02:57:38'),
(12, 12, 2023, 12, 12, 0, '2023-03-01', NULL, '2026-03-08 02:57:39', '2026-03-08 02:57:39'),
(13, 13, 2022, 12, 12, 0, '2022-05-10', NULL, '2026-03-08 02:57:39', '2026-03-08 02:57:39'),
(14, 14, 2023, 12, 12, 0, '2023-03-01', NULL, '2026-03-08 02:57:39', '2026-03-08 02:57:39');

-- --------------------------------------------------------

--
-- Table structure for table `leave_balance_logs`
--

CREATE TABLE `leave_balance_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `leave_balance_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `old_hak_cuti` smallint(6) DEFAULT NULL,
  `new_hak_cuti` smallint(6) DEFAULT NULL,
  `old_sisa_cuti` smallint(6) DEFAULT NULL,
  `new_sisa_cuti` smallint(6) DEFAULT NULL,
  `old_utang_cuti` smallint(6) DEFAULT NULL,
  `new_utang_cuti` smallint(6) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_permissions`
--

CREATE TABLE `leave_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `worker_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `leave_date_from` date NOT NULL,
  `leave_date_to` date NOT NULL,
  `leave_year` smallint(5) UNSIGNED NOT NULL COMMENT 'Tahun saldo cuti yang digunakan',
  `source_year` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Tahun asal histori cuti (jika manual)',
  `onsite_date_from` date DEFAULT NULL,
  `onsite_date_to` date DEFAULT NULL,
  `return_to_work_date` date DEFAULT NULL,
  `onsite_total_days` int(11) DEFAULT NULL,
  `total_leave` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `leave_reason` varchar(500) NOT NULL,
  `status` enum('pending','in_progress','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `current_level` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_manual` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `supporting_document` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_status_logs`
--

CREATE TABLE `leave_status_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `leave_permission_id` bigint(20) UNSIGNED NOT NULL,
  `changed_by` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `new_status` enum('pending','in_progress','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `report_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `leave_type_name`, `is_active`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'Sakit', 1, '2026-03-08 02:57:39', '2026-03-08 02:57:39', 2, 2),
(2, 'Izin', 1, '2026-03-08 02:57:39', '2026-03-08 02:57:39', 2, 2),
(3, 'Cuti Tahunan', 1, '2026-03-08 02:57:39', '2026-03-08 02:57:39', 2, 2),
(4, 'Field Break', 1, '2026-03-08 02:57:39', '2026-03-08 02:57:39', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '0001_01_01_000003_create_permission_tables', 1),
(4, '0001_01_01_000004_create_users_table', 1),
(5, '2025_06_07_083943_create_departments_table', 1),
(6, '2025_06_07_084014_create_department_approvers_table', 1),
(7, '2025_06_07_084049_create_workers_table', 1),
(8, '2025_06_07_084103_create_leave_types_table', 1),
(9, '2025_06_07_084122_create_leave_permissions_table', 1),
(10, '2025_06_07_084146_create_leave_approvals_table', 1),
(11, '2025_06_07_084152_create_leave_balances_table', 1),
(12, '2025_06_07_084203_create_leave_status_logs_table', 1),
(13, '2025_06_07_084215_create_activity_logs_table', 1),
(14, '2025_07_30_105300_create_leave_balance_logs_table', 1),
(15, '2025_09_26_031145_create_notifications_table', 1),
(16, '2025_10_28_081103_create_leader_assignments_table', 1),
(17, '2025_11_10_233714_alter_supporting_document_to_string_in_leave_permissions', 1),
(18, '2025_11_11_090000_migrate_leader_ids_from_workers_to_users', 1),
(19, '2025_11_11_100000_update_leader_assignments_leader_fk', 1),
(20, '2025_11_11_110000_add_email_verified_at_to_users', 1),
(21, '2025_11_14_022928_add_report_path_to_leave_permission_logs_table', 1),
(22, '2025_12_04_143622_create_national_holidays_table', 1),
(23, '2025_12_04_143641_create_extra_holidays_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 6),
(1, 'App\\Models\\User', 10),
(1, 'App\\Models\\User', 13),
(1, 'App\\Models\\User', 14),
(1, 'App\\Models\\User', 15),
(1, 'App\\Models\\User', 16),
(2, 'App\\Models\\User', 7),
(3, 'App\\Models\\User', 8),
(3, 'App\\Models\\User', 11),
(3, 'App\\Models\\User', 12),
(4, 'App\\Models\\User', 9),
(5, 'App\\Models\\User', 4),
(6, 'App\\Models\\User', 3),
(7, 'App\\Models\\User', 5),
(8, 'App\\Models\\User', 2),
(9, 'App\\Models\\User', 1);

-- --------------------------------------------------------

--
-- Table structure for table `national_holidays`
--

CREATE TABLE `national_holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_national` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view.employee', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(2, 'dashboard.view.approver', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(3, 'dashboard.view.admin', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(4, 'leave.create', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(5, 'leave.view.own', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(6, 'leave.view.department', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(7, 'leave.view.all', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(8, 'leave.edit.own', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(9, 'leave.edit.department', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(10, 'leave.delete.own', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(11, 'leave.delete.department', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(12, 'leave.delete.all', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(13, 'leave.approve', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(14, 'leave.export.self', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(15, 'leave.export.department', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(16, 'leave.export.all', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(17, 'leave.type.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(18, 'leave.balance.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(19, 'leave.approval.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(20, 'leave.balance.reset.self', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(21, 'leave.balance.reset.all', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(22, 'user.view', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(23, 'user.create', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(24, 'user.edit', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(25, 'user.delete', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(26, 'user.import', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(27, 'user.reset-password', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(28, 'user.assign-role', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(29, 'holiday.sync', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(30, 'holiday.extra.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(31, 'holiday.national.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(32, 'role.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(33, 'permission.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(34, 'department.view', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(35, 'department.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(36, 'department.approver.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(37, 'system.setting.manage', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(38, 'log.view', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'employee', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(2, 'senior_staff', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(3, 'leader', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(4, 'department_head', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(5, 'manager', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(6, 'director', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(7, 'hrd', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(8, 'admin', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33'),
(9, 'dev', 'web', '2026-03-08 02:57:33', '2026-03-08 02:57:33');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 9),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 9),
(3, 7),
(3, 8),
(3, 9),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 8),
(4, 9),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(5, 8),
(5, 9),
(6, 3),
(6, 4),
(6, 5),
(6, 8),
(6, 9),
(7, 6),
(7, 7),
(7, 8),
(7, 9),
(8, 1),
(8, 2),
(8, 3),
(8, 8),
(8, 9),
(9, 8),
(9, 9),
(10, 8),
(10, 9),
(11, 8),
(11, 9),
(12, 8),
(12, 9),
(13, 2),
(13, 3),
(13, 4),
(13, 5),
(13, 6),
(13, 7),
(13, 8),
(13, 9),
(14, 1),
(14, 2),
(14, 3),
(14, 4),
(14, 7),
(15, 4),
(16, 8),
(16, 9),
(17, 8),
(17, 9),
(18, 8),
(18, 9),
(19, 8),
(19, 9),
(20, 9),
(21, 9),
(22, 7),
(22, 8),
(22, 9),
(23, 8),
(23, 9),
(24, 8),
(24, 9),
(25, 8),
(25, 9),
(26, 8),
(26, 9),
(27, 8),
(27, 9),
(28, 8),
(28, 9),
(29, 8),
(30, 8),
(31, 8),
(32, 8),
(32, 9),
(33, 9),
(34, 8),
(34, 9),
(35, 8),
(35, 9),
(36, 8),
(36, 9),
(37, 8),
(37, 9),
(38, 9);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_number` varchar(20) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role_id`, `payroll_number`, `phone_number`, `avatar`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Developer', 'developer@fss.com', NULL, '$2y$12$VVptef6BTBei5uuVS/iso.4YNLqOIeJXp4.pSNJnQIXUMwb.SJ3la', 9, '007', NULL, NULL, 1, NULL, '2026-03-08 02:57:34', '2026-03-08 02:57:34'),
(2, 'Admin', 'admin@fss.com', NULL, '$2y$12$eyE/SfeOdQ8fmPQCOID2c.rCZsRpBMN3CLtCoYK3n3znZdqR4rpby', 8, '1001', NULL, NULL, 1, NULL, '2026-03-08 02:57:34', '2026-03-08 02:57:34'),
(3, 'Director User', 'director@fss.com', NULL, '$2y$12$E9V2LGP.qPgQi2eE87RoN.ztx7PcHymGFoTinFfLvjx1cX52ashv6', 6, '1006', NULL, NULL, 1, NULL, '2026-03-08 02:57:34', '2026-03-08 02:57:34'),
(4, 'Manager User', 'manager@fss.com', NULL, '$2y$12$ApXjCFoGxJxF5zuDnWmwh..vpEZgBU7GPiRCKvIQOJ7TJdJ8Vh9we', 5, '1011', NULL, NULL, 1, NULL, '2026-03-08 02:57:34', '2026-03-08 02:57:34'),
(5, 'HRD User', 'hrd@fss.com', NULL, '$2y$12$ZOGdo62HMjjBApHzj.94temF1KFPmZmAZ2Fl6yP7PmONBGPwRmM1e', 7, '1007', NULL, NULL, 1, NULL, '2026-03-08 02:57:36', '2026-03-08 02:57:36'),
(6, 'Worker User', 'worker@fss.com', NULL, '$2y$12$6yx7S3yq4HK47aDEAWIkXOjNjwdJUmALIgjTi/UFbPKbZHZQC1Vhi', 1, '1002', NULL, NULL, 1, NULL, '2026-03-08 02:57:36', '2026-03-08 02:57:36'),
(7, 'Senior Staff User', 'seniorstaff@fss.com', NULL, '$2y$12$JHFncX.Z008CJxhZdG7q7ORV//qdZGXZtIqoPPZ.u2JnzE5WghSku', 2, '1003', NULL, NULL, 1, NULL, '2026-03-08 02:57:37', '2026-03-08 02:57:37'),
(8, 'Leader User', 'leader@fss.com', NULL, '$2y$12$MGk8evnMbckPUrg6T9toMugpVOT9PMi0HvINOyHspHExfSESGchbu', 3, '1004', NULL, NULL, 1, NULL, '2026-03-08 02:57:37', '2026-03-08 02:57:37'),
(9, 'Department Head User', 'depthead@fss.com', NULL, '$2y$12$PKqC3qcdsCFMxy/5CMo/xuvRvTZDF1kAkFqxN6iFfhwUkJVLoxQPa', 4, '1005', NULL, NULL, 1, NULL, '2026-03-08 02:57:37', '2026-03-08 02:57:37'),
(10, 'Sales Worker', 'salesworker@fss.com', NULL, '$2y$12$LQ/owwzYgmcxpj.5.dLX5ulm1sC32y0FS2fjQxvSN6C3PDEYtLfTi', 1, '1008', NULL, NULL, 1, NULL, '2026-03-08 02:57:37', '2026-03-08 02:57:37'),
(11, 'Leader Site Makassar', 'leader.mks@fss.com', NULL, '$2y$12$h2X2OKCcvD1pmL6BpBerE.elzqIHHRghyZRBJSlG.2GKJmZwP8H9S', 3, '1101', NULL, NULL, 1, NULL, '2026-03-08 02:57:38', '2026-03-08 02:57:38'),
(12, 'Leader Site Jakarta', 'leader.jkt@fss.com', NULL, '$2y$12$W55BnNUxL5MN24AE2mOec.gGKGp2lIJ7kJb/gfyvBFQEDLSs33hrm', 3, '1102', NULL, NULL, 1, NULL, '2026-03-08 02:57:38', '2026-03-08 02:57:38'),
(13, 'Employee Makassar 1', 'employee.mks1@fss.com', NULL, '$2y$12$7pj5DBnxZilNuqpOYXnsNOLJp2S.RgGUilUgvcwP6Z7PECPKvd9e.', 1, '1201', NULL, NULL, 1, NULL, '2026-03-08 02:57:38', '2026-03-08 02:57:38'),
(14, 'Employee Makassar 2', 'employee.mks2@fss.com', NULL, '$2y$12$4H58gZZYkCwZ9Au87YAOC.PSiAgkRPYP61bPLHtiWwJ84GUTbaQI6', 1, '1202', NULL, NULL, 1, NULL, '2026-03-08 02:57:39', '2026-03-08 02:57:39'),
(15, 'Employee Jakarta 1', 'employee.jkt1@fss.com', NULL, '$2y$12$gn1XUKI61WrAySacdltbOudKpugque9ZzDw0uojUZs3uSHfOKyeS.', 1, '1203', NULL, NULL, 1, NULL, '2026-03-08 02:57:39', '2026-03-08 02:57:39'),
(16, 'Employee Jakarta 2', 'employee.jkt2@fss.com', NULL, '$2y$12$pu.8oaq27ocO1CFgqFKf0ejJPVx09vfIgz19B8ObrZw.7PFOOWfOm', 1, '1204', NULL, NULL, 1, NULL, '2026-03-08 02:57:39', '2026-03-08 02:57:39');

-- --------------------------------------------------------

--
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `company` varchar(100) DEFAULT NULL,
  `worker_name` varchar(255) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `cabang` varchar(100) DEFAULT NULL,
  `site` varchar(100) DEFAULT NULL,
  `employment_status` enum('P','C','O') NOT NULL DEFAULT 'P',
  `date_of_hire` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workers`
--

INSERT INTO `workers` (`id`, `user_id`, `department_id`, `company`, `worker_name`, `position`, `cabang`, `site`, `employment_status`, `date_of_hire`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 3, 10, 'FSS', 'Director User', 'Director', NULL, NULL, 'P', '2010-01-01', 1, 1, 1, '2026-03-08 02:57:34', '2026-03-08 02:57:34', NULL),
(2, 4, 9, 'FSS', 'Manager User', 'General Manager', NULL, NULL, 'P', '2015-09-01', 1, 1, 1, '2026-03-08 02:57:36', '2026-03-08 02:57:36', NULL),
(3, 5, 2, 'FSS', 'HRD User', 'HRD', NULL, NULL, 'P', '2016-02-20', 1, 1, 1, '2026-03-08 02:57:36', '2026-03-08 02:57:36', NULL),
(4, 6, 1, 'FSS', 'Worker User', 'Staff', NULL, NULL, 'P', '2020-01-01', 1, 1, 1, '2026-03-08 02:57:36', '2026-03-08 02:57:36', NULL),
(5, 7, 1, 'FSS', 'Senior Staff User', 'Head of IT', NULL, NULL, 'P', '2019-05-01', 1, 1, 1, '2026-03-08 02:57:37', '2026-03-08 02:57:37', NULL),
(6, 8, 1, 'FSS', 'Leader User', 'Leader', NULL, NULL, 'P', '2018-03-10', 1, 1, 1, '2026-03-08 02:57:37', '2026-03-08 02:57:37', NULL),
(7, 9, 1, 'FSS', 'Department Head User', 'Dept Head', NULL, NULL, 'P', '2017-06-15', 1, 1, 1, '2026-03-08 02:57:37', '2026-03-08 02:57:37', NULL),
(8, 10, 3, 'FSS', 'Sales Worker', 'Sales Staff', NULL, NULL, 'P', '2021-04-01', 1, 1, 1, '2026-03-08 02:57:37', '2026-03-08 02:57:37', NULL),
(9, 11, 4, 'FSS', 'Leader Site Makassar', 'Site Leader', NULL, 'Makassar', 'P', '2018-02-15', 1, 1, 1, '2026-03-08 02:57:38', '2026-03-08 02:57:38', NULL),
(10, 12, 4, 'FSS', 'Leader Site Jakarta', 'Site Leader', NULL, 'Jakarta', 'P', '2019-01-10', 1, 1, 1, '2026-03-08 02:57:38', '2026-03-08 02:57:38', NULL),
(11, 13, 4, 'FSS', 'Employee Makassar 1', 'Technician', NULL, 'Makassar', 'P', '2021-05-10', 1, 1, 1, '2026-03-08 02:57:38', '2026-03-08 02:57:38', NULL),
(12, 14, 4, 'FSS', 'Employee Makassar 2', 'Technician', NULL, 'Makassar', 'P', '2022-03-01', 1, 1, 1, '2026-03-08 02:57:39', '2026-03-08 02:57:39', NULL),
(13, 15, 4, 'FSS', 'Employee Jakarta 1', 'Technician', NULL, 'Jakarta', 'P', '2021-05-10', 1, 1, 1, '2026-03-08 02:57:39', '2026-03-08 02:57:39', NULL),
(14, 16, 4, 'FSS', 'Employee Jakarta 2', 'Technician', NULL, 'Jakarta', 'P', '2022-03-01', 1, 1, 1, '2026-03-08 02:57:39', '2026-03-08 02:57:39', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_approvers`
--
ALTER TABLE `department_approvers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_approvers_department_id_level_approver_id_unique` (`department_id`,`level`,`approver_id`),
  ADD KEY `department_approvers_approver_id_foreign` (`approver_id`),
  ADD KEY `department_approvers_created_by_foreign` (`created_by`),
  ADD KEY `department_approvers_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `extra_holidays`
--
ALTER TABLE `extra_holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `extra_holidays_date_unique` (`date`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leader_assignments`
--
ALTER TABLE `leader_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_assignment` (`leader_id`,`worker_id`,`ended_at`),
  ADD KEY `leader_assignments_worker_id_foreign` (`worker_id`),
  ADD KEY `leader_assignments_department_id_foreign` (`department_id`);

--
-- Indexes for table `leave_approvals`
--
ALTER TABLE `leave_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_approvals_leave_permission_id_level_approved_by_unique` (`leave_permission_id`,`level`,`approved_by`),
  ADD KEY `leave_approvals_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_balances_worker_id_year_unique` (`worker_id`,`year`),
  ADD KEY `leave_balances_year_index` (`year`);

--
-- Indexes for table `leave_balance_logs`
--
ALTER TABLE `leave_balance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_balance_logs_leave_balance_id_foreign` (`leave_balance_id`),
  ADD KEY `leave_balance_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `leave_permissions`
--
ALTER TABLE `leave_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_permissions_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_permissions_approved_by_foreign` (`approved_by`),
  ADD KEY `leave_permissions_worker_id_status_index` (`worker_id`,`status`),
  ADD KEY `leave_permissions_worker_id_leave_year_index` (`worker_id`,`leave_year`);

--
-- Indexes for table `leave_status_logs`
--
ALTER TABLE `leave_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_status_logs_leave_permission_id_foreign` (`leave_permission_id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_types_created_by_foreign` (`created_by`),
  ADD KEY `leave_types_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `national_holidays`
--
ALTER TABLE `national_holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `national_holidays_date_unique` (`date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_payroll_number_unique` (`payroll_number`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `workers_user_id_unique` (`user_id`),
  ADD KEY `workers_department_id_foreign` (`department_id`),
  ADD KEY `workers_created_by_foreign` (`created_by`),
  ADD KEY `workers_updated_by_foreign` (`updated_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `department_approvers`
--
ALTER TABLE `department_approvers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extra_holidays`
--
ALTER TABLE `extra_holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leader_assignments`
--
ALTER TABLE `leader_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_approvals`
--
ALTER TABLE `leave_approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `leave_balance_logs`
--
ALTER TABLE `leave_balance_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_permissions`
--
ALTER TABLE `leave_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_status_logs`
--
ALTER TABLE `leave_status_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `national_holidays`
--
ALTER TABLE `national_holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `department_approvers`
--
ALTER TABLE `department_approvers`
  ADD CONSTRAINT `department_approvers_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_approvers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `department_approvers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `department_approvers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leader_assignments`
--
ALTER TABLE `leader_assignments`
  ADD CONSTRAINT `leader_assignments_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leader_assignments_leader_id_foreign` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leader_assignments_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_approvals`
--
ALTER TABLE `leave_approvals`
  ADD CONSTRAINT `leave_approvals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_approvals_leave_permission_id_foreign` FOREIGN KEY (`leave_permission_id`) REFERENCES `leave_permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balance_logs`
--
ALTER TABLE `leave_balance_logs`
  ADD CONSTRAINT `leave_balance_logs_leave_balance_id_foreign` FOREIGN KEY (`leave_balance_id`) REFERENCES `leave_balances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balance_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_permissions`
--
ALTER TABLE `leave_permissions`
  ADD CONSTRAINT `leave_permissions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_permissions_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  ADD CONSTRAINT `leave_permissions_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`);

--
-- Constraints for table `leave_status_logs`
--
ALTER TABLE `leave_status_logs`
  ADD CONSTRAINT `leave_status_logs_leave_permission_id_foreign` FOREIGN KEY (`leave_permission_id`) REFERENCES `leave_permissions` (`id`);

--
-- Constraints for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD CONSTRAINT `leave_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_types_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workers`
--
ALTER TABLE `workers`
  ADD CONSTRAINT `workers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
