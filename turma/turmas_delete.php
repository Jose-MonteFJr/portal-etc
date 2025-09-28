<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: turmas_view.php');
  exit;
}

csrf_check();
$id_turma = (int)($_POST['id_turma'] ?? 0);

if ($id_turma <= 0) {
  flash_set('danger', 'ID inválido.');
  header('Location: turmas_view.php');
  exit;
}

$stmt_check = $pdo->prepare('SELECT COUNT(*) FROM aluno WHERE id_turma = ?');
$stmt_check->execute([$id_turma]);

// Pega o número de alunos encontrados
$alunos_count = (int)$stmt_check->fetchColumn();

// Se a contagem for maior que 0, exibe erro e para o script
if ($alunos_count > 0) {
  flash_set('danger', 'Não é possível excluir a turma, pois existem alunos vinculados a ela.');
  header('Location: turmas_view.php'); 
  exit; 
}

$stmt = $pdo->prepare('DELETE FROM turma WHERE id_turma=?');
$stmt->execute([$id_turma]);
flash_set('success', 'Turma excluída com sucesso!');
header('Location: turmas_view.php');
exit;