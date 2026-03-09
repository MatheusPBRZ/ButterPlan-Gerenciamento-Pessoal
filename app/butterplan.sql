-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 08/03/2026 às 23:45
-- Versão do servidor: 9.1.0
-- Versão do PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `butterplan`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `fixed_expenses`
--

DROP TABLE IF EXISTS `fixed_expenses`;
CREATE TABLE IF NOT EXISTS `fixed_expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `day_of_month` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `fixed_expenses`
--

INSERT INTO `fixed_expenses` (`id`, `title`, `amount`, `day_of_month`) VALUES
(1, 'Spotify', 12.90, 16),
(2, 'Faculdade', 255.00, 8);

-- --------------------------------------------------------

--
-- Estrutura para tabela `installments`
--

DROP TABLE IF EXISTS `installments`;
CREATE TABLE IF NOT EXISTS `installments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `total_installments` int NOT NULL,
  `installment_amount` decimal(10,2) NOT NULL,
  `due_day` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `installments`
--

INSERT INTO `installments` (`id`, `title`, `total_amount`, `total_installments`, `installment_amount`, `due_day`, `created_at`) VALUES
(2, 'Monitor Superframe', 990.00, 12, 82.50, 20, '2026-03-05 20:15:34');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tasks`
--

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `category` varchar(50) DEFAULT 'Geral',
  `status` enum('pending','done','expired') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_recurring` tinyint(1) DEFAULT '0',
  `recurrence_days` varchar(20) DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_task_parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('income','expense') NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT 'Geral',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `installment_id` int DEFAULT NULL,
  `is_investment` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `transactions`
--

INSERT INTO `transactions` (`id`, `type`, `description`, `amount`, `category`, `created_at`, `installment_id`, `is_investment`) VALUES
(2, 'expense', 'Faculdade', 255.00, 'Geral', '2026-02-09 01:09:48', NULL, 0),
(3, 'income', 'Salario', 255.00, 'Geral', '2026-02-09 01:29:56', NULL, 0),
(4, 'income', 'Saldo Atual ', 33.50, 'Geral', '2026-02-10 21:15:12', NULL, 0),
(5, 'expense', 'Spotify', 12.90, 'Geral', '2026-02-22 04:20:01', NULL, 0),
(6, 'income', 'Tia Maria', 100.00, 'Geral', '2026-02-22 04:20:18', NULL, 0),
(7, 'income', 'Vó cida', 100.00, 'Geral', '2026-02-22 04:20:32', NULL, 0),
(8, 'income', 'Salário', 241.00, 'Geral', '2026-02-22 04:21:43', NULL, 0),
(9, 'income', 'Vó Vera', 170.00, 'Geral', '2026-02-22 04:23:09', NULL, 0),
(10, 'expense', 'Pneu da Bike', 170.00, 'Geral', '2026-02-22 04:23:27', NULL, 0),
(11, 'expense', 'Parcela: Monitor Superframe (1x)', 82.50, 'Geral', '2026-03-01 14:42:30', NULL, 0),
(12, 'expense', 'Pizaria (MIster Dani)', 46.50, 'Geral', '2026-03-01 14:51:52', NULL, 0),
(13, 'expense', 'Braços Articulados', 160.00, 'Geral', '2026-03-01 14:53:04', NULL, 0),
(14, 'expense', 'Hub Usb', 51.51, 'Geral', '2026-03-01 14:53:26', NULL, 0),
(15, 'expense', 'Ifood', 7.90, 'Geral', '2026-03-01 14:53:51', NULL, 0),
(16, 'expense', 'Energético', 6.50, 'Geral', '2026-03-01 14:54:15', NULL, 0),
(17, 'income', 'Salario', 430.00, 'Geral', '2026-03-05 16:42:40', NULL, 0),
(18, 'income', 'Intera do que faltava', 7.38, 'Geral', '2026-03-05 16:43:51', NULL, 0),
(19, 'expense', 'Faculdade', 255.00, 'Geral', '2026-03-05 16:47:04', NULL, 0),
(20, 'expense', 'Parcela: Monitor Superframe (2x)', 165.00, 'Geral', '2026-03-05 20:11:48', NULL, 0),
(21, 'expense', 'Parcela: Monitor Superframe (1x)', 82.50, 'Geral', '2026-03-05 20:15:37', 2, 0),
(23, 'income', 'Pra ficar certo', 248.43, 'Geral', '2026-03-05 20:17:59', NULL, 0),
(24, 'expense', 'Rosca do Carlos e Edgar', 9.00, 'Geral', '2026-03-07 13:23:38', NULL, 0),
(25, 'expense', 'Fita dupla face', 19.00, 'Geral', '2026-03-07 14:32:49', NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_task_parent` FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
