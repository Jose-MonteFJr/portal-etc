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

// Definir os tipos que pertencem a esta categoria
$tipos_matricula = ['renovação de matrícula', 'trancamento de matrícula']; 

// Adaptar a consulta SQL para filtrar por tipo
// Criamos placeholders (?) dinamicamente para a cláusula IN
$placeholders = implode(',', array_fill(0, count($tipos_matricula), '?'));

$sql = "SELECT * FROM solicitacao WHERE id_aluno = ? AND tipo IN ($placeholders) ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$params = array_merge([$id_aluno], $tipos_matricula);
$stmt->execute($params);
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

                    <!-- CABEÇALHO RESPONSIVO CORRIGIDO -->
                    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center mb-3 gap-2">
                        <h2 class="h4 mb-0 text-center text-sm-start">Assuntos da Matrícula</h2>
                        <a class="btn btn-primary w-sm-auto" href="solicitacoes_create_matricula.php">+ Novo Requerimento</a>
                    </div>

                    <?php flash_show(); ?>

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
                                            <th class="text-center">Anexo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($solicitacoes)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">Você ainda não fez nenhum requerimento.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($solicitacoes as $s): ?>
                                                <tr>
                                                    <td data-label="ID">#<?php echo (int)$s['id_solicitacao']; ?></td>
                                                    <td data-label="Tipo"><?php echo htmlspecialchars(ucwords($s['tipo'])); ?></td>
                                                    <td data-label="Data"><?php echo date('d/m/Y', strtotime($s['created_at'])); ?></td>
                                                    <td data-label="Status" class="text-center">
                                                        <span class="badge <?php echo get_status_badge_class($s['status']); ?>">
                                                            <?php echo htmlspecialchars(ucwords($s['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td data-label="Observação"><?php echo htmlspecialchars($s['observacao'] ?? 'Nenhuma observação.'); ?></td>

                                                    <td data-label="Anexo" class="text-center">
                                                        <?php if (!empty($s['caminho_arquivo'])): ?>
                                                            <a href="../uploads/<?php echo htmlspecialchars($s['caminho_arquivo']); ?>"
                                                                class="btn btn-sm btn-outline-success" download>
                                                                Baixar PDF
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted small">Nenhum</span>
                                                        <?php endif; ?>
                                                    </td>
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