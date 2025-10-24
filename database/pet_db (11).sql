-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2025 at 07:31 PM
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `image`, `created_at`) VALUES
('lQ3YcntI1teQLZnmS1pW', 'melvince', 'melvince20bernardo@gmail.com', '356a192b7913b04c54574d18c28d46e6395428ab', 'default-admin.png', '2025-09-14 08:15:23');

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
(155, 'TR-20251002-175676', 'eAEvYO2B1DucUc8zPA0C', '2025-10-04', '07:00 AM - 08:00 AM', 'Pending', NULL, 4, NULL, '2025-10-02 17:27:38'),
(156, 'TR-20251002-264179', 'eAEvYO2B1DucUc8zPA0C', '2025-10-05', '08:00 AM - 08:50 AM', 'Cancelled', NULL, 4, NULL, '2025-10-02 17:27:46'),
(157, 'TR-20251002-253011', 'eAEvYO2B1DucUc8zPA0C', '2025-10-05', '08:00 AM - 08:50 AM', 'Cancelled', NULL, 4, NULL, '2025-10-02 17:28:03'),
(158, 'TR-20251002-594314', 'eAEvYO2B1DucUc8zPA0C', '2025-10-05', '08:00 AM - 08:50 AM', 'Cancelled', NULL, 4, NULL, '2025-10-02 17:30:00'),
(159, 'TR-20251002-438358', 'eAEvYO2B1DucUc8zPA0C', '2025-10-04', '10:00 AM - 11:00 AM', 'Cancelled', NULL, 4, NULL, '2025-10-02 17:30:17'),
(160, 'TR-20251002-771678', 'eAEvYO2B1DucUc8zPA0C', '2025-10-05', '08:00 AM - 08:50 AM', 'Pending', NULL, 4, NULL, '2025-10-02 17:30:27');

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

-- --------------------------------------------------------

--
-- Table structure for table `community_comments`
--

CREATE TABLE `community_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('active','deleted') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`id`, `user_id`, `title`, `content`, `image`, `score`, `views`, `created_at`, `status`) VALUES
(10, 'lSdjSHRx6MWqKBzAn0Vu', 'dog food', 'best dog food ever', 'uploads/community/1759136802_food.jpg', 0, 0, '2025-09-29 17:06:42', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `community_votes`
--

CREATE TABLE `community_votes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `vote` tinyint(4) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_votes`
--

INSERT INTO `community_votes` (`id`, `post_id`, `user_id`, `vote`, `created_at`) VALUES
(2, 2, 'MDg8LR6VWkkcSYEHYelh', 1, '2025-09-22 00:36:14'),
(7, 1, 'MDg8LR6VWkkcSYEHYelh', -1, '2025-09-22 00:37:56'),
(10, 11, 'lSdjSHRx6MWqKBzAn0Vu', -1, '2025-09-29 21:58:28'),
(11, 12, 'lSdjSHRx6MWqKBzAn0Vu', 1, '2025-09-29 21:59:24'),
(16, 15, 'eAEvYO2B1DucUc8zPA0C', 1, '2025-10-02 15:44:32');

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
(80, 'eAEvYO2B1DucUc8zPA0C', 13, '2025-10-02 04:17:01', '2025-10-02 04:17:01', 1),
(82, 'eAEvYO2B1DucUc8zPA0C', 14, '2025-10-02 04:19:03', '2025-10-02 04:19:03', 1);

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
  `phone` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(13, 'beef', 'Dog Food', 500.00, 200.00, 2, 'hehehheheh', 'active', '1758212401_foods.png', 1, '2025-09-18 16:20:01', '2025-10-01 18:08:46'),
(14, 'cats foods', 'Cat Food', 200.00, 10.00, 0, 'dasdasdasdasd', 'active', '1758212445_cat.png', 1, '2025-09-18 16:20:45', '2025-10-02 06:05:10'),
(15, 'chrisbrown', 'Dog Food', 200.00, 40.00, 0, 'asdasdasdasdascas', 'active', '1758295837_dogfood.png', 1, '2025-09-19 15:30:37', '2025-10-01 18:13:31');

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
(10, 50.00, 'now best offer!!!', '2025-10-02', '2025-10-03', '2025-10-01 16:33:42');

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
(23, '2025-10-04', '07:00:00', '17:00:00', 60, 3, 'available', '2025-10-02 17:10:27', 5),
(24, '2025-10-05', '08:00:00', '17:00:00', 50, 3, 'available', '2025-10-02 17:21:09', 3);

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
('TR-20251002-175676', 'eAEvYO2B1DucUc8zPA0C', ' Grooming', '2025-10-04', '07:00 AM - 08:00 AM', 'Pending', '2025-10-02 17:27:38'),
('TR-20251002-253011', 'eAEvYO2B1DucUc8zPA0C', ' Grooming', '2025-10-05', '08:00 AM - 08:50 AM', 'Cancelled', '2025-10-02 17:28:03'),
('TR-20251002-264179', 'eAEvYO2B1DucUc8zPA0C', ' Grooming', '2025-10-05', '08:00 AM - 08:50 AM', 'Cancelled', '2025-10-02 17:27:46'),
('TR-20251002-438358', 'eAEvYO2B1DucUc8zPA0C', ' Grooming', '2025-10-04', '10:00 AM - 11:00 AM', 'Cancelled', '2025-10-02 17:30:17'),
('TR-20251002-594314', 'eAEvYO2B1DucUc8zPA0C', ' Grooming', '2025-10-05', '08:00 AM - 08:50 AM', 'Cancelled', '2025-10-02 17:30:00'),
('TR-20251002-771678', 'eAEvYO2B1DucUc8zPA0C', ' Grooming', '2025-10-05', '08:00 AM - 08:50 AM', 'Pending', '2025-10-02 17:30:27');

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
  `verify_token` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `verification_code`, `code_expires_at`, `verify_token`, `is_verified`, `image`, `status`, `created_at`, `updated_at`, `address`) VALUES
('eAEvYO2B1DucUc8zPA0C', 'vince', 'vincebernardo47@gmail.com', '98323288328', '$2y$10$EJL2aIkHEnEG.m4dzsieqeGclFuTtEFRxlFYDxbvGgXQN37ILd6M6', NULL, NULL, NULL, 1, NULL, 'active', '2025-10-01 18:59:47', '2025-10-01 19:00:13', 'sanildefonso bulacan');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=284;

--
-- AUTO_INCREMENT for table `community_comments`
--
ALTER TABLE `community_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `community_votes`
--
ALTER TABLE `community_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `promotion`
--
ALTER TABLE `promotion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
