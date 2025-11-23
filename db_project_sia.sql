-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2025 at 02:20 AM
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
-- Database: `db_project_sia`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_chatbot`
--

CREATE TABLE `tb_chatbot` (
  `chatbot_id` int(11) NOT NULL,
  `stud_id` int(11) DEFAULT NULL,
  `chatbot_userMessage` text NOT NULL,
  `chatbot_aiResponse` text NOT NULL,
  `chatbot_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_chatbot`
--

INSERT INTO `tb_chatbot` (`chatbot_id`, `stud_id`, `chatbot_userMessage`, `chatbot_aiResponse`, `chatbot_created`) VALUES
(1, 1, 'I have Diarrhea', 'I understand you are experiencing diarrhea. Diarrhea can be caused by a variety of factors, including infections, dietary issues, or underlying medical conditions.\n\nFor mild cases, it is often recommended to stay hydrated by drinking plenty of fluids like water, clear broths, and electrolyte solutions. Avoiding dairy, fatty foods, and high-fiber foods can also be helpful.\n\nHowever, if your diarrhea is severe, persistent, accompanied by fever, blood in your stool, or signs of dehydration (such as dizziness, decreased urination, or extreme thirst), it is crucial to seek immediate medical attention from a healthcare professional. They can properly diagnose the cause and recommend the most appropriate course of treatment for your specific situation.', '2025-11-19 17:27:19'),
(34, 2, 'im sick', 'I understand you\'re not feeling well. Could you please provide more specific details about your symptoms? Knowing what you\'re experiencing will help me offer general information about potential health concerns.', '2025-11-22 10:28:36'),
(35, 2, 'I have fever', 'A fever is an elevated body temperature, and it can be a sign that your body is fighting off an infection or dealing with inflammation.\n\nIt is important to monitor your temperature and consider other symptoms you might be experiencing. If your fever is high, persistent, or accompanied by severe symptoms, it is advisable to consult a healthcare professional for proper evaluation and guidance.', '2025-11-22 10:36:47');

-- --------------------------------------------------------

--
-- Table structure for table `tb_monitor`
--

CREATE TABLE `tb_monitor` (
  `monitor_id` int(11) NOT NULL,
  `stud_id` int(11) NOT NULL,
  `monitor_datechecked` datetime NOT NULL,
  `monitor_temperature` varchar(50) NOT NULL,
  `monitor_symptoms` text NOT NULL,
  `monitor_remarks` enum('Good','Sick','Recovering','Critical') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_monitor`
--

INSERT INTO `tb_monitor` (`monitor_id`, `stud_id`, `monitor_datechecked`, `monitor_temperature`, `monitor_symptoms`, `monitor_remarks`) VALUES
(1, 1, '2025-11-01 18:52:00', '38', 'Chills,  headache, and fatigue.', 'Sick'),
(2, 2, '2025-11-22 05:23:22', '37', 'Healthy', 'Good');

-- --------------------------------------------------------

--
-- Table structure for table `tb_record`
--

