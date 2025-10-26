<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();

$id_usuario = (int)($_GET['id_usuario'] ?? 0);
$stmt = $pdo->prepare('SELECT u.id_usuario, u.nome_completo, u.cpf, u.email, u.telefone, u.data_nascimento, u.tipo, u.status, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep, t.id_turma, t.nome, t.ano, t.semestre, t.turno, a.data_ingresso, a.status_academico FROM usuario u LEFT JOIN aluno a ON a.id_usuario = u.id_usuario LEFT JOIN turma t ON t.id_turma = a.id_turma LEFT JOIN endereco e ON e.id_usuario = u.id_usuario WHERE u.id_usuario=?');

// Busca todas as turmas
$turmasStmt = $pdo->query("SELECT id_turma, nome, ano, semestre, turno FROM turma ORDER BY ano DESC, semestre DESC, nome ASC");
$turmas = $turmasStmt->fetchAll(PDO::FETCH_ASSOC);
$tem_turmas = count($turmas) > 0;

$stmt->execute([$id_usuario]);
$user = $stmt->fetch();
if (!$user) {
  flash_set('danger', 'Usuário não encontrado.');
  header('Location: admin.php');
  exit;
}

$errors = [];
// Campos usuario
$nome_completo = $user['nome_completo'];
$cpf = $user['cpf'];
$email = $user['email'];
$telefone = $user['telefone'];
$data_nascimento = $user['data_nascimento'];
$tipo = $user['tipo'];
$status = $user['status'];

// Campos aluno
$id_turma = $user['id_turma'];
$data_ingresso = $user['data_ingresso'];
$status_academico = $user['status_academico'];

