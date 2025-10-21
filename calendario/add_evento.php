<?php

session_start();
require '../config/db.php';
require '../helpers.php'; // Garante que a função criar_notificacao_para_grupo() esteja disponível

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$titulo = trim($_POST['titulo'] ?? '');
$hora_inicio = trim($_POST['hora_inicio'] ?? '');
$hora_fim = trim($_POST['hora_fim'] ?? '');
$data_evento = trim($_POST['data_evento'] ?? '');
$id_turma_alvo = (int)($_POST['id_turma_alvo'] ?? 0);

// Define o tipo do evento baseado no perfil
$tipo = 'pessoal';
$id_turma_alvo_final = null;
if ($_SESSION['tipo'] === 'professor' && $id_turma_alvo > 0) {
    $tipo = 'global';
    $id_turma_alvo_final = $id_turma_alvo;
}

if (!empty($titulo) && !empty($data_evento)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO evento_calendario (id_usuario_criador, titulo, hora_inicio, hora_fim, data_evento, tipo, id_turma_alvo) 
         VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_usuario, $titulo, $hora_inicio, $hora_fim, $data_evento, $tipo, $id_turma_alvo_final]);

        if ($tipo === 'global' && $id_turma_alvo_final) {
            $data_formatada = date('d/m/Y', strtotime($data_evento));
            $mensagem = "Aviso do professor no calendário para {$data_formatada}: \"{$titulo}\"";
            $link = "/portal-etc/calendario/calendario.php";

            // NOVO HELPER (veja abaixo)
            criar_notificacao_para_turma($pdo, $id_turma_alvo_final, $mensagem, $link);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar o evento.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}
