-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2026 at 11:49 AM
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
-- Database: `yatis_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `business_type` enum('food','goods','services') NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `is_open` tinyint(1) DEFAULT 1,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `available_tables` int(11) DEFAULT 0,
  `seats_per_table` int(11) DEFAULT 0,
  `is_subscribed` tinyint(1) DEFAULT 0,
  `subscription_date` timestamp NULL DEFAULT NULL,
  `shop_image` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `featured_products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`featured_products`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`id`, `user_id`, `business_name`, `business_type`, `description`, `address`, `phone`, `email`, `logo`, `is_open`, `opening_time`, `closing_time`, `capacity`, `available_tables`, `seats_per_table`, `is_subscribed`, `subscription_date`, `shop_image`, `latitude`, `longitude`, `featured_products`, `created_at`, `updated_at`) VALUES
(1, 3, 'ron restaurant', 'food', 'adsasa', 'The Church of Jesus Christ of Latter-day Saints, A.E. Marañon Street, Sagay, Negros Occidental, Negros Island Region, Philippines', '09123459756', 'ron@gmail.com', NULL, 0, '08:00:00', '17:00:00', 20, 9, 6, 0, NULL, NULL, 10.88864975, 123.41299117, NULL, '2026-03-09 05:40:16', '2026-03-09 09:00:53'),
(2, 3, 'Prince Hypermarket', 'goods', 'sadsad', 'Prince Hypermart, Bacolod North Road, Sagay, Negros Occidental, Negros Island Region, Philippines', '09123459798', 'ron@gmail.com', NULL, 0, '08:00:00', '18:00:00', 0, 0, 0, 0, NULL, NULL, 10.89233454, 123.41252714, NULL, '2026-03-09 05:49:53', '2026-03-09 10:00:57');

-- --------------------------------------------------------

--
-- Table structure for table `destination_reviews`
--

CREATE TABLE `destination_reviews` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_tasks`
--

CREATE TABLE `event_tasks` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` enum('steps','location','qr_scan','custom') NOT NULL,
  `target_value` int(11) DEFAULT NULL,
  `reward_points` int(11) DEFAULT 10,
  `reward_description` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `friendships`
--

CREATE TABLE `friendships` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friendships`
--

INSERT INTO `friendships` (`id`, `user_id`, `friend_id`, `status`, `created_at`) VALUES
(1, 4, 5, 'accepted', '2026-03-09 06:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `member_limit` int(11) DEFAULT 50,
  `privacy` enum('public','private') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`, `creator_id`, `member_limit`, `privacy`, `created_at`) VALUES
(1, 'Clan kalan', 'riot', 4, 50, 'public', '2026-03-09 08:32:02');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `role`, `joined_at`) VALUES
(6, 1, 4, 'member', '2026-03-09 08:37:07');

-- --------------------------------------------------------

--
-- Table structure for table `group_messages`
--

