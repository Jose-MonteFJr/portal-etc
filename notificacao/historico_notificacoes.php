<?php
require     '../protect.php';
require     '../config/db.php';
require     '../helpers.php';

$id_usuario = $_SESSION['id_usuario'];

// Busca TODAS as notificações do usuário, sem limite
$stmt = $pdo->prepare("SELECT * FROM notificacao WHERE id_usuario_destino = ? ORDER BY created_at DESC");
$stmt->execute([$id_usuario]);
$todas_notificacoes = $stmt->fetchAll();

include '../partials/portal_header.php';
?>
<div class="main">
    <div class="content">
        <div class="container mt-4">
            <h2 class="h4 mb-3">Histórico de Notificações</h2>

            <div class="list-group">
                <?php if (empty($todas_notificacoes)): ?>
                    <div class="list-group-item">Nenhuma notificação no seu histórico.</div>
                <?php else: ?>
                    <?php foreach ($todas_notificacoes as $notif): ?>
                        <a href="<?php echo htmlspecialchars($notif['link'] ?? '#'); ?>"
                            class="list-group-item list-group-item-action <?php echo ($notif['status'] === 'nao lida' ? 'list-group-item-info' : ''); ?>">
                            <div class="d-flex w-100 justify-content-between">
                                <p class="mb-1"><?php echo htmlspecialchars($notif['mensagem']); ?></p>
                                <small class="text-muted"><?php echo date('d/m/y H:i', strtotime($notif['created_at'])); ?></small>
                            </div>
                            <?php if ($notif['status'] === 'nao lida'): ?>
                                <small class="fw-bold">Não lida</small>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../partials/footer.php'; ?>