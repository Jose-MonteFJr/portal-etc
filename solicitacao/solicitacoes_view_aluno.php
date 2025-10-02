<?php
//Inclui o arquivo de proteção, só loga se for um usuário cadastrado
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';

if ($_SESSION['tipo'] !== 'aluno') {
    flash_set('danger', 'Acesso negado.');
    header('Location: ../admin.php'); // Se for admin, manda para o dashboard de admin
    exit;
}

// LÓGICA DO BACK-END

// 1. Encontrar o id_aluno correspondente ao id_usuario logado na sessão.
$id_aluno = null;
$stmt_aluno = $pdo->prepare("SELECT id_aluno FROM aluno WHERE id_usuario = ?");
$stmt_aluno->execute([$_SESSION['id_usuario']]);
$resultado_aluno = $stmt_aluno->fetch();

if ($resultado_aluno) {
    $id_aluno = $resultado_aluno['id_aluno'];
} else {
    // Se não encontrar um perfil de aluno, é uma situação de erro.
    // Impede o resto do script de rodar para evitar problemas.
    flash_set('danger', 'Perfil de aluno não encontrado para este usuário.');
    header('Location: ../portal_home.php'); // Redireciona para uma página de erro ou home do usuário
    exit;
}

// 2. Buscar no banco de dados todas as solicitações FEITAS POR ESTE ALUNO.
$stmt = $pdo->prepare("SELECT * FROM solicitacao WHERE id_aluno = ? ORDER BY created_at DESC");
$stmt->execute([$id_aluno]);
$solicitacoes = $stmt->fetchAll();

// 3. (Opcional, mas útil) Função para mapear status para cores do Bootstrap
function get_status_badge_class($status)
{
    switch ($status) {
        case 'pendente':
            return 'text-bg-warning'; // Amarelo
        case 'em análise':
            return 'text-bg-primary'; // Azul
        case 'aprovada':
        case 'concluída':
            return 'text-bg-success'; // Verde
        case 'rejeitada':
            return 'text-bg-danger';  // Vermelho
        default:
            return 'text-bg-secondary'; // Cinza
    }
}

include '../partials/portal_header.php';
?>

<!-- Conteúdo principal -->
<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Minhas Solicitações</h2>
                        <a class="btn btn-primary" href="solicitacoes_create.php">+ Nova Solicitação</a>
                    </div>

                    <?php flash_show(); // Exibe mensagens de sucesso/erro 
                    ?>

                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tipo</th>
                                            <th>Data da Solicitação</th>
                                            <th class="text-center">Status</th>
                                            <th>Observação da Secretaria</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($solicitacoes)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">Você ainda não fez nenhuma solicitação.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($solicitacoes as $s): ?>
                                                <tr>
                                                    <td data-label="ID">#<?php echo (int)$s['id_solicitacao']; ?></td>
                                                    <td data-label="Tipo"><?php echo htmlspecialchars(ucwords($s['tipo'])); ?></td>
                                                    <td data-label="Data"><?php echo date('d/m/Y', strtotime($s['created_at'])); ?></td>
                                                    <td class="text-center" data-label="Status">
                                                        <span class="badge <?php echo get_status_badge_class($s['status']); ?>">
                                                            <?php echo htmlspecialchars(ucwords($s['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td data-label="Observação"><?php echo htmlspecialchars($s['observacao'] ?? 'Nenhuma observação.'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>