CREATE TABLE `group_messages` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group_messages`
--

INSERT INTO `group_messages` (`id`, `group_id`, `sender_id`, `content`, `created_at`) VALUES
(1, 1, 4, 'dsad', '2026-03-09 08:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `group_message_reads`
--

CREATE TABLE `group_message_reads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `last_read_message_id` int(11) NOT NULL,
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group_message_reads`
--

INSERT INTO `group_message_reads` (`id`, `user_id`, `group_id`, `last_read_message_id`, `last_read_at`) VALUES
(1, 4, 1, 1, '2026-03-09 08:38:24');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `resume_path` varchar(255) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('pending','reviewed','accepted','rejected') DEFAULT 'pending',
  `interview_date` datetime DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`id`, `job_id`, `user_id`, `resume_path`, `cover_letter`, `status`, `interview_date`, `applied_at`) VALUES
(1, 1, 4, 'uploads/resumes/resume_4_1773037849.pdf', '', 'reviewed', '2026-03-13 08:30:00', '2026-03-09 06:30:49'),
(2, 2, 4, 'uploads/resumes/resume_4_1773040081.pdf', '', 'accepted', '2026-03-09 15:20:00', '2026-03-09 07:08:01'),
(3, 2, 5, 'uploads/resumes/resume_5_1773043147.pdf', '', 'accepted', '2026-03-09 16:22:00', '2026-03-09 07:59:07'),
(4, 1, 5, 'uploads/resumes/resume_5_1773044353.pdf', '', 'rejected', '2026-03-09 16:21:00', '2026-03-09 08:19:13');

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `business_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `job_type` enum('full-time','part-time','contract','freelance') DEFAULT 'full-time',
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`id`, `employer_id`, `business_id`, `title`, `description`, `requirements`, `salary_range`, `location`, `job_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'Waiter', 'High School Graduate', 'adsadsa', '15000', 'Sagay City, Negros Occidental', 'full-time', 'open', '2026-03-09 06:11:02', '2026-03-09 06:11:02'),
(2, 3, 2, 'Sales Associate', 'adasdsa', 'HS Grad', '15000', 'Sagay City, Negros Occidental', 'full-time', 'open', '2026-03-09 07:00:38', '2026-03-09 07:00:38');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `business_id`, `name`, `description`, `price`, `image`, `category`, `is_available`, `created_at`) VALUES
(1, 1, 'asdsad', 'rewqe4qw', 156.00, 'uploads/menu_items/menu_items_69ae63b8321ef.jpg', 'food', 1, '2026-03-09 06:07:52');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `privacy` enum('public','friends','private') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `private_messages`
--

CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `business_id`, `name`, `description`, `price`, `stock`, `image`, `category`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 2, 'Curls', 'qqweqw', 15.00, 1000, NULL, 'Curls', 1, '2026-03-09 06:07:07', '2026-03-09 06:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `profile_visits`
--

CREATE TABLE `profile_visits` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `visited_user_id` int(11) NOT NULL,
  `visit_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_tables`
--

CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `table_number` int(11) NOT NULL,
  `seats` int(11) NOT NULL DEFAULT 4,
  `is_occupied` tinyint(1) DEFAULT 0,
  `occupied_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `business_id`, `table_number`, `seats`, `is_occupied`, `occupied_at`, `created_at`) VALUES
(1, 1, 1, 6, 0, NULL, '2026-03-09 06:19:53'),
(2, 1, 2, 6, 0, NULL, '2026-03-09 06:19:53'),
(3, 1, 3, 6, 0, NULL, '2026-03-09 06:19:53'),
(4, 1, 4, 6, 0, NULL, '2026-03-09 06:19:53'),
(5, 1, 5, 6, 0, NULL, '2026-03-09 06:19:53'),
(6, 1, 6, 6, 0, NULL, '2026-03-09 06:19:53'),
(7, 1, 7, 6, 0, NULL, '2026-03-09 06:19:53'),
(8, 1, 8, 6, 0, NULL, '2026-03-09 06:19:53'),
(9, 1, 9, 6, 0, NULL, '2026-03-09 06:19:53');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan` enum('free','premium') DEFAULT 'free',
  `amount` decimal(10,2) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tourist_destinations`
--

CREATE TABLE `tourist_destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourist_destinations`
--

