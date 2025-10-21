<?php
// calendario/edit_evento.php
session_start();
require '../config/db.php'; // Ajuste o caminho
require '../helpers.php'; // Para as notificações

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

// 1. Captura todos os dados, incluindo o ID do evento a ser editado
$id_evento = (int)($_POST['id_evento'] ?? 0);
$id_usuario_logado = $_SESSION['id_usuario'];
$titulo = trim($_POST['titulo'] ?? '');
$hora_inicio = trim($_POST['hora_inicio'] ?? '');
$hora_fim = trim($_POST['hora_fim'] ?? '');
$id_turma_alvo = (int)($_POST['id_turma_alvo'] ?? 0);

if ($id_evento <= 0 || empty($titulo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

try {
    // 2. VERIFICAÇÃO DE SEGURANÇA: Confirma que o usuário é o dono do evento
    $stmt_check = $pdo->prepare("SELECT id_usuario_criador FROM evento_calendario WHERE id_evento = ?");
    $stmt_check->execute([$id_evento]);
    $criador_id = $stmt_check->fetchColumn();

    if ($criador_id != $id_usuario_logado) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Você não tem permissão para editar este evento.']);
        exit;
    }

    // Define o tipo do evento
    $tipo = 'pessoal';
    $id_turma_alvo_final = null;
    if ($_SESSION['tipo'] === 'professor' && $id_turma_alvo > 0) {
        $tipo = 'global';
        $id_turma_alvo_final = $id_turma_alvo;
    }

    // 3. Executa a atualização (UPDATE)
    $stmt_update = $pdo->prepare(
        "UPDATE evento_calendario 
         SET titulo = ?, hora_inicio = ?, hora_fim = ?, tipo = ?, id_turma_alvo = ?
         WHERE id_evento = ?"
    );
    $stmt_update->execute([$titulo, $hora_inicio, $hora_fim, $tipo, $id_turma_alvo_final, $id_evento]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}