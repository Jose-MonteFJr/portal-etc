<?php
session_start();
require '../config/db.php';

// Apenas executa se o usuário estiver logado e a requisição for POST
if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); // Proibido
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

try {
    $id_usuario = $_SESSION['id_usuario'];

    // Prepara e executa a consulta para DELETAR as notificações com status 'lida'
    $stmt = $pdo->prepare("DELETE FROM notificacao WHERE id_usuario_destino = ? AND status = 'lida'");
    $stmt->execute([$id_usuario]);

    // Retorna uma resposta de sucesso
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500); // Erro interno do servidor
    echo json_encode(['error' => 'Erro ao limpar notificações.']);
}