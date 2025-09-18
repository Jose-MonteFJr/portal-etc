<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: admin.php');
  exit;
}

csrf_check();
$id_curso = (int)($_POST['id_curso'] ?? 0);

if ($id_curso <= 0) {
  flash_set('danger', 'ID inválido.');
  header('Location: admin.php');
  exit;
}

$stmt = $pdo->prepare('DELETE FROM curso WHERE id_curso=?');
$stmt->execute([$id_curso]);
flash_set('success', 'Curso excluído.');
header('Location: admin.php');
exit;