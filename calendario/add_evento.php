<?php
// calendario/add_evento.php
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
$is_global = ($_POST['is_global'] ?? 'false') === 'true';

// Define o tipo do evento baseado no perfil
$tipo = 'pessoal';
if ($_SESSION['tipo'] === 'professor' && $is_global) {
    $tipo = 'global';
}

if (!empty($titulo) && !empty($data_evento)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO evento_calendario (id_usuario_criador, titulo, hora_inicio, hora_fim, data_evento, tipo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_usuario, $titulo, $hora_inicio, $hora_fim, $data_evento, $tipo]);

        // =====================================================================
        // == NOVO: LÓGICA PARA NOTIFICAR OS ALUNOS SOBRE O AVISO GLOBAL      ==
        // =====================================================================
        if ($tipo === 'global') {
            $data_formatada = date('d/m/Y', strtotime($data_evento));
            $mensagem = "Novo lembrete do professor para o dia {$data_formatada}: \"{$titulo}\"";
            $link = "/portal-etc/calendario/calendario.php"; // Link para a página do calendário

            // Chama a função para enviar a notificação para todos do grupo 'aluno'
            criar_notificacao_para_grupo($pdo, 'aluno', $mensagem, $link);
        }
        // =====================================================================
        
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar o evento.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
}