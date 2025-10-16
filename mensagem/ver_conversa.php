<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';

$id_usuario_logado = $_SESSION['id_usuario'];
$id_conversa = (int)($_GET['id_conversa'] ?? 0);

if ($id_conversa === 0) {
    flash_set('danger', 'Conversa não encontrada.');
    header('Location: caixa_de_entrada.php');
    exit;
}

try {
    // --- 1. VERIFICAÇÃO DE SEGURANÇA ---
    // Garante que o usuário logado é participante desta conversa
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM participante_conversa WHERE id_conversa = ? AND id_usuario = ?");
    $stmt_check->execute([$id_conversa, $id_usuario_logado]);
    if ($stmt_check->fetchColumn() === 0) {
        flash_set('danger', 'Acesso negado.');
        header('Location: caixa_de_entrada.php');
        exit;
    }

    // --- 2. LÓGICA DE RESPOSTA (se o formulário for enviado) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $conteudo = trim($_POST['conteudo'] ?? '');

        if (!empty($conteudo)) {
            $pdo->beginTransaction();

            // Insere a nova mensagem
            $stmt_insert = $pdo->prepare("INSERT INTO mensagem (id_conversa, id_usuario_remetente, conteudo) VALUES (?, ?, ?)");
            $stmt_insert->execute([$id_conversa, $id_usuario_logado, $conteudo]);

            // Atualiza o status de leitura do OUTRO participante para 'nao lida'
            $stmt_update = $pdo->prepare("UPDATE participante_conversa SET status_leitura = 'nao lida' WHERE id_conversa = ? AND id_usuario != ?");
            $stmt_update->execute([$id_conversa, $id_usuario_logado]);

            // =============================================================
            // == NOVO: CRIA A NOTIFICAÇÃO PARA O OUTRO PARTICIPANTE      ==
            // =============================================================
            
            // Busca o ID do outro participante para saber para quem enviar a notificação
            $stmt_dest = $pdo->prepare("SELECT id_usuario FROM participante_conversa WHERE id_conversa = ? AND id_usuario != ?");
            $stmt_dest->execute([$id_conversa, $id_usuario_logado]);
            $id_destinatario = $stmt_dest->fetchColumn();

            if ($id_destinatario) {
                $nome_remetente = $_SESSION['nome_completo'];
                $mensagem_notificacao = $nome_remetente . " respondeu à sua mensagem.";
                $link_notificacao = "/portal-etc/mensagem/ver_conversa.php?id_conversa=" . $id_conversa; // Ajuste o caminho

                criar_notificacao($pdo, $id_destinatario, $mensagem_notificacao, $link_notificacao);
            }
            // =============================================================


            $pdo->commit();

            // Redireciona para a mesma página para mostrar a nova mensagem
            header('Location: ver_conversa.php?id_conversa=' . $id_conversa);
            exit;
        }
    }

    // --- 3. MARCA A CONVERSA COMO LIDA para o usuário atual ---
    $stmt_read = $pdo->prepare("UPDATE participante_conversa SET status_leitura = 'lida' WHERE id_conversa = ? AND id_usuario = ?");
    $stmt_read->execute([$id_conversa, $id_usuario_logado]);

    // --- 4. BUSCA DADOS DA CONVERSA E HISTÓRICO DE MENSAGENS ---
    // Busca o nome do outro participante para o título da página
    $stmt_other = $pdo->prepare("
    SELECT u.nome_completo, pc.status_leitura 
    FROM usuario u 
    JOIN participante_conversa pc ON u.id_usuario = pc.id_usuario 
    WHERE pc.id_conversa = ? AND pc.id_usuario != ?
");
$stmt_other->execute([$id_conversa, $id_usuario_logado]);
$outro_participante = $stmt_other->fetch();

    // Busca todas as mensagens da conversa, com os dados do remetente
    $stmt_msgs = $pdo->prepare("
        SELECT m.*, u.nome_completo, u.foto_perfil 
        FROM mensagem m
        JOIN usuario u ON m.id_usuario_remetente = u.id_usuario
        WHERE m.id_conversa = ?
        ORDER BY m.created_at ASC
    ");
    $stmt_msgs->execute([$id_conversa]);
    $mensagens = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar a conversa: " . $e->getMessage());
}

include '../partials/portal_header.php'; // Ajuste o caminho
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">
                                Conversa com: <?php echo htmlspecialchars($outro_participante['nome_completo']); ?>
                            </h5>
                            <a href="caixa_de_entrada.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
                        </div>

                        <div class="card-body" style="height: 60vh; overflow-y: auto;" id="chat-box">
                            <?php foreach ($mensagens as $mensagem): ?>
                                <?php
                                $is_sender = $mensagem['id_usuario_remetente'] == $id_usuario_logado;
                                $foto_remetente = !empty($mensagem['foto_perfil'])
                                    ? '/portal-etc/uploads/perfil/' . $mensagem['foto_perfil']
                                    : '/portal-etc/partials/img/avatar_padrao.png';
                                ?>

                                <?php if ($is_sender): // SE A MENSAGEM FOI ENVIADA POR VOCÊ 
                                ?>

                                <div class="d-flex justify-content-end mb-3">
                                        <div class="chat-bubble sent">
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($mensagem['conteudo'])); ?></p>
                                            <small class="d-block text-end text-white-50">
                                                <?php echo date('H:i', strtotime($mensagem['created_at'])); ?>
                                                <?php if ($outro_participante['status_leitura'] === 'lida'): ?>
                                                    <i class="bi bi-check2-all ms-1 text-info"></i> <?php else: ?>
                                                    <i class="bi bi-check2 ms-1"></i> <?php endif; ?>
                                            </small>
                                        </div>
                                </div>

                                <?php else: // SE A MENSAGEM FOI RECEBIDA 
                                ?>

                                    <div class="d-flex justify-content-start mb-3">
                                        <div class="me-2">
                                            <img src="<?php echo htmlspecialchars($foto_remetente); ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        </div>
                                        <div class="chat-bubble received">
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($mensagem['conteudo'])); ?></p>
                                            <small class="d-block text-end text-muted">
                                                <?php echo date('H:i', strtotime($mensagem['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>

                                <?php endif; ?>

                            <?php endforeach; ?>
                        </div>

                        <div class="card-footer">
                            <form method="post" action="ver_conversa.php?id_conversa=<?php echo $id_conversa; ?>">
                                <?php csrf_input(); ?>
                                <div class="input-group">
                                    <textarea name="conteudo" class="form-control" placeholder="Digite sua mensagem..." rows="2" required></textarea>
                                    <button class="btn btn-primary" type="submit">Enviar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; // Ajuste o caminho 
?>