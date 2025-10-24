-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2025 at 08:34 PM
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
-- Database: `pet_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `image` varchar(100) DEFAULT 'default-admin.png',
  `phone` varchar(20) NOT NULL,
  `address` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `image`, `phone`, `address`, `role`, `status`, `created_at`) VALUES
('1d8d66d13e48d7de03e0', 'jjhobert B', 'mr.blanker001@gmail.com', '$2y$10$zqE1rJFwY.WR15XD9Ezjh.7nK32WHMDYyxp6F/aDRlHB.UBGyXkhS', 'default-admin.png', '+639489545100', 'blah blah', 'admin', 'active', '2025-10-07 21:03:59'),
('720bc4bd8d4037a98ccb', 'melvince', 'vincebernardo47@gmail.com', '$2y$10$1LzMHoOfc7HPqF4DAfL11uF5T2vjsFh6goB9WfoRN.6z8VC3foCtS', 'default-admin.png', '0', '1', 'admin', 'active', '2025-10-08 14:00:45'),
('OmSTGhgA8bBE5o8ypd2c', 'bertya', 'mr.blanker01@gmail.com', '356a192b7913b04c54574d18c28d46e6395428ab', 'default-admin.png', '0', '', 'admin', '', '2025-10-07 19:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `transaction_no` varchar(50) DEFAULT NULL,
  `user_id` varchar(36) NOT NULL,
  `appointment_date` date NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `status` enum('Pending','Approved','Completed','Rejected','Cancelled') DEFAULT 'Pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `date_seen` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active_slot` varchar(255) GENERATED ALWAYS AS (if(`status` in ('Pending','Approved'),concat(`appointment_date`,'-',`time_slot`,'-',`service_id`,'-',`user_id`),NULL)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `transaction_no`, `user_id`, `appointment_date`, `time_slot`, `status`, `completed_at`, `service_id`, `date_seen`, `created_at`) VALUES
(169, 'TR-20251009-330839', 'fKVqHNtxawqi9BgHxBTt', '2025-10-09', '08:00 AM - 09:00 AM', 'Approved', NULL, 4, '2025-10-09', '2025-10-09 14:24:21'),
(170, 'TR-20251009-434151', 'fKVqHNtxawqi9BgHxBTt', '2025-10-09', '09:00 AM - 10:00 AM', 'Completed', '2025-10-09 15:47:01', 4, '2025-10-09', '2025-10-09 14:34:14');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(604, 'fKVqHNtxawqi9BgHxBTt', 28, 2, '2025-10-11 15:49:47'),
(606, '2eb5ea9e4b6829676c530bc4bcd9ce07', 28, 1, '2025-10-11 16:01:18');

-- --------------------------------------------------------

--
-- Table structure for table `community_comments`
--

CREATE TABLE `community_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_comments`
--

