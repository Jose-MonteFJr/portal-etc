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
$id_usuario = (int)($_POST['id_usuario'] ?? 0);

if ($id_usuario <= 0) {
  flash_set('danger', 'ID inválido.');
  header('Location: admin.php');
  exit;
}

// Evita deletar a si mesmo
if ($id_usuario === (int)$_SESSION['id_usuario']) {
  flash_set('danger', 'Você não pode excluir o próprio usuário logado.');
  header('Location: admin.php');
  exit;
}

$stmt = $pdo->prepare('DELETE FROM usuario WHERE id_usuario=?');
$stmt->execute([$id_usuario]);
flash_set('success', 'Usuário excluído.');
header('Location: admin.php');
exit;