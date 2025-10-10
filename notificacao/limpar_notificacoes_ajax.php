<?php
session_start();
require '../config/db.php';

// Apenas executa se o usuário estiver logado e a requisição for POST
if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

try {
    $id_usuario = $_SESSION['id_usuario'];

    // ALTERADO: Em vez de DELETAR, agora ATUALIZA o status para 'arquivada'
    $stmt = $pdo->prepare("UPDATE notificacao SET status = 'arquivada' WHERE id_usuario_destino = ? AND status = 'lida'");
    $stmt->execute([$id_usuario]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao arquivar notificações.']);
}