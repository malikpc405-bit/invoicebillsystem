-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 07:14 AM
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
-- Database: `invoicebill`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `bill_number` varchar(100) DEFAULT NULL,
  `bill_date` varchar(50) DEFAULT NULL,
  `bill_time` varchar(50) DEFAULT NULL,
  `salesman` varchar(100) DEFAULT NULL,
  `sub_total` decimal(12,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment` decimal(12,2) DEFAULT 0.00,
  `change_amount` decimal(12,2) DEFAULT 0.00,
  `total_profit` decimal(12,2) DEFAULT 0.00,
  `bill_type` varchar(50) DEFAULT 'Sell',
  `user_id` int(11) DEFAULT 0,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `bill_number`, `bill_date`, `bill_time`, `salesman`, `sub_total`, `grand_total`, `created_at`, `payment`, `change_amount`, `total_profit`, `bill_type`, `user_id`, `discount_percent`, `discount_amount`) VALUES
(68, '1000', '23/05/2026', '06:41:35 PM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-23 13:41:35', 6000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(69, '1001', '23/05/2026', '06:41:47 PM', 'Aslam Malik', 400.00, 400.00, '2026-05-23 13:41:48', 400.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(70, '1002', '23/05/2026', '06:44:19 PM', 'Aslam Malik', 1000.00, 1000.00, '2026-05-23 13:44:19', 1000.00, 0.00, 300.00, 'Sell', 0, 0.00, 0.00),
(71, '1003', '23/05/2026', '06:44:27 PM', 'Aslam Malik', 1500.00, 1500.00, '2026-05-23 13:44:28', 1500.00, 0.00, 450.00, 'Sell', 0, 0.00, 0.00),
(72, '1004', '23/05/2026', '06:44:38 PM', 'Aslam Malik', 2000.00, 2000.00, '2026-05-23 13:44:39', 2000.00, 0.00, 600.00, 'Sell', 0, 0.00, 0.00),
(73, '1005', '23/05/2026', '06:44:48 PM', 'Aslam Malik', 500.00, 500.00, '2026-05-23 13:44:49', 500.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(74, '1006', '23/05/2026', '06:45:03 PM', 'Aslam Malik', 5000.00, 5000.00, '2026-05-23 13:45:03', 5000.00, 0.00, 500.00, 'Sell', 0, 0.00, 0.00),
(75, '1007', '23/05/2026', '06:45:15 PM', 'Aslam Malik', 500.00, 500.00, '2026-05-23 13:45:16', 500.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(76, '1008', '23/05/2026', '06:45:21 PM', 'Aslam Malik', 1000.00, 1000.00, '2026-05-23 13:45:22', 1000.00, 0.00, 300.00, 'Sell', 0, 0.00, 0.00),
(78, '1010', '23/05/2026', '06:45:33 PM', 'Aslam Malik', 5000.00, 5000.00, '2026-05-23 13:45:34', 2000.00, 0.00, 600.00, 'Sell', 0, 0.00, 0.00),
(80, '1011', '24/05/2026', '08:36:56 AM', 'Aslam Malik', 500.00, 500.00, '2026-05-24 03:36:56', 500.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(81, '1012', '24/05/2026', '08:39:02 AM', 'Aslam Malik', 400.00, 400.00, '2026-05-24 03:39:03', 400.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(82, '1013', '24/05/2026', '08:40:11 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 03:40:13', 6000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(83, '1014', '24/05/2026', '08:41:27 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 03:41:28', 6000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(84, '1015', '24/05/2026', '08:53:42 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 03:53:42', 6000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(85, '1016', '24/05/2026', '09:01:42 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 04:01:43', 6000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(86, '1017', '24/05/2026', '09:08:57 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 04:08:58', 6000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(87, '1018', '24/05/2026', '09:14:03 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 04:14:04', 6000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(88, '1019', '24/05/2026', '09:22:27 AM', 'Aslam Malik', 3499.00, 3499.00, '2026-05-24 04:22:28', 3499.00, 0.00, 1499.00, 'Sell', 0, 0.00, 0.00),
(89, '1020', '24/05/2026', '09:22:30 AM', 'Aslam Malik', 3499.00, 3499.00, '2026-05-24 04:22:31', 3499.00, 0.00, 1499.00, 'Sell', 0, 0.00, 0.00),
(90, '1021', '24/05/2026', '09:22:31 AM', 'Aslam Malik', 3499.00, 3499.00, '2026-05-24 04:22:32', 3499.00, 0.00, 1499.00, 'Sell', 0, 0.00, 0.00),
(91, '1021', '24/05/2026', '09:22:31 AM', 'Aslam Malik', 3499.00, 3499.00, '2026-05-24 04:22:32', 3499.00, 0.00, 1499.00, 'Sell', 0, 0.00, 0.00),
(92, '1023', '24/05/2026', '09:22:31 AM', 'Aslam Malik', 3499.00, 3499.00, '2026-05-24 04:22:32', 3499.00, 0.00, 1499.00, 'Sell', 0, 0.00, 0.00),
(93, '1024', '24/05/2026', '09:22:31 AM', 'Aslam Malik', 3499.00, 3499.00, '2026-05-24 04:22:32', 3499.00, 0.00, 1499.00, 'Sell', 0, 0.00, 0.00),
(94, '1025', '24/05/2026', '09:24:03 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 04:24:04', 6000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(95, '1026', '24/05/2026', '09:32:04 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 04:32:05', 6000.00, 0.00, 4000.00, 'Sell', 0, 0.00, 0.00),
(96, '1027', '24/05/2026', '09:36:27 AM', 'Aslam Malik', 5000.00, 5000.00, '2026-05-24 04:36:28', 5000.00, 0.00, 1600.00, 'Sell', 0, 0.00, 0.00),
(97, '1028', '24/05/2026', '09:45:33 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 04:45:34', 6000.00, 0.00, 4000.00, 'Sell', 0, 0.00, 0.00),
(98, '1029', '24/05/2026', '09:46:26 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-24 04:46:27', 6000.00, 0.00, 4000.00, 'Sell', 0, 0.00, 0.00),
(99, '1030', '24/05/2026', '09:57:00 AM', 'Aslam Malik', 5000.00, 5000.00, '2026-05-24 04:57:00', 5000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(100, '1031', '24/05/2026', '10:13:40 AM', 'Aslam Malik', 500.00, 500.00, '2026-05-24 05:13:41', 500.00, 0.00, 200.00, 'Sell', 0, 0.00, 0.00),
(101, '1032', '24/05/2026', '10:18:02 AM', 'Aslam Malik', 600.00, 600.00, '2026-05-24 05:18:03', 600.00, 0.00, 300.00, 'Sell', 0, 0.00, 0.00),
(102, '1033', '24/05/2026', '06:14:41 PM', 'Aslam Malik', 500.00, 500.00, '2026-05-24 13:14:41', 500.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(103, '1034', '24/05/2026', '10:06:34 PM', 'Aslam Malik', 5000.00, 5000.00, '2026-05-24 17:06:34', 5000.00, 0.00, 1000.00, 'Sell', 0, 0.00, 0.00),
(104, '1035', '25/05/2026', '09:13:20 AM', 'Aslam Malik', 500.00, 500.00, '2026-05-25 04:13:21', 500.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(105, '1036', '25/05/2026', '09:20:41 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-25 04:20:42', 6000.00, 0.00, 4000.00, 'Sell', 0, 0.00, 0.00),
(106, '1037', '25/05/2026', '09:34:03 AM', 'Aslam Malik', 6000.00, 6000.00, '2026-05-25 04:34:03', 6000.00, 0.00, 4000.00, 'Sell', 0, 0.00, 0.00),
(107, '1038', '25/05/2026', '09:38:12 AM', 'Aslam Malik', 1000.00, 1000.00, '2026-05-25 04:38:13', 1000.00, 0.00, 400.00, 'Sell', 0, 0.00, 0.00),
(108, '1039', '26/05/2026', '10:08:33 AM', 'Aslam Malik', 300.00, 300.00, '2026-05-26 05:08:33', 300.00, 0.00, 300.00, 'Sell', 0, 0.00, 0.00),
(109, '1040', '26/05/2026', '10:39:16 AM', 'Aslam Malik', 400.00, 400.00, '2026-05-26 05:39:16', 400.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(110, '1041', '26/05/2026', '10:39:33 AM', 'Aslam Malik', 800.00, 800.00, '2026-05-26 05:39:33', 800.00, 0.00, 300.00, 'Sell', 0, 0.00, 0.00),
(111, '1042', '26/05/2026', '06:32:12 PM', 'Aslam Malik', 1000.00, 1000.00, '2026-05-26 13:32:13', 1000.00, 0.00, 300.00, 'Sell', 0, 0.00, 0.00),
(112, '1043', '26/05/2026', '06:38:33 PM', 'Aslam Malik', 240.00, 240.00, '2026-05-26 13:38:34', 240.00, 0.00, 40.00, 'Sell', 0, 0.00, 0.00),
(113, '1044', '27/05/2026', '02:19:10 PM', 'Aslam Malik', 800.00, 800.00, '2026-05-27 09:19:11', 800.00, 0.00, 450.00, 'Sell', 0, 0.00, 0.00),
(114, '1045', '27/05/2026', '02:19:26 PM', 'Aslam Malik', 1600.00, 1600.00, '2026-05-27 09:19:27', 1600.00, 0.00, 900.00, 'Sell', 0, 0.00, 0.00),
(115, '1046', '27/05/2026', '02:30:17 PM', 'Aslam Malik', 400.00, 400.00, '2026-05-27 09:30:18', 400.00, 0.00, 150.00, 'Sell', 0, 0.00, 0.00),
(116, '1000', '28/05/2026', '10:19:47 PM', 'Aslam Malik', 3499.00, 3499.00, '2026-05-28 17:19:49', 3499.00, 0.00, 1499.00, 'Sell', 1, 0.00, 0.00),
(122, '1001', '02/06/2026', '05:42:52 AM', 'khan', 800.00, 800.00, '2026-06-02 00:42:53', 800.00, 0.00, 450.00, 'Sell', 1, 0.00, 0.00),
(123, '1002', '02/06/2026', '05:45:12 AM', 'khan', 150.00, 150.00, '2026-06-02 00:45:12', 150.00, 0.00, 30.00, 'Sell', 1, 0.00, 0.00),
(124, '1003', '02/06/2026', '05:50:20 AM', 'khan', 150.00, 150.00, '2026-06-02 00:50:21', 150.00, 0.00, 30.00, 'Sell', 1, 0.00, 0.00),
(125, '1004', '02/06/2026', '05:50:52 AM', 'khan', 1200.00, 1200.00, '2026-06-02 00:50:53', 1200.00, 0.00, 530.00, 'Sell', 1, 0.00, 0.00),
(126, '1005', '04/06/2026', '09:39:25 PM', 'Aslam Malik', 1200.00, 1200.00, '2026-06-04 16:39:26', 1200.00, 0.00, 530.00, 'Sell', 1, 0.00, 0.00),
(127, '1006', '04/06/2026', '09:48:45 PM', 'Aslam Malik', 2700.00, 2700.00, '2026-06-04 16:48:46', 2700.00, 0.00, 1110.00, 'Sell', 1, 0.00, 0.00),
(128, '1007', '04/06/2026', '09:58:41 PM', 'Aslam Malik', 2304.00, 2304.00, '2026-06-04 16:58:41', 2304.00, 0.00, 1254.00, 'Sell', 1, 0.00, 0.00),
(129, '1008', '05/06/2026', '02:24:33 PM', 'Aslam Malik', 300.00, 300.00, '2026-06-05 09:24:33', 300.00, 0.00, 50.00, 'Sell', 1, 0.00, 0.00),
(130, '1009', '25/08/2026', '11:57:41 PM', 'Aslam Malik', 400.00, 400.00, '2026-08-25 18:57:42', 400.00, 0.00, 150.00, 'Sell', 1, 0.00, 0.00),
(131, '1010', '25/08/2026', '11:57:51 PM', 'Aslam Malik', 1200.00, 1200.00, '2026-08-25 18:57:52', 1200.00, 0.00, 600.00, 'Sell', 1, 0.00, 0.00),
(132, '1011', '09/09/2026', '01:10:20 PM', 'Aslam Malik', 1750.00, 1750.00, '2026-09-09 08:10:20', 1750.00, 0.00, 850.00, 'Sell', 1, 0.00, 0.00),
(133, '1012', '09/09/2026', '01:10:52 PM', 'Aslam Malik', 1100.00, 1100.00, '2026-09-09 08:10:52', 1100.00, 0.00, 200.00, 'Sell', 1, 0.00, 0.00),
(134, '1013', '09/09/2026', '10:05:27 PM', 'Aslam Malik', 300.00, 300.00, '2026-09-09 17:05:28', 300.00, 0.00, 100.00, 'Sell', 1, 0.00, 0.00),
(135, '1014', '09/09/2026', '10:05:50 PM', 'Aslam Malik', 800.00, 800.00, '2026-09-09 17:05:51', 800.00, 0.00, 300.00, 'Sell', 1, 0.00, 0.00),
(136, '1015', '09/09/2026', '10:16:46 PM', 'Aslam Malik', 500.00, 500.00, '2026-09-09 17:16:47', 500.00, 0.00, 200.00, 'Sell', 1, 0.00, 0.00),
(137, '1016', '10/09/2026', '07:36:40 PM', 'Aslam Malik', 2350.00, 2350.00, '2026-09-10 14:36:40', 2350.00, 0.00, 550.00, 'Sell', 1, 0.00, 0.00),
(138, '1017', '10/09/2026', '07:43:35 PM', 'Aslam Malik', 320.00, 320.00, '2026-09-10 14:43:36', 320.00, 0.00, 120.00, 'Sell', 1, 0.00, 0.00),
(139, '1018', '10/09/2026', '07:53:45 PM', 'Aslam Malik', 100.00, 100.00, '2026-09-10 14:53:46', 100.00, 0.00, 30.00, 'Sell', 1, 0.00, 0.00),
(140, '1019', '10/09/2026', '08:32:41 PM', 'Aslam Malik', 6400.00, 6400.00, '2026-09-10 15:32:42', 6400.00, 0.00, 1130.00, 'Sell', 1, 0.00, 0.00),
(141, '1020', '10/09/2026', '08:46:50 PM', 'Aslam Malik', 2050.00, 2050.00, '2026-09-10 15:46:51', 2050.00, 0.00, 550.00, 'Sell', 1, 0.00, 0.00),
(142, '1021', '11/09/2026', '02:39:34 AM', 'Aslam Malik', 320.00, 320.00, '2026-09-10 21:39:35', 320.00, 0.00, 120.00, 'Sell', 1, 0.00, 0.00),
(143, '1021', '11/09/2026', '02:39:45 AM', 'Aslam Malik', 820.00, 820.00, '2026-09-10 21:39:46', 820.00, 0.00, 320.00, 'Sell', 1, 0.00, 0.00),
(144, '1022', '11/09/2026', '02:39:52 AM', 'Aslam Malik', 1320.00, 1320.00, '2026-09-10 21:39:53', 1320.00, 0.00, 520.00, 'Sell', 1, 0.00, 0.00),
(145, '1023', '11/09/2026', '02:40:01 AM', 'Aslam Malik', 320.00, 320.00, '2026-09-10 21:40:01', 320.00, 0.00, 120.00, 'Sell', 1, 0.00, 0.00),
(147, '1024', '15/09/2026', '12:17:35 PM', 'Aslam Malik', 500.00, 500.00, '2026-09-15 07:17:36', 500.00, 0.00, 200.00, 'Sell', 1, 0.00, 0.00),
(148, '1025', '15/09/2026', '12:18:13 PM', 'Aslam Malik', 500.00, 500.00, '2026-09-15 07:18:13', 500.00, 0.00, 200.00, 'Sell', 1, 0.00, 0.00),
(149, '1026', '15/09/2026', '12:18:51 PM', 'Aslam Malik', 1000.00, 1000.00, '2026-09-15 07:18:51', 1000.00, 0.00, 400.00, 'Sell', 1, 0.00, 0.00),
(150, '1027', '20/10/2026', '10:17:58 AM', 'Aslam Malik', 640.00, 640.00, '2026-10-20 05:18:00', 640.00, 0.00, 240.00, 'Sell', 1, 0.00, 0.00),
(151, '1028', '23/10/2026', '06:48:55 PM', 'Aslam Malik', 500.00, 500.00, '2026-10-23 13:48:56', 500.00, 0.00, 300.00, 'Sell', 1, 0.00, 0.00),
(152, '1029', '23/10/2026', '06:50:01 PM', 'Aslam Malik', 750.00, 750.00, '2026-10-23 13:50:01', 750.00, 0.00, 550.00, 'Sell', 1, 0.00, 0.00),
(153, '1030', '24/10/2026', '09:24:31 AM', 'Aslam Malik', 500.00, 500.00, '2026-10-24 04:24:32', 500.00, 0.00, 200.00, 'Sell', 1, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT 0.00,
  `quantity` int(11) DEFAULT 0,
  `amount` decimal(12,2) DEFAULT 0.00,
  `purchase_price` decimal(12,2) DEFAULT 0.00,
  `profit` decimal(12,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`id`, `bill_id`, `description`, `size`, `price`, `quantity`, `amount`, `purchase_price`, `profit`, `discount_percent`, `image`) VALUES
(76, 68, 'D-3T', '43', 6000.00, 1, 6000.00, 5000.00, 1000.00, 0.00, ''),
(77, 69, 'LUX', '', 400.00, 1, 400.00, 250.00, 150.00, 0.00, ''),
(78, 70, 'B-Rose', '', 500.00, 2, 1000.00, 350.00, 300.00, 0.00, ''),
(79, 71, 'B-Rose', '', 500.00, 3, 1500.00, 350.00, 450.00, 0.00, ''),
(80, 72, 'B-Rose', '', 500.00, 4, 2000.00, 350.00, 600.00, 0.00, ''),
(81, 73, 'B-Rose', '', 500.00, 1, 500.00, 350.00, 150.00, 0.00, ''),
(82, 74, 'D3-TTQ', '', 5000.00, 1, 5000.00, 4500.00, 500.00, 0.00, ''),
(83, 75, 'Pert', '', 500.00, 1, 500.00, 350.00, 150.00, 0.00, ''),
(84, 76, 'Pert', '', 500.00, 2, 1000.00, 350.00, 300.00, 0.00, ''),
(86, 78, 'Pert', '', 500.00, 10, 5000.00, 350.00, 600.00, 0.00, ''),
(88, 80, 'B-Rose', '', 500.00, 1, 500.00, 350.00, 150.00, 0.00, ''),
(89, 81, 'LUX', '', 400.00, 1, 400.00, 250.00, 150.00, 0.00, ''),
(90, 82, 'D-3T', '43', 6000.00, 1, 6000.00, 5000.00, 1000.00, 0.00, ''),
(91, 83, 'D-3T', '43', 6000.00, 1, 6000.00, 5000.00, 1000.00, 0.00, ''),
(92, 84, 'D-3T', '43', 6000.00, 1, 6000.00, 5000.00, 1000.00, 0.00, ''),
(93, 85, 'D-3T', '43', 6000.00, 1, 6000.00, 5000.00, 1000.00, 0.00, ''),
(94, 86, 'D2-WE', '43', 6000.00, 1, 6000.00, 5000.00, 1000.00, 0.00, ''),
(95, 87, 'D-3T', '43', 6000.00, 1, 6000.00, 5000.00, 1000.00, 0.00, ''),
(96, 88, 'W-D47 BLACK', '41', 3499.00, 1, 3499.00, 2000.00, 1499.00, 0.00, ''),
(97, 89, 'W-D47 BLACK', '41', 3499.00, 1, 3499.00, 2000.00, 1499.00, 0.00, ''),
(98, 90, 'W-D47 BLACK', '41', 3499.00, 1, 3499.00, 2000.00, 1499.00, 0.00, ''),
(99, 91, 'W-D47 BLACK', '41', 3499.00, 1, 3499.00, 2000.00, 1499.00, 0.00, ''),
(100, 92, 'W-D47 BLACK', '41', 3499.00, 1, 3499.00, 2000.00, 1499.00, 0.00, ''),
(101, 93, 'W-D47 BLACK', '41', 3499.00, 1, 3499.00, 2000.00, 1499.00, 0.00, ''),
(102, 94, 'D-3T', '43', 6000.00, 1, 6000.00, 5000.00, 1000.00, 0.00, ''),
(103, 95, 'Apple', '', 6000.00, 1, 6000.00, 2000.00, 4000.00, 0.00, ''),
(104, 96, 'D2-w', '', 5000.00, 1, 5000.00, 3400.00, 1600.00, 0.00, ''),
(105, 97, 'Apple', '', 6000.00, 1, 6000.00, 2000.00, 4000.00, 0.00, ''),
(106, 98, 'Apple', '', 6000.00, 1, 6000.00, 2000.00, 4000.00, 0.00, ''),
(107, 99, 'Bata', '43', 5000.00, 1, 5000.00, 4000.00, 1000.00, 0.00, ''),
(108, 100, 'BBBB', '', 500.00, 1, 500.00, 300.00, 200.00, 0.00, ''),
(109, 101, 'B2', '', 300.00, 2, 600.00, 150.00, 300.00, 0.00, ''),
(110, 102, 'B-Rose', '', 500.00, 1, 500.00, 350.00, 150.00, 0.00, ''),
(111, 103, 'Bata', '43', 5000.00, 1, 5000.00, 4000.00, 1000.00, 0.00, ''),
(112, 104, 'B-Rose', '', 500.00, 1, 500.00, 350.00, 150.00, 0.00, ''),
(113, 105, 'Apple', '', 6000.00, 1, 6000.00, 2000.00, 4000.00, 0.00, ''),
(114, 106, 'Apple', '', 6000.00, 1, 6000.00, 2000.00, 4000.00, 0.00, ''),
(115, 107, '', '', 500.00, 2, 1000.00, 300.00, 400.00, 0.00, ''),
(116, 108, 'W', '', 300.00, 1, 300.00, 0.00, 300.00, 0.00, ''),
(117, 109, 'LUX', '', 400.00, 1, 400.00, 250.00, 150.00, 0.00, ''),
(118, 110, 'LUX', '', 400.00, 2, 800.00, 250.00, 300.00, 0.00, ''),
(119, 111, 'ddd', '', 1000.00, 1, 1000.00, 700.00, 300.00, 0.00, ''),
(120, 112, 'syp brufen', '', 120.00, 2, 240.00, 100.00, 40.00, 0.00, ''),
(121, 113, 'Darham', '', 800.00, 1, 800.00, 350.00, 450.00, 0.00, ''),
(122, 114, 'Darham', '', 800.00, 2, 1600.00, 350.00, 900.00, 0.00, ''),
(123, 115, 'LUX', '', 400.00, 1, 400.00, 250.00, 150.00, 0.00, ''),
(124, 116, 'W-D47 BLACK', '41', 3499.00, 1, 3499.00, 2000.00, 1499.00, 0.00, ''),
(130, 122, 'Darham', '', 800.00, 1, 800.00, 350.00, 450.00, 0.00, ''),
(131, 123, 'پاوڈر', '', 150.00, 1, 150.00, 120.00, 30.00, 0.00, ''),
(132, 124, 'پاوڈر', '', 150.00, 1, 150.00, 120.00, 30.00, 0.00, ''),
(133, 125, 'BBB', '', 1200.00, 1, 1200.00, 670.00, 530.00, 0.00, ''),
(134, 126, 'BBB', '', 1200.00, 1, 1200.00, 670.00, 530.00, 0.00, ''),
(135, 127, 'BBB', '', 1200.00, 2, 2400.00, 670.00, 1060.00, 0.00, ''),
(136, 127, 'Penadol', '', 300.00, 1, 300.00, 250.00, 50.00, 0.00, ''),
(137, 128, 'Darham', '', 800.00, 3, 2304.00, 350.00, 1254.00, 4.00, ''),
(138, 129, 'Penadol', '', 300.00, 1, 300.00, 250.00, 50.00, 0.00, ''),
(139, 130, 'LUX', '', 400.00, 1, 400.00, 250.00, 150.00, 0.00, ''),
(140, 131, 'LUX', '', 400.00, 1, 400.00, 250.00, 150.00, 0.00, ''),
(141, 131, 'Darham', '', 800.00, 1, 800.00, 350.00, 450.00, 0.00, ''),
(142, 132, 'Vitamin C', '', 500.00, 1, 500.00, 400.00, 100.00, 0.00, 'uploads/prod_20260906_192107_26f7f6d47889.jpg'),
(143, 132, 'JonP', '', 750.00, 1, 750.00, 200.00, 550.00, 0.00, 'uploads/prod_20260906_191957_508935a21e7a.webp'),
(144, 132, 'CaC', '', 500.00, 1, 500.00, 300.00, 200.00, 0.00, 'uploads/prod_20260906_191643_9a70338276be.jpg'),
(145, 133, 'TwC', '', 300.00, 1, 300.00, 200.00, 100.00, 0.00, 'uploads/prod_20260906_192325_4a17a28edf00.webp'),
(146, 133, 'Resveratol', '', 800.00, 1, 800.00, 700.00, 100.00, 0.00, 'uploads/prod_20260906_192218_35ed141fca28.webp'),
(147, 134, 'Risk', '', 300.00, 1, 300.00, 200.00, 100.00, 0.00, 'uploads/prod_20260906_191436_7d2e6c67bf89.png'),
(148, 135, 'Risk', '', 300.00, 1, 300.00, 200.00, 100.00, 0.00, 'uploads/prod_20260906_191436_7d2e6c67bf89.png'),
(149, 135, 'CaC', '', 500.00, 1, 500.00, 300.00, 200.00, 0.00, 'uploads/prod_20260906_191643_9a70338276be.jpg'),
(150, 136, 'CaC', '', 500.00, 1, 500.00, 300.00, 200.00, 0.00, 'uploads/prod_20260906_191643_9a70338276be.jpg'),
(151, 137, 'Hand VSL', '', 350.00, 1, 350.00, 200.00, 150.00, 0.00, 'uploads/prod_20260906_192412_a5048516904b.png'),
(152, 137, 'Vitamin C', '', 500.00, 4, 2000.00, 400.00, 400.00, 0.00, 'uploads/prod_20260906_192107_26f7f6d47889.jpg'),
(153, 138, 'Risk', '', 320.00, 1, 320.00, 200.00, 120.00, 0.00, 'uploads/prod_20260906_191436_7d2e6c67bf89.png'),
(154, 139, 'Sphine', '', 100.00, 1, 100.00, 70.00, 30.00, 0.00, 'uploads/prod_20260910_165152_bf29d4e4e9a9.png'),
(155, 140, 'Brandex D3', '', 3000.00, 2, 6000.00, 2500.00, 1000.00, 0.00, 'uploads/prod_20260910_173121_ca1e1e6972f1.webp'),
(156, 140, 'Sphine', '', 100.00, 1, 100.00, 70.00, 30.00, 0.00, 'uploads/prod_20260910_165152_bf29d4e4e9a9.png'),
(157, 140, 'TwC', '', 300.00, 1, 300.00, 200.00, 100.00, 0.00, 'uploads/prod_20260906_192325_4a17a28edf00.webp'),
(158, 141, 'Brandex D3', '', 300.00, 2, 600.00, 200.00, 200.00, 0.00, 'uploads/prod_20260910_174505_ded6e18d7745.webp'),
(159, 141, 'Hand VSL', '', 350.00, 1, 350.00, 200.00, 150.00, 0.00, 'uploads/prod_20260906_192412_a5048516904b.png'),
(160, 141, 'TwC', '', 300.00, 1, 300.00, 200.00, 100.00, 0.00, 'uploads/prod_20260906_192325_4a17a28edf00.webp'),
(161, 141, 'Resveratol', '', 800.00, 1, 800.00, 700.00, 100.00, 0.00, 'uploads/prod_20260906_192218_35ed141fca28.webp'),
(162, 142, 'Risk', '', 320.00, 1, 320.00, 200.00, 120.00, 0.00, 'uploads/prod_20260906_191436_7d2e6c67bf89.png'),
(163, 143, 'Risk', '', 320.00, 1, 320.00, 200.00, 120.00, 0.00, 'uploads/prod_20260906_191436_7d2e6c67bf89.png'),
(164, 143, 'Triflex', '', 500.00, 1, 500.00, 300.00, 200.00, 0.00, 'uploads/prod_20260906_191737_4334c22744d2.jpg'),
(165, 144, 'Risk', '', 320.00, 1, 320.00, 200.00, 120.00, 0.00, 'uploads/prod_20260906_191436_7d2e6c67bf89.png'),
(166, 144, 'Triflex', '', 500.00, 2, 1000.00, 300.00, 400.00, 0.00, 'uploads/prod_20260906_191737_4334c22744d2.jpg'),
(167, 145, 'Risk', '', 320.00, 1, 320.00, 200.00, 120.00, 0.00, 'uploads/prod_20260906_191436_7d2e6c67bf89.png'),
(169, 147, 'CaC', '', 500.00, 1, 500.00, 300.00, 200.00, 0.00, 'uploads/prod_20260906_191643_9a70338276be.jpg'),
(170, 148, 'CaC', '', 500.00, 1, 500.00, 300.00, 200.00, 0.00, 'uploads/prod_20260906_191643_9a70338276be.jpg'),
(171, 149, 'Panadol', '', 500.00, 2, 1000.00, 300.00, 400.00, 0.00, 'uploads/prod_20260906_191909_693ab576654b.jpg'),
(172, 150, 'Risk', '', 320.00, 2, 640.00, 200.00, 240.00, 0.00, 'uploads/prod_20260906_191436_7d2e6c67bf89.png'),
(173, 151, 'Nalozone', '', 500.00, 1, 500.00, 200.00, 300.00, 0.00, 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNj'),
(174, 152, 'JonP', '', 750.00, 1, 750.00, 200.00, 550.00, 0.00, 'uploads/prod_20260906_191957_508935a21e7a.webp'),
(175, 153, 'Panadol', '', 500.00, 1, 500.00, 300.00, 200.00, 0.00, 'uploads/prod_20260906_191909_693ab576654b.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `period_type` varchar(20) NOT NULL,
  `period_value` varchar(50) NOT NULL,
  `salesman_expense` decimal(12,2) DEFAULT 0.00,
  `shop_rent` decimal(12,2) DEFAULT 0.00,
  `other_expenses` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `period_type`, `period_value`, `salesman_expense`, `shop_rent`, `other_expenses`) VALUES
(1, 1, 'daily', '2026-06-01', 200.00, 0.00, 0.00),
(6, 1, 'daily', '2026-10-23', 0.00, 0.00, 0.00),
(10, 1, 'daily', '2026-09-23', 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `khata_customers`
--

CREATE TABLE `khata_customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT '',
  `address` varchar(255) DEFAULT '',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `khata_entries`
--

CREATE TABLE `khata_entries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `customer_id` int(11) NOT NULL,
  `entry_type` enum('credit','payment') NOT NULL DEFAULT 'credit',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `entry_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `bill_no` varchar(100) DEFAULT '',
  `description` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `subscription_end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `status`, `subscription_end_date`, `created_at`) VALUES
(1, 'aslampc406@gmail.com', '$2y$10$mHxKER5yXi/jv5a3uQUIwOVAX2Y8EH2VoggAvPhC/43/dvqJj2V8i', 'admin', 'approved', '2099-12-31', '2026-05-28 16:23:15'),
(2, 'malikpc405@gmail.com', '$2y$10$D6kxqIGVLTxbA5KP70sv8OwiiamnBULuepr2971HdLfdw.EnD.VPK', 'user', 'approved', '2026-06-28', '2026-05-28 16:23:43'),
(3, 'nooomiiiiii@gmail.com', '$2y$10$fJ0oRN08ynHO.OeiOQx2NOsIOf5NtpnAmGtzhwRseZLS6shLRCWRe', 'user', 'pending', NULL, '2026-05-28 17:00:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`period_type`,`period_value`);

--
-- Indexes for table `khata_customers`
--
ALTER TABLE `khata_customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_customer` (`user_id`,`name`);

--
-- Indexes for table `khata_entries`
--
ALTER TABLE `khata_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`entry_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `bill_items`
--
ALTER TABLE `bill_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=176;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `khata_customers`
--
ALTER TABLE `khata_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `khata_entries`
--
ALTER TABLE `khata_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `khata_entries`
--
ALTER TABLE `khata_entries`
  ADD CONSTRAINT `khata_entries_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `khata_customers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
