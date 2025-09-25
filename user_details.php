<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();

$id_usuario = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT u.id_usuario, u.nome_completo, u.cpf, u.email, u.telefone, 
DATE_FORMAT(u.data_nascimento, "%d/%m/%Y") AS data_nascimento,
u.tipo, u.status, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep, t.id_turma, t.nome, t.ano, t.semestre, t.turno, DATE_FORMAT(a.data_ingresso, "%d/%m/%Y") AS data_ingresso, a.status_academico FROM usuario u LEFT JOIN aluno a ON a.id_usuario = u.id_usuario LEFT JOIN turma t ON t.id_turma = a.id_turma LEFT JOIN endereco e ON e.id_usuario = u.id_usuario WHERE u.id_usuario=?');

$stmt->execute([$id_usuario]);
$user = $stmt->fetch();
if (!$user) {
  echo json_encode(["erro" => "Usuário não encontrado"]);
}

echo json_encode($user);