INSERT INTO `tourist_destinations` (`id`, `name`, `description`, `location`, `address`, `latitude`, `longitude`, `image`, `average_rating`, `total_reviews`, `created_at`) VALUES
(1, 'Panal Reef', 'Another beautiful marine reef perfect for snorkeling and swimming. Features diverse coral formations and tropical fish.', 'Sagay City, Negros Occidental', 'Panal Reef, Sagay Marine Reserve', 10.94500000, 123.41500000, '', 0.00, 0, '2026-03-09 03:40:34'),
(2, 'Macahulom Reef', 'Rich marine area ideal for nature exploration and underwater activities. Part of the protected marine sanctuary.', 'Sagay City, Negros Occidental', 'Macahulom, Sagay City', 10.91200000, 123.43800000, '', 0.00, 0, '2026-03-09 03:40:34'),
(3, 'Molocaboc Island', 'Scenic island with mangroves and a traditional fishing community. Great for eco-tourism and cultural immersion.', 'Sagay City, Negros Occidental', 'Molocaboc Island, Sagay City', 10.93500000, 123.42800000, '', 0.00, 0, '2026-03-09 03:40:34'),
(4, 'Margaha Beach', 'Unique black sand beach with local dining options and beautiful seaside views. Popular spot for sunset watching.', 'Sagay City, Negros Occidental', 'Barangay Margaha, Sagay City', 10.90500000, 123.41000000, '', 0.00, 0, '2026-03-09 03:40:34'),
(5, 'Museo Sang Bata sa Negros', 'Interactive children\'s marine museum showcasing marine life and environmental education. Fun and educational for all ages.', 'Sagay City, Negros Occidental', 'Sagay City Center', 10.89700000, 123.42600000, '', 0.00, 0, '2026-03-09 03:40:34'),
(6, 'Vito Church (St. Joseph the Worker Parish)', 'Historic church and important cultural site. Beautiful architecture and peaceful atmosphere for prayer and reflection.', 'Sagay City, Negros Occidental', 'Barangay Vito, Sagay City', 10.88000000, 123.44800000, '', 0.00, 0, '2026-03-09 03:40:34'),
(7, 'The Legendary Siete', 'A preserved old steam locomotive on display, representing Sagay\'s sugar industry heritage. Great photo opportunity and historical landmark.', 'Sagay City, Negros Occidental', 'Sagay City Center', 10.89650000, 123.42500000, '', 0.00, 0, '2026-03-09 03:40:34'),
(8, 'City Garden & Living Tree Museum', 'Beautiful botanical garden featuring various plant species and living tree exhibits. Perfect for nature walks and relaxation.', 'Sagay City, Negros Occidental', 'Sagay City Center', 10.89750000, 123.42550000, '', 0.00, 0, '2026-03-09 03:40:34'),
(9, 'Himoga-an River Cruise', 'Scenic river boat ride through mangroves and natural landscapes. Relaxing way to experience Sagay\'s natural beauty.', 'Sagay City, Negros Occidental', 'Himoga-an, Sagay City', 10.92800000, 123.41200000, '', 0.00, 0, '2026-03-09 03:40:34'),
(10, 'Sinigayan Festival', 'Annual cultural festival celebrated every March. Features street dancing, cultural performances, and local food. Experience Sagay\'s vibrant culture and traditions.', 'Sagay City, Negros Occidental', 'Sagay City Plaza', 10.89670000, 123.42530000, '', 0.00, 0, '2026-03-09 03:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_private` tinyint(1) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `role` enum('user','business','employer','admin') DEFAULT 'user',
  `is_premium` tinyint(1) DEFAULT 0,
  `premium_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `profile_picture`, `cover_photo`, `bio`, `is_private`, `latitude`, `longitude`, `location_name`, `role`, `is_premium`, `premium_expires_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@yatis.com', '$2y$10$RAz7Fxnh2fsR0FFs3hPlFOG3VAvlkmQg.gYAWpDA0NPaJ78BX/3fC', 'System', 'Administrator', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'admin', 0, NULL, '2026-03-09 03:43:46', '2026-03-09 03:50:54'),
(2, 'almencion@gmail.com', 'almencion@gmail.com', '$2y$10$YnXQPlilGA0WXiGIjwhBaOujBtGtDxD7auhMGs3Mqm1bILTDZl4NW', 'kent', 'almencion', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'business', 0, NULL, '2026-03-09 03:56:43', '2026-03-09 03:56:43'),
(3, 'ron', 'ron@gmail.com', '$2y$10$ioQ.a/AUOgy8orfg9ND6X.35pnkfiD5XjsdwNrciz6jG9d681/Ms.', 'ron', 'almencion', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'business', 0, NULL, '2026-03-09 04:00:13', '2026-03-09 04:00:13'),
(4, 'jayson', 'jayson@gmail.com', '$2y$10$rYAT29fQjGSTynliS2iE2OmdBdojKPxFOLnpRcrGBNzHglS5A0tS.', 'jayson', 'almencion', 'uploads/profile_photos/profile_4_1773052426.jpg', NULL, NULL, 0, NULL, NULL, NULL, 'user', 0, NULL, '2026-03-09 04:00:53', '2026-03-09 10:33:46'),
(5, 'kelir', 'kelir@gmail.com', '$2y$10$lYa5xMEjQMZbHKUcZqm4nOy19vBuFWhZk6fYdNiUnFxXh4aIwFEdO', 'kelir', 'almencion', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'user', 0, NULL, '2026-03-09 04:02:25', '2026-03-09 04:02:25');

-- --------------------------------------------------------

--
-- Table structure for table `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `total_tasks_completed` int(11) DEFAULT 0,
  `rank_position` int(11) DEFAULT 0,
  `badges` text DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_task_completions`
--

CREATE TABLE `user_task_completions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `proof_data` text DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business_type` (`business_type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_subscribed` (`is_subscribed`);

--
-- Indexes for table `destination_reviews`
--
ALTER TABLE `destination_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`destination_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `event_tasks`
--
ALTER TABLE `event_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `friendships`
--
ALTER TABLE `friendships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_friendship` (`user_id`,`friend_id`),
  ADD KEY `friend_id` (`friend_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_creator` (`creator_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_membership` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group_messages`
--
ALTER TABLE `group_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_group_id` (`group_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_group_created` (`group_id`,`created_at`);

--
-- Indexes for table `group_message_reads`
--
ALTER TABLE `group_message_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_group` (`user_id`,`group_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `last_read_message_id` (`last_read_message_id`),
  ADD KEY `idx_user_group` (`user_id`,`group_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employer_id` (`employer_id`),
  ADD KEY `idx_business_id` (`business_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business_id` (`business_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_receiver_id` (`receiver_id`),
  ADD KEY `idx_conversation` (`sender_id`,`receiver_id`,`created_at`),
  ADD KEY `idx_unread` (`receiver_id`,`is_read`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business_id` (`business_id`);

--
-- Indexes for table `profile_visits`
--
ALTER TABLE `profile_visits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_visitor_visited` (`visitor_id`,`visited_user_id`),
  ADD KEY `idx_visited_user_time` (`visited_user_id`,`visit_time`),
  ADD KEY `idx_visitor_time` (`visitor_id`,`visit_time`);

--
-- Indexes for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_table` (`business_id`,`table_number`),
  ADD KEY `idx_business_id` (`business_id`),
  ADD KEY `idx_is_occupied` (`is_occupied`),
  ADD KEY `idx_business_occupied` (`business_id`,`is_occupied`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business_id` (`business_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `tourist_destinations`
--
ALTER TABLE `tourist_destinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_location` (`location`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `user_task_completions`
--
ALTER TABLE `user_task_completions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_task` (`user_id`,`task_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `task_id` (`task_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `destination_reviews`
--
ALTER TABLE `destination_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_tasks`
--
ALTER TABLE `event_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friendships`
--
ALTER TABLE `friendships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `group_messages`
--
ALTER TABLE `group_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `group_message_reads`
--
ALTER TABLE `group_message_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `profile_visits`
--
ALTER TABLE `profile_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tourist_destinations`
--
ALTER TABLE `tourist_destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_task_completions`
--
ALTER TABLE `user_task_completions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `destination_reviews`
--
ALTER TABLE `destination_reviews`
  ADD CONSTRAINT `destination_reviews_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `tourist_destinations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `destination_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_tasks`
--
ALTER TABLE `event_tasks`
  ADD CONSTRAINT `event_tasks_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `friendships`
--
ALTER TABLE `friendships`
  ADD CONSTRAINT `friendships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friendships_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_messages`
--
ALTER TABLE `group_messages`
  ADD CONSTRAINT `group_messages_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_message_reads`
--
ALTER TABLE `group_message_reads`
  ADD CONSTRAINT `group_message_reads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_message_reads_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_message_reads_ibfk_3` FOREIGN KEY (`last_read_message_id`) REFERENCES `group_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_postings_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `private_messages`
--
ALTER TABLE `private_messages`
  ADD CONSTRAINT `private_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_visits`
--
ALTER TABLE `profile_visits`
  ADD CONSTRAINT `profile_visits_ibfk_1` FOREIGN KEY (`visitor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profile_visits_ibfk_2` FOREIGN KEY (`visited_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD CONSTRAINT `restaurant_tables_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_task_completions`
--
ALTER TABLE `user_task_completions`
  ADD CONSTRAINT `user_task_completions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_task_completions_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_task_completions_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `event_tasks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
