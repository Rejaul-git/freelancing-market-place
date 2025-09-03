-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2025 at 05:40 AM
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
-- Database: `complete_database_schema`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `parent_id`, `sort_order`, `status`, `created_at`, `image`) VALUES
(2, 'logo desigin', 'logo-desigin', 'design your logo', '', NULL, 0, 'active', '2025-08-06 03:54:40', ''),
(8, 'web development', 'web-development', 'asfasf', '', 2, 0, 'active', '2025-08-06 19:58:20', 'uploads/1754510300_6893b3dc58305.jpg'),
(10, 'Graphics & Design', 'graphics-design', 'Graphics & Design', ' 🎨 ', 2, 0, 'active', '2025-08-09 11:47:38', 'uploads/videoAnimi.jpeg'),
(13, 'Video & Animation', 'video-animation', 'Video & Animation Bring your story to life with creative videos.', '🎥', 2, 2, 'active', '2025-08-09 12:03:41', 'uploads/videoAnimi.jpeg'),
(14, 'App development', 'app-development', 'Build your modern app', '📱', 8, 0, 'active', '2025-08-09 12:10:21', 'uploads/appdev.png'),
(15, 'Writing & Translation', 'writing-translation', 'write anythings you need', ' 🎨 ', 10, 0, 'active', '2025-08-10 18:09:32', 'uploads/1754849372_6898e05cc2a49.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` varchar(100) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `last_message` text DEFAULT NULL,
  `last_message_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `earnings`
--

CREATE TABLE `earnings` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL,
  `net_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','available','withdrawn') DEFAULT 'pending',
  `available_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gig_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gigs`
--

CREATE TABLE `gigs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `total_stars` int(11) DEFAULT 0,
  `star_number` int(11) DEFAULT 0,
  `category` varchar(100) NOT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cover` varchar(255) NOT NULL,
  `images` text DEFAULT NULL,
  `short_title` varchar(255) NOT NULL,
  `short_description` text NOT NULL,
  `delivery_time` int(11) NOT NULL,
  `revision_number` int(11) NOT NULL,
  `features` text DEFAULT NULL,
  `sales` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `status` enum('active','inactive','pending','rejected') DEFAULT 'pending',
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gigs`
--

INSERT INTO `gigs` (`id`, `user_id`, `title`, `description`, `total_stars`, `star_number`, `category`, `subcategory`, `price`, `cover`, `images`, `short_title`, `short_description`, `delivery_time`, `revision_number`, `features`, `sales`, `views`, `status`, `featured`, `created_at`, `updated_at`) VALUES
(4, 9, 'i will desing your logo', 'i will help to build your website dynamic and interactive ', 5, 4, 'logo desigin', NULL, 343.00, 'uploads/1754511734_T17187911911718792285.png', '[]', 'safasf', 'i will help to build your website dynamic and interactive ', 3, 3, '[\"asfas\",\"sfasf\",\"asfasdf\",\"asfas\"]', 15, 0, 'active', 0, '2025-08-06 20:22:14', '2025-08-19 18:43:41'),
(6, 8, 'I will do full stack website development as full stack, front end web developer', 'About this gig\r\nTired of generic developers who can\'t tailor solutions to your real business challenges?\r\n\r\nLet\'s change that.\r\n\r\n\r\n\r\nI\'m a full stack website and software developer with a strong track record of building custom, high-performance websites that solve real business problems. Whether you\'re launching something new or need to rebuild an underperforming site, I focus on clean architecture, seamless UX, and scalable backend logic so your platform does exactly what you need it to do, without compromise.\r\n\r\n\r\n\r\nWhat I Offer:\r\n\r\n\r\n\r\nCustom Full Stack Website Development\r\nResponsive and Mobile-Friendly Design\r\nFront End and Back End Development with PHP, Laravel, HTML, CSS\r\nFull Integration with Payment Gateways\r\nRedesign Website for Better Performance & User Experience\r\nSEO Optimization for Enhanced Visibility\r\nCustom Features and Functionalities\r\n\r\n\r\nWhy Choose Me?\r\n\r\n\r\n\r\nExperienced Full Stack Developer & UI UX Designer\r\nOn-Time Delivery & Post-Delivery Support\r\n24/7 Constant Communication (Literally)\r\n\r\n\r\nReady to take your online presence to the next level? Let\'s work together to create your custom online presence!', 0, 0, 'web development', NULL, 200.00, 'uploads/1755515738_end-developer.jpg', '[]', 'full stack website and software developer ', 'I\'m a full stack website and software developer with a strong track record of building custom, high-performance websites that solve real business problems.', 3, 4, '[\"Custom Full Stack Website Development\",\"Responsive and Mobile-Friendly Design\",\"Front End and Back End Development with PHP, Laravel, HTML, CSS\",\"Full Integration with Payment Gateways\"]', 3, 0, 'active', 0, '2025-08-18 11:15:38', '2025-08-19 20:33:24'),
(7, 8, 'I will do ios app development and android development as a mobile app developer', 'About this gig\r\nExpert Mobile App Developer for iOS app development and Android Development\r\n\r\n\r\n\r\nAre you looking for a skilled mobile app developer to bring your ideas to life? With over 4 years of experience and a portfolio of 200+ apps, I specialize in high-quality Android and iOS app development. I use the latest technology to build apps such as:\r\n\r\n\r\n\r\n- Business management App\r\n\r\n- Gym App\r\n\r\n- Employee Management App\r\n\r\n- Event Management App\r\n\r\n- Restaurant Management and Food Delivery App\r\n\r\n- Chat and Call App\r\n\r\n- On-demand App\r\n\r\n\r\n\r\nMy services\r\n\r\n\r\n\r\nAndroid App Development\r\niOS App Development\r\nHybrid App Development\r\nCross-Platform App Development\r\n\r\n\r\nWhy me\r\n\r\n\r\n\r\nSuccessfully developed 200+ apps\r\n100% client satisfaction rate\r\n4+ years of industry experience\r\nStrong base of returning clients\r\nTimely project delivery\r\nCommitment to quality and ongoing support\r\n\r\n\r\nNote: Contact me before placing your order to discuss your project requirements.', 0, 0, 'App development', NULL, 150.00, 'uploads/1755516586_do-android-app-ios-app-development.png', '[\"uploads\\/1755516586_do-android-app-ios-app-development (2).png\",\"uploads\\/1755516586_do-android-app-ios-app-development (1).png\"]', 'I will do ios app development and android development as a mobile app developer', 'I will do ios app development and android development as a mobile app developer', 3, 3, '[\"Android App Development\",\"iOS App Development\",\"Hybrid App Development\",\"Cross-Platform App Development\"]', 1, 0, 'active', 0, '2025-08-18 11:29:46', '2025-08-19 18:43:45');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` varchar(100) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('order','message','review','payment','system') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `gig_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `gig_title` varchar(255) NOT NULL,
  `gig_image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','active','delivered','completed','cancelled','disputed') DEFAULT 'pending',
  `deadline` date DEFAULT NULL,
  `delivery_date` timestamp NULL DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `delivery_note` text DEFAULT NULL,
  `reviewed` tinyint(1) DEFAULT 0,
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `platform_fee` decimal(10,0) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `gig_id`, `buyer_id`, `seller_id`, `gig_title`, `gig_image`, `price`, `status`, `deadline`, `delivery_date`, `requirements`, `delivery_note`, `reviewed`, `payment_status`, `created_at`, `updated_at`, `platform_fee`) VALUES
(18, 4, 10, 9, 'i will desing your logo', 'uploads/1754511734_T17187911911718792285.png', 343.00, 'active', '2025-08-11', NULL, NULL, NULL, 0, 'pending', '2025-08-08 16:25:14', '2025-08-11 17:41:05', 0),
(19, 4, 10, 9, 'i will desing your logo', 'uploads/1754511734_T17187911911718792285.png', 343.00, 'active', '2025-08-11', NULL, NULL, NULL, 0, 'pending', '2025-08-08 16:25:44', '2025-08-17 17:53:37', 0),
(23, 7, 10, 8, 'I will do ios app development and android development as a mobile app developer', 'uploads/1755516586_do-android-app-ios-app-development.png', 150.00, 'completed', '2025-08-21', '2025-08-19 19:28:57', NULL, NULL, 1, 'pending', '2025-08-18 12:00:06', '2025-08-19 19:47:31', 15),
(24, 6, 10, 8, 'I will do full stack website development as full stack, front end web developer', 'uploads/1755515738_end-developer.jpg', 200.00, 'pending', '2025-08-22', NULL, NULL, NULL, 0, 'pending', '2025-08-19 20:20:28', '2025-08-19 20:20:28', 20);

