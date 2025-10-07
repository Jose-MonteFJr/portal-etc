<?php
session_start();
require __DIR__ . '/config/db.php';

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Conta as não lidas
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notificacao WHERE id_usuario_destino = ? AND status = 'nao lida'");
$stmt_count->execute([$id_usuario]);
$unread_count = $stmt_count->fetchColumn();

// Busca as 5 mais recentes
$stmt_list = $pdo->prepare("SELECT mensagem, link, created_at FROM notificacao WHERE id_usuario_destino = ? ORDER BY created_at DESC LIMIT 5");
$stmt_list->execute([$id_usuario]);
$notifications = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

// Retorna os dados em formato JSON
header('Content-Type: application/json');
echo json_encode([
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);