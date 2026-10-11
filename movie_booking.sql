-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2026 at 04:50 PM
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
-- Database: `movie_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `cinema` varchar(100) NOT NULL,
  `booking_date` date NOT NULL,
  `showtime` varchar(50) NOT NULL,
  `tickets` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `booking_status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_seats`
--

CREATE TABLE `booking_seats` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `seat_number` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `release_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `description`, `genre`, `duration`, `rating`, `price`, `poster`, `status`, `release_date`) VALUES
(1, 'Spider-Man: Brand New Day', 'Spider-Man returns for a new adventure filled with action, challenges, and surprises.', 'Action • Adventure', 120, 8.8, 300.00, 'Spiderman-BrandNewday.jpeg', 'now_showing', '2026-07-31'),
(2, 'Insidious: Out of the Further', 'A terrifying journey into the Further as a new supernatural mystery unfolds.', 'Horror • Thriller', 110, 8.5, 300.00, 'Insidious-outofthefurther.jpeg', 'now_showing', '2026-08-21'),
(3, '2 Valid IDs', 'A fun and relatable story about the challenges of getting two valid IDs.', 'Comedy • Drama', 105, 8.2, 250.00, '2-Valid-IDs.jpeg', 'now_showing', '2026-08-15'),
(4, 'HB2ME', 'A story filled with emotions, friendship, and unforgettable moments.', 'Drama • Romance', 115, 0.0, 300.00, 'HB2ME.jpg', 'coming_soon', '2026-12-01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `given_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `date_of_birth` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `given_name`, `surname`, `password`, `phone_number`, `date_of_birth`, `created_at`) VALUES
(1, 'helloworld@gmail.com', 'hello', 'wolrd', '$2y$10$bljuU9CJHlGG4jIHGaIsm.7mtNZe.7JQf0p6fo5ldKKC1jvsLqsFO', '0912456789', '2000-12-30', '2026-08-18 20:23:52'),
(2, 'xyraeunice02@gmail.com', 'xyra', 'eunice', '$2y$10$FFt3RrR8UUHAQ97xHMpVfOauVeP5spwaxui0rMuQqrLAfmCyOzQLa', '09940500550', '2004-02-08', '2026-09-07 04:10:44'),
(3, 'Xyraquitiquit32@gmail.com', 'xyra', 'quitiquit', '$2y$10$6bESP2NKKrwvaiOfG/u47OMvaDxYHMt63BjHvSsEedcdGrmwR.quG', '09940500550', '2004-08-20', '2026-09-09 19:35:18'),
(4, 'jaschabuenafe@gmail.com', 'jascha', 'buenafe', '$2y$10$Pcm7TdoalsA6bAeofn5OCuX.Qvz0ZCLNzhI99x9GT/4TqbRZy6loO', '0912456789', '2000-12-10', '2026-09-10 11:22:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `booking_date` (`booking_date`);

--
-- Indexes for table `booking_seats`
--
ALTER TABLE `booking_seats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_booking_seat` (`booking_id`,`seat_number`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_seats`
--
ALTER TABLE `booking_seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking_seats`
--
ALTER TABLE `booking_seats`
  ADD CONSTRAINT `booking_seats_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
