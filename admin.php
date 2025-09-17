<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();

// Parâmetros de busca/filtro
$q          = trim($_GET['q'] ?? '');
$roleFilter = $_GET['tipo'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 8;

// WHERE dinâmico
$clauses = [];
$params  = [];

if ($q !== '') {
  $clauses[] = "(u.nome_completo LIKE ? OR u.email LIKE ? OR t.nome LIKE ? OR a.matricula LIKE ?)"; // Quando adicionar a matricula, adicionar um novo parametro
  $like = "%$q%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
}

if ($roleFilter === 'secretaria' || $roleFilter === 'aluno' || $roleFilter === 'professor' || $roleFilter === 'coordenador') {
  $clauses[] = "tipo = ?";
  $params[]  = $roleFilter;
}

$whereSql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// Total para paginação
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario u LEFT JOIN aluno a ON a.id_usuario = u.id_usuario LEFT JOIN turma t ON t.id_turma = a.id_turma $whereSql");
$stmt->execute($params);
$total  = (int)$stmt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Busca usuários
$sql = "SELECT u.id_usuario, u.nome_completo, u.email, u.tipo, u.status, a.matricula, a.status_academico, t.nome
        FROM usuario u LEFT JOIN aluno a ON a.id_usuario = u.id_usuario LEFT JOIN turma t ON t.id_turma = a.id_turma
        $whereSql
        ORDER BY u.id_usuario DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Dashboard de administração</h2>
  <span class="badge text-bg-primary">Perfil: Secretaria</span>
</div>

<?php flash_show(); ?>

<form method="get" class="card card-body shadow-sm mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-6">
      <label class="form-label">Buscar</label>
      <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nome, e-mail, turma ou matrícula">
    </div>
    <div class="col-md-3">
      <label class="form-label">Perfil</label>
      <select name="tipo" class="form-select">
        <option value="">Todos</option>
        <option value="aluno" <?php echo $roleFilter === 'aluno'  ? 'selected' : ''; ?>>Aluno</option>
        <option value="secretaria" <?php echo $roleFilter === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
        <option value="professor" <?php echo $roleFilter === 'professor' ? 'selected' : ''; ?>>Professor</option>
        <option value="coordenador" <?php echo $roleFilter === 'coordenador' ? 'selected' : ''; ?>>Coordenador</option>
      </select>
    </div>

    <!-- BOTOES(ADICIONAR + NOVA TURMA + NOVO CURSO) -->

    <div class="col-md-3 text-end">
      <a class="btn btn-outline-secondary" href="admin.php">Limpar</a>
      <button class="btn btn-primary">Filtrar</button>
      <a class="btn btn-success" href="users_create.php">+ Novo Usuário</a>
    </div>
  </div>
</form>

<!-- TABELA VIEW -->

<div class="card shadow-sm">
  <div class="card-header">Usuários cadastrados (<?php echo $total; ?>)</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Perfil</th>
            <th>Status</th>
            <th>Matrícula</th>
            <th>Turma</th>
            <th>Situação acadêmica</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo (int)$u['id_usuario']; ?></td>
              <td><?php echo htmlspecialchars($u['nome_completo']); ?></td>
              <td><?php echo htmlspecialchars($u['email']); ?></td>
              <!-- Tipo destacado -->
              <td>
                <span class="badge text-bg-<?php echo $u['tipo'] === 'secretaria' ? 'danger' : 'secondary'; ?>">
                  <?php echo htmlspecialchars($u['tipo']); ?>
                </span>
              </td>
              <!-- Status destacado -->
              <td>
                <span class="badge text-bg-<?php echo $u['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                  <?php echo htmlspecialchars($u['status']); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($u['matricula']); ?></td>
              <td><?php echo htmlspecialchars($u['nome']); ?></td>
              <td><?php echo htmlspecialchars($u['status_academico']); ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="users_edit.php?id_usuario=<?php echo (int)$u['id_usuario']; ?>">Editar</a>
                <form action="users_delete.php" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                  <?php require_once __DIR__ . '/helpers.php';
                  csrf_input(); ?>
                  <input type="hidden" name="id_usuario" value="<?php echo (int)$u['id_usuario']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado.</td>
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
        $href = 'admin.php?' . http_build_query($baseQuery);
      ?>
        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
          <a class="page-link" href="<?php echo htmlspecialchars($href); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>