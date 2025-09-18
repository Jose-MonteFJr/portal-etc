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
  $clauses[] = "(nome LIKE ?)"; 
  $like = "%$q%";
  $params[] = $like;
}

$whereSql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// Total para paginação
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM curso $whereSql");
$stmt->execute($params);
$total  = (int)$stmt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Busca cursos
$sql = "SELECT id_curso, nome, carga_horaria, created_at, updated_at
        FROM curso
        $whereSql
        ORDER BY id_curso DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include '../partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Dashboard de administração</h2>
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
            <th>Carga horária</th>
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
              <td><?php echo htmlspecialchars($u['carga_horaria']); ?></td>
              <td><?php echo htmlspecialchars($u['created_at']); ?></td>
              <td><?php echo htmlspecialchars($u['updated_at']); ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="cursos_edit.php?id_curso=<?php echo (int)$u['id_curso']; ?>">Editar</a>
                <form action="cursos_delete.php" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                  <?php require_once '../helpers.php';
                  csrf_input(); ?>
                  <input type="hidden" name="id_curso" value="<?php echo (int)$u['id_curso']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Nenhum curso encontrado.</td>
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