<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

// FILTRO DE MÓDULO(FILTRO PRINCIPAL)
$id_modulo = (int)($_GET['id_modulo'] ?? 0);
if ($id_modulo === 0) {
    flash_set('danger', 'Módulo não especificado.');
    header('Location: ../modulo/modulos_view.php');
    exit;
}

// BUSCA DE DADOS DO MÓDULO E CURSO PARA CONTEXTO
$stmt = $pdo->prepare('SELECT m.nome AS nome_modulo, c.nome AS nome_curso, c.id_curso 
                       FROM modulo m 
                       JOIN curso c ON m.id_curso = c.id_curso 
                       WHERE m.id_modulo = ?');
$stmt->execute([$id_modulo]);
$modulo_info = $stmt->fetch();
if (!$modulo_info) {
    flash_set('danger', 'Módulo não encontrado.');
    header('Location: ../modulo/modulos_view.php');
    exit;
}

// Parâmetros de busca/filtro
$q          = trim($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 8;

// WHERE dinâmico
$clauses = [];
$params  = [];

$clauses[] = "id_modulo = ?";
$params[] = $id_modulo;

// BUSCA DINÂMICA
if ($q !== '') {
    $clauses[] = "(nome LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
}

$whereSql = 'WHERE ' . implode(' AND ', $clauses);

$countSql = "SELECT COUNT(*) FROM disciplina $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT id_disciplina, nome, carga_horaria, 
        DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS  created_at,
        DATE_FORMAT(updated_at, '%d/%m/%Y %H:%i') AS updated_at
        FROM disciplina
        $whereSql
        ORDER BY nome ASC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$disciplinas = $stmt->fetchAll();

include '../partials/admin_header.php';
?>

<div class="main">
    <div class="content mt-5">
        <div class="container-fluid mt-4">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text fs-4 text-success"></i>
                    <div>
                        <h2 class="h4 mb-0">Disciplinas: <?php echo htmlspecialchars($modulo_info['nome_modulo']); ?></h2>
                        <small class="text-muted">Curso: <?php echo htmlspecialchars($modulo_info['nome_curso']); ?></small>
                    </div>
                </div>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="../admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="../curso/cursos_view.php">Cursos</a></li>
                        <li class="breadcrumb-item"><a href="../modulo/modulos_view.php?id_curso=<?php echo (int)$modulo_info['id_curso']; ?>"><?php echo htmlspecialchars($modulo_info['nome_curso']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($modulo_info['nome_modulo']); ?></li>
                    </ol>
                </nav>
            </div>

            <?php flash_show(); ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-center">
                        <input type="hidden" name="id_modulo" value="<?php echo $id_modulo; ?>">

                        <div class="col-md-9">
                            <label for="q" class="form-label visually-hidden">Buscar</label>
                            <input type="text" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar por nome da disciplina...">
                        </div>
                        <div class="col-md-3 d-flex justify-content-end gap-2">
                            <a class="btn btn-outline-secondary" href="disciplinas_view.php?id_modulo=<?php echo $id_modulo; ?>">Limpar</a>
                            <button class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                    <hr>
                    <div class="d-flex justify-content-end align-items-center">
                        <a class="btn btn-success" href="disciplinas_create.php?id_modulo=<?php echo $id_modulo; ?>"><i class="bi bi-plus-lg"></i> Nova Disciplina</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Disciplinas cadastradas (<?php echo $total; ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nome da Disciplina</th>
                                    <th class="text-center">Carga Horária</th>
                                    <th>Criado em</th>
                                    <th>Atualizado em</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($disciplinas as $d): ?>
                                    <tr>
                                        <td><?php echo (int)$d['id_disciplina']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($d['nome']); ?></strong></td>
                                        <td class="text-center"><?php echo (int)$d['carga_horaria']; ?>h</td>
                                        <td class="small"><?php echo htmlspecialchars($d['created_at']); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($d['updated_at']); ?></td>
                                        <td class="text-end text-nowrap">
                                            <div class="btn-group" role="group">
                                                <a class="btn btn-sm btn-outline-secondary" href="disciplinas_edit.php?id_disciplina=<?php echo (int)$d['id_disciplina']; ?>">
                                                    Editar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($disciplinas)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Nenhuma disciplina encontrada.</td>
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
                            $href = 'disciplinas_view.php?' . http_build_query($baseQuery);
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