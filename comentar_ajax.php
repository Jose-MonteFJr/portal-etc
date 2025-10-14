<?php
// comentar_ajax.php
session_start();
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

$id_aviso = (int)($_POST['id_aviso'] ?? 0);
$id_usuario = $_SESSION['id_usuario'];
$conteudo = trim($_POST['conteudo'] ?? '');

if ($id_aviso <= 0 || empty($conteudo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

try {
    // 1. Insere o novo comentário
    $stmt_insert = $pdo->prepare("INSERT INTO comentario (id_aviso, id_usuario, conteudo) VALUES (?, ?, ?)");
    $stmt_insert->execute([$id_aviso, $id_usuario, $conteudo]);
    $id_novo_comentario = $pdo->lastInsertId();

    // 2. Busca o comentário recém-criado com os dados do autor
    $stmt_select = $pdo->prepare("
        SELECT c.conteudo, c.created_at, u.nome_completo, u.foto_perfil 
        FROM comentario c
        JOIN usuario u ON c.id_usuario = u.id_usuario
        WHERE c.id_comentario = ?
    ");
    $stmt_select->execute([$id_novo_comentario]);
    $comentario = $stmt_select->fetch(PDO::FETCH_ASSOC);

    // VERIFICAÇÃO CRUCIAL: Garante que o comentário foi encontrado
    if (!$comentario) {
        throw new Exception("Não foi possível recuperar o comentário recém-criado.");
    }

    // 3. Busca a nova contagem total de comentários
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM comentario WHERE id_aviso = ?");
    $stmt_count->execute([$id_aviso]);
    $new_comment_count = $stmt_count->fetchColumn();

    // 4. Monta o HTML do novo comentário
    $foto_autor = !empty($comentario['foto_perfil'])
        ? '/portal-etc/uploads/perfil/' . htmlspecialchars($comentario['foto_perfil'])
        : '/portal-etc/partials/img/avatar_padrao.png';

    $comment_html = '
        <div class="d-flex gap-2 mb-2">
            <img src="' . $foto_autor . '" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
            <div class="rounded p-2 flex-grow-1">
                <strong>' . htmlspecialchars($comentario['nome_completo']) . '</strong>
                <p class="mb-0 small">' . nl2br(htmlspecialchars($comentario['conteudo'])) . '</p>
            </div>
        </div>';

    // 5. Retorna a resposta JSON completa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'comment_html' => $comment_html,
        'new_comment_count' => $new_comment_count
    ]);

} catch (Exception $e) {
    http_response_code(500);
    // Para depuração, você pode logar o erro real: error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ocorreu um erro no servidor ao processar o comentário.']);
}