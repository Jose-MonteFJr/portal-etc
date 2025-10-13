<?php
// calendario/delete_evento.php
session_start();
require '../config/db.php'; // Ajuste o caminho

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); exit;
}

$id_evento = (int)($_POST['id_evento'] ?? 0);
$id_usuario = $_SESSION['id_usuario'];

// Regra: Um usuário só pode deletar seus próprios eventos.
// (Futuramente, um professor poderia deletar seus eventos globais).
$stmt = $pdo->prepare("DELETE FROM evento_calendario WHERE id_evento = ? AND id_usuario_criador = ?");
$stmt->execute([$id_evento, $id_usuario]);

echo json_encode(['success' => true]);