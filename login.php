<?php

session_start();
require __DIR__ . '/config/db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';


if (!$email || !$password) {
  header('Location: index.php?error=Preencha e-mail e senha.');
  exit;
}

if ($status === 'inativo') {
  header('Location: index.php?error=Usuário inativo, contate a secretaria.');
  exit;
}

$stmt = $pdo->prepare('SELECT id_usuario, nome_completo, email, password_hash, tipo, foto_perfil, status FROM usuario WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {

  if ($user['status'] === 'inativo') {
    // Destrói qualquer sessão que possa ter sido iniciada por engano e redireciona
    session_destroy();
    header('Location: index.php?error=Usuário inativo, contate a secretaria.');
    exit;
  }



  // Guarda o essencial na sessão
  $_SESSION['id_usuario'] = $user['id_usuario'];
  $_SESSION['nome_completo'] = $user['nome_completo'];
  $_SESSION['email'] = $user['email'];
  $_SESSION['tipo'] = $user['tipo'];
  $_SESSION['foto_perfil'] = $user['foto_perfil'];

  if ($user['tipo'] === 'secretaria') {
    header('Location: admin.php');
  } else {
    header('Location: portal_home.php');
  }
  exit;
}

header('Location: index.php?error=Credenciais inválidas.');
exit;
