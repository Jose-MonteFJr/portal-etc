<?php
// calendario/add_evento.php
session_start();
require '../config/db.php'; // Ajuste o caminho

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403); exit;
}

$id_usuario = $_SESSION['id_usuario'];
$titulo = trim($_POST['titulo'] ?? '');
$hora_inicio = trim($_POST['hora_inicio'] ?? '');
$hora_fim = trim($_POST['hora_fim'] ?? '');
$data_evento = trim($_POST['data_evento'] ?? '');
$is_global = ($_POST['is_global'] ?? 'false') === 'true';

// Define o tipo do evento baseado no perfil do usuário
$tipo = 'pessoal';
if ($_SESSION['tipo'] === 'professor' && $is_global) {
    $tipo = 'global';
}

if (!empty($titulo) && !empty($data_evento)) {
    $stmt = $pdo->prepare("INSERT INTO evento_calendario (id_usuario_criador, titulo, hora_inicio, hora_fim, data_evento, tipo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_usuario, $titulo, $hora_inicio, $hora_fim, $data_evento, $tipo]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}