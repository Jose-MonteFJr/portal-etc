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

$sql = "SELECT 
    m.id_modulo,
    m.nome AS nome_modulo,
    m.ordem,
    m.created_at,
    m.updated_at,
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

include '../partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Dashboard de administração</h2>
  <span class="badge text-bg-primary">Perfil: Secretaria</span>
  <a class="btn btn-outline-secondary" href="../admin.php">Voltar</a>
</div>

<?php flash_show(); ?>

