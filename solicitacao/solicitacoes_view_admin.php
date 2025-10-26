<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

// Parâmetros de busca/filtro
$q       = trim($_GET['q'] ?? '');      // Busca por nome do aluno
$status  = trim($_GET['status'] ?? ''); // Filtro por status
$tipo    = trim($_GET['tipo'] ?? '');    // Filtro por tipo de solicitação
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// WHERE dinâmico
$clauses = [];
$params  = [];

if ($q !== '') {
    // Busca no nome completo do usuário (aluno)
    $clauses[] = "(u.nome_completo LIKE ? OR a.matricula LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '') {
    $clauses[] = "s.status = ?";
    $params[] = $status;
}

if ($tipo !== '') {
    $clauses[] = "s.tipo = ?";
    $params[] = $tipo;
}

$whereSql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// Total para paginação
$countSql = "SELECT COUNT(s.id_solicitacao) 
             FROM solicitacao s
             JOIN aluno a ON s.id_aluno = a.id_aluno
             JOIN usuario u ON a.id_usuario = u.id_usuario
             $whereSql";

$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Consulta SQL
$sql = "SELECT 
            s.id_solicitacao,
            s.tipo,
            s.status,
            a.matricula,
            DATE_FORMAT(s.created_at, '%d/%m/%Y') AS created_at,
            DATE_FORMAT(s.updated_at, '%d/%m/%Y') AS updated_at,
            u.nome_completo AS nome_aluno
        FROM solicitacao s
        JOIN aluno a ON s.id_aluno = a.id_aluno
        JOIN usuario u ON a.id_usuario = u.id_usuario
        $whereSql
        ORDER BY s.created_at DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$solicitacoes = $stmt->fetchAll();

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

include '../partials/admin_header.php';
?>

<div class="main">
    <div class="content mt-5">
        <div class="container-fluid mt-4">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-inbox-fill fs-4 text-primary"></i>
                    <h2 class="h4 mb-0">Gerenciar Solicitações</h2>
                </div>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="../admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Solicitações</li>
                    </ol>
                </nav>
            </div>

            <?php flash_show(); ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="q" class="form-label">Buscar por Aluno</label>
                            <input type="text" name="q" id="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nome do aluno ou matrícula">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="pendente" <?php echo ($status === 'pendente' ? 'selected' : ''); ?>>Pendente</option>
                                <option value="em análise" <?php echo ($status === 'em análise' ? 'selected' : ''); ?>>Em Análise</option>
                                <option value="aprovada" <?php echo ($status === 'aprovada' ? 'selected' : ''); ?>>Aprovada</option>
                                <option value="rejeitada" <?php echo ($status === 'rejeitada' ? 'selected' : ''); ?>>Rejeitada</option>
                                <option value="concluída" <?php echo ($status === 'concluída' ? 'selected' : ''); ?>>Concluída</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select name="tipo" id="tipo" class="form-select">
                                <option value="">Todos</option>
                                <option value="renovação de matrícula" <?php echo ($tipo === 'renovação de matrícula' ? 'selected' : ''); ?>>Renovação</option>
                                <option value="emissão de diploma" <?php echo ($tipo === 'emissão de diploma' ? 'selected' : ''); ?>>Diploma</option>
                                <option value="emissão de certificado" <?php echo ($tipo === 'emissão de certificado' ? 'selected' : ''); ?>>Certificado</option>
                                <option value="trancamento de matrícula" <?php echo ($tipo === 'trancamento de matrícula' ? 'selected' : ''); ?>>Trancamento</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex justify-content-end gap-2">
                            <a href="solicitacoes_view_admin.php" class="btn btn-outline-secondary">Limpar</a>
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Solicitações encontradas (<?php echo $total; ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Matrícula</th>
                                    <th>Aluno</th>
                                    <th>Tipo</th>
                                    <th>Data solicitação</th>
                                    <th>Atualizado em</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($solicitacoes)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Nenhuma solicitação encontrada.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($solicitacoes as $s): ?>
                                        <tr>
                                            <td>#<?php echo (int)$s['id_solicitacao']; ?></td>
                                            <td><?php echo htmlspecialchars($s['matricula']); ?></td>
                                            <td><?php echo htmlspecialchars($s['nome_aluno']); ?></td>
                                            <td><?php echo htmlspecialchars(ucwords($s['tipo'])); ?></td>
                                            <td><?php echo htmlspecialchars($s['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($s['updated_at']); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?php echo get_status_badge_class($s['status']); ?>">
                                                    <?php echo htmlspecialchars(ucwords($s['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <a href="solicitacoes_edit.php?id_solicitacao=<?php echo (int)$s['id_solicitacao']; ?>" class="btn btn-sm btn-outline-primary">
                                                    Analisar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php
                        $baseQuery = $_GET;
                        for ($i = 1; $i <= $pages; $i++):
                            $baseQuery['page'] = $i;
                            // CORRIGIDO: O link da paginação deve apontar para a página atual
                            $href = 'solicitacoes_view_admin.php?' . http_build_query($baseQuery);
                        ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars($href); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>