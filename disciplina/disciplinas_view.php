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
$stmt = $pdo->prepare('SELECT m.nome AS nome_modulo, c.nome AS nome_curso 
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

$sql = "SELECT id_disciplina, nome, carga_horaria, created_at, updated_at
        FROM disciplina
        $whereSql
        ORDER BY nome ASC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$disciplinas = $stmt->fetchAll();

include '../partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="h4 mb-0">Disciplinas do Módulo: <?php echo htmlspecialchars($modulo_info['nome_modulo']); ?></h2>
        <small class="text-muted">Curso: <?php echo htmlspecialchars($modulo_info['nome_curso']); ?></small>
    </div>
    <div>
        <a class="btn btn-outline-secondary" href="../modulo/modulos_view.php">Voltar aos Módulos</a>
        <a class="btn btn-success" href="disciplinas_create.php?id_modulo=<?php echo $id_modulo; ?>">+ Nova Disciplina</a>
    </div>
</div>

<?php flash_show(); ?>
