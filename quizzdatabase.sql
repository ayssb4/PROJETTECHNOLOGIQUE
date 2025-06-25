 

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

 

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_answer` enum('A','B','C','D') NOT NULL,
  `points` int(11) DEFAULT 1,
  `question_order` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `questions` (`id`, `quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `points`, `question_order`) VALUES
(1, 1, 'quelle est la date de fin de la deuxieme guerre mondiale', '1945', '1830', '2001', '1954', 'A', 1, 1),
(2, 1, 'Quelle est la relation entre la guerre d\'algerie et la deuxieme guerre mondiale', 'les promesses des francais aux algeriens ', 'l\'espagne ', 'les mathematiques', 'math', 'A', 1, 2),
(3, 2, '1+1=', '20', '3', '2', '22', 'C', 1, 1),
(4, 3, '1+1=', '20', '3', '2', '22', 'C', 1, 1),
(5, 3, 'le dl de cos x de 0 à 2', 'x-x2/2!', '50', '20', '60', 'A', 1, 2);

 
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `professor_id` int(11) DEFAULT NULL,
  `time_limit` int(11) NOT NULL DEFAULT 600,
  `total_questions` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

 

INSERT INTO `quizzes` (`id`, `title`, `description`, `subject_id`, `professor_id`, `time_limit`, `total_questions`, `is_active`, `created_at`) VALUES
(1, 'histoire', 'l&#039;histoire de la guerre froide', 2, 3, 60, 2, 1, '2025-06-24 22:39:02'),
(2, 'mathematique', 'addition', 1, 7, 180, 1, 1, '2025-06-25 09:25:45'),
(3, 'mathematique', 'exercice de dl', 1, 7, 60, 2, 1, '2025-06-25 09:44:07');
 

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `total_questions` int(11) DEFAULT 0,
  `correct_answers` int(11) DEFAULT 0,
  `time_taken` int(11) DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

 

INSERT INTO `quiz_attempts` (`id`, `student_id`, `quiz_id`, `score`, `total_questions`, `correct_answers`, `time_taken`, `started_at`, `completed_at`, `is_completed`) VALUES
(1, 4, 1, 100.00, 2, 2, 0, '2025-06-24 22:44:13', '2025-06-24 22:44:13', 1),
(2, 5, 1, 100.00, 2, 2, 0, '2025-06-24 23:11:37', '2025-06-24 23:11:37', 1),
(3, 8, 1, 100.00, 2, 2, 0, '2025-06-25 09:46:28', '2025-06-25 09:46:28', 1),
(4, 8, 3, 50.00, 2, 1, 0, '2025-06-25 09:47:10', '2025-06-25 09:47:10', 1);

 

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `selected_answer` enum('A','B','C','D') DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

 

INSERT INTO `student_answers` (`id`, `attempt_id`, `question_id`, `selected_answer`, `is_correct`, `answered_at`) VALUES
(1, 1, 1, 'A', 1, '2025-06-24 22:44:13'),
(2, 1, 2, 'A', 1, '2025-06-24 22:44:13'),
(3, 2, 1, 'A', 1, '2025-06-24 23:11:37'),
(4, 2, 2, 'A', 1, '2025-06-24 23:11:37'),
(5, 3, 1, 'A', 1, '2025-06-25 09:46:28'),
(6, 3, 2, 'A', 1, '2025-06-25 09:46:28'),
(7, 4, 4, 'A', 0, '2025-06-25 09:47:10'),
(8, 4, 5, 'A', 1, '2025-06-25 09:47:10');

 

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT '?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

 

INSERT INTO `subjects` (`id`, `name`, `icon`) VALUES
(1, 'Mathématiques', '🔢'),
(2, 'Histoire', '📚'),
(3, 'Anglais', '🇬🇧');

 

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('student','professor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 

INSERT INTO `users` (`id`, `email`, `password`, `name`, `role`, `created_at`) VALUES
(1, 'prof@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Professeur Demo', 'professor', '2025-06-24 19:08:22'),
(2, 'eleve@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Élève Demo', 'student', '2025-06-24 19:08:22'),
(3, 'cydra@demo.com', '$2y$10$gno1HXSzM8Sjb9l8flW78Of8zOAX88m2TJd9VfpdteF1C5pSlhh4W', 'cydra', 'professor', '2025-06-24 19:16:28'),
(4, 'ines@demo.com', '$2y$10$cS9bpn.4z4Qx71JTExsF/OpeqL2Asf4boqKcjyjhdkoXzlxohHqlW', 'ines miloudi', 'student', '2025-06-24 20:32:46'),
(5, 'younane@demo.com', '$2y$10$mrvZyb6OLljVJkgP/swlfOho5miWyR3.KDg0h3ORdhdDGAPa6s.Ay', 'younane', 'student', '2025-06-24 23:10:46'),
(6, 'katia@demo.com', '$2y$10$vBBFaaMhV8LMAYZhHQHk8eOUt6lXlKf6uvtzFLgHhyTxHKCy9GBCm', 'miloudikatia', 'student', '2025-06-25 09:20:11'),
(7, 'alissia@demo.com', '$2y$10$PVZ6qxQw8/nFSpr6DHBmR.qHrb6TUJJsxc8GGQQcRp8.0OlrtaA2.', 'alissia', 'professor', '2025-06-25 09:23:20'),
(8, 'henri@demo.com', '$2y$10$V4hgSWtMJQfz7Xo0VzK2fOjzP1RPvrmyJuKC7v6gpq8K4YSfoLA2i', 'HENRI', 'student', '2025-06-25 09:45:41'),
(9, 'alissia@student.com', '$2y$10$W/.yi3HDfVS1rhywsFeTXeNJ3G1nIPTAxhOZ2opTMb6zsEhaHlvfu', 'alissia', 'student', '2025-06-25 09:53:04'),
(10, 'kiko@prof.com', '$2y$10$Hr4tDaD9GgxqFJJhMtQAh.NdrJtrs2UxQO1/NmGcJ3J.4LecyFKra', 'kiko', 'professor', '2025-06-25 09:55:07');

 
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

- 
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `professor_id` (`professor_id`);

 
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `quiz_id` (`quiz_id`);

 
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`);

 
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

 
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

 
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

 
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

 
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

 
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

 
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

 
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

 
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

 
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`professor_id`) REFERENCES `users` (`id`);

 
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`);

 
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);
COMMIT;
 
