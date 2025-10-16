<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';

$id_usuario_logado = $_SESSION['id_usuario'];

try {
    // --- CONSULTA SQL CORRIGIDA E MAIS COMPATÍVEL ---
    $sql = "
        SELECT
            c.id_conversa,
            c.assunto,
            outros.nome_completo AS nome_outro_participante,
            outros.foto_perfil AS foto_outro_participante,
            pc_atual.status_leitura,
            ultima_msg.conteudo AS ultimo_conteudo,
            ultima_msg.created_at AS data_ultima_mensagem,
            remetente_ultima_msg.nome_completo AS nome_remetente_ultima
        FROM
            participante_conversa pc_atual
        JOIN
            participante_conversa pc_outro ON pc_atual.id_conversa = pc_outro.id_conversa AND pc_outro.id_usuario != ?
        JOIN
            usuario outros ON pc_outro.id_usuario = outros.id_usuario
        JOIN
            conversa c ON pc_atual.id_conversa = c.id_conversa
        -- A mágica acontece aqui: primeiro encontramos a data da última mensagem
        LEFT JOIN (
            SELECT id_conversa, MAX(created_at) AS max_created_at
            FROM mensagem
            GROUP BY id_conversa
        ) AS ultimas_datas ON c.id_conversa = ultimas_datas.id_conversa
        -- E depois juntamos com a tabela de mensagens novamente para pegar o conteúdo daquela data
        LEFT JOIN
            mensagem ultima_msg ON ultimas_datas.id_conversa = ultima_msg.id_conversa AND ultimas_datas.max_created_at = ultima_msg.created_at
        LEFT JOIN
            usuario remetente_ultima_msg ON ultima_msg.id_usuario_remetente = remetente_ultima_msg.id_usuario
        WHERE
            pc_atual.id_usuario = ?
        ORDER BY
            data_ultima_mensagem DESC;
    ";

    $stmt = $pdo->prepare($sql);
    // Note que o ID do usuário logado agora é usado duas vezes
    $stmt->execute([$id_usuario_logado, $id_usuario_logado]);
    $conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar a caixa de entrada: " . $e->getMessage());
}

include '../partials/portal_header.php'; // Ajuste o caminho
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Caixa de Entrada</h2>
                        <a class="btn btn-primary" href="nova_mensagem.php">
                            <i class="bi bi-plus-lg"></i> Nova Mensagem
                        </a>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (empty($conversas)): ?>
                                    <div class="list-group-item text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1"></i>
                                        <p class="mb-0 mt-2">Sua caixa de entrada está vazia.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($conversas as $conversa): ?>
                                        <?php
                                        // Define a classe para destacar conversas não lidas
                                        $unread_class = $conversa['status_leitura'] === 'nao lida' ? 'list-group-item-light fw-bold' : '';
                                        $foto_participante = !empty($conversa['foto_outro_participante'])
                                            ? '/portal-etc/uploads/perfil/' . $conversa['foto_outro_participante']
                                            : '/portal-etc/partials/img/avatar_padrao.png';
                                        
                                        // Prepara o preview da última mensagem
                                        $preview_msg = 'Nenhuma mensagem ainda.';
                                        if ($conversa['ultimo_conteudo']) {
                                            $remetente = ($conversa['nome_remetente_ultima'] == $_SESSION['nome_completo']) ? 'Você: ' : '';
                                            $preview_msg = $remetente . htmlspecialchars(substr($conversa['ultimo_conteudo'], 0, 70)) . '...';
                                        }
                                        ?>
                                                    <a href="ver_conversa.php?id_conversa=<?php echo (int)$conversa['id_conversa']; ?>" 
                                                    class="list-group-item list-group-item-action">
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($conversa['status_leitura'] === 'nao lida'): ?>
                                                                <span class="unread-dot me-3"></span>
                                                            <?php else: ?>
                                                                <span class="placeholder-dot me-3"></span> <?php endif; ?>

                                                            <img src="<?php echo htmlspecialchars($foto_participante); ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex w-100 justify-content-between">
                                                                    <h6 class="mb-1 <?php echo $conversa['status_leitura'] === 'nao lida' ? 'fw-bold' : ''; ?>">
                                                                        <?php echo htmlspecialchars($conversa['nome_outro_participante']); ?>
                                                                    </h6>
                                                                    <small class="text-muted"><?php echo date('d/m/y', strtotime($conversa['data_ultima_mensagem'])); ?></small>
                                                                </div>
                                                                <p class="mb-1 small text-muted text-truncate">
                                                                    <?php echo $preview_msg; ?>
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
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; // Ajuste o caminho ?>