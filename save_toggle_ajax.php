<?php
// save_toggle_ajax.php
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
    // CORRIGIDO: Verifica se o usuário já SALVOU este aviso
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM aviso_salvo WHERE id_aviso = ? AND id_usuario = ?");
    $stmt->execute([$id_aviso, $id_usuario]);
    $save_exists = $stmt->fetchColumn() > 0;

    if ($save_exists) {
        // Se já salvou, remove o registro (DELETE)
        $stmt_delete = $pdo->prepare("DELETE FROM aviso_salvo WHERE id_aviso = ? AND id_usuario = ?");
        $stmt_delete->execute([$id_aviso, $id_usuario]);
        $saved = false; // CORRIGIDO
    } else {
        // Se não salvou, adiciona o registro (INSERT)
        $stmt_insert = $pdo->prepare("INSERT INTO aviso_salvo (id_aviso, id_usuario) VALUES (?, ?)");
        $stmt_insert->execute([$id_aviso, $id_usuario]);
        $saved = true; // CORRIGIDO
    }

    // Após a ação, você pode querer a contagem, mas para "Salvar" ela não é tão útil quanto para Curtir.
    // Manter a contagem de curtidas é mais comum. Para "Salvar", geralmente só o estado (salvo/não salvo) é suficiente.
    // Mas, se quiser, pode calcular a contagem total de salvamentos para aquele post.

    // Retorna uma resposta JSON para o JavaScript
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'saved' => $saved, // CORRIGIDO
        // 'new_save_count' => $new_save_count // Opcional
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados.']);
}