-- --------------------------------------------------------

--
-- Table structure for table `order_deliveries`
--

CREATE TABLE `order_deliveries` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `summary` text DEFAULT NULL,
  `files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`files`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_deliveries`
--

INSERT INTO `order_deliveries` (`id`, `order_id`, `summary`, `files`, `created_at`) VALUES
(1, 18, 'project submitted', '[\"689c85cc93c82.sql\",\"689c85cc93dcc.jpeg\"]', '2025-08-13 12:32:12'),
(2, 20, 'project.php', '[\"68a4cc796103b.png\"]', '2025-08-19 19:11:53'),
(3, 23, 'react.js', '[\"68a4d07914d1b.png\"]', '2025-08-19 19:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) DEFAULT 0.00,
  `seller_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `buyer_id`, `seller_id`, `amount`, `platform_fee`, `seller_amount`, `payment_method`, `transaction_id`, `status`, `created_at`, `updated_at`) VALUES
(4, 18, 10, 9, 343.00, 17.15, 325.85, 'credit_card', 'txn_1754670314412', 'pending', '2025-08-08 16:25:14', '2025-08-08 16:25:14'),
(9, 23, 10, 8, 150.00, 7.50, 142.50, 'paypal', 'txn_1755518406397', 'pending', '2025-08-18 12:00:06', '2025-08-18 12:00:06'),
(10, 24, 10, 8, 200.00, 10.00, 190.00, 'paypal', 'txn_1755634828428', 'pending', '2025-08-19 20:20:28', '2025-08-19 20:20:28');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `gig_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `order_id`, `gig_id`, `buyer_id`, `seller_id`, `rating`, `review_text`, `helpful_count`, `created_at`) VALUES
(2, 23, 7, 10, 8, 5, 'thank you', 0, '2025-08-19 19:47:31');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `img` varchar(255) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `des` text DEFAULT NULL,
  `role` enum('admin','seller','buyer') DEFAULT 'buyer',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `img`, `country`, `phone`, `des`, `role`, `status`, `last_login`, `email_verified`, `created_at`, `updated_at`) VALUES
