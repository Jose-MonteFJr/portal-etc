<?php
// comentar_ajax.php
session_start();
require __DIR__ . '/config/db.php';
if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$id_aviso = (int)($_POST['id_aviso'] ?? 0);
$id_usuario = $_SESSION['id_usuario'];
$conteudo = trim($_POST['conteudo'] ?? '');

if ($id_aviso > 0 && !empty($conteudo)) {
    try {
        $stmt_insert = $pdo->prepare("INSERT INTO comentario (id_aviso, id_usuario, conteudo) VALUES (?, ?, ?)");
        $stmt_insert->execute([$id_aviso, $id_usuario, $conteudo]);

        // NOVO: Após inserir, busca a nova contagem total de comentários
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM comentario WHERE id_aviso = ?");
        $stmt_count->execute([$id_aviso]);
        $new_comment_count = $stmt_count->fetchColumn();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'new_comment_count' => $new_comment_count]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro de banco de dados.']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
