<?php
require     '../protect.php'; // Ajuste o caminho conforme sua estrutura
require     '../config/db.php';
require     '../helpers.php';

$allowed_types = ['aluno', 'secretaria', 'professor'];
if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], $allowed_types)) {
    // Se não for nenhum dos dois, nega o acesso
    flash_set('danger', 'Acesso negado.');
    // Redireciona para um local seguro (admin.php lida com outros perfis)
    header('Location: ../admin.php');
    exit;
}

$id_usuario_logado = $_SESSION['id_usuario'];

// --- A CONSULTA MESTRA DO FEED ---
// Esta consulta é complexa, mas muito otimizada. Vamos analisá-la em detalhes.

try {
    // 1. Define uma variável na sessão do MySQL com o ID do usuário logado.
    // Isso é necessário para que as subconsultas possam saber "quem está olhando".
    $pdo->exec("SET @id_usuario_logado = " . (int)$id_usuario_logado);

    // 2. Prepara a consulta principal
    $sql = "SELECT
                a.id_aviso, a.titulo, a.descricao, a.caminho_imagem, a.created_at,
                u.nome_completo AS nome_autor,
                u.foto_perfil AS foto_autor,
                
                -- Subconsulta para contar o total de curtidas
                (SELECT COUNT(*) FROM curtida WHERE id_aviso = a.id_aviso) AS total_curtidas,
                
                -- Subconsulta para contar o total de comentários
                (SELECT COUNT(*) FROM comentario WHERE id_aviso = a.id_aviso) AS total_comentarios,
                
                -- Verifica se o usuário logado JÁ CURTIU este aviso (retorna 1 se sim, 0 se não)
                EXISTS(SELECT 1 FROM curtida WHERE id_aviso = a.id_aviso AND id_usuario = @id_usuario_logado) AS usuario_curtiu,
                
                -- Verifica se o usuário logado JÁ SALVOU este aviso (retorna 1 se sim, 0 se não)
                EXISTS(SELECT 1 FROM aviso_salvo WHERE id_aviso = a.id_aviso AND id_usuario = @id_usuario_logado) AS usuario_salvou
            FROM
                aviso a
            JOIN
                usuario u ON a.id_usuario_autor = u.id_usuario
            ORDER BY
                a.created_at DESC";

    $stmt = $pdo->query($sql);
    $avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em caso de erro, exibe uma mensagem amigável
    die("Erro ao carregar o feed: " . $e->getMessage());
}

if ($_SESSION['tipo'] === 'aluno') {
    include '../partials/portal_header.php'; // Header do Aluno
} elseif ($_SESSION['tipo'] === 'professor') {
    include '../partials/portal_header.php'; // Header do Aluno
} else {
    include '../partials/admin_header.php'; // Header da Secretaria
}
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Avisos</h2>

                        <?php if ($_SESSION['tipo'] === 'secretaria'): ?>
                            <a href="avisos_create.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Novo Aviso
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php flash_show(); // Movido para após o cabeçalho 
                    ?>

                    <?php if (empty($avisos)): ?>
                        <div class="card shadow-sm text-center">
                            <div class="card-body">
                                <p class="mb-0 text-muted">Nenhum aviso publicado ainda.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($avisos as $aviso): ?>
                            <div class="card shadow-sm mb-4" id="aviso-<?php echo (int)$aviso['id_aviso']; ?>">
                                <div class="card-header d-flex align-items-center justify-content-between">

                                    <div class="d-flex align-items-center">
                                        <?php
                                        $foto_autor = !empty($aviso['foto_autor'])
                                            ? '/portal-etc/uploads/perfil/' . $aviso['foto_autor']
                                            : '/portal-etc/partials/img/avatar_padrao.png';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($foto_autor); ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($aviso['nome_autor']); ?></h6>
                                            <small class="text-muted"><?php echo date('d/m/Y \à\s H:i', strtotime($aviso['created_at'])); ?></small>
                                        </div>
                                    </div>

                                    <div>
                                        <?php if ($_SESSION['tipo'] === 'secretaria'): ?>
                                            <a href="avisos_edit.php?id_aviso=<?php echo (int)$aviso['id_aviso']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil-fill"></i> Editar
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                </div>

                                <div class="card-body">
                                    <?php if (!empty($aviso['caminho_imagem'])): ?>
                                        <img src="/portal-etc/uploads/avisos/<?php echo htmlspecialchars($aviso['caminho_imagem']); ?>" class="img-fluid rounded mb-3">
                                    <?php endif; ?>

                                    <h5 class="card-title"><?php echo htmlspecialchars($aviso['titulo']); ?></h5>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($aviso['descricao'])); ?></p>
                                </div>

                                <div class="card-footer">
                                    <div class="d-flex justify-content-between text-muted small mb-2">
                                        <span><span class="total-curtidas-count"><?php echo (int)$aviso['total_curtidas']; ?></span> curtidas</span>
                                    </div>
                                    <hr class="my-1">

                                    <div class="d-flex justify-content-around">
                                        <button class="btn btn-link text-decoration-none like-btn <?php echo $aviso['usuario_curtiu'] ? 'text-danger' : 'text-muted'; ?>"
                                            data-aviso-id="<?php echo (int)$aviso['id_aviso']; ?>">
                                            <i class="bi <?php echo $aviso['usuario_curtiu'] ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                            <span class="like-text"><?php echo $aviso['usuario_curtiu'] ? 'Curtido' : 'Curtir'; ?></span>

                                            (<span class="like-count"><?php echo (int)$aviso['total_curtidas']; ?></span>)
                                        </button>

                                        <button class="btn btn-link text-decoration-none text-muted"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#comment-section-<?php echo (int)$aviso['id_aviso']; ?>">
                                            <i class="bi bi-chat-dots"></i>
                                            Comentários (<span class="total-comentarios-count"><?php echo (int)$aviso['total_comentarios']; ?></span>)
                                        </button>

                                        <button class="btn btn-link text-decoration-none save-btn <?php echo $aviso['usuario_salvou'] ? 'text-primary' : 'text-muted'; ?>"
                                            data-aviso-id="<?php echo (int)$aviso['id_aviso']; ?>">
                                            <i class="bi <?php echo $aviso['usuario_salvou'] ? 'bi-bookmark-fill' : 'bi-bookmark'; ?>"></i>
                                            <span class="save-text"><?php echo $aviso['usuario_salvou'] ? 'Salvo' : 'Salvar'; ?></span>
                                        </button>
                                    </div>

                                    <div class="collapse comments-section mt-3" id="comment-section-<?php echo (int)$aviso['id_aviso']; ?>">
                                        <div class="comment-list mb-3" data-comments-loaded="false"></div>

                                        <form class="comment-form">
                                            <input type="hidden" name="id_aviso" value="<?php echo (int)$aviso['id_aviso']; ?>">
                                            <div class="input-group">
                                                <input type="text" name="conteudo" class="form-control form-control-sm" placeholder="Escreva um comentário..." required autocomplete="off">
                                                <button class="btn btn-outline-secondary btn-sm" type="submit">Publicar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>