(7, 'admin', 'admin@gmail.com', '$2y$10$J9E4qFs4XsiBkNCns9yqHuJcgYyXJkY4ZL5YU7RH/yPO46lIFYOcy', '1754511516_ceo1.png', 'Bangladesh', '+8801234567890', 'System Administrator', 'admin', 'active', NULL, 0, '2025-08-06 12:11:37', '2025-08-17 17:51:09'),
(8, 'seller', 'seller@gmail.com', '$2y$10$BX.OGkTVM4myD0f2k698oeEFrZf.deYbwo7J29rQ/ams8dzwTr9ti', '1754506443_chibify-1754339182240.png', 'Bangladesh', '+8801783822929', 'i am seller', 'seller', 'active', NULL, 0, '2025-08-06 18:54:03', '2025-08-06 18:54:03'),
(9, 'rejaul', 'rejaulk431@gmail.com', '$2y$10$xAlg75t1nbPtu8n6NO3qQe0rJzqWq1orkVqwwsAREDQeYH5o.3Csu', '1754511516_ceo1.png', 'Bangladesh', '+8801783822929', 'i am a full stack developer', 'seller', 'active', NULL, 0, '2025-08-06 20:18:36', '2025-08-17 17:51:17'),
(10, 'buyer', 'buyer@gmail.com', '$2y$10$CBD17Xze4zO//mY/2MZmqu4eGNdXJAb6JW4xHW4uGW0955kIodlGy', '1754597243_admin.jpeg', 'Bangladesh', '+8801783822929', 'i am a buyer', 'buyer', 'active', NULL, 0, '2025-08-07 20:07:23', '2025-08-11 17:40:27');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(50) NOT NULL,
  `account_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`account_details`)),
  `status` enum('pending','processing','completed','rejected') DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `earnings`
--
ALTER TABLE `earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_gig` (`user_id`,`gig_id`),
  ADD KEY `gig_id` (`gig_id`);

--
-- Indexes for table `gigs`
--
ALTER TABLE `gigs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`),
  ADD KEY `featured` (`featured`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gig_id` (`gig_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `order_deliveries`
--
ALTER TABLE `order_deliveries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `gig_id` (`gig_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `rating` (`rating`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role` (`role`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `earnings`
--
ALTER TABLE `earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gigs`
--
ALTER TABLE `gigs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `order_deliveries`
--
ALTER TABLE `order_deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `earnings`
--
ALTER TABLE `earnings`
  ADD CONSTRAINT `earnings_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `earnings_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`gig_id`) REFERENCES `gigs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gigs`
--
ALTER TABLE `gigs`
  ADD CONSTRAINT `gigs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_4` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`gig_id`) REFERENCES `gigs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`gig_id`) REFERENCES `gigs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_4` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
