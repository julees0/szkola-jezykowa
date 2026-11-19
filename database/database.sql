CREATE DATABASE IF NOT EXISTS `szkola_jezykowa` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `szkola_jezykowa`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `surname` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `role` ENUM('client', 'employee', 'admin') NOT NULL DEFAULT 'client',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `service_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `duration` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT `chk_service_duration` CHECK (`duration` > 0),
  CONSTRAINT `chk_service_price` CHECK (`price` >= 0),
  FOREIGN KEY (`category_id`) REFERENCES `service_categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `description` TEXT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `employee_services` (
  `employee_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  PRIMARY KEY (`employee_id`, `service_id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `employee_availability` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `day_of_week` TINYINT NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  CONSTRAINT `chk_day_of_week` CHECK (`day_of_week` BETWEEN 1 AND 7),
  CONSTRAINT `chk_avail_time` CHECK (`start_time` < `end_time`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  INDEX `idx_emp_day` (`employee_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `reservation_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('oczekująca', 'potwierdzona', 'zrealizowana', 'anulowana') NOT NULL DEFAULT 'oczekująca',
  `comment` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `chk_res_time` CHECK (`start_time` < `end_time`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE RESTRICT,
  INDEX `idx_res_date_emp` (`reservation_date`, `employee_id`),
  INDEX `idx_res_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `name`, `surname`, `email`, `password`, `phone`, `role`, `active`) VALUES
(1, 'Admin', 'Systemu', 'admin@szkola.pl', '$2y$12$TOWyrL0ONMGYRM4WcaTwG.bJn0gl/CuMUI7f/F.GnaTod/rxTaWle', '500100100', 'admin', 1),
(2, 'Jan', 'Kowalski', 'jan.kowalski@szkola.pl', '$2y$12$TOWyrL0ONMGYRM4WcaTwG.bJn0gl/CuMUI7f/F.GnaTod/rxTaWle', '500100200', 'employee', 1),
(3, 'Anna', 'Nowak', 'anna.nowak@szkola.pl', '$2y$12$TOWyrL0ONMGYRM4WcaTwG.bJn0gl/CuMUI7f/F.GnaTod/rxTaWle', '500100300', 'employee', 1),
(4, 'Piotr', 'Wiśniewski', 'piotr.w@gmail.com', '$2y$12$TOWyrL0ONMGYRM4WcaTwG.bJn0gl/CuMUI7f/F.GnaTod/rxTaWle', '500100400', 'client', 1);

INSERT INTO `service_categories` (`id`, `name`, `description`) VALUES
(1, 'Język angielski', 'Kursy i lekcje języka angielskiego'),
(2, 'Język niemiecki', 'Kursy i lekcje języka niemieckiego'),
(3, 'Język hiszpański', 'Kursy i lekcje języka hiszpańskiego');

INSERT INTO `services` (`id`, `category_id`, `name`, `description`, `duration`, `price`, `active`) VALUES
(1, 1, 'Angielski - lekcja indywidualna', 'Lekcja 1 na 1 dopasowana do poziomu ucznia', 60, 100.00, 1),
(2, 1, 'Angielski - konwersacje', 'Lekcja konwersacyjna', 45, 80.00, 1),
(3, 2, 'Niemiecki - lekcja indywidualna', 'Lekcja 1 na 1 dla początkujących i zaawansowanych', 60, 100.00, 1),
(4, 3, 'Hiszpański - lekcja indywidualna', 'Lekcja 1 na 1 z lektorem', 60, 110.00, 1);

INSERT INTO `employees` (`id`, `user_id`, `description`, `active`) VALUES
(1, 2, 'Lektor języka angielskiego i niemieckiego, 8 lat doświadczenia.', 1),
(2, 3, 'Lektorka języka angielskiego i hiszpańskiego.', 1);

INSERT INTO `employee_services` (`employee_id`, `service_id`) VALUES
(1, 1), (1, 2), (1, 3),
(2, 1), (2, 4);

INSERT INTO `employee_availability` (`employee_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 1, '08:00:00', '16:00:00'),
(1, 2, '08:00:00', '16:00:00'),
(1, 3, '08:00:00', '16:00:00'),
(1, 4, '08:00:00', '16:00:00'),
(1, 5, '08:00:00', '14:00:00'),
(2, 1, '12:00:00', '20:00:00'),
(2, 3, '12:00:00', '20:00:00'),
(2, 5, '12:00:00', '20:00:00');

INSERT INTO `reservations` (`user_id`, `employee_id`, `service_id`, `reservation_date`, `start_time`, `end_time`, `status`, `comment`) VALUES
(4, 1, 1, '2026-11-09', '10:00:00', '11:00:00', 'potwierdzona', 'Pierwsza lekcja'),
(4, 2, 4, '2026-11-11', '13:00:00', '14:00:00', 'oczekująca', NULL),
(4, 1, 2, '2026-10-28', '09:00:00', '09:45:00', 'zrealizowana', NULL),
(4, 1, 3, '2026-10-30', '11:00:00', '12:00:00', 'anulowana', 'Odwołane przez klienta');
