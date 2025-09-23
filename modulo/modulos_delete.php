<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: modulos_view.php');
  exit;
}

csrf_check();
$id_modulo = (int)($_POST['id_modulo'] ?? 0);

if ($id_modulo <= 0) {
  flash_set('danger', 'ID inválido.');
  header('Location: modulos_view.php');
  exit;
}

// Prepara a consulta para contar turmas vinculadas ao curso
$stmt_check = $pdo->prepare('SELECT COUNT(*) FROM disciplina WHERE id_modulo = ?');
$stmt_check->execute([$id_modulo]);

// Pega o número de disciplinas encontradas
$disciplinas_count = (int)$stmt_check->fetchColumn();

// Se a contagem for maior que 0, exibe erro e para o script
if ($disciplinas_count > 0) {
  flash_set('danger', 'Não é possível excluir o módulo, pois existem disciplinas vinculadas a ele.');
  header('Location: modulos_view.php'); 
  exit; 
}

$stmt = $pdo->prepare('DELETE FROM modulo WHERE id_modulo=?');
$stmt->execute([$id_modulo]);
flash_set('success', 'Módulo excluído com sucesso!');
header('Location: modulos_view.php');
exit;