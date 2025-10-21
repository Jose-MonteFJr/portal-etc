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
  foto_perfil VARCHAR(255) NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_endereco_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE curso (
    id_curso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) UNIQUE NOT NULL,
    descricao TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE modulo (
  id_modulo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_curso INT UNSIGNED NOT NULL,
  nome VARCHAR(150) NOT NULL,
  ordem TINYINT UNSIGNED NOT NULL,  -- Sequencia do modulo, ex - 1, 2, 3...
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_modulo_curso FOREIGN KEY (id_curso) REFERENCES curso(id_curso) ON DELETE RESTRICT,
  CONSTRAINT uq_modulo_curso UNIQUE (id_curso, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE disciplina (
  id_disciplina INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_modulo INT UNSIGNED NOT NULL,
  nome VARCHAR(150) NOT NULL,
  carga_horaria SMALLINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_disciplina_modulo FOREIGN KEY (id_modulo) REFERENCES modulo(id_modulo) ON DELETE RESTRICT,
  CONSTRAINT uq_disciplina_modulo UNIQUE (id_modulo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE turma (
    id_turma INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_curso INT UNSIGNED NOT NULL,
    id_modulo_atual INT UNSIGNED NULL DEFAULT NULL,
    nome VARCHAR(100) NOT NULL,
    ano YEAR NOT NULL,
    semestre ENUM('1', '2') NOT NULL, 
    turno ENUM('matutino','vespertino','noturno') NOT NULL,
    status ENUM('aberta', 'fechada') NOT NULL DEFAULT 'aberta',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_turma_curso FOREIGN KEY (id_curso) REFERENCES curso(id_curso) ON DELETE RESTRICT,
    CONSTRAINT uq_turma UNIQUE (nome, ano, semestre, id_curso),
    CONSTRAINT fk_turma_modulo_atual FOREIGN KEY (id_modulo_atual) REFERENCES modulo(id_modulo) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE aluno (
  id_aluno INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  id_turma INT UNSIGNED NULL,
  matricula CHAR(8) UNIQUE, -- 23000001 - PRIMEIROS 2 DIGITOS REFERENTE AO ANO E O RESTO É A MATRICULA
  data_ingresso DATE NOT NULL,
  status_academico ENUM('cursando', 'formado', 'trancado', 'desistente') NOT NULL DEFAULT 'cursando',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

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

CREATE TABLE solicitacao (
    id_solicitacao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_aluno INT UNSIGNED NOT NULL,
    tipo ENUM('renovação de matrícula', 'emissão de diploma', 'emissão de certificado', 'trancamento de matrícula') NOT NULL,
    status ENUM('pendente', 'em análise', 'aprovada', 'rejeitada', 'concluída') NOT NULL DEFAULT 'pendente',
    observacao_aluno TEXT NULL, -- Campo para o aluno adicionar informações
    observacao_secretaria TEXT NULL, -- Campo para a secretaria adicionar informações
    caminho_arquivo VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_solicitacao_aluno FOREIGN KEY (id_aluno) REFERENCES aluno(id_aluno) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE notificacao (
    id_notificacao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario_destino INT UNSIGNED NOT NULL,
    mensagem VARCHAR(255) NOT NULL,
    link VARCHAR(255) NULL,
    status ENUM('nao lida', 'lida', 'arquivada') NOT NULL DEFAULT 'nao lida',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notificacao_usuario FOREIGN KEY (id_usuario_destino) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela principal para os avisos
CREATE TABLE aviso (
    id_aviso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario_autor INT UNSIGNED NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    caminho_imagem VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_aviso_usuario FOREIGN KEY (id_usuario_autor) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela para os comentários
CREATE TABLE comentario (
    id_comentario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_aviso INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    conteudo TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_comentario_aviso FOREIGN KEY (id_aviso) REFERENCES aviso(id_aviso) ON DELETE CASCADE,
    CONSTRAINT fk_comentario_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de ligação para as curtidas (relação Muitos-para-Muitos)
CREATE TABLE curtida (
    id_aviso INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_aviso, id_usuario), -- Impede que um usuário curta o mesmo post duas vezes
    CONSTRAINT fk_curtida_aviso FOREIGN KEY (id_aviso) REFERENCES aviso(id_aviso) ON DELETE CASCADE,
    CONSTRAINT fk_curtida_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de ligação para os avisos salvos (relação Muitos-para-Muitos)
CREATE TABLE aviso_salvo (
    id_aviso INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_aviso, id_usuario), -- Impede que um usuário salve o mesmo post duas vezes
    CONSTRAINT fk_aviso_salvo_aviso FOREIGN KEY (id_aviso) REFERENCES aviso(id_aviso) ON DELETE CASCADE,
    CONSTRAINT fk_aviso_salvo_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE evento_calendario (
    id_evento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario_criador INT UNSIGNED NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    hora_inicio VARCHAR(10) NOT NULL,
    hora_fim VARCHAR(10) NOT NULL,
    data_evento DATE NOT NULL,
    tipo ENUM('pessoal', 'global') NOT NULL DEFAULT 'pessoal',
    id_turma_alvo INT UNSIGNED NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_evento_usuario FOREIGN KEY (id_usuario_criador) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_evento_turma_alvo FOREIGN KEY (id_turma_alvo) REFERENCES turma(id_turma) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE conversa (
    id_conversa INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assunto VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE participante_conversa (
    id_conversa INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    status_leitura ENUM('lida', 'nao lida') NOT NULL DEFAULT 'nao lida',

    PRIMARY KEY (id_conversa, id_usuario),
    CONSTRAINT fk_participante_conversa FOREIGN KEY (id_conversa) REFERENCES conversa(id_conversa) ON DELETE CASCADE,
    CONSTRAINT fk_participante_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE mensagem (
    id_mensagem INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_conversa INT UNSIGNED NOT NULL,
    id_usuario_remetente INT UNSIGNED NOT NULL,
    conteudo TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_mensagem_conversa FOREIGN KEY (id_conversa) REFERENCES conversa(id_conversa) ON DELETE CASCADE,
    CONSTRAINT fk_mensagem_usuario FOREIGN KEY (id_usuario_remetente) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE definicao_horario (
    id_definicao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turno ENUM('matutino','vespertino','noturno') NOT NULL,
    horario_label ENUM('primeiro', 'segundo') NOT NULL COMMENT 'Ex: 1º ou 2º horário',
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_turno_horario (turno, horario_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE horario_aula (
    id_horario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_turma INT UNSIGNED NOT NULL,
    id_disciplina INT UNSIGNED NOT NULL,
    id_professor INT UNSIGNED NOT NULL,
    dia_semana ENUM('segunda', 'terca', 'quarta', 'quinta', 'sexta') NOT NULL,
    horario ENUM('primeiro', 'segundo') NOT NULL,
    sala VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_horario_turma FOREIGN KEY (id_turma) REFERENCES turma(id_turma) ON DELETE CASCADE,
    CONSTRAINT fk_horario_disciplina FOREIGN KEY (id_disciplina) REFERENCES disciplina(id_disciplina) ON DELETE CASCADE,
    CONSTRAINT fk_horario_professor FOREIGN KEY (id_professor) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY uq_horario_turma_dia (id_turma, dia_semana, horario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- INSERTS PADRÃO

INSERT INTO usuario (nome_completo, cpf, email, password_hash, telefone, data_nascimento, tipo) 
VALUE
-- SECRETARIA/ADMIN 
('Jose Admin','123.123.123-23','jose@admin.com','$2y$10$4v3s86.rU8.Bq7UfqQ2mYuFmbv2voXZxdoeTDb4XdsX0w9AGUlrHG','(61) 91234-1234','2000-02-24','secretaria'); -- Senha: admin123

-- INSERTS TESTE

-- 1. CURSOS
-- Primeiro, criamos os cursos principais.
INSERT INTO curso (nome, descricao) VALUES 
('Técnico em Informática', 'Curso voltado para o desenvolvimento de sistemas, redes e manutenção de computadores.'),
('Técnico em Enfermagem', 'Curso para formação de profissionais da área da saúde, com foco em cuidados e procedimentos de enfermagem.');

-- 2. MÓDULOS
-- Agora, criamos os módulos para cada curso, usando os IDs que acabamos de criar.
-- Assumindo que 'Técnico em Informática' tem id_curso = 1
INSERT INTO modulo (id_curso, nome, ordem) VALUES
(1, 'Módulo I - Fundamentos de TI', 1),
(1, 'Módulo II - Desenvolvimento Web', 2),
(1, 'Módulo III - Banco de Dados Avançado', 3);

-- Assumindo que 'Técnico em Enfermagem' tem id_curso = 2
INSERT INTO modulo (id_curso, nome, ordem) VALUES
(2, 'Módulo I - Cuidados Básicos e Ética', 1),
(2, 'Módulo II - Procedimentos Hospitalares', 2);

-- 3. DISCIPLINAS
-- Criamos as disciplinas para cada módulo.
-- Assumindo que os módulos de Informática são id_modulo = 1, 2, 3
INSERT INTO disciplina (id_modulo, nome, carga_horaria) VALUES
(1, 'Lógica de Programação', 80),
(1, 'Hardware e Redes', 60),
(2, 'HTML5 e CSS3', 80),
(2, 'JavaScript Básico', 80),
(3, 'SQL Avançado', 100),
(3, 'Modelação de Dados', 60);

-- Assumindo que os módulos de Enfermagem são id_modulo = 4, 5
INSERT INTO disciplina (id_modulo, nome, carga_horaria) VALUES
(4, 'Anatomia e Fisiologia Humana', 80),
(4, 'Ética em Enfermagem', 40),
(5, 'Técnicas de Curativo', 100),
(5, 'Farmacologia Aplicada', 80);


-- 4. TURMAS
-- Por fim, criamos instâncias (turmas) dos cursos.
-- Assumindo que 'Técnico em Informática' tem id_curso = 1
INSERT INTO turma (id_curso, nome, ano, semestre, turno, status) VALUES
(1, 'INF-2025.1-NOT', 2025, '1', 'noturno', 'aberta'),
(1, 'INF-2024.2-MAT', 2024, '2', 'matutino', 'aberta');

-- Assumindo que 'Técnico em Enfermagem' tem id_curso = 2
INSERT INTO turma (id_curso, nome, ano, semestre, turno, status) VALUES
(2, 'ENF-2025.1-VES', 2025, '1', 'vespertino', 'aberta');

-- ===================================================================
-- == INSERTS DE TESTE ADICIONAIS
-- ===================================================================

-- 5. USUÁRIOS
-- Criando usuários de diferentes tipos (professores e alunos).
-- A senha para todos os usuários de teste é 'senha123'
INSERT INTO usuario (nome_completo, cpf, email, password_hash, telefone, data_nascimento, tipo) 
VALUES
-- Professores (IDs: 2, 3)
('Carlos Nogueira','111.222.333-44','carlos.prof@email.com','$2y$10$GJ9AMaahNTSm.q1Dd.5fluMADwsH32C8J5BJ9i9DiWXIuuMtZTeAu','(61) 98877-6655','1985-05-20','professor'),
('Ana Souza','444.555.666-77','ana.prof@email.com','$2y$10$GJ9AMaahNTSm.q1Dd.5fluMADwsH32C8J5BJ9i9DiWXIuuMtZTeAu','(61) 91122-3344','1990-11-10','professor'),
-- Alunos (IDs: 4, 5, 6)
('Mariana Costa','777.888.999-00','mariana.aluna@email.com','$2y$10$GJ9AMaahNTSm.q1Dd.5fluMADwsH32C8J5BJ9i9DiWXIuuMtZTeAu','(61) 91234-5678','2005-02-15','aluno'),
('Pedro Almeida','123.456.789-10','pedro.aluno@email.com','$2y$10$GJ9AMaahNTSm.q1Dd.5fluMADwsH32C8J5BJ9i9DiWXIuuMtZTeAu','(61) 98765-4321','2006-08-30','aluno'),
('Juliana Lima','987.654.321-00','juliana.aluna@email.com','$2y$10$GJ9AMaahNTSm.q1Dd.5fluMADwsH32C8J5BJ9i9DiWXIuuMtZTeAu','(61) 99999-8888','2004-12-01','aluno');

-- 6. ENDEREÇOS
-- Adicionando endereços para os usuários criados acima.
-- O id_usuario deve corresponder ao ID do usuário na tabela `usuario`.
INSERT INTO endereco (id_usuario, logradouro, numero, bairro, cidade, estado, cep)
VALUES
(1, 'Rua da Administração, Quadra 10', '1A', 'Centro', 'Brasília', 'DF', '70000-100'), -- Endereço para Jose Admin
(2, 'Avenida dos Professores, Lote 5', '42', 'Asa Norte', 'Brasília', 'DF', '70770-100'), -- Endereço para Carlos Nogueira
(3, 'Rua das Flores, Apto 201', '300', 'Águas Claras', 'Brasília', 'DF', '71900-100'), -- Endereço para Ana Souza
(4, 'Quadra 301, Conjunto B', '22', 'Samambaia Sul', 'Samambaia', 'DF', '72300-100'), -- Endereço para Mariana Costa
(5, 'Rua 10, Chácara 123', 'S/N', 'Vicente Pires', 'Brasília', 'DF', '72005-100'), -- Endereço para Pedro Almeida
(6, 'Avenida Principal, Bloco C, Apto 904', '1020', 'Taguatinga Centro', 'Taguatinga', 'DF', '72010-010'); -- Endereço para Juliana Lima


-- 7. ALUNOS
-- Criando o perfil de aluno para os usuários correspondentes.
-- O campo 'matricula' NÃO é inserido, pois o TRIGGER o gera automaticamente.
-- Assumindo que as turmas criadas são: id_turma=1 (INF-NOT), id_turma=2 (INF-MAT), id_turma=3 (ENF-VES)
INSERT INTO aluno (id_usuario, id_turma, data_ingresso, status_academico)
VALUES
(4, 1, '2025-02-10', 'cursando'), -- Mariana Costa na Turma de Informática Noturno
(5, 1, '2025-02-10', 'cursando'), -- Pedro Almeida na Turma de Informática Noturno
(6, 3, '2025-02-10', 'cursando'); -- Juliana Lima na Turma de Enfermagem Vespertino


-- Horários para o turno da Noite
INSERT INTO definicao_horario (turno, horario_label, hora_inicio, hora_fim) VALUES
('noturno', 'primeiro', '19:00:00', '20:50:00'),
('noturno', 'segundo', '21:00:00', '22:40:00');

-- Horários para o turno da Tarde (Vespertino)
INSERT INTO definicao_horario (turno, horario_label, hora_inicio, hora_fim) VALUES
('vespertino', 'primeiro', '13:30:00', '15:20:00'),
('vespertino', 'segundo', '15:40:00', '17:30:00');

-- Horários para o turno da Manhã (Matutino)
INSERT INTO definicao_horario (turno, horario_label, hora_inicio, hora_fim) VALUES
('matutino', 'primeiro', '07:30:00', '09:20:00'),
('matutino', 'segundo', '09:40:00', '11:30:00');


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;