-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2025 at 01:09 AM
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
-- Database: `library_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `isbn` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `edition` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `total_copies` int(11) DEFAULT 1,
  `available_copies` int(11) DEFAULT 1,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `isbn`, `title`, `author`, `genre`, `publisher`, `publication_date`, `edition`, `description`, `total_copies`, `available_copies`, `status`, `created_at`) VALUES
(1, '978-0451524935', '1984', 'George Orwell', 'Fiction', 'Signet Classic', '1950-06-08', NULL, NULL, 5, 6, 'available', '2025-09-19 07:21:10'),
(2, '978-0141439518', 'Pride and Prejudice', 'Jane Austen', 'Romance', 'Penguin Classics', '1813-01-28', NULL, NULL, 3, 3, 'available', '2025-09-19 07:21:10'),
(3, '978-0544003415', 'The Hobbit', 'J.R.R. Tolkien', 'Fantasy', 'Houghton Mifflin', '1937-09-21', NULL, NULL, 4, 3, 'available', '2025-09-19 07:21:10'),
(4, '9780744026955', 'Science of Strength Training: Understand the anatomy and physiology to transform your body', 'Austin Current', 'Science', 'DK', '2021-05-04', '', '', 4, 3, 'available', '2025-09-19 09:54:13'),
(5, '9781512172669', 'Grade 4 Science, 2015-2016, Utah State Office of Education', 'Utah State ', 'Science', 'Utah State Office of Education', '2015-06-09', '', '', 2, 2, 'available', '2025-09-19 12:32:48'),
(7, '9781400242337', 'First-Time Manager: HR', 'Paul Falcone', 'HR', 'HarperCollins Leadership', '2024-05-06', '', '', 10, 10, 'available', '2025-09-19 15:08:24');

-- --------------------------------------------------------

--
-- Table structure for table `book_loans`
--

CREATE TABLE `book_loans` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `librarian_id` int(11) DEFAULT NULL,
  `checkout_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('active','returned','overdue') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_loans`
--

INSERT INTO `book_loans` (`id`, `book_id`, `member_id`, `librarian_id`, `checkout_date`, `due_date`, `return_date`, `status`, `created_at`) VALUES
(1, 1, 1, 1, '2025-09-19', '2025-10-03', '2025-09-19', 'returned', '2025-09-19 09:38:19'),
(2, 4, 1, 1, '2025-09-19', '2025-10-31', '2025-09-19', 'returned', '2025-09-19 09:55:39'),
(3, 2, 1, 1, '2025-09-19', '2025-09-19', '2025-09-19', 'returned', '2025-09-19 14:44:19'),
(4, 7, 5, 1, '2025-09-19', '2025-09-19', '2025-09-19', 'returned', '2025-09-19 15:11:22'),
(5, 3, 2, 1, '2025-09-19', '2025-09-19', '2025-09-20', 'returned', '2025-09-19 20:47:32'),
(6, 5, 5, 1, '2025-09-19', '2025-09-19', '2025-09-20', 'returned', '2025-09-19 20:47:49'),
(7, 3, 6, 1, '2025-09-20', '2025-09-20', NULL, 'active', '2025-09-20 10:07:03'),
(8, 4, 6, 1, '2025-09-20', '2025-09-20', NULL, 'active', '2025-09-20 10:07:22');

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fines`
--

