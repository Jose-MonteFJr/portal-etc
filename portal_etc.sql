-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 03/09/2025 às 01:04
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.2.4

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `portal_etc`
--

-- CRIAÇÃO DO BANCO DE DADOS
DROP DATABASE IF EXISTS portal_etc;
CREATE DATABASE portal_etc CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE portal_etc;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


-- TABELAS
-- OBS - LEMBRAR DE CRIAR NA ORDEM CORRETA
CREATE TABLE usuario (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
  nome_completo VARCHAR(150) NOT NULL,
  cpf CHAR(14) UNIQUE NOT NULL, -- 123.123.123-09
  email VARCHAR(150) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  telefone CHAR(15) NOT NULL, 
  data_nascimento DATE NOT NULL,
  tipo ENUM('aluno', 'professor', 'coordenador', 'secretaria') NOT NULL DEFAULT 'aluno',
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE endereco (
    id_endereco INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    logradouro VARCHAR(150) NOT NULL,
    numero VARCHAR(10) NOT NULL,
    complemento VARCHAR(50) NULL,
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado CHAR(2) NOT NULL,           -- Armazena a UF, ex: 'DF', 'SP', 'RJ'
    cep CHAR(10) NOT NULL,               -- formato 00000-000
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_endereco_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE curso (
    id_curso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) UNIQUE NOT NULL, -- Adicionado UNIQUE para evitar cursos com o mesmo nome
    carga_horaria SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE turma (
    id_turma INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    ano YEAR NOT NULL,
    semestre TINYINT UNSIGNED NOT NULL, -- Ex - 1 ou 2
    turno ENUM('matutino','vespertino','noturno') NOT NULL,
    id_curso INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_turma_curso FOREIGN KEY (id_curso) REFERENCES curso(id_curso) ON DELETE RESTRICT,
    CONSTRAINT uq_turma UNIQUE (nome, ano, semestre, id_curso)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE aluno (
  id_aluno INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  matricula CHAR(8) UNIQUE, -- 23000001 - PRIMEIROS 2 DIGITOS REFERENTE AO ANO E O RESTO É A MATRICULA
  data_ingresso DATE NOT NULL,
  status_academico ENUM('cursando', 'formado', 'trancado', 'desistente') NOT NULL DEFAULT 'cursando',
  id_turma INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_aluno_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_aluno_turma FOREIGN KEY (id_turma) REFERENCES turma(id_turma) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE sequencias_matricula (
  ano CHAR(2) PRIMARY KEY,           -- ex: "25"
  ultimo_numero INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DELIMITER $$

CREATE TRIGGER trg_aluno_matricula
BEFORE INSERT ON aluno
FOR EACH ROW
BEGIN
  DECLARE seq INT;
  DECLARE ano_char CHAR(2);

  SET ano_char := DATE_FORMAT(NOW(), '%y'); -- pega últimos 2 dígitos do ano

  -- Se não existir sequência para o ano, cria com 0
  INSERT INTO sequencias_matricula (ano, ultimo_numero)
  VALUES (ano_char COLLATE utf8mb4_general_ci, 0)
  ON DUPLICATE KEY UPDATE ultimo_numero = ultimo_numero; -- evita comparar ano

  -- Incrementa sequência
  UPDATE sequencias_matricula
  SET ultimo_numero = ultimo_numero + 1
  WHERE ano = ano_char COLLATE utf8mb4_general_ci;

  -- Busca valor atualizado
  SELECT ultimo_numero INTO seq
  FROM sequencias_matricula
  WHERE ano = ano_char COLLATE utf8mb4_general_ci;

  -- Monta matrícula no formato: AA + 6 dígitos
  SET NEW.matricula = CONCAT(ano_char, LPAD(seq, 6, '0'));
END$$

DELIMITER ;

-- INSERTS PADRÃO

INSERT INTO usuario (nome_completo, cpf, email, password_hash, telefone, data_nascimento, tipo) 
VALUE 
-- SECRETARIA/ADMIN 
('Jose Jose','123.123.123-23','jose@admin.com','$2y$10$4v3s86.rU8.Bq7UfqQ2mYuFmbv2voXZxdoeTDb4XdsX0w9AGUlrHG','(61) 91234-1234','2000-02-24','secretaria'); -- Senha: admin123




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
