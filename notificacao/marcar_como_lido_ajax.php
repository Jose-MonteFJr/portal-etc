<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['id_usuario'])) { exit; }

$stmt = $pdo->prepare("UPDATE notificacao SET status = 'lida' WHERE id_usuario_destino = ? AND status = 'nao lida'");
$stmt->execute([$_SESSION['id_usuario']]);

echo json_encode(['success' => true]);