-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 04, 2026 at 12:52 PM
-- Server version: 11.4.13-MariaDB-cll-lve-log
-- PHP Version: 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ljucoid_pbj`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `code` varchar(80) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` enum('asset','inventaris') NOT NULL DEFAULT 'asset',
  `sub_category` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `value` decimal(15,2) DEFAULT 0.00,
  `location_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `condition_status` enum('BAIK','RUSAK RINGAN','RUSAK BERAT') DEFAULT 'BAIK',
  `status` enum('AKTIF','RUSAK','DIPINDAHKAN','DIHAPUS','HILANG') DEFAULT 'AKTIF',
  `qr_file` varchar(255) DEFAULT NULL,
  `serial_number` varchar(120) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_disposals`
--

CREATE TABLE `asset_disposals` (
  `id` int(11) NOT NULL,
  `disposal_no` varchar(40) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `reason` enum('RUSAK BERAT','HILANG','TIDAK EKONOMIS','TIDAK DIPAKAI','LAINNYA') DEFAULT 'RUSAK BERAT',
  `description` text DEFAULT NULL,
  `status` enum('PENGAJUAN','VERIFIKASI','PERSETUJUAN','BERITA_ACARA','DIHAPUS') DEFAULT 'PENGAJUAN',
  `verified_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `ba_no` varchar(100) DEFAULT NULL,
  `ba_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_mutations`
--

CREATE TABLE `asset_mutations` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `from_unit` varchar(100) DEFAULT NULL,
  `from_location` varchar(150) DEFAULT NULL,
  `from_user` varchar(150) DEFAULT NULL,
  `to_unit` varchar(100) DEFAULT NULL,
  `to_location` varchar(150) DEFAULT NULL,
  `to_user` varchar(150) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `mutated_by` int(11) DEFAULT NULL,
  `mutated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `damage_reports`
--

CREATE TABLE `damage_reports` (
  `id` int(11) NOT NULL,
  `report_no` varchar(40) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `report_date` datetime DEFAULT current_timestamp(),
  `damage_type` varchar(80) DEFAULT 'Fisik',
  `damage_desc` text NOT NULL,
  `additional_note` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('DILAPORKAN','DIVERIFIKASI','DIAGNOSIS','PERBAIKAN','DALAM PERBAIKAN','SELESAI','DITOLAK') DEFAULT 'DILAPORKAN',
  `verified_by` int(11) DEFAULT NULL,
  `repair_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `damage_reports`
--

INSERT INTO `damage_reports` (`id`, `report_no`, `asset_id`, `item_name`, `location_id`, `reported_by`, `report_date`, `damage_type`, `damage_desc`, `additional_note`, `photo`, `status`, `verified_by`, `repair_id`, `created_at`, `updated_at`) VALUES
(2, 'KB-2026-0001', NULL, 'Papan tulis kaca', NULL, 24, '2026-11-03 19:04:45', 'Lainnya', 'Ppermintaan pemindahan papan tulis kaca ke dindang dekat meja rapat ruang bisnis', NULL, NULL, 'DILAPORKAN', NULL, NULL, '2026-11-03 12:04:45', '2026-11-03 12:04:45');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(80) DEFAULT 'Lainnya',
  `location_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `capacity` varchar(100) DEFAULT NULL,
  `status` enum('BAIK','RUSAK','DIPINJAM','BERMASALAH','PERBAIKAN','TIDAK AKTIF') DEFAULT 'BAIK',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facility_tickets`
--

CREATE TABLE `facility_tickets` (
  `id` int(11) NOT NULL,
  `ticket_no` varchar(40) NOT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `problem_type` varchar(80) DEFAULT NULL,
  `problem_desc` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `priority` enum('Rendah','Normal','Tinggi','Segera') DEFAULT 'Normal',
  `reported_by` int(11) NOT NULL,
  `status` enum('LAPORAN','VERIFIKASI','PENUGASAN','PERBAIKAN','PEMERIKSAAN','SELESAI') DEFAULT 'LAPORAN',
  `assigned_to` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `handovers`
--

CREATE TABLE `handovers` (
  `id` int(11) NOT NULL,
  `handover_no` varchar(40) NOT NULL,
  `ref_type` varchar(40) DEFAULT 'permintaan_atk',
  `ref_id` int(11) DEFAULT NULL,
  `from_user_id` int(11) DEFAULT NULL,
  `to_user_id` int(11) DEFAULT NULL,
  `handover_date` date NOT NULL,
  `items_desc` text DEFAULT NULL,
  `sig_giver` mediumtext DEFAULT NULL,
  `sig_receiver` mediumtext DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `handovers`
--

INSERT INTO `handovers` (`id`, `handover_no`, `ref_type`, `ref_id`, `from_user_id`, `to_user_id`, `handover_date`, `items_desc`, `sig_giver`, `sig_receiver`, `note`, `created_at`) VALUES
(4, 'ST-2026-0001', 'permintaan_atk', 3, 31, 8, '2026-10-23', 'Penyerahan barang sesuai Permintaan ATK No. PA-2026-0002', 'uploads/foto/ttd_20261023113240_dcc0f067.png', 'uploads/foto/ttd_20261023113240_924536df.png', '', '2026-10-23 04:32:40'),
(5, 'ST-2026-0002', 'permintaan_atk', 2, 31, 7, '2026-10-23', 'Penyerahan barang sesuai Permintaan ATK No. PA-2026-0001', 'uploads/foto/ttd_20261023113341_8e40123d.png', 'uploads/foto/ttd_20261023113341_63a0542f.png', '', '2026-10-23 04:33:41'),
(6, 'ST-2026-0003', 'permintaan_atk', 4, 31, 8, '2026-10-23', 'Penyerahan barang sesuai Permintaan ATK No. PA-2026-0003', 'uploads/foto/ttd_20261023114155_39bc1041.png', 'uploads/foto/ttd_20261023114155_5c244315.png', '', '2026-10-23 04:41:55');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `type` enum('gedung','lantai','ruang') NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `parent_id`, `type`, `name`, `code`, `description`, `is_active`, `created_at`) VALUES
(1, NULL, 'gedung', 'Gedung A', 'GA', NULL, 1, '2026-10-19 03:45:40'),
(2, 1, 'lantai', 'Lantai 1', 'GA-L1', NULL, 1, '2026-10-19 03:45:40'),
(3, 1, 'lantai', 'Lantai 2', 'GA-L2', NULL, 1, '2026-10-19 03:45:40'),
(4, 2, 'ruang', 'Ruang Direktur', 'DIR', NULL, 1, '2026-10-19 03:45:40'),
(5, 2, 'ruang', 'Ruang Keuangan', 'KEU', NULL, 1, '2026-10-19 03:45:40'),
(6, 2, 'ruang', 'Ruang Umum', 'UMUM', NULL, 1, '2026-10-19 03:45:40'),
(7, 3, 'ruang', 'Ruang IT', 'IT', NULL, 1, '2026-10-19 03:45:40'),
(8, 3, 'ruang', 'Ruang Operasional', 'OPS', NULL, 1, '2026-10-19 03:45:40'),
(9, 2, 'ruang', 'Ruang Sekretariat', 'SEKRET', '', 1, '2026-10-19 03:48:11'),
(10, 2, 'ruang', 'Ruang Infokom', 'INF', '', 1, '2026-10-19 03:48:27');

-- --------------------------------------------------------

--
-- Table structure for table `maintenances`
--

CREATE TABLE `maintenances` (
  `id` int(11) NOT NULL,
  `maint_no` varchar(40) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `damage_report_id` int(11) DEFAULT NULL,
  `damage_desc` text DEFAULT NULL,
  `maint_date` date NOT NULL,
  `vendor` varchar(200) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `action_taken` text DEFAULT NULL,
  `technician` varchar(150) DEFAULT NULL,
  `status` enum('TERENCANA','JANJI','PENGERJAAN','DALAM PERBAIKAN','SELESAI','BATAL') DEFAULT 'TERENCANA',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `needs_items`
--

CREATE TABLE `needs_items` (
  `id` int(11) NOT NULL,
  `need_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `category` enum('asset','inventaris','peralatan_kantor','sparepart','barang_habis_pakai') NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit` varchar(30) DEFAULT 'unit',
  `est_price` decimal(15,2) DEFAULT 0.00,
  `specification` text DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `needs_items`
--

INSERT INTO `needs_items` (`id`, `need_id`, `item_name`, `category`, `qty`, `unit`, `est_price`, `specification`, `note`) VALUES
(3, 2, 'Pena', 'barang_habis_pakai', 1, 'unit', 0.00, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `needs_requests`
--

CREATE TABLE `needs_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(40) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_bidang` varchar(100) DEFAULT NULL,
  `nama_divisi` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `needed_date` date DEFAULT NULL,
  `status` enum('DRAFT','DIAJUKAN','DIVERIFIKASI','DISETUJUI_KABAG','DALAM_PROSES_PENGADAAN','MASUK_RENCANA_PENGADAAN','FINALISASI_KEUANGAN','DITERIMA','DITOLAK') NOT NULL DEFAULT 'DRAFT',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `approved1_by` int(11) DEFAULT NULL,
  `approved1_at` datetime DEFAULT NULL,
  `approved2_by` int(11) DEFAULT NULL,
  `approved2_at` datetime DEFAULT NULL,
  `finance_by` int(11) DEFAULT NULL,
  `finance_at` datetime DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `needs_requests`
--

INSERT INTO `needs_requests` (`id`, `request_no`, `user_id`, `nama_bidang`, `nama_divisi`, `purpose`, `needed_date`, `status`, `verified_by`, `verified_at`, `approved1_by`, `approved1_at`, `approved2_by`, `approved2_at`, `finance_by`, `finance_at`, `rejected_reason`, `plan_id`, `created_at`, `updated_at`) VALUES
(2, 'KB-2026-0001', 8, 'Infokom', 'Bisnis', 'Kebutuhan Infokom', '2026-10-23', 'MASUK_RENCANA_PENGADAAN', 31, '2026-10-23 11:25:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-10-23 04:25:11', '2026-10-23 04:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_plans`
--

CREATE TABLE `procurement_plans` (
  `id` int(11) NOT NULL,
  `plan_no` varchar(40) NOT NULL,
  `plan_date` date NOT NULL,
  `requesting_units` varchar(255) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `priority` enum('Rendah','Sedang','Tinggi','Sangat Tinggi') DEFAULT 'Sedang',
  `status` enum('DRAFT','DIAJUKAN','DISSETUJUI_KADIV','DISETUJUI_DIREKTUR','FINAL_KEUANGAN','DALAM_PROSES_PENGADAAN','DITERIMA','DITOLAK') NOT NULL DEFAULT 'DRAFT',
  `approved1_by` int(11) DEFAULT NULL,
  `approved1_at` datetime DEFAULT NULL,
  `approved2_by` int(11) DEFAULT NULL,
  `approved2_at` datetime DEFAULT NULL,
  `finance_by` int(11) DEFAULT NULL,
  `finance_at` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `procurement_plans`
--

INSERT INTO `procurement_plans` (`id`, `plan_no`, `plan_date`, `requesting_units`, `budget`, `priority`, `status`, `approved1_by`, `approved1_at`, `approved2_by`, `approved2_at`, `finance_by`, `finance_at`, `note`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'RP-2026-0001', '2026-10-23', 'Infokom', 0.00, 'Sedang', 'DRAFT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 31, '2026-10-23 04:26:39', '2026-10-23 04:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_plan_items`
--

CREATE TABLE `procurement_plan_items` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `need_item_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `category` enum('asset','inventaris','peralatan_kantor','sparepart','barang_habis_pakai','jasa') DEFAULT 'asset',
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit` varchar(30) DEFAULT 'unit',
  `est_price` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `procurement_plan_items`
--

INSERT INTO `procurement_plan_items` (`id`, `plan_id`, `need_item_id`, `item_name`, `category`, `qty`, `unit`, `est_price`) VALUES
(1, 1, 3, 'Pena', 'barang_habis_pakai', 1, 'unit', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `receptions`
--

CREATE TABLE `receptions` (
  `id` int(11) NOT NULL,
  `receive_no` varchar(40) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `need_id` int(11) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `receive_date` date NOT NULL,
  `received_by` int(11) DEFAULT NULL,
  `status` enum('DITERIMA','DITERIMA SEBAGIAN','DITOLAK') NOT NULL DEFAULT 'DITERIMA',
  `chk_qty` tinyint(1) DEFAULT 0,
  `chk_spec` tinyint(1) DEFAULT 0,
  `chk_condition` tinyint(1) DEFAULT 0,
  `chk_docs` tinyint(1) DEFAULT 0,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `receptions`
--

INSERT INTO `receptions` (`id`, `receive_no`, `plan_id`, `need_id`, `supplier`, `invoice_no`, `receive_date`, `received_by`, `status`, `chk_qty`, `chk_spec`, `chk_condition`, `chk_docs`, `note`, `created_at`) VALUES
(2, 'PB-2026-0001', NULL, NULL, 'TEs', '000', '2026-10-23', 31, 'DITERIMA', 1, 1, 1, 1, 'Tes', '2026-10-23 04:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `reception_items`
--

CREATE TABLE `reception_items` (
  `id` int(11) NOT NULL,
  `reception_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `category` enum('asset','inventaris','peralatan_kantor','sparepart','barang_habis_pakai','jasa') DEFAULT 'asset',
  `qty_ordered` int(11) DEFAULT 1,
  `qty_received` int(11) DEFAULT 0,
  `unit` varchar(30) DEFAULT 'unit',
  `price` decimal(15,2) DEFAULT 0.00,
  `to_stock` tinyint(1) DEFAULT 0,
  `asset_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `reception_items`
--

INSERT INTO `reception_items` (`id`, `reception_id`, `item_name`, `category`, `qty_ordered`, `qty_received`, `unit`, `price`, `to_stock`, `asset_id`, `note`) VALUES
(3, 2, 'Kertas HVS 80 Gram', 'barang_habis_pakai', 10, 10, 'rim', 80000.00, 0, NULL, 'Tes');

-- --------------------------------------------------------

--
-- Table structure for table `role_mapping`
--

CREATE TABLE `role_mapping` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `app_role` enum('admin','staff_pengadaan','kepala_bidang','kepala_divisi','pimpinan','pegawai') NOT NULL DEFAULT 'pegawai',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `role_mapping`
--

INSERT INTO `role_mapping` (`id`, `user_id`, `app_role`, `created_at`, `updated_at`) VALUES
(1, 31, 'staff_pengadaan', '2026-10-23 04:04:11', '2026-10-23 04:07:04'),
(3, 7, 'staff_pengadaan', '2026-10-23 04:09:00', '2026-10-23 04:09:00');

-- --------------------------------------------------------

--
-- Table structure for table `stock_opnames`
--

CREATE TABLE `stock_opnames` (
  `id` int(11) NOT NULL,
  `opname_no` varchar(40) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `opname_date` date NOT NULL,
  `status` enum('OPEN','VALIDATED','REPORTED') DEFAULT 'OPEN',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stock_opnames`
--

INSERT INTO `stock_opnames` (`id`, `opname_no`, `title`, `location_id`, `unit`, `opname_date`, `status`, `created_by`, `created_at`) VALUES
(1, 'SO-2026-0001', '', NULL, '', '2026-10-23', 'OPEN', 31, '2026-10-23 04:11:20');

-- --------------------------------------------------------

--
-- Table structure for table `stock_opname_items`
--

CREATE TABLE `stock_opname_items` (
  `id` int(11) NOT NULL,
  `opname_id` int(11) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `scanned_code` varchar(120) DEFAULT NULL,
  `match_status` enum('COOK','MISSING','EXTRA') DEFAULT 'COOK',
  `checked_condition` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplies`
--

CREATE TABLE `supplies` (
  `id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` enum('atk','perlengkapan','sparepart','barang_habis_pakai','peralatan') DEFAULT 'atk',
  `unit` varchar(30) DEFAULT 'pcs',
  `stock` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 0,
  `stock_minimum_warning` tinyint(1) DEFAULT 0,
  `last_price` decimal(15,2) DEFAULT 0.00,
  `location_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `supplies`
--

INSERT INTO `supplies` (`id`, `code`, `name`, `category`, `unit`, `stock`, `min_stock`, `stock_minimum_warning`, `last_price`, `location_id`, `note`, `created_at`, `updated_at`) VALUES
(4, 'SPL-0002', 'Tinta Printer Warna', 'barang_habis_pakai', 'pcs', 2, 0, 0, 115000.00, 6, 'Tes', '2026-10-23 04:14:34', '2026-10-23 04:36:06'),
(5, 'SPL-0003', 'Pena', 'barang_habis_pakai', 'pcs', 40, 5, 0, 5000.00, NULL, '', '2026-10-23 04:17:15', '2026-10-24 02:14:02');

-- --------------------------------------------------------

--
-- Table structure for table `supply_requests`
--

CREATE TABLE `supply_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(40) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_bidang` varchar(100) DEFAULT NULL,
  `nama_divisi` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('MENUNGGU','DIPROSES','DISETUJUI','DISTRIBUSI','SELESAI','TIDAK_TERSEDIA','DITOLAK') DEFAULT 'MENUNGGU',
  `stock_available` tinyint(1) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `distributed_by` int(11) DEFAULT NULL,
  `handover_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `supply_requests`
--

INSERT INTO `supply_requests` (`id`, `request_no`, `user_id`, `nama_bidang`, `nama_divisi`, `purpose`, `status`, `stock_available`, `verified_by`, `approved_by`, `distributed_by`, `handover_id`, `created_at`, `updated_at`) VALUES
(2, 'PA-2026-0001', 7, 'Infokom', 'Bisnis', '', 'SELESAI', 1, 1, 31, 31, 5, '2026-10-23 04:28:52', '2026-10-23 04:34:01'),
(3, 'PA-2026-0002', 8, 'Infokom', 'Bisnis', '', 'SELESAI', 1, 31, 31, 31, 4, '2026-10-23 04:30:51', '2026-10-23 04:33:19'),
(4, 'PA-2026-0003', 8, 'Infokom', 'Bisnis', '', 'SELESAI', 1, 31, 31, 31, 6, '2026-10-23 04:41:15', '2026-10-23 04:43:18');

-- --------------------------------------------------------

--
-- Table structure for table `supply_request_items`
--

CREATE TABLE `supply_request_items` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `supply_id` int(11) NOT NULL,
  `qty_requested` int(11) DEFAULT 1,
  `qty_approved` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `supply_request_items`
--

INSERT INTO `supply_request_items` (`id`, `request_id`, `supply_id`, `qty_requested`, `qty_approved`) VALUES
(3, 2, 5, 20, 20),
(4, 3, 3, 1, 1),
(5, 4, 5, 10, 10);

-- --------------------------------------------------------

--
-- Table structure for table `supply_transactions`
--

CREATE TABLE `supply_transactions` (
  `id` int(11) NOT NULL,
  `supply_id` int(11) NOT NULL,
  `type` enum('MASUK','KELUAR','MUTASI') NOT NULL,
  `qty` int(11) NOT NULL,
  `ref_type` varchar(40) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `from_unit` varchar(100) DEFAULT NULL,
  `to_unit` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `supply_transactions`
--

INSERT INTO `supply_transactions` (`id`, `supply_id`, `type`, `qty`, `ref_type`, `ref_id`, `from_unit`, `to_unit`, `note`, `created_by`, `created_at`) VALUES
(6, 5, 'MASUK', 20, 'manual', NULL, NULL, 'Gudang Umum', 'tes', 31, '2026-10-23 04:22:03'),
(7, 5, 'MASUK', 50, 'manual', NULL, NULL, 'Gudang Umum', 'tes', 31, '2026-10-23 04:22:34'),
(8, 4, 'MASUK', 2, 'manual', NULL, NULL, 'Gudang Umum', '', 31, '2026-10-23 04:23:01'),
(10, 5, 'KELUAR', 20, 'serah_terima', 5, 'Gudang Umum', 'Ami Tri Cahyadi', 'Distribusi via ST-2026-0002', 31, '2026-10-23 04:33:41'),
(11, 5, 'KELUAR', 10, 'serah_terima', 6, 'Gudang Umum', 'Dodi Subarkah', 'Distribusi via ST-2026-0003', 31, '2026-10-23 04:41:55');

-- --------------------------------------------------------

--
-- Table structure for table `user_cache`
--

CREATE TABLE `user_cache` (
  `user_id` int(11) NOT NULL,
  `nik` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `nama_bidang` varchar(60) DEFAULT NULL,
  `nama_divisi` varchar(60) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `synced_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_cache`
--

INSERT INTO `user_cache` (`user_id`, `nik`, `name`, `email`, `phone`, `position`, `nama_bidang`, `nama_divisi`, `avatar`, `is_active`, `synced_at`) VALUES
(1, '1111111', 'Administrator', 'admin@admin.com', NULL, 'System Administrator', 'IT', 'Bisnis', 'avatar_1_1789024939.png', 1, '2026-10-19 03:46:12'),
(7, NULL, 'Ami Tri Cahyadi', 'cahyadi.ami@gmail.com', NULL, 'Staff', 'Infokom', 'Bisnis', 'avatar_6a7bfd28e9a4f_1786510632.PNG', 1, '2026-10-19 03:46:12'),
(8, NULL, 'Dodi Subarkah', 'chocovolverdz@gmail.com', NULL, 'Kepala Bidang', 'Infokom', 'Bisnis', 'avatar_8_1789358505.jpeg', 1, '2026-10-19 03:46:12'),
(11, NULL, 'Ventania Gusti Amelza Agung', 'gustiventania@gmail.com', NULL, 'Kepala Bidang', 'Legal', 'Sekretariat', 'avatar_11_1789359828.jpg', 1, '2026-10-19 03:46:12'),
(14, NULL, 'Innaya Rizky', 'innayarizky@gmail.com', NULL, 'Staff', 'Kesekretariatan', 'Sekretariat', NULL, 1, '2026-10-19 03:46:12'),
(15, NULL, 'OKTAVIANUS YULIA, S.T.', NULL, NULL, 'Direktur Utama', 'Direksi', 'Direksi', NULL, 1, '2026-10-19 03:46:12'),
(16, NULL, 'AMRI ZAMANI', 'amri@lju.co.id', NULL, 'Direktur Operasional', 'Direksi', 'Direksi', 'avatar_16_1790843558.png', 1, '2026-10-19 03:46:12'),
(17, NULL, 'Ahmad Marulloh', NULL, NULL, 'Kepala Divisi', NULL, 'Keuangan & Umum', NULL, 1, '2026-10-19 03:46:12'),
(18, NULL, 'Abdul Yamin', NULL, '', 'Kepala Divisi', NULL, 'Bisnis', NULL, 1, '2026-10-22 07:39:53'),
(19, NULL, 'Edi Wahyudi', NULL, NULL, 'Corporate Secretary', NULL, 'Sekretariat', NULL, 1, '2026-10-19 03:46:12'),
(20, NULL, 'Iqbal Prayudha', NULL, NULL, 'Kepala Bidang', 'Keuangan', 'Keuangan & Umum', NULL, 1, '2026-10-19 03:46:12'),
(21, NULL, 'Dewi Safitri', '02dewisafitri@gmail.com', NULL, 'Kepala Bidang', 'SDM', 'Sekretariat', NULL, 1, '2026-10-19 03:46:12'),
(22, '199003012016121005', 'Iqbal Damara', 'iqbaldamara90@gmail.com', NULL, 'Kepala Bidang', 'Jasa Konstruksi', 'Bisnis', 'avatar_22_9cba61a4f91bcdd5.jpg', 1, '2026-10-23 03:47:43'),
(23, NULL, 'Arya Jaya Putra Sumbahan', 'aryajaya72606@gmail.com', NULL, 'Kepala Bidang', 'Pengangkutan & Pergudangan', 'Bisnis', 'avatar_6ab365b895706_1790141880.png', 1, '2026-10-19 03:46:12'),
(24, NULL, 'M Rido Radiansyah', 'radiansyahrido@gmail.com', NULL, 'Kepala Bidang', 'Perdagangan', 'Bisnis', NULL, 1, '2026-10-19 03:46:12'),
(25, NULL, 'Mitha Aprilia', NULL, NULL, 'Staff', 'Jasa Konstruksi', 'Bisnis', NULL, 1, '2026-10-19 03:46:12'),
(26, NULL, 'Aldi Aditya', NULL, NULL, 'Staff', 'Pengangkutan & Pergudangan', 'Bisnis', NULL, 1, '2026-10-19 03:46:12'),
(28, NULL, 'M Mukhtaridi.R.A', 'muhammadmuktaridiratuagung@gmail.com', NULL, 'Staff', 'Perdagangan', 'Bisnis', 'avatar_6ab337750b818_1790130037.jpg', 1, '2026-10-19 03:46:12'),
(29, NULL, 'M Adrian Gumelar', NULL, NULL, 'Kepala Bidang', 'Pengadaan', 'Sekretariat', NULL, 1, '2026-10-19 03:46:12'),
(30, NULL, 'Afrilia Damayanti', NULL, NULL, 'Sekretaris Direksi', 'Kesekretariatan', 'Sekretariat', NULL, 1, '2026-10-19 03:46:12'),
(31, NULL, 'Adhi Mulia', NULL, NULL, 'Staff', 'Pengadaan', 'Sekretariat', NULL, 1, '2026-10-19 03:46:12'),
(32, NULL, 'Dede Maysaroh', 'dedemaysaroh24@gmail.com', NULL, 'Sekretaris Komisaris', 'Kesekretariatan', 'Sekretariat', NULL, 1, '2026-10-19 03:46:12'),
(33, NULL, 'Muhammad Nico Santana', NULL, NULL, 'Staff', 'Perdagangan', 'Bisnis', NULL, 1, '2026-10-19 03:46:12'),
(34, NULL, 'Kharasia Fatra P', 'kharasiafp@gmail.com', NULL, 'Staff', 'Keuangan', 'Keuangan & Umum', NULL, 1, '2026-10-19 03:46:12'),
(35, '', 'dummy HRs', NULL, NULL, 'Kepala Bidang', 'SDM', 'Sekretariat', 'avatar_35_915e354d157d9f93.jpg', 1, '2026-10-19 03:46:12'),
(36, NULL, 'dummyFIN2', NULL, NULL, 'Kepala Divisi', 'Keuangan', 'Keuangan & Umum', 'avatar_36_c8f052f454fd4c1e.jpg', 1, '2026-10-19 03:46:12'),
(37, NULL, 'dummyDO', NULL, NULL, 'Direktur Operasional', 'Direksi', 'Direksi', NULL, 1, '2026-10-19 03:46:12'),
(38, NULL, 'dummyKD', NULL, NULL, 'Kepala Divisi', NULL, 'Bisnis', NULL, 1, '2026-10-19 03:46:12');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `tnkb` varchar(20) NOT NULL,
  `brand_model` varchar(120) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `frame_no` varchar(60) DEFAULT NULL,
  `engine_no` varchar(60) DEFAULT NULL,
  `condition_status` enum('BAIK','RUSAK RINGAN','RUSAK BERAT') DEFAULT 'BAIK',
  `location` varchar(120) DEFAULT NULL,
  `pic` varchar(150) DEFAULT NULL,
  `status` enum('TERSEDIA','DIPINJAM','DIGUNAKAN','SERVICE','RUSAK','NON OPERASIONAL') DEFAULT 'TERSEDIA',
  `last_km` int(11) DEFAULT 0,
  `service_interval_km` int(11) DEFAULT 10000,
  `next_service_km` int(11) DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_loans`
--

CREATE TABLE `vehicle_loans` (
  `id` int(11) NOT NULL,
  `loan_no` varchar(40) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nik` varchar(50) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `driver` varchar(120) DEFAULT NULL,
  `passengers` text DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `start_km` int(11) DEFAULT NULL,
  `end_km` int(11) DEFAULT NULL,
  `fuel_cost` decimal(15,2) DEFAULT 0.00,
  `proof_file` varchar(255) DEFAULT NULL,
  `return_condition` text DEFAULT NULL,
  `return_note` text DEFAULT NULL,
  `status` enum('MENUNGGU','DISETUJUI','DITOLAK','DISERAHKAN','DIGUNAKAN','DIKEMBALIKAN','SELESAI') NOT NULL DEFAULT 'MENUNGGU',
  `approved_by` int(11) DEFAULT NULL,
  `approve_note` text DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `handover_out_id` int(11) DEFAULT NULL,
  `handover_in_id` int(11) DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `km_start` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `return_datetime` datetime DEFAULT NULL,
  `km_end` int(10) UNSIGNED DEFAULT NULL,
  `return_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_services`
--

CREATE TABLE `vehicle_services` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `service_date` date NOT NULL,
  `service_type` varchar(120) DEFAULT NULL,
  `km_at_service` int(11) DEFAULT NULL,
  `vendor` varchar(200) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_usage_logs`
--

CREATE TABLE `vehicle_usage_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `destination` varchar(200) DEFAULT NULL,
  `borrow_date` datetime DEFAULT NULL,
  `return_date` datetime DEFAULT NULL,
  `start_km` int(11) DEFAULT 0,
  `end_km` int(11) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'DIGUNAKAN',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `asset_disposals`
--
ALTER TABLE `asset_disposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `disposal_no` (`disposal_no`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `asset_mutations`
--
ALTER TABLE `asset_mutations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `damage_reports`
--
ALTER TABLE `damage_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_no` (`report_no`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `facility_tickets`
--
ALTER TABLE `facility_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_no` (`ticket_no`);

--
-- Indexes for table `handovers`
--
ALTER TABLE `handovers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `handover_no` (`handover_no`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `maintenances`
--
ALTER TABLE `maintenances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `maint_no` (`maint_no`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `needs_items`
--
ALTER TABLE `needs_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `need_id` (`need_id`);

--
-- Indexes for table `needs_requests`
--
ALTER TABLE `needs_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`);

--
-- Indexes for table `procurement_plans`
--
ALTER TABLE `procurement_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_no` (`plan_no`);

--
-- Indexes for table `procurement_plan_items`
--
ALTER TABLE `procurement_plan_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `receptions`
--
ALTER TABLE `receptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receive_no` (`receive_no`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `need_id` (`need_id`);

--
-- Indexes for table `reception_items`
--
ALTER TABLE `reception_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reception_id` (`reception_id`);

--
-- Indexes for table `role_mapping`
--
ALTER TABLE `role_mapping`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `opname_no` (`opname_no`);

--
-- Indexes for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `opname_id` (`opname_id`);

--
-- Indexes for table `supplies`
--
ALTER TABLE `supplies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `supply_requests`
--
ALTER TABLE `supply_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_no` (`request_no`);

--
-- Indexes for table `supply_request_items`
--
ALTER TABLE `supply_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `supply_transactions`
--
ALTER TABLE `supply_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supply_id` (`supply_id`);

--
-- Indexes for table `user_cache`
--
ALTER TABLE `user_cache`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_loans`
--
ALTER TABLE `vehicle_loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_no` (`loan_no`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `vehicle_services`
--
ALTER TABLE `vehicle_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `vehicle_usage_logs`
--
ALTER TABLE `vehicle_usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `asset_disposals`
--
ALTER TABLE `asset_disposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_mutations`
--
ALTER TABLE `asset_mutations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `damage_reports`
--
ALTER TABLE `damage_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `facility_tickets`
--
ALTER TABLE `facility_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `handovers`
--
ALTER TABLE `handovers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `maintenances`
--
ALTER TABLE `maintenances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `needs_items`
--
ALTER TABLE `needs_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `needs_requests`
--
ALTER TABLE `needs_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `procurement_plans`
--
ALTER TABLE `procurement_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `procurement_plan_items`
--
ALTER TABLE `procurement_plan_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `receptions`
--
ALTER TABLE `receptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reception_items`
--
ALTER TABLE `reception_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role_mapping`
--
ALTER TABLE `role_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplies`
--
ALTER TABLE `supplies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `supply_requests`
--
ALTER TABLE `supply_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supply_request_items`
--
ALTER TABLE `supply_request_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `supply_transactions`
--
ALTER TABLE `supply_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vehicle_loans`
--
ALTER TABLE `vehicle_loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vehicle_services`
--
ALTER TABLE `vehicle_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_usage_logs`
--
ALTER TABLE `vehicle_usage_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_disposals`
--
ALTER TABLE `asset_disposals`
  ADD CONSTRAINT `asset_disposals_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_mutations`
--
ALTER TABLE `asset_mutations`
  ADD CONSTRAINT `asset_mutations_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `maintenances`
--
ALTER TABLE `maintenances`
  ADD CONSTRAINT `maintenances_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `needs_items`
--
ALTER TABLE `needs_items`
  ADD CONSTRAINT `needs_items_ibfk_1` FOREIGN KEY (`need_id`) REFERENCES `needs_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `procurement_plan_items`
--
ALTER TABLE `procurement_plan_items`
  ADD CONSTRAINT `procurement_plan_items_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `procurement_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receptions`
--
ALTER TABLE `receptions`
  ADD CONSTRAINT `receptions_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `procurement_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `receptions_ibfk_2` FOREIGN KEY (`need_id`) REFERENCES `needs_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reception_items`
--
ALTER TABLE `reception_items`
  ADD CONSTRAINT `reception_items_ibfk_1` FOREIGN KEY (`reception_id`) REFERENCES `receptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  ADD CONSTRAINT `stock_opname_items_ibfk_1` FOREIGN KEY (`opname_id`) REFERENCES `stock_opnames` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_request_items`
--
ALTER TABLE `supply_request_items`
  ADD CONSTRAINT `supply_request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `supply_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_transactions`
--
ALTER TABLE `supply_transactions`
  ADD CONSTRAINT `supply_transactions_ibfk_1` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_loans`
--
ALTER TABLE `vehicle_loans`
  ADD CONSTRAINT `vehicle_loans_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `vehicle_services`
--
ALTER TABLE `vehicle_services`
  ADD CONSTRAINT `vehicle_services_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_usage_logs`
--
ALTER TABLE `vehicle_usage_logs`
  ADD CONSTRAINT `vehicle_usage_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
