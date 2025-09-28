<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

// Parâmetros de busca/filtro
$q          = trim($_GET['q'] ?? '');
$id_curso   = (int)($_GET['id_curso'] ?? 0);
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 8;

// WHERE dinâmico
$clauses = [];
$params  = [];

// Busca dinâmica
if ($q !== '') {
    $clauses[] = "(t.nome LIKE ? OR c.nome LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
}

if ($id_curso > 0) {
    $clauses[] = "t.id_curso = ?";
    $params[] = $id_curso;
}

$whereSql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// Total para paginação
$countSql = "SELECT COUNT(*) FROM turma t LEFT JOIN curso c ON t.id_curso = c.id_curso $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT
            t.id_turma,
            t.nome,
            t.ano,
            t.semestre,
            t.turno,
            t.status,
            DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i') AS created_at,
            DATE_FORMAT(t.updated_at, '%d/%m/%Y %H:%i') AS updated_at,
            c.nome AS nome_curso,
            COUNT(a.id_aluno) AS total_alunos
        FROM
            turma t
        JOIN
            curso c ON t.id_curso = c.id_curso
        LEFT JOIN
            aluno a ON t.id_turma = a.id_turma
        $whereSql
        GROUP BY
            t.id_turma
        ORDER BY
            t.ano DESC, c.nome ASC
        LIMIT $perPage OFFSET $offset";
        
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$turmas = $stmt->fetchAll();

$cursos = $pdo->query("SELECT id_curso, nome FROM curso ORDER BY nome ASC")->fetchAll();

include '../partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="h4 mb-0">
            <?php
            if (!empty($turmas) && $id_curso > 0) {
                // Se sim, pega o nome do curso do PRIMEIRO resultado do array
                echo 'Turmas do Curso: ' . htmlspecialchars($turmas[0]['nome_curso']);
            } else {
                // Senão, exibe um título genérico
                echo 'Gerenciamento de Turmas';
            }
            ?>
        </h2>
    </div>
    <span class="badge text-bg-primary">Perfil: Secretaria</span>
    <a class="btn btn-outline-secondary" href="../curso/cursos_view.php">Voltar</a>
</div>

<?php flash_show(); ?>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label" for="q">Buscar</label>
            <input type="text" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nome da disciplina ou curso">
        </div>

        <div class="col-md-3">
            <label class="form-label" for="id_curso">Filtrar por curso</label>
            <select name="id_curso" id="id_curso" class="form-select">
                <option value="">Todos os cursos</option>
                <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo (int)$curso['id_curso']; ?>"
                        <?php echo ((int)$curso['id_curso'] === $id_curso ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <div class="d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="turmas_view.php">Limpar</a>
                <button class="btn btn-primary">Filtrar</button>
                <a class="btn btn-outline-success" href="turmas_create.php">+ Nova Turma</a>
            </div>
        </div>
    </div>
</form>

<!-- TABELA VIEW -->
<div class="card shadow-sm">
    <div class="card-header">Turmas cadastradas (<?php echo $total; ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Curso</th>
                        <th class="text-center">Qtd alunos</th>
                        <th class="text-center">Ano</th>
                        <th class="text-center">Semestre</th>
                        <th>Turno</th>
                        <th>Status</th>
                        <th class="text-center">Criado em</th>
                        <th class="text-center">Atualizado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($turmas as $t): ?>
                        <tr>
                            <td><?php echo (int)$t['id_turma']; ?></td>
                            <td><?php echo htmlspecialchars($t['nome']); ?></td>
                            <td><?php echo htmlspecialchars($t['nome_curso']); ?></td>
                            <td class="text-center"><?php echo ((int)$t['total_alunos']); ?></td>
                            <td class="text-center"><?php echo ((int)$t['ano']); ?></td>
                            <td class="text-center"><?php echo ((int)$t['semestre']); ?></td>
                            <td><?php echo htmlspecialchars($t['turno']); ?></td>
                            <td><?php echo htmlspecialchars($t['status']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($t['created_at']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($t['updated_at']); ?></td>
                            <td class="text-end">
                                <div class="btn-group" role="group" aria-label="Ações da turma">
                                    <a href="turmas_edit.php?id_turma=<?php echo (int)$t['id_turma']; ?>"
                                        class="btn btn-sm btn-outline-secondary">
                                        Editar
                                    </a>

                                    <form action="turmas_delete.php" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                        <?php require_once '../helpers.php';
                                        csrf_input(); ?>
                                        <input type="hidden" name="id_turma" value="<?php echo (int)$t['id_turma']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$turmas): ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">Nenhuma turma encontrada.</td>
                        </tr>
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
                $href = '../admin.php?' . http_build_query($baseQuery);
            ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo htmlspecialchars($href); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php include '../partials/footer.php'; ?>