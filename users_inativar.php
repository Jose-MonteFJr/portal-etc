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

if ($id_usuario === (int)$_SESSION['id_usuario']) {
  flash_set('warning', 'Você não pode inativar o próprio usuário logado.');
  header('Location: admin.php');
  exit;
}

try {
    $stmt = $pdo->prepare('UPDATE usuario SET status = "inativo" WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    flash_set('success', 'Usuário inativado com sucesso.');
    header('Location: admin.php');
    exit;
} catch (PDOException $e) {
    flash_set('danger', 'Erro ao inativar usuário: ' . $e->getMessage());
}
?>