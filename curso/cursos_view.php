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
            DATE_FORMAT(c.created_at, '%d/%m/%Y %H:%i') as created_at,
            DATE_FORMAT(c.updated_at, '%d/%m/%Y %H:%i') as updated_at,
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
        ORDER BY c.id_curso DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include '../partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Dashboard de administração: cursos</h2>
  <span class="badge text-bg-primary">Perfil: Secretaria</span>
  <a class="btn btn-outline-secondary" href="../admin.php">Voltar</a>
</div>

<?php flash_show(); ?>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-6">
      <label class="form-label" for="q">Buscar</label>
      <input type="text" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nome do curso">
    </div>
    <div class="col-md-6 text-end">
      <a class="btn btn-outline-secondary" href="cursos_view.php">Limpar</a>
      <button class="btn btn-primary">Filtrar</button>
      <a class="btn btn-outline-success" href="cursos_create.php">+ Novo Curso</a>
    </div>
  </div>
</form>

<!-- TABELA VIEW -->
<div class="card shadow-sm">
  <div class="card-header">Cursos cadastrados (<?php echo $total; ?>)</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Nome</th>
            <th class="text-center">Carga horária</th>
            <th class="text-center">Qtd alunos</th>
            <th class="text-center">Descrição</th>
            <th>Criado em</th>
            <th>Atualizado em</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo (int)$u['id_curso']; ?></td>
              <td><?php echo htmlspecialchars($u['nome']); ?></td>
              <td class="text-center"><?php echo ((int)$u['carga_horaria_total']); ?>h</td>
              <td class="text-center"><?php echo ((int)$u['total_alunos']); ?></td>
              <td class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($u['descricao']); ?>"> <?php echo htmlspecialchars($u['descricao']); ?></td>
              <td><?php echo htmlspecialchars($u['created_at']); ?></td>
              <td><?php echo htmlspecialchars($u['updated_at']); ?></td>
              <td class="text-end">
                <div class="btn-group" role="group" aria-label="Ações do curso">
                  <a href="../turma/turmas_view.php?q=&id_curso=<?php echo (int)$u['id_curso']; ?>"
                      class="btn btn-sm btn-outline-info">Turmas</a>
                  <a href="../modulo/modulos_view.php?q=&id_curso=<?php echo (int)$u['id_curso']; ?>"
                      class="btn btn-sm btn-outline-info">
                      Módulos
                  </a>
                  <a class="btn btn-sm btn-outline-secondary" href="cursos_edit.php?id_curso=<?php echo (int)$u['id_curso']; ?>">Editar</a>
                  <form action="cursos_delete.php" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                    <?php require_once '../helpers.php';
                    csrf_input(); ?>
                    <input type="hidden" name="id_curso" value="<?php echo (int)$u['id_curso']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">Nenhum curso encontrado.</td>
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