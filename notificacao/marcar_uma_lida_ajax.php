<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); exit;
}

$id_notificacao = (int)($_POST['id_notificacao'] ?? 0);
$id_usuario = $_SESSION['id_usuario'];

if ($id_notificacao > 0) {
    // A condição id_usuario_destino = ? é uma segurança para garantir que um
    // usuário não possa marcar como lida a notificação de outro.
    $stmt = $pdo->prepare("UPDATE notificacao SET status = 'lida' WHERE id_notificacao = ? AND id_usuario_destino = ? AND status = 'nao lida'");
    $stmt->execute([$id_notificacao, $id_usuario]);
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);