INSERT INTO `community_comments` (`id`, `post_id`, `user_id`, `comment`, `created_at`, `parent_id`) VALUES
(135, 84, 'admin', 'hey', '2025-10-10 00:10:38', NULL),
(147, 84, 'admin', 'asd', '2025-10-10 01:15:32', NULL),
(149, 84, 'fKVqHNtxawqi9BgHxBTt', 'asd', '2025-10-10 01:22:26', 147),
(152, 100, 'fKVqHNtxawqi9BgHxBTt', 'hey]', '2025-10-11 23:32:13', NULL),
(153, 100, 'fKVqHNtxawqi9BgHxBTt', 'hey]', '2025-10-11 23:32:13', 0),
(155, 100, '2eb5ea9e4b6829676c530bc4bcd9ce07', 'what wrong', '2025-10-11 23:32:29', 152);

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','deleted') NOT NULL DEFAULT 'active',
  `is_announcement` tinyint(1) NOT NULL DEFAULT 0,
  `is_admin_post` tinyint(1) NOT NULL DEFAULT 0,
  `poster_type` enum('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`id`, `user_id`, `title`, `content`, `image`, `score`, `views`, `created_at`, `status`, `is_announcement`, `is_admin_post`, `poster_type`) VALUES
(84, 'fKVqHNtxawqi9BgHxBTt', 'hi', 'sabdhasjbdha', 'uploads/community/1760007605_groom.jpg', 0, 2, '2025-10-09 19:00:05', 'active', 0, 0, 'user'),
(85, 'admin', 'hey', 'hasbdabdhabd', 'uploads/community/post_1760008512.jpg', 0, 1, '2025-10-09 19:15:12', 'active', 0, 1, 'admin'),
(95, 'admin', 'asd', 'asdasdas', NULL, 0, 1, '2025-10-09 23:58:34', 'active', 0, 1, 'admin'),
(96, 'admin', 'note', 'dsjkhasd', NULL, 0, 0, '2025-10-10 00:26:40', 'active', 1, 1, 'admin'),
(99, 'fKVqHNtxawqi9BgHxBTt', 'hehe', 'hahhahahha', 'uploads/community/1760031824_b1.jpg', 0, 1, '2025-10-10 01:38:43', 'active', 0, 0, 'user'),
(100, '2eb5ea9e4b6829676c530bc4bcd9ce07', '#DogTraining', 'jkbdaskbdk jbkjasdb b jkb askjbd kjab', 'uploads/community/1760196655_d.jpg', 0, 0, '2025-10-11 23:30:55', 'active', 0, 0, 'user');

-- --------------------------------------------------------

--
-- Table structure for table `community_votes`
--

CREATE TABLE `community_votes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `upvote` tinyint(1) NOT NULL DEFAULT 0,
  `downvote` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_votes`
--

INSERT INTO `community_votes` (`id`, `post_id`, `user_id`, `upvote`, `downvote`, `created_at`) VALUES
(2, 2, 'MDg8LR6VWkkcSYEHYelh', 0, 0, '2025-09-22 00:36:14'),
(7, 1, 'MDg8LR6VWkkcSYEHYelh', 0, 0, '2025-09-22 00:37:56'),
(10, 11, 'lSdjSHRx6MWqKBzAn0Vu', 0, 0, '2025-09-29 21:58:28'),
(11, 12, 'lSdjSHRx6MWqKBzAn0Vu', 0, 0, '2025-09-29 21:59:24'),
(16, 15, 'eAEvYO2B1DucUc8zPA0C', 0, 0, '2025-10-02 15:44:32'),
(42, 23, 'eAEvYO2B1DucUc8zPA0C', 1, 0, '2025-10-05 02:07:55'),
(43, 23, 'nnAlTiHzGCxZUUwkQ39a', 1, 0, '2025-10-05 02:09:12'),
(45, 22, 'eAEvYO2B1DucUc8zPA0C', 1, 0, '2025-10-05 02:22:46'),
(47, 25, 'eAEvYO2B1DucUc8zPA0C', 1, 0, '2025-10-06 16:36:12'),
(48, 45, 'eAEvYO2B1DucUc8zPA0C', 1, 0, '2025-10-07 11:31:51'),
(49, 45, 'bda6d5c7d17ca42829466e51d6f04e50', 0, 0, '2025-10-07 20:33:52'),
(50, 47, 'ZwKydew1OTXHaGzKgM9o', 0, 1, '2025-10-07 21:28:06'),
(51, 49, 'ZwKydew1OTXHaGzKgM9o', 1, 0, '2025-10-07 22:05:42'),
(52, 53, 'czJyzSkvak339IEQsFwS', 1, 0, '2025-10-07 22:18:31'),
(53, 54, 'ZwKydew1OTXHaGzKgM9o', 1, 0, '2025-10-07 22:23:13'),
(54, 52, 'ZwKydew1OTXHaGzKgM9o', 0, 1, '2025-10-07 22:23:54'),
(55, 54, 'czJyzSkvak339IEQsFwS', 1, 0, '2025-10-07 22:24:20'),
(56, 55, 'ZwKydew1OTXHaGzKgM9o', 1, 0, '2025-10-07 22:34:31'),
(57, 52, 'czJyzSkvak339IEQsFwS', 1, 0, '2025-10-07 22:52:43'),
(58, 51, 'ZwKydew1OTXHaGzKgM9o', 1, 0, '2025-10-07 22:53:40'),
(59, 56, 'ZwKydew1OTXHaGzKgM9o', 1, 0, '2025-10-07 22:54:05'),
(60, 57, 'aUgE4kJ8bWBrnXTklz7z', 1, 0, '2025-10-07 23:11:04'),
(63, 59, 'fKVqHNtxawqi9BgHxBTt', 0, 0, '2025-10-08 21:44:43'),
(64, 64, 'fKVqHNtxawqi9BgHxBTt', 0, 1, '2025-10-08 22:13:05'),
(65, 66, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-08 22:30:44'),
(66, 67, 'fKVqHNtxawqi9BgHxBTt', 0, 1, '2025-10-08 22:31:27'),
(67, 70, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-08 23:12:33'),
(68, 71, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-08 23:19:02'),
(69, 73, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-09 09:29:01'),
(70, 83, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-09 15:23:10'),
(71, 77, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-09 15:30:39'),
(72, 76, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-09 15:30:42'),
(73, 85, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-09 19:21:21'),
(76, 89, 'fKVqHNtxawqi9BgHxBTt', 0, 1, '2025-10-09 20:25:02'),
(77, 84, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-10 00:47:16'),
(79, 99, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-10 01:43:10'),
(80, 100, 'fKVqHNtxawqi9BgHxBTt', 1, 0, '2025-10-11 23:31:56');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `product_id`, `created_at`, `updated_at`, `status`) VALUES
(317, 'eAEvYO2B1DucUc8zPA0C', 18, '2025-10-03 20:30:49', '2025-10-03 20:30:49', 1),
(318, 'eAEvYO2B1DucUc8zPA0C', 15, '2025-10-03 20:30:50', '2025-10-03 20:30:50', 1),
(528, 'eAEvYO2B1DucUc8zPA0C', 19, '2025-10-03 22:58:17', '2025-10-03 22:58:17', 1),
(541, 'eAEvYO2B1DucUc8zPA0C', 20, '2025-10-03 23:05:45', '2025-10-03 23:05:45', 1),
(641, 'nnAlTiHzGCxZUUwkQ39a', 21, '2025-10-05 00:49:32', '2025-10-05 00:49:32', 1),
(642, 'nnAlTiHzGCxZUUwkQ39a', 23, '2025-10-05 00:49:34', '2025-10-05 00:49:34', 1),
(651, 'eAEvYO2B1DucUc8zPA0C', 24, '2025-10-07 10:30:34', '2025-10-07 10:30:34', 1),
(652, 'eAEvYO2B1DucUc8zPA0C', 23, '2025-10-07 10:30:36', '2025-10-07 10:30:36', 1),
(653, 'eAEvYO2B1DucUc8zPA0C', 22, '2025-10-07 10:30:37', '2025-10-07 10:30:37', 1),
(655, 'HYMKqoWL7xrEgJO34AQw', 24, '2025-10-08 09:27:57', '2025-10-08 09:27:57', 1),
(689, 'fKVqHNtxawqi9BgHxBTt', 21, '2025-10-11 14:09:32', '2025-10-11 14:09:32', 1),
(702, 'fKVqHNtxawqi9BgHxBTt', 22, '2025-10-11 18:48:35', '2025-10-11 18:48:35', 1),
(728, 'fKVqHNtxawqi9BgHxBTt', 28, '2025-10-12 00:31:19', '2025-10-12 00:31:19', 1);

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` varchar(36) NOT NULL,
  `holiday_date` varchar(5) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `holiday_date`, `description`, `created_at`) VALUES
('216dcdcd-9f8a-11f0-8049-49de7dd5b611', '10-30', 'payday', '2025-10-02 12:20:06'),
('d5ab9719-9aa7-11f0-85b7-5923a3ca0ad1', '10-28', 'non workings', '2025-09-26 07:10:08');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `checkout_email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `delivery_method` varchar(255) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `fullname`, `address`, `checkout_email`, `phone`, `payment_method`, `delivery_method`, `total`, `shipping_fee`, `status`, `completed_at`, `created_at`) VALUES
(199, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 340.00, 50.00, 'pending', NULL, '2025-10-03 06:20:05'),
(200, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 250.00, 50.00, 'pending', NULL, '2025-10-03 09:07:38'),
(201, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 10.00, 0.00, 'pending', NULL, '2025-10-03 09:09:13'),
(202, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 550.00, 50.00, 'pending', NULL, '2025-10-03 12:00:28'),
(203, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 250.00, 50.00, 'pending', NULL, '2025-10-03 12:48:14'),
(204, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 250.00, 50.00, 'pending', NULL, '2025-10-03 12:48:28'),
(205, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 150.00, 50.00, 'pending', NULL, '2025-10-03 14:34:41'),
(206, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 450.00, 50.00, 'pending', NULL, '2025-10-03 14:57:09'),
(207, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 100.00, 50.00, 'pending', NULL, '2025-10-03 15:03:27'),
(208, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 200.00, 50.00, 'pending', NULL, '2025-10-03 15:19:02'),
(209, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 200.00, 50.00, 'pending', NULL, '2025-10-03 15:22:22'),
(210, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 550.00, 50.00, 'pending', NULL, '2025-10-03 15:33:04'),
(211, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 450.00, 50.00, 'pending', NULL, '2025-10-03 15:35:44'),
(212, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 1000.00, 50.00, 'pending', NULL, '2025-10-03 15:36:25'),
(213, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 830.00, 30.00, 'pending', NULL, '2025-10-03 16:26:26'),
(214, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 580.00, 30.00, 'pending', NULL, '2025-10-03 16:27:26'),
(215, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 430.00, 30.00, 'pending', NULL, '2025-10-03 16:36:29'),
(216, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 430.00, 30.00, 'pending', NULL, '2025-10-03 16:36:46'),
(217, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 430.00, 30.00, 'pending', NULL, '2025-10-03 16:37:26'),
(218, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 280.00, 30.00, 'pending', NULL, '2025-10-03 16:38:37'),
(219, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 280.00, 30.00, 'pending', NULL, '2025-10-03 16:38:58'),
(220, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 330.00, 30.00, 'pending', NULL, '2025-10-03 16:39:50'),
(221, 'eAEvYO2B1DucUc8zPA0C', 'melvince', 'san ildefonso bulacan', NULL, '0977372872777787', 'gcash', '', 300.00, 0.00, 'pending', NULL, '2025-10-03 16:40:17'),
(225, 'HYMKqoWL7xrEgJO34AQw', 'jjhobert', 'blah blah', NULL, '+639489545117', 'pay_shop', '', 250.00, 0.00, 'pending', NULL, '2025-10-07 21:55:54'),
(226, '5f19a3572b446c20aa077261c0602f78', 'melvince', 'san ildefonso bulacan', NULL, '09762373332', 'gcash', '', 220.00, 20.00, 'shipped', NULL, '2025-10-08 06:20:12'),
(227, '5f19a3572b446c20aa077261c0602f78', 'kiel', 'san ildefonso bulacan', NULL, '09762373332', 'gcash', '', 70.00, 20.00, 'confirmed', NULL, '2025-10-08 06:26:44'),
(228, '5f19a3572b446c20aa077261c0602f78', 'kiel', 'san ildefonso bulacan', NULL, '09762373332', 'gcash', '', 50.00, 0.00, 'confirmed', NULL, '2025-10-08 06:28:50'),
(229, '5f19a3572b446c20aa077261c0602f78', 'kiel', 'san ildefonso bulacan', NULL, '09762373332', 'gcash', '', 180.00, 30.00, 'shipped', NULL, '2025-10-08 06:40:57'),
(230, '5f19a3572b446c20aa077261c0602f78', 'kiel', 'san ildefonso bulacan', NULL, '09762373332', 'gcash', '', 180.00, 30.00, 'pending', NULL, '2025-10-08 06:46:29'),
(231, '5f19a3572b446c20aa077261c0602f78', 'kiel', 'san ildefonso bulacan', NULL, '09762373332', 'pay_shop', '', 50.00, 0.00, 'pending', NULL, '2025-10-08 06:50:24'),
(234, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso bulacan', 'jbert.blank@gmail.com', '09762373332', 'cod', '', 230.00, 30.00, 'shipped', NULL, '2025-10-08 07:28:06'),
(235, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso bulacan', 'jbert.blank@gmail.com', '09762373332', 'gcash', '', 430.00, 30.00, 'shipped', NULL, '2025-10-08 07:29:32'),
(236, 'fKVqHNtxawqi9BgHxBTt', 'melvince', 'san ildefonso bulacan', 'vincebernardo47@gmail.com', '09762373332', 'gcash', '', 230.00, 30.00, 'confirmed', NULL, '2025-10-08 07:31:19'),
(237, 'fKVqHNtxawqi9BgHxBTt', 'gelo', 'san ildefonso bulacan', 'jobertblanker0608@gmail.com', '09762373332', 'gcash', '', 430.00, 30.00, 'pending', NULL, '2025-10-08 07:37:38'),
(239, 'fKVqHNtxawqi9BgHxBTt', 'gelo', 'san ildefonso bulacan', 'jhobertblanker0608@gmail.com', '09762373332', 'gcash', '', 400.00, 0.00, 'confirmed', NULL, '2025-10-08 07:41:49'),
(241, 'fKVqHNtxawqi9BgHxBTt', 'gelo', 'san ildefonso bulacan', 'jhobertblanker0608@gmail.com', '09762373332', 'gcash', 'courier', 480.00, 30.00, 'shipped', NULL, '2025-10-08 08:13:10'),
(242, 'fKVqHNtxawqi9BgHxBTt', 'terence', 'san ildefonso', 'melvince20bernardo@gmail.com', '65657556456456', 'gcash', 'courier', 200.00, 100.00, 'pending', NULL, '2025-10-11 06:12:05'),
(243, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso', 'melvince20bernardo@gmail.com', '65657556456456', 'gcash', 'courier', 200.00, 100.00, 'pending', NULL, '2025-10-11 06:22:49'),
(244, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso', 'melvince20bernardo@gmail.com', '0976676632', 'gcash', 'pickup', 150.00, 0.00, 'pending', NULL, '2025-10-11 06:30:17'),
(245, 'fKVqHNtxawqi9BgHxBTt', 'asdasdasd', 'san ildefonso', 'melvince20bernardo@gmail.com', '65657556456456', 'gcash', 'pickup', 150.00, 0.00, 'pending', NULL, '2025-10-11 06:31:07'),
(246, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso', 'melvince20bernardo@gmail.com', '0976676632', 'gcash', 'courier', 400.00, 100.00, 'pending', NULL, '2025-10-11 06:34:18'),
(247, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso', 'vincebernardo47@gmail.com', '65657556456456', 'gcash', 'courier', 250.00, 100.00, 'pending', NULL, '2025-10-11 06:36:29'),
(248, 'fKVqHNtxawqi9BgHxBTt', 'asdasdasd', 'san ildefonso', 'melvince20bernardo@gmail.com', '0976676632', 'gcash', 'courier', 120.00, 100.00, 'pending', NULL, '2025-10-11 09:27:22'),
(249, 'fKVqHNtxawqi9BgHxBTt', 'asdasdasd', 'san ildefonso', 'vincebernardo47@gmail.com', '65657556456456', 'gcash', 'courier', 780.00, 100.00, 'pending', NULL, '2025-10-11 10:12:10'),
(250, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso', 'melvince20bernardo@gmail.com', '0976676632', 'pay_shop', 'pickup', 60.00, 0.00, 'pending', NULL, '2025-10-11 10:16:22'),
(251, '2eb5ea9e4b6829676c530bc4bcd9ce07', 'jjhobert B', 'pulilan bulacan', 'mr.blanker001@gmail.com', '+639489545100', 'gcash', 'courier', 400.00, 100.00, 'pending', NULL, '2025-10-11 10:16:48'),
(252, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso', 'vincebernardo47@gmail.com', '0976676632', 'gcash', 'courier', 250.00, 100.00, 'pending', NULL, '2025-10-11 11:12:34'),
(253, 'fKVqHNtxawqi9BgHxBTt', 'asdasdasd', 'san ildefonso', 'melvince20bernardo@gmail.com', '65657556456456', 'gcash', 'courier', 200.00, 100.00, 'pending', NULL, '2025-10-11 11:21:49'),
(254, 'fKVqHNtxawqi9BgHxBTt', 'asdasdasd', 'san ildefonso', 'melvince20bernardo@gmail.com', '65657556456456', 'gcash', 'courier', 200.00, 100.00, 'pending', NULL, '2025-10-11 11:22:11'),
(255, 'fKVqHNtxawqi9BgHxBTt', 'kiel', 'san ildefonso', 'melvince20bernardo@gmail.com', '65657556456456', 'gcash', 'courier', 200.00, 100.00, 'pending', NULL, '2025-10-11 11:22:32'),
(256, 'fKVqHNtxawqi9BgHxBTt', 'asdasdasd', 'san ildefonso', 'melvince20bernardo@gmail.com', '65657556456456', 'gcash', 'courier', 200.00, 100.00, 'pending', NULL, '2025-10-11 11:22:50'),
(257, '2eb5ea9e4b6829676c530bc4bcd9ce07', 'asdasdasd', 'san ildefonso', 'melvince20bernardo@gmail.com', '0976676632', 'gcash', 'courier', 200.00, 100.00, 'pending', NULL, '2025-10-11 14:11:28'),
(258, '2eb5ea9e4b6829676c530bc4bcd9ce07', 'asdasdasd', 'san ildefonso', 'melvince20bernardo@gmail.com', '65657556456456', 'gcash', 'courier', 200.00, 100.00, 'pending', NULL, '2025-10-11 14:12:01'),
(259, '2eb5ea9e4b6829676c530bc4bcd9ce07', 'kiel', 'san ildefonso', 'vincebernardo47@gmail.com', '65657556456456', 'gcash', 'pickup', 400.00, 0.00, 'pending', NULL, '2025-10-11 14:58:24'),
(260, 'fKVqHNtxawqi9BgHxBTt', 'fgd', 'egdfgsdfsdf', 'melvince20bernardo@gmail.com', '0234234234', 'gcash', 'courier', 400.00, 100.00, 'pending', NULL, '2025-10-11 15:06:26'),
(261, 'fKVqHNtxawqi9BgHxBTt', 'fgd', 'egdfgsdfsdf', 'melvince20bernardo@gmail.com', '0234234234', 'gcash', 'courier', 300.00, 100.00, 'pending', NULL, '2025-10-11 15:07:25'),
(262, 'fKVqHNtxawqi9BgHxBTt', 'fgd', 'egdfgsdfsdf', 'melvince20bernardo@gmail.com', '0234234234', 'gcash', 'courier', 350.00, 100.00, 'pending', NULL, '2025-10-11 15:09:17'),
(263, 'fKVqHNtxawqi9BgHxBTt', 'fgd', 'egdfgsdfsdf', 'melvince20bernardo@gmail.com', '0234234234', 'pay_shop', 'pickup', 100.00, 0.00, 'pending', NULL, '2025-10-11 15:09:58'),
(264, 'fKVqHNtxawqi9BgHxBTt', 'fgd', 'egdfgsdfsdf', 'melvince20bernardo@gmail.com', '0234234234', 'gcash', 'courier', 250.00, 100.00, 'pending', NULL, '2025-10-11 15:10:17'),
(265, '2eb5ea9e4b6829676c530bc4bcd9ce07', 'kiel', 'san ildefonso', 'vincebernardo47@gmail.com', '0976676632', 'gcash', 'courier', 600.00, 100.00, 'delivered', '2025-10-11 18:31:01', '2025-10-11 15:16:20'),
(266, '2eb5ea9e4b6829676c530bc4bcd9ce07', 'asdasdasd', 'san ildefonso', 'melvince20bernardo@gmail.com', '0976676632', 'gcash', 'courier', 250.00, 100.00, 'delivered', '2025-10-11 18:31:22', '2025-10-11 15:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(242, 199, 13, 1, 200.00),
(243, 199, 14, 1, 10.00),
(244, 199, 15, 2, 40.00),
(245, 200, 13, 1, 200.00),
(246, 201, 14, 1, 10.00),
(247, 202, 18, 1, 500.00),
(248, 203, 19, 1, 200.00),
(249, 204, 19, 1, 200.00),
(250, 205, 20, 2, 50.00),
(251, 206, 19, 2, 200.00),
(252, 207, 20, 1, 50.00),
(253, 208, 21, 1, 150.00),
(254, 209, 22, 1, 150.00),
(255, 210, 23, 2, 250.00),
(256, 211, 24, 1, 400.00),
(257, 212, 21, 1, 150.00),
(258, 212, 22, 1, 150.00),
(259, 212, 23, 1, 250.00),
(260, 212, 24, 1, 400.00),
(261, 213, 22, 1, 150.00),
(262, 213, 23, 1, 250.00),
(263, 213, 24, 1, 400.00),
(264, 214, 21, 1, 150.00),
(265, 214, 24, 1, 400.00),
(266, 215, 24, 1, 400.00),
(267, 216, 24, 1, 400.00),
(268, 217, 24, 1, 400.00),
(269, 218, 23, 1, 250.00),
(270, 219, 23, 1, 250.00),
(271, 220, 22, 1, 150.00),
(272, 220, 21, 1, 150.00),
(273, 221, 22, 1, 150.00),
(274, 221, 21, 1, 150.00),
(278, 225, 23, 1, 250.00),
(279, 226, 24, 1, 200.00),
(280, 227, 21, 1, 50.00),
(281, 228, 21, 1, 50.00),
(282, 229, 22, 1, 150.00),
(283, 230, 22, 1, 150.00),
(284, 231, 21, 1, 50.00),
(287, 234, 24, 1, 200.00),
(288, 235, 24, 2, 200.00),
(289, 236, 24, 1, 200.00),
(290, 237, 24, 2, 200.00),
(292, 239, 24, 2, 200.00),
(294, 241, 21, 1, 50.00),
(295, 241, 22, 1, 150.00),
(296, 241, 23, 1, 250.00),
(297, 242, 23, 5, 20.00),
(298, 243, 21, 2, 50.00),
(299, 244, 22, 1, 150.00),
(300, 245, 22, 1, 150.00),
(301, 246, 25, 3, 100.00),
(302, 247, 22, 1, 150.00),
(303, 248, 23, 1, 20.00),
(304, 249, 21, 6, 50.00),
(305, 249, 22, 2, 150.00),
(306, 249, 23, 4, 20.00),
(307, 250, 23, 3, 20.00),
(308, 251, 22, 2, 150.00),
(309, 252, 22, 1, 150.00),
(310, 253, 26, 1, 100.00),
(311, 254, 26, 1, 100.00),
(312, 255, 26, 1, 100.00),
(313, 256, 26, 1, 100.00),
(314, 257, 27, 1, 100.00),
(315, 258, 27, 1, 100.00),
(316, 259, 27, 4, 100.00),
(317, 260, 28, 2, 150.00),
(318, 261, 27, 2, 100.00),
(319, 262, 27, 1, 100.00),
(320, 262, 28, 1, 150.00),
(321, 263, 27, 1, 100.00),
(322, 264, 28, 1, 150.00),
(323, 265, 27, 2, 100.00),
(324, 265, 28, 2, 150.00),
(325, 266, 28, 1, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_reviews`
--

CREATE TABLE `order_reviews` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_reviews`
--

INSERT INTO `order_reviews` (`id`, `order_id`, `rating`, `feedback`, `created_at`) VALUES
(1, 266, 5, 'apakaganda', '2025-10-12 02:33:06');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL,
  `on_sale` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `name`, `category`, `price`, `sale_price`, `stock`, `description`, `status`, `image`, `on_sale`, `created_at`, `updated_at`) VALUES
(27, 'dog food', 'Dog Food', 100.00, NULL, 0, 'asdasdas', 'active', '1760189447_dogfood.png', 0, '2025-10-11 13:30:47', '2025-10-11 15:16:20'),
(28, 'cat food', 'Cat Food', 200.00, 150.00, 2, 'sdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdsdafsdgsdgsdf dsf sdf sdssdgsdgsdf dsf', 'active', '1760194982_cat.png', 1, '2025-10-11 15:03:02', '2025-10-11 17:19:52');

-- --------------------------------------------------------

--
-- Table structure for table `promotion`
--

CREATE TABLE `promotion` (
  `id` int(11) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 100.00,
  `promo_note` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotion`
--

INSERT INTO `promotion` (`id`, `delivery_fee`, `promo_note`, `start_date`, `end_date`, `updated_at`) VALUES
(1, 100.00, '', '2025-09-19', '2025-09-20', '2025-09-18 16:29:56'),
(2, 150.00, '', '2025-09-19', '2025-09-19', '2025-09-19 12:58:55'),
(3, 20.00, '', '2025-09-19', '2025-09-20', '2025-09-19 13:03:18'),
(4, 50.00, '', '2025-09-19', '2025-09-20', '2025-09-19 15:38:42'),
(5, 10.00, '', '2025-09-19', '2025-09-19', '2025-09-19 15:56:35'),
(6, 10.00, '', '2025-09-20', '2025-09-20', '2025-09-19 16:04:53'),
(7, 35.00, '', '2025-09-20', '2025-09-21', '2025-09-19 16:26:58'),
(8, 50.00, '', '2025-09-26', '2025-09-26', '2025-09-26 07:34:41'),
(9, 100.00, 'flash sale', '2025-09-29', '2025-10-02', '2025-09-29 12:46:23'),
(10, 50.00, 'now best offer!!!', '2025-10-02', '2025-10-03', '2025-10-01 16:33:42'),
(11, 30.00, 'flash sale now !!!', '2025-10-04', '2025-10-06', '2025-10-03 16:01:27'),
(12, 20.00, 'now free', '2025-10-07', '2025-10-08', '2025-10-07 03:21:32'),
(13, 30.00, 'flash sale', '2025-10-08', '2025-10-09', '2025-10-08 06:40:26'),
(14, 100.00, 'now offer', '2025-10-11', '2025-10-12', '2025-10-11 05:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `slots_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration` int(11) NOT NULL,
  `slots` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_slots` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `slots_date`, `start_time`, `end_time`, `duration`, `slots`, `status`, `created_at`, `total_slots`) VALUES
(32, '2025-10-09', '08:00:00', '17:00:00', 60, 2, 'available', '2025-10-07 20:50:13', 2);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `service_detail` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `service_detail`, `image`, `status`, `created_at`) VALUES
(4, ' Grooming', 'Keep your pets clean, healthy, and happy with professional grooming. From baths and haircuts to nail trimming, our grooming service ensures your furry friends look and feel their best.', 'groom.jpg', 'active', '2025-09-20 16:08:33'),
(5, ' Vaccination', 'Protect your pets from common diseases with safe and reliable vaccinations. Regular shots help boost immunity and keep your companions healthy for years to come.', 'bg3.jpg', 'active', '2025-09-20 16:09:31');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_no` varchar(50) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `service` varchar(100) NOT NULL,
  `appointment_date` date NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `status` enum('Pending','Approved','Completed','Rejected','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_no`, `user_id`, `service`, `appointment_date`, `time_slot`, `status`, `created_at`) VALUES
('TR-20251009-330839', 'fKVqHNtxawqi9BgHxBTt', ' Grooming', '2025-10-09', '08:00 AM - 09:00 AM', 'Approved', '2025-10-09 14:24:21'),
('TR-20251009-434151', 'fKVqHNtxawqi9BgHxBTt', ' Grooming', '2025-10-09', '09:00 AM - 10:00 AM', 'Completed', '2025-10-09 14:34:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `code_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `profile_pic` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `address` varchar(255) NOT NULL,
  `last_activity` datetime DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `verification_code`, `code_expires_at`, `is_verified`, `profile_pic`, `status`, `created_at`, `updated_at`, `address`, `last_activity`, `role`) VALUES
('2eb5ea9e4b6829676c530bc4bcd9ce07', 'melvince', 'petcare608@gmail.com', '0976676632', '$2y$10$XfXWt49Fwcazhg8MDaLmsO63LNujISYseCXC80W3NIbpzkkRPLFR.', NULL, NULL, 1, 'uploads/1760196606_d1.jpg', 'active', '2025-10-08 06:07:10', '2025-10-11 15:33:51', 'san ildefonso', '2025-10-11 23:33:51', 'user'),
('5f19a3572b446c20aa077261c0602f78', 'heh', 'jbert.blank@gmail.com', '09762373332', '$2y$10$CN1IrKrYfenlqqrq.gEXd.ScIIuvi6tyzwMTgPhGYuhYPvwshQ4OO', NULL, NULL, 1, NULL, 'active', '2025-10-08 06:11:38', '2025-10-08 06:14:17', 'san ildefonso bulacan', '2025-10-08 14:14:17', 'user'),
('fKVqHNtxawqi9BgHxBTt', 'melvince', 'melvince20bernardo@gmail.com', '65657556456456', '$2y$10$kVn8eUrtJ840nqwyDTHwsODZXvdHOWfu2sAX2VcL8eV1FhVd6aaou', NULL, NULL, 1, 'uploads/1760019732_b3.jpg', 'active', '2025-10-08 07:04:36', '2025-10-11 15:35:46', 'san ildefonso', '2025-10-11 23:35:46', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_slot` (`active_slot`),
  ADD KEY `fk_transaction` (`transaction_no`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `community_comments`
--
ALTER TABLE `community_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `community_votes`
--
ALTER TABLE `community_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_id` (`post_id`,`user_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`product_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_reviews`
--
ALTER TABLE `order_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promotion`
--
ALTER TABLE `promotion`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_no`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=607;

--
-- AUTO_INCREMENT for table `community_comments`
--
ALTER TABLE `community_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `community_votes`
--
ALTER TABLE `community_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=729;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=326;

--
-- AUTO_INCREMENT for table `order_reviews`
--
ALTER TABLE `order_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `promotion`
--
ALTER TABLE `promotion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_transaction` FOREIGN KEY (`transaction_no`) REFERENCES `transactions` (`transaction_no`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_reviews`
--
ALTER TABLE `order_reviews`
  ADD CONSTRAINT `fk_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
