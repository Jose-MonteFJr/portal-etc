<?php
// calendario/get_eventos.php
session_start();
require '../config/db.php'; // Ajuste o caminho

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([]); // Retorna array vazio se não estiver logado
    exit;
}

$id_usuario_logado = $_SESSION['id_usuario'];

// Busca eventos PESSOAIS do usuário logado OU eventos GLOBAIS de qualquer professor
$stmt = $pdo->prepare("
    SELECT * FROM evento_calendario 
    WHERE id_usuario_criador = ? OR tipo = 'global'
");
$stmt->execute([$id_usuario_logado]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($eventos);