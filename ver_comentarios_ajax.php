<?php
// ver_comentarios_ajax.php
session_start();
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    exit;
}

$id_aviso = (int)($_GET['id_aviso'] ?? 0);
if ($id_aviso === 0) {
    http_response_code(400);
    exit;
}

try {
    // Busca todos os comentários do aviso, junto com os dados do autor
    $stmt = $pdo->prepare("
        SELECT c.*, u.nome_completo, u.foto_perfil 
        FROM comentario c
        JOIN usuario u ON c.id_usuario = u.id_usuario
        WHERE c.id_aviso = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$id_aviso]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monta o HTML para cada comentário e devolve
    if (empty($comentarios)) {
        echo '<p class="text-muted small text-center">Nenhum comentário ainda.</p>';
        exit;
    }

    $html = '';
    foreach ($comentarios as $comentario) {
        $foto_autor = !empty($comentario['foto_perfil'])
            ? '/portal-etc/uploads/perfil/' . $comentario['foto_perfil']
            : '/portal-etc/partials/img/avatar_padrao.png';

        $html .= '
            <div class="d-flex gap-2 mb-2">
                <img src="' . htmlspecialchars($foto_autor) . '" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                <div class="rounded p-2 flex-grow-1">
                    <strong>' . htmlspecialchars($comentario['nome_completo']) . '</strong>
                    <p class="mb-0 small">' . nl2br(htmlspecialchars($comentario['conteudo'])) . '</p>
                </div>
            </div>';
    }
    echo $html;

} catch (PDOException $e) {
    http_response_code(500);
    echo '<p class="text-danger small">Erro ao carregar comentários.</p>';
}