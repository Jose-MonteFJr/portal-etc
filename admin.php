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
  array_push($params, $like, $like, $like, $like);
}

if ($roleFilter) {
  $clauses[] = "u.tipo = ?";
  $params[]  = $roleFilter;
}

$whereSql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// Total para paginação
$countSql = "SELECT COUNT(DISTINCT u.id_usuario) 
             FROM usuario u 
             LEFT JOIN aluno a ON a.id_usuario = u.id_usuario 
             LEFT JOIN turma t ON t.id_turma = a.id_turma 
             $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total  = (int)$stmt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Busca usuários
$sql = "SELECT u.id_usuario, u.nome_completo, u.email, u.tipo, u.status, 
               TIMESTAMPDIFF(YEAR, u.data_nascimento, CURDATE()) AS idade, -- <-- ADICIONADO AQUI
               a.matricula, a.status_academico, 
               t.nome AS nome_turma
        FROM usuario u 
        LEFT JOIN aluno a ON a.id_usuario = u.id_usuario 
        LEFT JOIN turma t ON t.id_turma = a.id_turma
        $whereSql
        ORDER BY u.id_usuario DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/partials/admin_header.php';
?>
<div class="main">
  <div class="content mt-5">
    <div class="container-fluid">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h4 mb-0">Dashboard de Administração</h2>
        <span class="badge text-bg-primary">Perfil: Secretaria</span>
      </div>

      <?php flash_show(); ?>

      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <form method="get" class="row g-3 align-items-center mb-3">
            <div class="col-md-5">
              <label for="q" class="form-label visually-hidden">Buscar</label>
              <input type="text" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar por nome, e-mail, turma, matrícula...">
            </div>
            <div class="col-md-3">
              <label for="tipo" class="form-label visually-hidden">Perfil</label>
              <select name="tipo" id="tipo" class="form-select">
                <option value="">Todos os Perfis</option>
                <option value="aluno" <?php echo ($roleFilter ?? '') === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                <option value="secretaria" <?php echo ($roleFilter ?? '') === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
                <option value="professor" <?php echo ($roleFilter ?? '') === 'professor' ? 'selected' : ''; ?>>Professor</option>
                <option value="coordenador" <?php echo ($roleFilter ?? '') === 'coordenador' ? 'selected' : ''; ?>>Coordenador</option>
              </select>
            </div>
            <div class="col-md-4 d-flex justify-content-end gap-2">
              <a class="btn btn-outline-secondary" href="admin.php">Limpar</a>
              <button class="btn btn-primary">Filtrar</button>
              <a class="btn btn-success" href="users_create.php"><i class="bi bi-person-plus-fill"></i> Novo Usuário</a>
            </div>
          </form>
          <hr>
          <div class="d-flex justify-content-start align-items-center flex-wrap gap-2">
            <small class="text-muted me-2">Links Rápidos de Gerenciamento:</small>
            <a class="btn btn-sm btn-outline-secondary" href="curso/cursos_view.php">Cursos</a>
            <a class="btn btn-sm btn-outline-secondary" href="modulo/modulos_view.php">Módulos</a>
            <a class="btn btn-sm btn-outline-secondary" href="turma/turmas_view.php">Turmas</a>
            <a class="btn btn-sm btn-outline-secondary" href="solicitacao/solicitacoes_view_admin.php">Solicitações</a>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header">Usuários cadastrados (<?php echo $total; ?>)</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th>Usuário</th>
                  <th class="text-center">Perfil</th>
                  <th class="text-center">Status</th>
                  <th class="text-center">Idade</th>
                  <th>Dados Acadêmicos</th>
                  <th class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($u['nome_completo']); ?></strong>
                      <div class="small text-muted"><?php echo htmlspecialchars($u['email']); ?></div>
                      <div class="small text-muted">ID: <?php echo (int)$u['id_usuario']; ?></div>
                    </td>

                    <td class="text-center">
                      <?php
                      $cor_perfil = 'secondary';
                      if ($u['tipo'] === 'aluno') $cor_perfil = 'light text-dark';
                      if ($u['tipo'] === 'professor') $cor_perfil = 'info text-dark';
                      if ($u['tipo'] === 'secretaria') $cor_perfil = 'primary';
                      if ($u['tipo'] === 'coordenador') $cor_perfil = 'dark';
                      ?>
                      <span class="badge text-bg-<?php echo $cor_perfil; ?>">
                        <?php echo htmlspecialchars(ucfirst($u['tipo'])); ?>
                      </span>
                    </td>

                    <td class="text-center">
                      <span class="badge text-bg-<?php echo $u['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                        <?php echo htmlspecialchars(ucfirst($u['status'])); ?>
                      </span>
                    </td>

                    <td class="text-center">
                      <?php if ($u['idade'] < 18): ?>
                        <span class="text-danger fw-bold" title="Menor de idade">
                          <?php echo (int)$u['idade']; ?> anos
                          <i class="bi bi-exclamation-triangle-fill"></i>
                        </span>
                      <?php else: ?>
                        <span><?php echo (int)$u['idade']; ?> anos</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <?php if ($u['tipo'] === 'aluno'): ?>
                        <div class="small">
                          <strong>Matrícula:</strong> <?php echo htmlspecialchars($u['matricula'] ?? 'N/A'); ?><br>
                          <strong>Turma:</strong> <?php echo htmlspecialchars($u['nome_turma'] ?? 'N/A'); ?><br>
                          <strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($u['status_academico'] ?? 'N/A')); ?>
                        </div>
                      <?php else: ?>
                        <span class="text-muted small">—</span>
                      <?php endif; ?>
                    </td>

                    <td class="text-end text-nowrap">
                      <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-info btn-detalhes-usuario" data-bs-toggle="modal" data-id="<?php echo $u['id_usuario']; ?>" data-bs-target="#modalDetalhesUsuario">
                          Detalhes
                        </button>
                        <a class="btn btn-sm btn-outline-secondary" href="users_edit.php?id_usuario=<?php echo (int)$u['id_usuario']; ?>">Editar</a>
                      </div>
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



      <!-- Modal -->
      <div class="modal fade" id="modalDetalhesUsuario" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Detalhes dos usuários</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="accordion accordion-flush" id="accordionDetalhesUsuario">

                <div class="accordion-item">
                  <h2 class="accordion-header" id="headingPessoal">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePessoal" aria-expanded="true" aria-controls="collapsePessoal">
                      <i class="bi bi-person-fill me-2"></i> Dados Pessoais
                    </button>
                  </h2>
                  <div id="collapsePessoal" class="accordion-collapse collapse show" aria-labelledby="headingPessoal" data-bs-parent="#accordionDetalhesUsuario">
                    <div class="accordion-body">
                      <dl class="row">
                        <dt class="col-sm-5">ID:</dt>
                        <dd class="col-sm-7"><span id="modal-id"></span></dd>

                        <dt class="col-sm-5">Nome Completo:</dt>
                        <dd class="col-sm-7"><span id="modal-nome"></span></dd>

                        <dt class="col-sm-5">CPF:</dt>
                        <dd class="col-sm-7"><span id="modal-cpf"></span></dd>

                        <dt class="col-sm-5">Email:</dt>
                        <dd class="col-sm-7"><span id="modal-email"></span></dd>

                        <dt class="col-sm-5">Telefone:</dt>
                        <dd class="col-sm-7"><span id="modal-telefone"></span></dd>

                        <dt class="col-sm-5">Nascimento:</dt>
                        <dd class="col-sm-7"><span id="modal-data_nascimento"></span></dd>

                        <dt class="col-sm-5">Idade:</dt>
                        <dd class="col-sm-7"><span id="modal-idade"></span></dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7"><span id="modal-status"></span></dd>

                        <dt class="col-sm-5">Perfil:</dt>
                        <dd class="col-sm-7"><span id="modal-tipo"></span></dd>
                      </dl>
                    </div>
                  </div>
                </div>

                <div class="accordion-item" id="accordion-item-academico" style="display: none;">
                  <h2 class="accordion-header" id="headingAcademico">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAcademico" aria-expanded="false" aria-controls="collapseAcademico">
                      <i class="bi bi-mortarboard-fill me-2"></i> Dados Acadêmicos
                    </button>
                  </h2>
                  <div id="collapseAcademico" class="accordion-collapse collapse" aria-labelledby="headingAcademico" data-bs-parent="#accordionDetalhesUsuario">
                    <div class="accordion-body">
                      <dl class="row">
                        <dt class="col-sm-5">Turma:</dt>
                        <dd class="col-sm-7"><span id="modal-tNome"></span></dd>

                        <dt class="col-sm-5">Data de Ingresso:</dt>
                        <dd class="col-sm-7"><span id="modal-data_ingresso"></span></dd>

                        <dt class="col-sm-5">Status Acadêmico:</dt>
                        <dd class="col-sm-7"><span id="modal-status_academico"></span></dd>
                      </dl>
                    </div>
                  </div>
                </div>

                <div class="accordion-item" id="accordion-item-endereco" style="display: none;">
                  <h2 class="accordion-header" id="headingEndereco">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEndereco" aria-expanded="false" aria-controls="collapseEndereco">
                      <i class="bi bi-geo-alt-fill me-2"></i> Endereço
                    </button>
                  </h2>
                  <div id="collapseEndereco" class="accordion-collapse collapse" aria-labelledby="headingEndereco" data-bs-parent="#accordionDetalhesUsuario">
                    <div class="accordion-body">
                      <dl class="row">
                        <dt class="col-sm-5">CEP:</dt>
                        <dd class="col-sm-7"><span id="modal-cep"></span></dd>

                        <dt class="col-sm-5">Logradouro:</dt>
                        <dd class="col-sm-7"><span id="modal-logradouro"></span></dd>

                        <dt class="col-sm-5">Número:</dt>
                        <dd class="col-sm-7"><span id="modal-numero"></span></dd>

                        <dt class="col-sm-5">Complemento:</dt>
                        <dd class="col-sm-7"><span id="modal-complemento"></span></dd>

                        <dt class="col-sm-5">Bairro:</dt>
                        <dd class="col-sm-7"><span id="modal-bairro"></span></dd>

                        <dt class="col-sm-5">Cidade:</dt>
                        <dd class="col-sm-7"><span id="modal-cidade"></span></dd>

                        <dt class="col-sm-5">Estado:</dt>
                        <dd class="col-sm-7"><span id="modal-estado"></span></dd>
                      </dl>
                    </div>
                  </div>
                </div>

              </div>
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
                var usuario = response;

                // === PREENCHIMENTO DOS DADOS PESSOAIS ===
                $("#modal-id").text(usuario.id_usuario);
                $("#modal-nome").text(usuario.nome_completo);
                $("#modal-cpf").text(usuario.cpf);
                $("#modal-email").text(usuario.email);
                $("#modal-telefone").text(usuario.telefone);
                $("#modal-data_nascimento").text(usuario.data_nascimento_formatada);

                // --- 1. LÓGICA DA IDADE (NOVO) ---
                const idadeSpan = $("#modal-idade");
                idadeSpan.text(usuario.idade + ' anos'); // Preenche a idade

                // Limpa classes de destaque antigas
                idadeSpan.removeClass('text-danger fw-bold').find('i').remove();

                if (usuario.idade < 18) {
                  // Aplica o destaque vermelho se for menor de idade
                  idadeSpan.addClass('text-danger fw-bold');
                  idadeSpan.append(' <i class="bi bi-exclamation-triangle-fill" title="Menor de idade"></i>');
                }

                // --- 2. LÓGICA DOS BADGES (NOVO) ---

                // Badge para Status do Usuário (ativo/inativo)
                let statusBadgeClass = usuario.status === 'ativo' ? 'text-bg-success' : 'text-bg-danger';
                $("#modal-status").html(`<span class="badge ${statusBadgeClass}">${usuario.status}</span>`);

                // Badge para Perfil (tipo)
                let tipoBadgeClass = 'text-bg-secondary';
                if (usuario.tipo === 'secretaria') tipoBadgeClass = 'text-bg-primary';
                if (usuario.tipo === 'professor') tipoBadgeClass = 'text-bg-info text-dark';
                if (usuario.tipo === 'coordenador') tipoBadgeClass = 'text-bg-dark';
                if (usuario.tipo === 'aluno') tipoBadgeClass = 'text-bg-light text-dark';
                $("#modal-tipo").html(`<span class="badge ${tipoBadgeClass}">${usuario.tipo}</span>`);


                // --- 3. LÓGICA CONDICIONAL (DADOS ACADÊMICOS) ---
                const secaoAcademica = $("#accordion-item-academico");
                if (usuario.tipo === 'aluno' && usuario.matricula) {
                  $("#modal-tNome").text(usuario.nome_turma || 'Não informado');
                  $("#modal-data_ingresso").text(usuario.data_ingresso);

                  // Badge para Status Acadêmico
                  let statusAcademicoBadgeClass = 'text-bg-secondary';
                  if (usuario.status_academico === 'cursando') statusAcademicoBadgeClass = 'text-bg-info text-dark';
                  if (usuario.status_academico === 'formado') statusAcademicoBadgeClass = 'text-bg-success';
                  if (usuario.status_academico === 'trancado' || usuario.status_academico === 'desistente') statusAcademicoBadgeClass = 'text-bg-warning text-dark';
                  $("#modal-status_academico").html(`<span class="badge ${statusAcademicoBadgeClass}">${usuario.status_academico}</span>`);

                  secaoAcademica.show();
                } else {
                  secaoAcademica.hide();
                }

                // --- 4. LÓGICA CONDICIONAL (ENDEREÇO) ---
                const secaoEndereco = $("#accordion-item-endereco");
                if (usuario.cep || usuario.logradouro) {
                  $("#modal-cep").text(usuario.cep);
                  $("#modal-logradouro").text(usuario.logradouro);
                  $("#modal-numero").text(usuario.numero);
                  $("#modal-complemento").text(usuario.complemento || 'N/A');
                  $("#modal-bairro").text(usuario.bairro);
                  $("#modal-cidade").text(usuario.cidade);
                  $("#modal-estado").text(usuario.estado);
                  secaoEndereco.show();
                } else {
                  secaoEndereco.hide();
                }

                // Reseta o accordion e mostra o modal
                $('#collapsePessoal').collapse('show');
                $('#collapseAcademico').collapse('hide');
                $('#collapseEndereco').collapse('hide');
                $('#modalDetalhesUsuario').modal('show');
              },
              error: function() {
                $("#detalhes-usuario-content").html('<p class="text-danger text-center">Erro ao buscar dados do usuário.</p>');
                $('#modalDetalhesUsuario').modal('show');
              }
            });
          });
        });
      </script>

      <?php include __DIR__ . '/partials/footer.php'; ?>