// Campos endereço
$logradouro = $user['logradouro'];
$numero = $user['numero'];
$complemento = $user['complemento'];
$bairro = $user['bairro'];
$cidade = $user['cidade'];
$estado = $user['estado'];
$cep = $user['cep'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  // Captura dados do formulário
  $nome_completo   = trim($_POST['nome_completo'] ?? '');
  $cpf             = trim($_POST['cpf'] ?? '');
  $email           = trim($_POST['email'] ?? '');
  $password        = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';
  $telefone        = trim($_POST['telefone'] ?? '');
  $data_nascimento = trim($_POST['data_nascimento'] ?? '');
  $status            = $_POST['status'] ?? 'ativo';

  // Dados aluno
  $id_turma      = $_POST['id_turma'] ?? null;
  $data_ingresso = $_POST['data_ingresso'] ?? null;
  $status_academico = $_POST['status_academico'] ?? 'cursando';

  // Dados endereço
  $cep = $_POST['cep'] ?? '';
  $logradouro = $_POST['logradouro'] ?? '';
  $numero = $_POST['numero'] ?? '';
  $complemento = $_POST['complemento'] ?? ''; // Opcional
  $bairro = $_POST['bairro'] ?? '';
  $cidade = $_POST['cidade'] ?? '';
  $estado = $_POST['estado'] ?? '';

  // --- Validações ---
  if ($nome_completo === '') $errors[] = 'Nome completo é obrigatório.';

  // CPF: formato e dígito verificador
  if ($cpf === '') $errors[] = 'CPF é obrigatório.';

  if (!empty($cpf) && !validaCPF($cpf)) {
    $errors[] = "O CPF informado não é válido.";
  }

  // E-mail
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail inválido.';
  }

  if ($telefone === '') $errors[] = 'Telefone é obrigatório.';

  $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);

  if (!empty($telefone_limpo) && strlen($telefone_limpo) < 10) {
    $errors[] = "O número de telefone parece estar incompleto.";
  }

  // Data de nascimento
  if ($data_nascimento === '') {
    $errors[] = 'Data de nascimento é obrigatória.';
  } elseif (!DateTime::createFromFormat('Y-m-d', $data_nascimento)) {
    $errors[] = 'Data de nascimento inválida.';
  }

  if (!empty($data_nascimento)) {
    try {
      $data_nasc_obj = new DateTime($data_nascimento);
      $data_hoje = new DateTime();
      $data_14_anos = (clone $data_nasc_obj)->modify('+14 years');

      if ($data_14_anos > $data_hoje) {
        $errors[] = "O usuário deve ter no mínimo 14 anos de idade para se inscrever.";
      }
    } catch (Exception $e) {
      $errors[] = "Formato de data de nascimento inválido.";
    }
  }

  // Tipo de usuário
  if (!in_array($tipo, ['secretaria', 'aluno', 'professor', 'coordenador'], true)) {
    $errors[] = 'Perfil inválido.';
  }

  // --- Validações específicas para aluno ---
  if ($tipo === 'aluno' && $tem_turmas) {
    if (!$id_turma) $errors[] = 'Selecione a turma do aluno.';
    if (!$data_ingresso) $errors[] = 'Data de ingresso é obrigatória.';
    elseif (!DateTime::createFromFormat('Y-m-d', $data_ingresso)) $errors[] = 'Data de ingresso inválida.';
  }

  // Validações endereço
  if ($cep === '') $errors[] = 'Cep é obrigatório.';

  if ($logradouro === '') $errors[] = 'Logradouro é obrigatório.';

  if ($numero === '') $errors[] = 'Numero é obrigatório.';

  if ($bairro === '') $errors[] = 'Bairro é obrigatório.';

  if ($cidade === '') $errors[] = 'Cidade é obrigatório.';

  if ($estado === '') $errors[] = 'Estado é obrigatório.';

  if (!$errors) {
    try {
      if ($password) {
        if (strlen($password) < 8) $errors[] = 'Senha deve ter pelo menos 8 caracteres.';
      }

      if (!empty($password)) {
        if (strlen($password) < 8) {
          $errors[] = "A senha deve ter no mínimo 8 caracteres.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
          $errors[] = "A senha deve conter pelo menos uma letra maiúscula.";
        }
        if (!preg_match('/[a-z]/', $password)) {
          $errors[] = "A senha deve conter pelo menos uma letra minúscula.";
        }
        if (!preg_match('/[0-9]/', $password)) {
          $errors[] = "A senha deve conter pelo menos um número.";
        }
        // Opcional: Forçar um caractere especial (ex: @, #, $, %)
        if (!preg_match('/[\W]/', $password)) {
          $errors[] = "A senha deve conter pelo menos um caractere especial.";
        }
        if ($password !== $password_confirm) {
          $errors[] = "A senha e a confirmação de senha não coincidem.";
        }
      }

      if (!$errors) {
        // verificar duplicidade de e-mail em outro ID
        $chk = $pdo->prepare('SELECT id_usuario FROM usuario WHERE (email=? OR cpf=?) AND id_usuario<>?');
        $chk->execute([$email, $cpf, $id_usuario]);

        if ($chk->fetch()) {
          $errors[] = 'Já existe um usuário com este e-mail ou cpf.';
        } else {
          if ($password) {
            $stmt = $pdo->prepare('UPDATE usuario SET nome_completo=?, cpf=?, email=?, telefone=?, tipo=?, password_hash=?, data_nascimento=?, status=? WHERE id_usuario=?');
            $stmt->execute([$nome_completo, $cpf, $email, $telefone, $tipo, password_hash($password, PASSWORD_DEFAULT), $data_nascimento, $status, $id_usuario]);
          } else {
            $stmt = $pdo->prepare('UPDATE usuario SET nome_completo=?, cpf=?, email=?, telefone=?, tipo=?, data_nascimento=?, status=? WHERE id_usuario=?');
            $stmt->execute([$nome_completo, $cpf, $email, $telefone, $tipo, $data_nascimento, $status, $id_usuario]);
          }

          $stmt = $pdo->prepare('UPDATE endereco SET logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, estado=?, cep=? WHERE id_usuario=?');
          $stmt->execute([$logradouro, $numero, $complemento, $bairro, $cidade, $estado, $cep, $id_usuario]);
          if ($tipo === 'aluno') {
            $stmt = $pdo->prepare('UPDATE aluno SET data_ingresso=?, status_academico=?, id_turma=? WHERE id_usuario=?');
            $stmt->execute([$data_ingresso, $status_academico, $id_turma, $id_usuario]);
          }

          flash_set('success', 'Usuário atualizado com sucesso.');
          header('Location: admin.php');
          exit;
        }
      }
    } catch (PDOException $e) {
      $errors[] = 'Erro ao salvar: ' . $e->getMessage();
    }
  }
}

