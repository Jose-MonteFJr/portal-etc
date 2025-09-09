-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 03/09/2025 às 01:04
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `login_roles`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `usuario` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_completo` varchar(150) NOT NULL,
  `cpf` varchar(11) UNIQUE NOT NULL,
  `data_nascimento` DATE NOT NULL,
  `email` varchar(100) UNIQUE NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `endereco` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `cep` VARCHAR(8) NOT NULL,
  `logradouro` VARCHAR(120) NOT NULL,
  `numero` VARCHAR(10),
  `complemento` VARCHAR(100),
  `bairro` VARCHAR(80),
  `cidade` VARCHAR(80),
  `estado` VARCHAR(50),
  `pais` VARCHAR(50) DEFAULT 'Brasil',
  FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Site', 'Admin', 'admin@example.com', '$2y$10$4v3s86.rU8.Bq7UfqQ2mYuFmbv2voXZxdoeTDb4XdsX0w9AGUlrHG', 'admin', '2025-08-30 22:42:50'),
(2, 'Cristiano', 'Cristiano', 'cristiano@cristiano.com', '$2y$10$GQiaYY.XhqkuZ/GzRpygZO7BWgEjL3SqIZdmUebCFJnfwDkX0erwW', 'user', '2025-08-30 22:42:50'),
(3, 'Micaela', 'Morais', 'micaela@micaela.com', '$2y$10$A5thQ3yREy.eLqp5fi.C/O8TRRblpPPfTopf75bP8HSqDsqheo.H2', 'user', '2025-08-30 23:15:29'),
(5, 'Cristiano', 'Morais', 'cristiano@admin.com', '$2y$10$sIAToRWB9iPfexeKqf6tquxaPy4nvBsDH9lrV5DHuXzJt0QPsYwc6', 'admin', '2025-08-30 23:16:22');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
