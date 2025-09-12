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
-- Banco de dados: `portal_etc`
--

-- CREATE DATABASE portal_etc;


-- -------------------------------------------------------- LEMBRAR DE COLOCAR TODOS NO PLURAL!!!!!!!

--
-- Tabela usuarios
--

CREATE TABLE usuarios (
  id_usuarios INT AUTO_INCREMENT PRIMARY KEY,
  nome_completo VARCHAR(150) NOT NULL,
  cpf VARCHAR(11) UNIQUE NOT NULL, 
  email VARCHAR(150) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  telefone VARCHAR(20) NOT NULL,
  data_nascimento DATE NOT NULL,
  tipo ENUM('aluno', 'professor', 'coordenador', 'secretaria') NOT NULL DEFAULT 'aluno',
  status ENUM('ativo', 'inativo') DEFAULT 'ativo',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- INSERINDO VALORES DE TESTE

INSERT INTO usuarios (nome_completo, cpf, email, password_hash, telefone, data_nascimento) VALUE ('Joao Gomes', '12312312309','joao@joao.com', '$2y$10$4v3s86.rU8.Bq7UfqQ2mYuFmbv2voXZxdoeTDb4XdsX0w9AGUlrHG', '61912341234', '2020-06-25' );

--
-- Tabela alunos
--

CREATE TABLE alunos (
  id_alunos INT AUTO_INCREMENT PRIMARY KEY,
  id_usuarios INT NOT NULL,
  matricula VARCHAR(20) UNIQUE NOT NULL,
  data_ingresso DATE NOT NULL,
  status_academico ENUM('cursando', 'formado', 'trancado', 'desistente') NOT NULL DEFAULT 'cursando',
  id_curso INT NOT NULL,
  id_turma INT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

  CONSTRAINT fk_alunos_usuarios FOREIGN KEY (id_usuarios) REFERENCES usuarios(id_usuarios) ON DELETE CASCADE,
    CONSTRAINT fk_alunos_cursos FOREIGN KEY (id_cursos) REFERENCES cursos(id_cursos) ON DELETE RESTRICT,
    CONSTRAINT fk_alunos_turmas FOREIGN KEY (id_turmas) REFERENCES turmas(id_turmas) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;







-- CREATE TABLE `users` (
--   `id` int(11) NOT NULL,
--   `first_name` varchar(100) NOT NULL,
--   `last_name` varchar(100) NOT NULL,
--   `email` varchar(190) NOT NULL,
--   `password_hash` varchar(255) NOT NULL,
--   `role` enum('admin','user') NOT NULL DEFAULT 'user',
--   `created_at` timestamp NOT NULL DEFAULT current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Despejando dados para a tabela `usuarios`
--

-- INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
-- (1, 'Site', 'Admin', 'admin@example.com', '$2y$10$4v3s86.rU8.Bq7UfqQ2mYuFmbv2voXZxdoeTDb4XdsX0w9AGUlrHG', 'admin', '2025-08-30 22:42:50'),
-- (2, 'Cristiano', 'Cristiano', 'cristiano@cristiano.com', '$2y$10$GQiaYY.XhqkuZ/GzRpygZO7BWgEjL3SqIZdmUebCFJnfwDkX0erwW', 'user', '2025-08-30 22:42:50'),
-- (3, 'Micaela', 'Morais', 'micaela@micaela.com', '$2y$10$A5thQ3yREy.eLqp5fi.C/O8TRRblpPPfTopf75bP8HSqDsqheo.H2', 'user', '2025-08-30 23:15:29'),
-- (5, 'Cristiano', 'Morais', 'cristiano@admin.com', '$2y$10$sIAToRWB9iPfexeKqf6tquxaPy4nvBsDH9lrV5DHuXzJt0QPsYwc6', 'admin', '2025-08-30 23:16:22');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `users`
--
-- ALTER TABLE `users`
--   ADD PRIMARY KEY (`id`),
--   ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `users`
--
-- ALTER TABLE `users`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
-- COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