include __DIR__ . '/partials/admin_header.php';
?>
<div class="main">
  <div class="content mt-5">
    <div class="container-fluid mt-4">
      <div class="row justify-content-center">
        <div class="col-lg-9"> <!-- Um pouco mais largo para o formulário -->

          <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0">Editar Usuário #<?php echo (int)$user['id_usuario']; ?></h2>
            <a class="btn btn-outline-secondary btn-sm" href="admin.php">Voltar</a>
          </div>

          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
            </div>
          <?php endif; ?>

          <!-- O formulário agora envolve todo o card e as abas -->
          <form method="post" autocomplete="off">
            <?php csrf_input(); ?>
            <div class="card shadow-sm">
              <!-- Abas de Navegação -->
              <div class="card-header">
                <ul class="nav nav-pills card-header-pills" id="edit-user-tabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pessoal-tab" data-bs-toggle="tab" data-bs-target="#pessoal-pane" type="button" role="tab">1.Dados Pessoais</button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="endereco-tab" data-bs-toggle="tab" data-bs-target="#endereco-pane" type="button" role="tab">2.Endereço</button>
                  </li>
                  <!-- A aba "Dados Acadêmicos" só aparece se o usuário for um aluno -->
                  <?php if ($tipo === 'aluno'): ?>
                    <li class="nav-item" role="presentation">
                      <button class="nav-link" id="academico-tab" data-bs-toggle="tab" data-bs-target="#academico-pane" type="button" role="tab">3.Dados Acadêmicos</button>
                    </li>
                  <?php endif; ?>
                </ul>
              </div>

              <!-- Conteúdo das Abas -->
              <div class="tab-content" id="edit-user-tabsContent">

                <!-- Aba 1: Dados Pessoais -->
                <div class="tab-pane fade show active" id="pessoal-pane" role="tabpanel">
                  <div class="card-body p-4">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label" for="nome_completo">Nome completo:</label>
                        <input type="text" id="nome_completo" name="nome_completo" maxlength="150" placeholder="Nome completo" class="form-control" value="<?php echo htmlspecialchars($nome_completo); ?>" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="email">E-mail:</label>
                        <input type="email" id="email" name="email" maxlength="150" placeholder="exemplo@exemplo.com" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="cpf">Cpf:</label>
                        <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="XXX.XXX.XXX-XX" class="form-control" value="<?php echo htmlspecialchars($cpf); ?>" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="telefone">Telefone:</label>
                        <input type="tel" id="telefone" name="telefone" maxlength="20" placeholder="(XX) XXXXX-XXXX" class="form-control" value="<?php echo htmlspecialchars($telefone); ?>" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="data_nascimento">Data de nascimento:</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?php echo htmlspecialchars($data_nascimento); ?>" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="status">Status:</label>
                        <select name="status" id="status" class="form-select">
                          <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                          <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="perfil">Perfil:</label>
                        <select name="tipo" id="perfil" class="form-select" disabled>
                          <option value="aluno" <?php echo $tipo === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                          <option value="secretaria" <?php echo $tipo === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
                          <option value="professor" <?php echo $tipo === 'professor' ? 'selected' : ''; ?>>Professor</option>
                          <option value="coordenador" <?php echo $tipo === 'coordenador' ? 'selected' : ''; ?>>Coordenador</option>
                        </select>
                        <div class="form-text">O perfil não pode ser alterado após a criação.</div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="password">Senha:</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Deixe em branco para manter a senha">

                        <div class="mt-2">
                          <div class="progress" style="height: 5px;">
                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                          </div>

                          <span id="password-strength-text" class="form-text small">
                            Mínimo 8 caracteres, 1 maiúscula (A-Z), 1 minúscula (a-z), 1 número (0-9) e 1 símbolo (@, #, $...).
                          </span>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label" for="password_confirm">Confirmar Senha:</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Repita a senha">
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Aba 2: Endereço -->
                <div class="tab-pane fade" id="endereco-pane" role="tabpanel">
                  <div class="card-body p-4">
                    <div class="row g-3">
                      <div class="col-md-3">
                        <label class="form-label" for="cep">Cep:</label>
                        <div class="input-group">
                          <input type="text" name="cep" id="cep" placeholder="00000-000" maxlength="9" class="form-control" value="<?php echo htmlspecialchars($cep); ?>" required>
                          <span class="input-group-text" id="spinner" style="display: none;">...</span>
                        </div>
                        <div id="cep-error" class="text-danger small mt-1"></div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="logradouro">Logradouro:</label>
                        <input type="text" name="logradouro" id="logradouro" class="form-control" value="<?php echo htmlspecialchars($logradouro); ?>" required>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label" for="numero">Número:</label>
                        <input type="text" name="numero" id="numero" class="form-control" value="<?php echo htmlspecialchars($numero); ?>" required>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label" for="complemento">Complemento:</label>
                        <input type="text" name="complemento" id="complemento" class="form-control" value="<?php echo htmlspecialchars($complemento); ?>">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="bairro">Bairro:</label>
                        <input type="text" name="bairro" id="bairro" class="form-control" value="<?php echo htmlspecialchars($bairro); ?>" required>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label" for="cidade">Cidade:</label>
                        <input type="text" name="cidade" id="cidade" class="form-control" value="<?php echo htmlspecialchars($cidade); ?>" required>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label" for="estado">Estado:</label>
                        <select name="estado" id="estado" class="form-select" required>
                          <option value="">Selecione</option>
                          <option value="AC" <?php echo ($estado === 'AC') ? 'selected' : ''; ?>>Acre (AC)</option>
                          <option value="AL" <?php echo ($estado === 'AL') ? 'selected' : ''; ?>>Alagoas (AL)</option>
                          <option value="AP" <?php echo ($estado === 'AP') ? 'selected' : ''; ?>>Amapá (AP)</option>
                          <option value="AM" <?php echo ($estado === 'AM') ? 'selected' : ''; ?>>Amazonas (AM)</option>
                          <option value="BA" <?php echo ($estado === 'BA') ? 'selected' : ''; ?>>Bahia (BA)</option>
                          <option value="CE" <?php echo ($estado === 'CE') ? 'selected' : ''; ?>>Ceará (CE)</option>
                          <option value="DF" <?php echo ($estado === 'DF') ? 'selected' : ''; ?>>Distrito Federal (DF)</option>
                          <option value="ES" <?php echo ($estado === 'ES') ? 'selected' : ''; ?>>Espírito Santo (ES)</option>
                          <option value="GO" <?php echo ($estado === 'GO') ? 'selected' : ''; ?>>Goiás (GO)</option>
                          <option value="MA" <?php echo ($estado === 'MA') ? 'selected' : ''; ?>>Maranhão (MA)</option>
                          <option value="MT" <?php echo ($estado === 'MT') ? 'selected' : ''; ?>>Mato Grosso (MT)</option>
                          <option value="MS" <?php echo ($estado === 'MS') ? 'selected' : ''; ?>>Mato Grosso do Sul (MS)</option>
                          <option value="MG" <?php echo ($estado === 'MG') ? 'selected' : ''; ?>>Minas Gerais (MG)</option>
                          <option value="PA" <?php echo ($estado === 'PA') ? 'selected' : ''; ?>>Pará (PA)</option>
                          <option value="PB" <?php echo ($estado === 'PB') ? 'selected' : ''; ?>>Paraíba (PB)</option>
                          <option value="PR" <?php echo ($estado === 'PR') ? 'selected' : ''; ?>>Paraná (PR)</option>
                          <option value="PE" <?php echo ($estado === 'PE') ? 'selected' : ''; ?>>Pernambuco (PE)</option>
                          <option value="PI" <?php echo ($estado === 'PI') ? 'selected' : ''; ?>>Piauí (PI)</option>
                          <option value="RJ" <?php echo ($estado === 'RJ') ? 'selected' : ''; ?>>Rio de Janeiro (RJ)</option>
                          <option value="RN" <?php echo ($estado === 'RN') ? 'selected' : ''; ?>>Rio Grande do Norte (RN)</option>
                          <option value="RS" <?php echo ($estado === 'RS') ? 'selected' : ''; ?>>Rio Grande do Sul (RS)</option>
                          <option value="RO" <?php echo ($estado === 'RO') ? 'selected' : ''; ?>>Rondônia (RO)</option>
                          <option value="RR" <?php echo ($estado === 'RR') ? 'selected' : ''; ?>>Roraima (RR)</option>
                          <option value="SC" <?php echo ($estado === 'SC') ? 'selected' : ''; ?>>Santa Catarina (SC)</option>
                          <option value="SP" <?php echo ($estado === 'SP') ? 'selected' : ''; ?>>São Paulo (SP)</option>
                          <option value="SE" <?php echo ($estado === 'SE') ? 'selected' : ''; ?>>Sergipe (SE)</option>
                          <option value="TO" <?php echo ($estado === 'TO') ? 'selected' : ''; ?>>Tocantins (TO)</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Aba 3: Dados Acadêmicos (Condicional) -->
                <?php if ($tipo === 'aluno'): ?>
                  <div class="tab-pane fade" id="academico-pane" role="tabpanel">
                    <div class="card-body p-4">
                      <?php if ($tem_turmas): ?>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label" for="turma">Turma:</label>
                            <select id="turma" name="id_turma" class="form-select">
                              <option value="">Selecione</option>
                              <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id_turma']; ?>" <?php echo ($id_turma == $turma['id_turma']) ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($turma['nome'] . " - " . $turma['ano'] . "/" . $turma['semestre']); ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label" for="data_ingresso">Data de ingresso:</label>
                            <input type="date" id="data_ingresso" name="data_ingresso" class="form-control" value="<?php echo htmlspecialchars($data_ingresso); ?>" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label" for="status_academico">Status acadêmico:</label>
                            <select name="status_academico" id="status_academico" class="form-select">
                              <option value="cursando" <?php echo $status_academico === 'cursando' ? 'selected' : ''; ?>>Cursando</option>
                              <option value="formado" <?php echo $status_academico === 'formado' ? 'selected' : ''; ?>>Formado</option>
                              <option value="trancado" <?php echo $status_academico === 'trancado' ? 'selected' : ''; ?>>Trancado</option>
                              <option value="desistente" <?php echo $status_academico === 'desistente' ? 'selected' : ''; ?>>Desistente</option>
                            </select>
                          </div>
                        </div>
                      <?php else: ?>
                        <p class="text-muted">Nenhuma turma cadastrada para associar.</p>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <div class="card-footer text-end">
                <a href="admin.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
              </div>
            </div>
          </form>

          <div class="card border-danger shadow-sm mt-4">
            <div class="card-header bg-danger">
              <h5 class="mb-0">Zona de Perigo</h5>
            </div>
            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
              <div>
                <strong>Excluir este usuário</strong>
                <p class="mb-sm-0 text-muted small">Uma vez excluído, o usuário não poderá ser recuperado. Esta ação é permanente.</p>
              </div>

              <button type="button" class="btn btn-danger w-100 w-sm-auto mt-2 mt-sm-0 delete-btn"
                data-bs-toggle="modal"
                data-bs-target="#confirmDeleteModal"
                data-form-action="users_delete.php"
                data-item-id="<?php echo (int)$user['id_usuario']; ?>"
                data-item-name="<?php echo htmlspecialchars($user['nome_completo']); ?>"
                data-id-field="id_usuario">
                <i class="bi bi-trash-fill"></i> Excluir Usuário
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Máscara para cpf
  const cpfInput = document.getElementById('cpf');
  cpfInput.addEventListener('input', function(e) {
    let v = this.value.replace(/\D/g, '');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    this.value = v;
  });

  // Máscara para telefone
  const telInput = document.querySelector('input[name="telefone"]');
  telInput.addEventListener('input', function(e) {
    let v = this.value.replace(/\D/g, ''); // remove tudo que não é número
    if (v.length > 10) { // celular 11 dígitos
      v = v.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    } else if (v.length > 5) { // telefone fixo 10 dígitos
      v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
    } else if (v.length > 2) {
      v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
    } else if (v.length > 0) {
      v = v.replace(/^(\d*)/, '($1');
    }
    this.value = v;
  });

  // Máscara para cep
  const cepInput = document.getElementById("cep");
  cepInput.addEventListener("input", function() {
    let v = this.value.replace(/\D/g, ""); // remove tudo que não é número
    if (v.length > 5) {
      v = v.replace(/^(\d{5})(\d)/, "$1-$2");
    }
    this.value = v;
  });

  // Consulta API ViaCEP
  const eNumero = (numero) => /^[0-9]+$/.test(numero);

  const cepValido = (cep) => cep.length == 8 && eNumero(cep);

  const limparFormulario = () => {
    document.getElementById("cidade").value = "";
    document.getElementById("logradouro").value = "";
    document.getElementById("bairro").value = "";
    document.getElementById("estado").value = "";
  };

  const preencherFormulario = (endereco) => {
    document.getElementById("cidade").value = endereco.localidade;
    document.getElementById("logradouro").value = endereco.logradouro;
    document.getElementById("bairro").value = endereco.bairro;
    document.getElementById("estado").value = endereco.uf;
  };

  const pesquisarCep = async () => {

    document.getElementById("spinner").style.display = 'inline-block';

    limparFormulario();
    document.getElementById("cep-error").textContent = ""; //Limpar mensagem anterior

    const cep = document.getElementById("cep").value.replace("-", "");

    // Se o campo estiver vazio
    if (!cep) {
      document.getElementById("cep-error").textContent = "Cep é obrigatório!";
      document.getElementById("spinner").style.display = 'none';
      return;
    }

    const url = `https://viacep.com.br/ws/${cep}/json/`;
    if (cepValido(cep)) {
      const dados = await fetch(url);
      const endereco = await dados.json();
      if (endereco.hasOwnProperty("erro")) {
        document.getElementById("cep-error").textContent = "CEP não encontrado!";
      } else {
        preencherFormulario(endereco);
      }
    } else {
      document.getElementById("cep-error").textContent = "CEP incorreto!";
    }
    document.getElementById("spinner").style.display = 'none';

  };
  document.getElementById("cep").addEventListener("focusout", pesquisarCep);

  document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');

    if (passwordInput && strengthBar && strengthText) {

      const initialHelpText = strengthText.innerHTML;
      passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const score = checkPasswordStrength(password);

        let width = '0%';
        let colorClass = '';
        let text = '';

        if (password.length === 0) {
          // Se a senha estiver vazia, reseta tudo e mostra as regras
          text = initialHelpText; // Volta o texto para a lista de regras
          width = '0%';
          strengthBar.style.width = width;
          strengthBar.className = 'progress-bar'; // Limpa as cores
          strengthText.innerHTML = text; // Usa innerHTML para manter a formatação
          strengthText.className = 'form-text small'; // Reseta a cor do texto
          return; // Para a execução aqui
        }

        switch (score) {
          case 0:
          case 1:
            width = '20%';
            colorClass = 'bg-danger';
            text = 'Fraca';
            break;
          case 2:
            width = '40%';
            colorClass = 'bg-warning';
            text = 'Média';
            break;
          case 3:
            width = '60%';
            colorClass = 'bg-warning';
            text = 'Razoável';
            break;
          case 4:
            width = '80%';
            colorClass = 'bg-success';
            text = 'Forte';
            break;
          case 5:
            width = '100%';
            colorClass = 'bg-success';
            text = 'Muito Forte';
            break;
        }

        // Atualiza a barra de progresso
        strengthBar.style.width = width;
        strengthBar.className = 'progress-bar';
        strengthBar.classList.add(colorClass);

        // Atualiza o texto
        strengthText.textContent = text;
        strengthText.className = (score <= 2) ? 'form-text small text-danger' : 'form-text small text-muted';
      });
    }

    // Função auxiliar que calcula a "pontuação" da senha
    function checkPasswordStrength(password) {
      let score = 0;
      // Critério 1: Mínimo de 8 caracteres
      if (password.length >= 8) score++;
      // Critério 2: Contém pelo menos uma letra maiúscula
      if (/[A-Z]/.test(password)) score++;
      // Critério 3: Contém pelo menos uma letra minúscula
      if (/[a-z]/.test(password)) score++;
      // Critério 4: Contém pelo menos um número
      if (/[0-9]/.test(password)) score++;
      // (Opcional: se quiser 5 critérios, adicione a verificação de caractere especial)
      if (/[\W_]/.test(password)) score++;

      return score;
    }
  });
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>