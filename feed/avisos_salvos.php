<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';

// Garante que o usuário esteja logado
if (!isset($_SESSION['id_usuario'])) {
    redirect('index.php');
}

$id_usuario_logado = $_SESSION['id_usuario'];

// --- A CONSULTA SQL PARA AVISOS SALVOS ---
try {
    // Define a variável na sessão do MySQL com o ID do usuário logado
    $pdo->exec("SET @id_usuario_logado = " . (int)$id_usuario_logado);

    // A MUDANÇA PRINCIPAL ESTÁ AQUI:
    // A consulta agora começa pela tabela 'aviso_salvo' e usa INNER JOIN
    // para garantir que apenas os avisos salvos pelo usuário apareçam.
    $sql = "SELECT
                a.id_aviso, a.titulo, a.descricao, a.caminho_imagem, a.created_at,
                u.nome_completo AS nome_autor,
                u.foto_perfil AS foto_autor,
                
                (SELECT COUNT(*) FROM curtida WHERE id_aviso = a.id_aviso) AS total_curtidas,
                (SELECT COUNT(*) FROM comentario WHERE id_aviso = a.id_aviso) AS total_comentarios,
                
                EXISTS(SELECT 1 FROM curtida WHERE id_aviso = a.id_aviso AND id_usuario = @id_usuario_logado) AS usuario_curtiu,
                EXISTS(SELECT 1 FROM aviso_salvo WHERE id_aviso = a.id_aviso AND id_usuario = @id_usuario_logado) AS usuario_salvou
            FROM
                aviso_salvo asv -- Começamos pela tabela de itens salvos
            JOIN
                aviso a ON asv.id_aviso = a.id_aviso -- Pegamos os dados do aviso
            JOIN
                usuario u ON a.id_usuario_autor = u.id_usuario -- Pegamos os dados do autor
            WHERE
                asv.id_usuario = @id_usuario_logado -- Filtramos para o usuário logado
            ORDER BY
                asv.created_at DESC"; // Ordena pelos salvos mais recentemente

    $stmt = $pdo->query($sql);
    $avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar os avisos salvos: " . $e->getMessage());
}

include '../partials/portal_header.php'; // Ajuste o caminho
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Meus Avisos Salvos</h2>
                        <a href="feed.php" class="btn btn-outline-secondary btn-sm">Voltar para o Feed</a>
                    </div>

                    <?php if (empty($avisos)): ?>
                        <div class="card shadow-sm text-center">
                            <div class="card-body">
                                <p class="mb-0 text-muted">Você ainda não salvou nenhum aviso. Clique no ícone <i class="bi bi-bookmark"></i> nos avisos do feed para salvá-los aqui.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($avisos as $aviso): ?>
                            <a href="feed.php#aviso-<?php echo (int)$aviso['id_aviso']; ?>" class="card-link-wrapper">
                                <div class="card shadow-sm mb-4 card-hover">
                                    <div class="card-header d-flex align-items-center">
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

                                    <div class="card-body">
                                        <?php if (!empty($aviso['caminho_imagem'])): ?>
                                            <img src="/portal-etc/uploads/avisos/<?php echo htmlspecialchars($aviso['caminho_imagem']); ?>" class="img-fluid rounded mb-3">
                                        <?php endif; ?>

                                        <h5 class="card-title"><?php echo htmlspecialchars($aviso['titulo']); ?></h5>
                                        <p class="card-text text-muted">
                                            <?php echo htmlspecialchars(substr($aviso['descricao'], 0, 150)); ?>...
                                        </p>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>


<?php include '../partials/footer.php'; // Ajuste o caminho ?>