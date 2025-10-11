<?php
// like_toggle_ajax.php
session_start();
require __DIR__ . '/config/db.php';

// Segurança: verifica se o usuário está logado e se a requisição é POST
if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$id_aviso = (int)($_POST['id_aviso'] ?? 0);
$id_usuario = $_SESSION['id_usuario'];

if ($id_aviso === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID do aviso inválido.']);
    exit;
}

try {
    // Verifica se o usuário já curtiu este aviso
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM curtida WHERE id_aviso = ? AND id_usuario = ?");
    $stmt->execute([$id_aviso, $id_usuario]);
    $like_exists = $stmt->fetchColumn() > 0;

    if ($like_exists) {
        // Se já curtiu, remove a curtida (DELETE)
        $stmt_delete = $pdo->prepare("DELETE FROM curtida WHERE id_aviso = ? AND id_usuario = ?");
        $stmt_delete->execute([$id_aviso, $id_usuario]);
        $liked = false;
    } else {
        // Se não curtiu, adiciona a curtida (INSERT)
        $stmt_insert = $pdo->prepare("INSERT INTO curtida (id_aviso, id_usuario) VALUES (?, ?)");
        $stmt_insert->execute([$id_aviso, $id_usuario]);
        $liked = true;
    }

    // Após a ação, busca a nova contagem total de curtidas
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM curtida WHERE id_aviso = ?");
    $stmt_count->execute([$id_aviso]);
    $new_like_count = $stmt_count->fetchColumn();

    // Retorna uma resposta JSON para o JavaScript
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'new_like_count' => $new_like_count
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados.']);
}