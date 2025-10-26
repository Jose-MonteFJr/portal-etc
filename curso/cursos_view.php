<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

// Parâmetros de busca/filtro
$q          = trim($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 8;

// WHERE dinâmico
$clauses = [];
$params  = [];

// Busca dinâmica

if ($q !== '') {
  $clauses[] = "(c.nome LIKE ?)";
  $like = "%$q%";
  $params[] = $like;
}
$whereSql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// Total para paginação
$countSql = "SELECT COUNT(*) FROM curso c $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Busca cursos
$sql = "SELECT
            c.id_curso,
            c.nome,
            c.descricao,
            c.created_at,
            c.updated_at,
            COALESCE(ch.carga_horaria_total, 0) AS carga_horaria_total,
            COALESCE(ta.total_alunos, 0) AS total_alunos
        FROM
            curso c
        LEFT JOIN (
            SELECT m.id_curso, SUM(d.carga_horaria) AS carga_horaria_total
            FROM modulo m JOIN disciplina d ON m.id_modulo = d.id_modulo
            GROUP BY m.id_curso
        ) AS ch ON c.id_curso = ch.id_curso
        LEFT JOIN (
            SELECT t.id_curso, COUNT(a.id_aluno) AS total_alunos
            FROM turma t JOIN aluno a ON t.id_turma = a.id_turma
            GROUP BY t.id_curso
        ) AS ta ON c.id_curso = ta.id_curso
        $whereSql
        ORDER BY c.nome ASC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../partials/admin_header.php';
?>

<div class="main">
  <div class="content mt-5">
    <div class="container-fluid">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h4 mb-0">Gerenciamento de Cursos</h2>
        <a class="btn btn-outline-secondary btn-sm" href="../admin.php">Voltar ao Dashboard</a>
      </div>

      <?php flash_show(); ?>

      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <form method="get" class="row g-3 align-items-center">
            <div class="col-md-9">
              <label for="q" class="form-label visually-hidden">Buscar</label>
              <input type="text" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar por nome do curso...">
            </div>
            <div class="col-md-3 d-flex justify-content-end gap-2">
              <a class="btn btn-outline-secondary" href="cursos_view.php">Limpar</a>
              <button class="btn btn-primary">Filtrar</button>
            </div>
          </form>
          <hr>
          <div class="d-flex justify-content-end align-items-center">
            <a class="btn btn-success" href="cursos_create.php"><i class="bi bi-plus-lg"></i> Novo Curso</a>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header">Cursos cadastrados (<?php echo $total; ?>)</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th>Curso</th>
                  <th>Descrição</th>
                  <th class="text-center">Dados</th>
                  <th>Cadastro</th>
                  <th class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cursos as $c): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($c['nome']); ?></strong>
                      <div class="small text-muted">ID: <?php echo (int)$c['id_curso']; ?></div>
                    </td>

                    <td class="text-muted small" style="max-width: 300px;">
                      <span class="d-inline-block text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($c['descricao']); ?>">
                        <?php echo htmlspecialchars($c['descricao']); ?>
                      </span>
                    </td>

                    <td class="text-center">
                      <div class="small">
                        <strong>Carga:</strong> <?php echo (int)$c['carga_horaria_total']; ?>h<br>
                        <strong>Alunos:</strong> <?php echo (int)$c['total_alunos']; ?>
                      </div>
                    </td>

                    <td class="small">
                      <?php echo date('d/m/Y', strtotime($c['created_at'])); ?>
                    </td>

                    <td class="text-end text-nowrap">
                      <div class="btn-group" role="group">
                        <a href="../modulo/modulos_view.php?q=&id_curso=<?php echo (int)$c['id_curso']; ?>" class="btn btn-sm btn-outline-info">
                          Módulos
                        </a>
                        <a href="../turma/turmas_view.php?q=&id_curso=<?php echo (int)$c['id_curso']; ?>" class="btn btn-sm btn-outline-info">
                          Turmas
                        </a>
                        <a class="btn btn-sm btn-outline-secondary" href="cursos_edit.php?id_curso=<?php echo (int)$c['id_curso']; ?>">
                          Editar
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($cursos)): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Nenhum curso encontrado.</td>
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
              $href = 'cursos_view.php?' . http_build_query($baseQuery);
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