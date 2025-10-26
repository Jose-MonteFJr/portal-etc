<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

// Parâmetros de busca/filtro
$q          = trim($_GET['q'] ?? '');
$id_curso   = (int)($_GET['id_curso'] ?? 0);
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 10;

// WHERE dinâmico
$clauses = [];
$params  = [];

// Busca dinâmica
if ($q !== '') {
    $clauses[] = "(m.nome LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
}

if ($id_curso > 0) {
    $clauses[] = "m.id_curso = ?";
    $params[] = $id_curso;
}

$whereSql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// Total para paginação
$countSql = "SELECT COUNT(DISTINCT m.id_modulo) 
             FROM modulo m
             LEFT JOIN curso c ON m.id_curso = c.id_curso
             $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// DATE_FORMAT(c.created_at, '%d/%m/%Y %H:%i') as created_at
$sql = "SELECT 
    m.id_modulo,
    m.nome AS nome_modulo,
    m.ordem,
    DATE_FORMAT(m.created_at, '%d/%m/%Y %H:%i') AS created_at,
    DATE_FORMAT(m.updated_at, '%d/%m/%Y %H:%i') AS updated_at,
    c.nome AS nome_curso,
    COUNT(d.id_disciplina) AS total_disciplinas,
    COALESCE(SUM(d.carga_horaria), 0) AS carga_horaria_modulo
    FROM modulo m 
    JOIN curso c ON m.id_curso = c.id_curso
    LEFT JOIN disciplina d ON m.id_modulo = d.id_modulo 
    $whereSql
    GROUP BY m.id_modulo
    ORDER BY c.nome ASC, m.ordem ASC
    LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$modulos = $stmt->fetchAll();

$cursos = $pdo->query("SELECT id_curso, nome FROM curso ORDER BY nome ASC")->fetchAll();

// Pega o nome do curso selecionado (se houver) para o título
$nomeCursoSelecionado = '';
if ($id_curso > 0) {
    // Tenta obter o nome do primeiro resultado
    if (!empty($modulos)) {
        $nomeCursoSelecionado = $modulos[0]['nome_curso'];
    } else {
        // Se não houver módulos, busca o nome do curso pelo ID
        $stmtCurso = $pdo->prepare("SELECT nome FROM curso WHERE id_curso = ?");
        $stmtCurso->execute([$id_curso]);
        $nomeCursoSelecionado = $stmtCurso->fetchColumn();
    }
}

include '../partials/admin_header.php';
?>

<div class="main">
    <div class="content mt-5">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="h4 mb-0">
                    <?php
                    if ($id_curso > 0 && $nomeCursoSelecionado) {
                        echo 'Módulos do Curso: ' . htmlspecialchars($nomeCursoSelecionado);
                    } else {
                        echo 'Gerenciamento de Módulos';
                    }
                    ?>
                </h2>
                <a class="btn btn-outline-secondary btn-sm" href="../curso/cursos_view.php">Voltar para Cursos</a>
            </div>

            <?php flash_show(); ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label" for="q">Buscar</label>
                            <input type="text" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nome do módulo">
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

                        <div class="col-md-3 d-flex justify-content-end gap-2">
                            <a class="btn btn-outline-secondary" href="modulos_view.php">Limpar</a>
                            <button class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                    <hr>
                    <div class="d-flex justify-content-end align-items-center">
                        <?php
                        $create_url = 'modulos_create.php';
                        if ($id_curso > 0) {
                            $create_url .= '?id_curso=' . $id_curso;
                        }
                        ?>
                        <a class="btn btn-success" href="<?php echo htmlspecialchars($create_url); ?>"><i class="bi bi-plus-lg"></i> Novo Módulo</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Módulos cadastrados (<?php echo $total; ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nome</th>
                                    <th>Curso</th>
                                    <th class="text-center">Módulo</th>
                                    <th class="text-center">Disciplinas</th>
                                    <th class="text-center">Carga Horária</th>
                                    <th>Criado em</th>
                                    <th>Atualizado em</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modulos as $m): ?>
                                    <tr>
                                        <td><?php echo (int)$m['id_modulo']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($m['nome_modulo']); ?></strong></td>

                                        <td class="small">
                                            <a href="/portal-etc/curso/cursos_view.php" class="text-decoration-none">
                                                <?php echo htmlspecialchars($m['nome_curso']); ?>
                                            </a>
                                        </td>

                                        <td class="text-center"><?php echo ((int)$m['ordem']); ?>°</td>
                                        <td class="text-center"><?php echo ((int)$m['total_disciplinas']); ?></td>
                                        <td class="text-center"><?php echo ((int)$m['carga_horaria_modulo']); ?>h</td>
                                        <td class="small"><?php echo htmlspecialchars($m['created_at']); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($m['updated_at']); ?></td>
                                        <td class="text-end text-nowrap">
                                            <div class="btn-group" role="group">
                                                <a href="../disciplina/disciplinas_view.php?id_modulo=<?php echo (int)$m['id_modulo']; ?>" class="btn btn-sm btn-outline-info">
                                                    Disciplinas
                                                </a>
                                                <a href="modulos_edit.php?id_modulo=<?php echo (int)$m['id_modulo']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    Editar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($modulos)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Nenhum módulo encontrado.</td>
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
                            $href = 'modulos_view.php?' . http_build_query($baseQuery);
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