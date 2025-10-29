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

// Pega o nome do curso selecionado (se houver) para o título
$nomeCursoSelecionado = '';
if ($id_curso > 0) {
    // Se a busca de turmas retornou algo, usa o nome do curso de lá
    if (!empty($turmas)) {
        $nomeCursoSelecionado = $turmas[0]['nome_curso'];
    } else {
        // Se não houver turmas, busca o nome do curso pelo ID
        $stmtCurso = $pdo->prepare("SELECT nome FROM curso WHERE id_curso = ?");
        $stmtCurso->execute([$id_curso]);
        $nomeCursoSelecionado = $stmtCurso->fetchColumn();
    }
}

include '../partials/admin_header.php';
?>

<div class="main">
    <div class="content mt-5">
        <div class="container-fluid mt-4">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill fs-4 text-warning"></i>
                    <div>
                        <h2 class="h4 mb-0">
                            <?php
                            if ($id_curso > 0 && $nomeCursoSelecionado) {
                                echo 'Turmas: ' . htmlspecialchars($nomeCursoSelecionado);
                            } else {
                                echo 'Gerenciamento de Turmas';
                            }
                            ?>
                        </h2>
                    </div>
                </div>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="../admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="../curso/cursos_view.php">Cursos</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php echo ($id_curso > 0) ? 'Turmas do Curso' : 'Todas as Turmas'; ?>
                        </li>
                    </ol>
                </nav>
            </div>

            <?php flash_show(); ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label" for="q">Buscar</label>
                            <input type="text" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nome da turma ou curso...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="id_curso">Filtrar por curso</label>
                            <select name="id_curso" id="id_curso" class="form-select">
                                <option value="">Todos os cursos</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo (int)$curso['id_curso']; ?>" <?php echo ((int)$curso['id_curso'] === $id_curso ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($curso['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex justify-content-end gap-2">
                            <a class="btn btn-outline-secondary" href="turmas_view.php">Limpar</a>
                            <button class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                    <hr>
                    <div class="d-flex justify-content-end align-items-center">
                        <?php
                        // Adiciona o id_curso ao link de criação, se estiver filtrando
                        $create_url = 'turmas_create.php';
                        if ($id_curso > 0) {
                            $create_url .= '?id_curso=' . $id_curso;
                        }
                        ?>
                        <a class="btn btn-success" href="<?php echo htmlspecialchars($create_url); ?>"><i class="bi bi-plus-lg"></i> Nova Turma</a>
                    </div>
                </div>
            </div>

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
                                    <th class="text-center">Alunos</th>
                                    <th class="text-center">Ano/Sem.</th>
                                    <th>Turno</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th>Atualizado em</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($turmas as $t): ?>
                                    <tr>
                                        <td><?php echo (int)$t['id_turma']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($t['nome']); ?></strong></td>
                                        <td class="small">
                                            <a href="turmas_view.php?id_curso=<?php echo (int)$t['id_curso']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($t['nome_curso']); ?>
                                            </a>
                                        </td>
                                        <td class="text-center"><?php echo ((int)$t['total_alunos']); ?></td>
                                        <td class="text-center"><?php echo ((int)$t['ano']); ?>/<?php echo ((int)$t['semestre']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($t['turno'])); ?></td>
                                        <td>
                                            <span class="badge text-bg-<?php echo $t['status'] === 'aberta' ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($t['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($t['created_at']); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($t['updated_at']); ?></td>
                                        <td class="text-end text-nowrap">
                                            <div class="btn-group" role="group" aria-label="Ações da turma">
                                                <a href="/portal-etc/grade_horaria/horarios_definicao.php" class="btn btn-sm btn-outline-info">
                                                    Horários
                                                </a>
                                                <a href="/portal-etc/grade_horaria/montar_horario.php?id_turma=<?php echo (int)$t['id_turma']; ?>" class="btn btn-sm btn-outline-info">
                                                    Grade
                                                </a>
                                                <a href="turmas_edit.php?id_turma=<?php echo (int)$t['id_turma']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    Editar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$turmas): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">Nenhuma turma encontrada.</td>
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
                            // CORRIGIDO: O link da paginação deve apontar para a página atual
                            $href = 'turmas_view.php?' . http_build_query($baseQuery);
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