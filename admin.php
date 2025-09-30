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
  $clauses[] = "(u.nome_completo LIKE ? OR u.email LIKE ? OR t.nome LIKE ? OR a.matricula LIKE ?)";
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
  <h2 class="h4 mb-0">Dashboard de administração: usuários</h2>
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

    <div class="col-md-3 text-end">
      <a class="btn btn-outline-secondary" href="admin.php">Limpar</a>
      <button class="btn btn-primary">Filtrar</button>
    </div>
  </div>
</form>

<div class="card card-body shadow-sm mb-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
    <a class="btn btn-outline-secondary" href="curso/cursos_view.php">Ver Cursos</a>
    <a class="btn btn-outline-success" href="users_create.php">+ Novo Usuário</a>
  </div>
</div>

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
                <span class="badge text-bg-<?php echo $u['tipo'] === 'secretaria' ? 'primary' : 'secondary'; ?>">
                  <?php echo htmlspecialchars($u['tipo']); ?>
                </span>
              </td>
              <!-- Status destacado -->
              <td>
                <span class="badge text-bg-<?php echo $u['status'] === 'ativo' ? 'success' : 'warning'; ?>">
                  <?php echo htmlspecialchars($u['status']); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($u['matricula']); ?></td>
              <td><?php echo htmlspecialchars($u['nome']); ?></td>
              <td><?php echo htmlspecialchars($u['status_academico']); ?></td>
              <td class="text-end">
                <div class="btn-group" role="group" aria-label="Ações dos usuários">
                  <!-- Button trigger modal -->
                  <button type="button" class="btn btn-sm btn-outline-info btn-detalhes-usuario" data-bs-toggle="modal" data-id="<?php echo $u['id_usuario']; ?>" data-bs-target="#modalDetalhesUsuario">
                    Detalhes
                  </button>
                  <a class="btn btn-sm btn-outline-secondary" href="users_edit.php?id_usuario=<?php echo (int)$u['id_usuario']; ?>">Editar</a>

                  <!-- Inativar usuario -->
                  <form action="users_inativar.php" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja inativar esse usuário?');">
                    <?php require_once __DIR__ . '/helpers.php';
                    csrf_input(); ?>
                    <input type="hidden" name="id_usuario" value="<?php echo (int)$u['id_usuario']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning">Inativar</button>
                  </form>

                  <!-- Excluir usuario -->
                  <form action="users_delete.php" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                    <?php require_once __DIR__ . '/helpers.php';
                    csrf_input(); ?>
                    <input type="hidden" name="id_usuario" value="<?php echo (int)$u['id_usuario']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">Nenhum usuário encontrado.</td>
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

<!-- Modal -->
<div class="modal fade" id="modalDetalhesUsuario" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Detalhes dos usuários</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- CAMPOS PARA APARECER NO MODAL -->
        <p><strong>Id:</strong> <span id="modal-id"></span></p> <!-- COPIAR PARA BAIXO - MUDAR O ID -->
        <p><strong>Nome:</strong> <span id="modal-nome"></span></p>
        <p><strong>Cpf:</strong> <span id="modal-cpf"></span></p>
        <p><strong>Email:</strong> <span id="modal-email"></span></p>
        <p><strong>Telefone:</strong> <span id="modal-telefone"></span></p>
        <p><strong>Data de nascimento:</strong> <span id="modal-data_nascimento"></span></p>
        <p><strong>Status:</strong> <span id="modal-status"></span></p>
        <p><strong>Perfil:</strong> <span id="modal-tipo"></span></p>
        <br>
        <p><strong>Turma:</strong> <span id="modal-tNome"></span></p>
        <p><strong>Data de ingresso:</strong> <span id="modal-data_ingresso"></span></p>
        <p><strong>Status acadêmico:</strong> <span id="modal-status_academico"></span></p>
        <br>
        <p><strong>Cep:</strong> <span id="modal-cep"></span></p>
        <p><strong>Logradouro:</strong> <span id="modal-logradouro"></span></p>
        <p><strong>Número:</strong> <span id="modal-numero"></span></p>
        <p><strong>Complemento:</strong> <span id="modal-complemento"></span></p>
        <p><strong>bairro:</strong> <span id="modal-bairro"></span></p>
        <p><strong>Cidade:</strong> <span id="modal-cidade"></span></p>
        <p><strong>Estado:</strong> <span id="modal-estado"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>
<script src="/portal-etc/partials/js/jquery-3.7.1.min.js"></script>
<script type="text/javascript">
  $(document).ready(function() {
    // Quando o botão de detalhes for clicado
    $(".btn-detalhes-usuario").click(function() {
      var userId = $(this).data("id"); // Pega o ID do usuário

      // Faz a requisição AJAX para buscar os dados do usuário
      $.ajax({
        url: 'user_details.php', // Arquivo que irá processar a requisição
        type: 'GET',
        data: {
          id: userId
        }, // Passa o ID do usuário como parâmetro
        success: function(response) {
          // Resposta do servidor (dados do usuário)
          var usuario = JSON.parse(response);

          // Preenche os campos do modal com os dados recebidos
          $("#modal-id").text(usuario.id_usuario);
          $("#modal-nome").text(usuario.nome_completo); // COPIAR PARA BAIXO MAS ALTERAR O text.(usuario.campo)e o ID
          $("#modal-cpf").text(usuario.cpf);
          $("#modal-email").text(usuario.email);
          $("#modal-telefone").text(usuario.telefone);
          $("#modal-data_nascimento").text(usuario.data_nascimento);
          $("#modal-status").text(usuario.status);
          $("#modal-tipo").text(usuario.tipo);

          // TURMA
          $("#modal-tNome").text(usuario.nome);
          $("#modal-data_ingresso").text(usuario.data_ingresso);
          $("#modal-status_academico").text(usuario.status_academico);

          $("#modal-cep").text(usuario.cep);
          $("#modal-logradouro").text(usuario.logradouro);
          $("#modal-numero").text(usuario.numero);
          $("#modal-complemento").text(usuario.complemento);
          $("#modal-bairro").text(usuario.bairro);
          $("#modal-cidade").text(usuario.cidade);
          $("#modal-estado").text(usuario.estado);


          // Exibe o modal
          $('#modalDetalhes').modal('show');
        },
        error: function() {
          alert("Erro ao buscar dados do usuário.");
        }
      });
    });
  });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>