CREATE TABLE `tb_record` (
  `record_id` int(11) NOT NULL,
  `stud_id` int(11) NOT NULL,
  `record_height` varchar(50) NOT NULL,
  `record_weight` varchar(50) NOT NULL,
  `record_bmi` varchar(50) NOT NULL,
  `record_bmiCategory` enum('Underweight','Healthy Weight','Overweight','Obesity') NOT NULL,
  `record_healthissues` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_record`
--

INSERT INTO `tb_record` (`record_id`, `stud_id`, `record_height`, `record_weight`, `record_bmi`, `record_bmiCategory`, `record_healthissues`) VALUES
(1, 1, '175.26', '71', '23.11', 'Healthy Weight', 'Allergy'),
(2, 2, '157.48', '63', '25.4', 'Overweight', 'Rhinitis'),
(3, 3, '157.48', '63', '25.4', 'Overweight', ''),
(4, 4, '167.64', '48', '17.08', 'Underweight', 'Asthma'),
(61, 13, '160.02', '50', '19.53', 'Healthy Weight', ''),
(62, 14, '170.18', '78', '26.93', 'Overweight', '');

-- --------------------------------------------------------

--
-- Table structure for table `tb_students`
--

CREATE TABLE `tb_students` (
  `stud_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stud_lname` varchar(50) NOT NULL,
  `stud_fname` varchar(50) NOT NULL,
  `stud_mname` varchar(50) NOT NULL,
  `stud_course` enum('BS IT','BS Architecture','BS CoE','BS CE','BS EE','BS ME','BSED','BEED','BS Mathemathics','ABEL') NOT NULL,
  `stud_year` enum('1','2','3','4','5') NOT NULL,
  `stud_gender` enum('Male','Female') NOT NULL,
  `stud_age` varchar(50) NOT NULL,
  `stud_imageurl` varchar(255) NOT NULL DEFAULT 'uploads/default_avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_students`
--

INSERT INTO `tb_students` (`stud_id`, `user_id`, `stud_lname`, `stud_fname`, `stud_mname`, `stud_course`, `stud_year`, `stud_gender`, `stud_age`, `stud_imageurl`) VALUES
(1, 2, 'Cruz', 'Juan Mariano', 'Dela', 'BS CoE', '1', 'Male', '22', 'images/students/student_1_1763794142.jpg'),
(2, 3, 'Garcia', 'Maria Isabel', 'Monteclaro', 'BS Mathemathics', '1', 'Female', '19', 'images/students/student_2_1763794226.jpg'),
(3, 4, 'Tabalba', 'Joreson', 'Almuete', 'BS CE', '3', 'Male', '22', ''),
(4, 5, 'Rivera', 'Mill', 'C', 'BS IT', '4', 'Female', '24', ''),
(13, 6, 'Viray', 'Sy', 'Perez', 'BS Architecture', '4', 'Female', '24', 'images/students/default_image.jpg'),
(14, 7, 'Choi', 'Abcde', 'Aguas', 'ABEL', '2', 'Male', '23', '');

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `user_id` int(11) NOT NULL,
  `user_username` varchar(50) NOT NULL,
  `user_email` varchar(50) NOT NULL,
  `user_password` varchar(50) NOT NULL,
  `user_role` enum('Admin','Student') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`user_id`, `user_username`, `user_email`, `user_password`, `user_role`) VALUES
(1, 'admin', 'admin@gmail.com', 'admin', 'Admin'),
(2, '25-UR-0001', 'student_1@gmail.com', '25-UR-0001', 'Student'),
(3, '25-UR-0002', 'student_2@gmail.com', '25-UR-0002', 'Student'),
(4, '25-UR-0003', 'student_3@gmail.com', '25-UR-0003', 'Student'),
(5, '25-UR-0004', 'student_4@gmail.com', '25-UR-0004', 'Student'),
(6, '25-UR-0005', 'student_5@gmail.com', '25-UR-0005', 'Student'),
(7, '25-UR-0006', 'student_6@gmail.com', '25-UR-0006', 'Student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_chatbot`
--
ALTER TABLE `tb_chatbot`
  ADD PRIMARY KEY (`chatbot_id`),
  ADD KEY `fk_student` (`stud_id`);

--
-- Indexes for table `tb_monitor`
--
ALTER TABLE `tb_monitor`
  ADD PRIMARY KEY (`monitor_id`),
  ADD UNIQUE KEY `stud_id` (`stud_id`);

--
-- Indexes for table `tb_record`
--
ALTER TABLE `tb_record`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `stud_id` (`stud_id`);

--
-- Indexes for table `tb_students`
--
ALTER TABLE `tb_students`
  ADD PRIMARY KEY (`stud_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_chatbot`
--
ALTER TABLE `tb_chatbot`
  MODIFY `chatbot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `tb_monitor`
--
ALTER TABLE `tb_monitor`
  MODIFY `monitor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tb_record`
--
ALTER TABLE `tb_record`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `tb_students`
--
ALTER TABLE `tb_students`
  MODIFY `stud_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_chatbot`
--
ALTER TABLE `tb_chatbot`
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`stud_id`) REFERENCES `tb_students` (`stud_id`) ON DELETE SET NULL;

--
-- Constraints for table `tb_monitor`
--
ALTER TABLE `tb_monitor`
  ADD CONSTRAINT `fk_monitor_student` FOREIGN KEY (`stud_id`) REFERENCES `tb_students` (`stud_id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_record`
--
ALTER TABLE `tb_record`
  ADD CONSTRAINT `fk_record_student` FOREIGN KEY (`stud_id`) REFERENCES `tb_students` (`stud_id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_students`
--
ALTER TABLE `tb_students`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
