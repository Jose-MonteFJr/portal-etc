<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: cursos_view.php');
  exit;
}

csrf_check();
$id_curso = (int)($_POST['id_curso'] ?? 0);

if ($id_curso <= 0) {
  flash_set('danger', 'ID inválido.');
  header('Location: cursos_view.php');
  exit;
}

// Prepara a consulta para contar turmas vinculadas ao curso
$stmt_check = $pdo->prepare('SELECT COUNT(*) FROM turma WHERE id_curso = ?');
$stmt_check->execute([$id_curso]);

// Pega o número de turmas encontradas
$turmas_count = (int)$stmt_check->fetchColumn();

// Se a contagem for maior que 0, exibe erro e para o script
if ($turmas_count > 0) {
  flash_set('danger', 'Não é possível excluir o curso, pois existem turmas vinculadas a ele.');
  header('Location: cursos_view.php'); // Ou para a página de cursos
  exit; // Impede que o resto do código seja executado
}

$stmt = $pdo->prepare('DELETE FROM curso WHERE id_curso=?');
$stmt->execute([$id_curso]);
flash_set('success', 'Curso excluído.');
header('Location: cursos_view.php');
exit;