INSERT INTO `fines` (`id`, `loan_id`, `member_id`, `amount`, `reason`, `status`, `paid_date`, `created_at`) VALUES
(1, 5, 2, 2.00, 'Late return - 1 days overdue', 'paid', '2025-09-20', '2025-09-19 22:21:39'),
(2, 6, 5, 10.00, 'Late return - 5 days overdue', 'pending', NULL, '2025-09-19 22:35:26');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `membership_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `status` enum('active','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `user_id`, `membership_id`, `first_name`, `last_name`, `phone`, `address`, `date_of_birth`, `status`) VALUES
(1, 2, 'MEM000002', 'Sydney', 'Moagi', '0789591500', '125 Luipaard Street', '2019-01-29', 'active'),
(2, 3, 'MEM000003', 'Thindeka Lucratia', 'Muhlari', '0784549041', '125 Luipaard Street', '2025-09-03', 'active'),
(3, 4, 'MEM000004', 'Lungile', 'Mabuza', '0704174467', '125 Luipaard Street', '2005-10-09', 'active'),
(4, 5, 'MEM000005', 'katlego', 'Mabuza', '0781364036', '125 Luipaard Street', '2008-01-28', 'suspended'),
(5, 6, 'MEM000006', 'Xilaveko Adelaide', 'Moagi', '0789591500', '125 Luipaard Street', '2019-12-31', 'active'),
(6, 7, 'MEM000007', 'Katlego Libon', 'Mabuza', '0781364036', '125 Luipaard street', '2008-01-28', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('active','fulfilled','expired','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `book_id`, `member_id`, `reservation_date`, `expiry_date`, `status`, `created_at`) VALUES
(1, 1, 2, '2025-09-19', '2025-09-26', 'cancelled', '2025-09-19 07:36:17'),
(2, 1, 2, '2025-09-19', '2025-09-26', 'fulfilled', '2025-09-19 09:32:19'),
(3, 1, 2, '2025-09-19', '2025-09-26', 'active', '2025-09-19 09:36:24'),
(4, 4, 3, '2025-09-19', '2025-09-26', 'active', '2025-09-19 11:45:45'),
(5, 2, 2, '2025-09-19', '2025-09-26', 'active', '2025-09-19 14:41:44'),
(6, 7, 5, '2025-09-19', '2025-09-26', 'fulfilled', '2025-09-19 15:09:16'),
(7, 1, 5, '2025-09-20', '2025-09-27', 'cancelled', '2025-09-19 22:46:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','librarian','member') DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@library.com', 'admin', '2025-09-19 07:21:10'),
(2, 'smoagi67@gmail.com', '$2y$10$.smy17BLIO9jR.wHD5mNS.W6ZemqOtAARFkHUEYxWCvBy3z4PWuny', 'smoagi67@gmail.com', 'member', '2025-09-19 07:29:42'),
(3, 'thindeka67@gmail.com', '$2y$10$gzDniOR5/1fnJczZjqydHu41lSjxJZgLa6LguBzMRyYSjLh1cc5f.', 'thindeka67@gmail.com', 'member', '2025-09-19 07:31:52'),
(4, 'gift', '$2y$10$eIBYajG6IzbFg61XQusGdO2JoJXDbF4Jlg0EYXwy61658RDeidnRS', 'lungilemabuza901@gmail.com', 'member', '2025-09-19 11:41:30'),
(5, 'KATLEGO', '$2y$10$JuRrJ9JatPrUf4QxiVPEkOqNKKNMVq32agFkwxMhDgBy/RU8xc93a', 'katlego.lybon@gmail.com', 'member', '2025-09-19 11:58:45'),
(6, 'xilaveko', '$2y$10$PqZJEfixI804vrD.9BLWse1lFgqigSKuIRuCZydTFlmpyTn04Att.', 'xilaveko.adelaide@gmail.com', 'member', '2025-09-19 15:04:24'),
(7, 'katlego01', '$2y$10$.CstDyU2gxXoh1EdhuQ.auGf3freA4fbT6SCHOsbezpL4agu1bYh.', '218065477@student.uj.ac.za', 'member', '2025-09-20 09:55:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `book_loans`
--
ALTER TABLE `book_loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `librarian_id` (`librarian_id`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `membership_id` (`membership_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `book_loans`
--
ALTER TABLE `book_loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `book_loans`
--
ALTER TABLE `book_loans`
  ADD CONSTRAINT `book_loans_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`),
  ADD CONSTRAINT `book_loans_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `book_loans_ibfk_3` FOREIGN KEY (`librarian_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `book_loans` (`id`),
  ADD CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
