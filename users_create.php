<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();

$errors = [];
// Inicializa variáveis vazias para o formulário
$nome_completo = $cpf = $email = $telefone = $data_nascimento = $password = '';
$tipo = 'aluno'; // Define 'aluno' como padrão
$status = 'ativo';
$cep = $logradouro = $numero = $complemento = $bairro = $cidade = $estado = '';
$id_turma = $data_ingresso = $status_academico = '';

// --- LÓGICA DE CRIAÇÃO (BACK-END) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  // Captura dados do usuário
  $nome_completo = trim($_POST['nome_completo'] ?? '');
  $cpf = trim($_POST['cpf'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefone = trim($_POST['telefone'] ?? '');
  $data_nascimento = trim($_POST['data_nascimento'] ?? '');
  $password = $_POST['password'] ?? '';
  $tipo = trim($_POST['tipo'] ?? '');
  $status = trim($_POST['status'] ?? '');

  // Captura dados do endereço
  $cep = trim($_POST['cep'] ?? '');
  $logradouro = trim($_POST['logradouro'] ?? '');
  $numero = trim($_POST['numero'] ?? '');
  $complemento = trim($_POST['complemento'] ?? '');
  $bairro = trim($_POST['bairro'] ?? '');
  $cidade = trim($_POST['cidade'] ?? '');
  $estado = trim($_POST['estado'] ?? '');

  // Captura dados do aluno (se for o caso)
  $id_turma = (int)($_POST['id_turma'] ?? 0);
  $data_ingresso = trim($_POST['data_ingresso'] ?? '');
  $status_academico = trim($_POST['status_academico'] ?? 'cursando');

  // --- Validações ---
  if (empty($nome_completo)) $errors[] = "Nome é obrigatório.";
  if (empty($cpf)) $errors[] = "CPF é obrigatório.";
  if (empty($email)) $errors[] = "E-mail é obrigatório.";
  if (empty($password)) $errors[] = "Senha é obrigatória.";
  if (strlen($password) < 8) $errors[] = "A senha deve ter no mínimo 8 caracteres.";

  // Validação de unicidade
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE email = ? OR cpf = ?");
  $stmt->execute([$email, $cpf]);
  if ($stmt->fetchColumn() > 0) {
    $errors[] = "E-mail ou CPF já cadastrado no sistema.";
  }

  // Validação de dados de aluno (se o tipo for aluno)
  if ($tipo === 'aluno') {
    if ($id_turma === 0) $errors[] = "A turma é obrigatória para o aluno.";
    if (empty($data_ingresso)) $errors[] = "A data de ingresso é obrigatória para o aluno.";
  }

  if (empty($errors)) {
    // Usa uma transação para garantir que tudo seja salvo
    $pdo->beginTransaction();
    try {
      // 1. Criptografa a senha
      $password_hash = password_hash($password, PASSWORD_DEFAULT);

      // 2. Insere na tabela 'usuario'
      $stmt_user = $pdo->prepare(
        "INSERT INTO usuario (nome_completo, cpf, email, password_hash, telefone, data_nascimento, tipo, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
      );
      $stmt_user->execute([$nome_completo, $cpf, $email, $password_hash, $telefone, $data_nascimento, $tipo, $status]);
      $id_novo_usuario = $pdo->lastInsertId();

      // 3. Insere na tabela 'endereco'
      $stmt_addr = $pdo->prepare(
        "INSERT INTO endereco (id_usuario, logradouro, numero, complemento, bairro, cidade, estado, cep) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
      );
      $stmt_addr->execute([$id_novo_usuario, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $cep]);

      // 4. Insere na tabela 'aluno' (se for aluno)
      if ($tipo === 'aluno') {
        $stmt_aluno = $pdo->prepare(
          "INSERT INTO aluno (id_usuario, id_turma, data_ingresso, status_academico) 
                     VALUES (?, ?, ?, ?)"
        );
        $stmt_aluno->execute([$id_novo_usuario, $id_turma, $data_ingresso, $status_academico]);
      }

      $pdo->commit();
      flash_set('success', 'Usuário cadastrado com sucesso!');
      header('Location: admin.php');
      exit;
    } catch (Exception $e) {
      $pdo->rollBack();
      $errors[] = "Erro ao salvar os dados: " . $e->getMessage();
    }
  }
}

// Busca dados para os dropdowns (ex: turmas)
$turmas = $pdo->query("SELECT t.id_turma, t.nome, t.ano, t.semestre, c.nome as nome_curso 
                      FROM turma t 
                      JOIN curso c ON t.id_curso = c.id_curso 
                      ORDER BY c.nome, t.ano DESC, t.nome ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/partials/admin_header.php';
?>

<div class="main">
  <div class="content">
    <div class="container-fluid mt-4">
      <div class="row justify-content-center">
        <div class="col-lg-9">

          <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0">Criar Novo Usuário</h2>
            <a class="btn btn-outline-secondary btn-sm" href="admin.php">Voltar</a>
          </div>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
            </div>
          <?php endif; ?>

          <form method="post" novalidate>
            <?php csrf_input(); ?>
            <div class="card shadow-sm">
              <div class="card-header">
                <ul class="nav nav-pills card-header-pills" id="create-user-tabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pessoal-tab" data-bs-toggle="tab" data-bs-target="#pessoal-pane" type="button" role="tab">1. Dados Pessoais</button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="endereco-tab" data-bs-toggle="tab" data-bs-target="#endereco-pane" type="button" role="tab">2. Endereço</button>
                  </li>
                  <li class="nav-item" role="presentation" id="academico-tab-nav" style="display: <?php echo $tipo === 'aluno' ? 'list-item' : 'none'; ?>;">
                    <button class="nav-link" id="academico-tab" data-bs-toggle="tab" data-bs-target="#academico-pane" type="button" role="tab">3. Dados Acadêmicos</button>
                  </li>
                </ul>
              </div>

              <div class="tab-content" id="create-user-tabsContent">

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
                        <label class="form-label" for="cpf">CPF:</label>
                        <input type="text" id="cpf" name="cpf" class="form-control" maxlength="14" placeholder="XXX.XXX.XXX-XX" value="<?php echo htmlspecialchars($cpf); ?>" required>
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
                        <label class="form-label" for="perfil-select">Perfil:</label>
                        <select name="tipo" id="perfil-select" class="form-select" required>
                          <option value="aluno" <?php echo $tipo === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                          <option value="secretaria" <?php echo $tipo === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
                          <option value="professor" <?php echo $tipo === 'professor' ? 'selected' : ''; ?>>Professor</option>
                          <option value="coordenador" <?php echo $tipo === 'coordenador' ? 'selected' : ''; ?>>Coordenador</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label" for="status">Status:</label>
                        <select name="status" id="status" class="form-select">
                          <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                          <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                      </div>
                      <div class="col-12">
                        <label class="form-label" for="password">Senha:</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                      </div>
                    </div>
                  </div>
                </div>

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

                <div class="tab-pane fade" id="academico-pane" role="tabpanel">
                  <div class="card-body p-4">
                    <?php if (!empty($turmas)): ?>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label" for="turma">Turma:</label>
                          <select id="turma" name="id_turma" class="form-select">
                            <option value="">Selecione a turma...</option>
                            <?php foreach ($turmas as $turma): ?>
                              <option value="<?php echo (int)$turma['id_turma']; ?>" <?php echo ($id_turma == $turma['id_turma']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($turma['nome_curso'] . ' / ' . $turma['nome']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="data_ingresso">Data de ingresso:</label>
                          <input type="date" id="data_ingresso" name="data_ingresso" class="form-control" value="<?php echo htmlspecialchars($data_ingresso); ?>">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="status_academico">Status acadêmico:</label>
                          <select name="status_academico" id="status_academico" class="form-select">
                            <option value="cursando" selected>Cursando</option>
                            <option value="formado">Formado</option>
                            <option value="trancado">Trancado</option>
                            <option value="desistente">Desistente</option>
                          </select>
                        </div>
                      </div>
                    <?php else: ?>
                      <p class="text-muted text-center">Nenhuma turma foi cadastrada ainda. Crie uma turma antes de cadastrar um aluno.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="card-footer text-end">
                <a href="admin.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
              </div>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
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

    // --- LÓGICA PARA MOSTRAR/ESCONDER A ABA DE ALUNO ---
    const perfilSelect = document.getElementById('perfil-select');
    const academicoTabNav = document.getElementById('academico-tab-nav');

    // Verifica se os elementos da aba existem
    if (perfilSelect && academicoTabNav) {
      const academicoPane = document.getElementById('academico-pane');
      const academicoFields = academicoPane.querySelectorAll('input, select');
      const pessoalTab = new bootstrap.Tab(document.getElementById('pessoal-tab'));

      function toggleAlunoFields() {
        if (perfilSelect.value === 'aluno') {
          academicoTabNav.style.display = 'list-item';
          academicoFields.forEach(field => field.required = true);
        } else {
          academicoTabNav.style.display = 'none';
          if (academicoTabNav.querySelector('button').classList.contains('active')) {
            pessoalTab.show();
          }
          academicoFields.forEach(field => field.required = false);
        }
      }

      perfilSelect.addEventListener('change', toggleAlunoFields);
      toggleAlunoFields(); // Executa ao carregar a página
    }

    // =================================================================
    // == NOVO: LÓGICA PARA VALIDAR ABAS OCULTAS AO SALVAR          ==
    // =================================================================
    const form = document.querySelector('form[method="post"]');
    const submitButton = form.querySelector('button[type="submit"]');
    const errorContainer = document.querySelector('.alert.alert-danger');
    const errorList = errorContainer ? errorContainer.querySelector('ul') : null;

    if (submitButton && errorContainer && errorList) {

      submitButton.addEventListener('click', function(event) {
        // 1. Limpa mensagens de erro de abas anteriores
        const oldTabError = errorList.querySelector('.tab-error-message');
        if (oldTabError) {
          oldTabError.remove();
        }

        // 2. Encontra todos os campos obrigatórios no formulário
        const requiredFields = form.querySelectorAll('[required]');
        let firstErrorTab = null;
        let firstErrorTabName = '';

        // 3. Verifica cada campo
        for (const field of requiredFields) {
          // checkValidity() é a verificação de formulário nativa do navegador
          if (!field.checkValidity()) {

            // Se o campo é inválido, encontra a aba (tab-pane) onde ele está
            const tabPane = field.closest('.tab-pane');

            // Se a aba do campo inválido NÃO for a aba ativa...
            if (tabPane && !tabPane.classList.contains('active')) {

              // Encontra o botão da aba que controla este painel
              const tabId = tabPane.id;
              firstErrorTab = document.querySelector(`[data-bs-target="#${tabId}"]`);
              if (firstErrorTab) {
                firstErrorTabName = firstErrorTab.textContent; // Pega o nome da aba (ex: "2. Endereço")
              }
              break; // Para no primeiro erro encontrado
            }
          }
        }

        // 4. Se um erro foi encontrado em uma aba oculta...
        if (firstErrorTab) {
          // Impede o envio do formulário
          event.preventDefault();

          // Cria e exibe a mensagem de erro no topo da página
          const errorMsg = `Existem campos obrigatórios na aba "${firstErrorTabName}". Por favor, revise.`;
          const errorLi = document.createElement('li');
          errorLi.className = 'tab-error-message'; // Para podermos limpar depois
          errorLi.innerHTML = htmlspecialchars(errorMsg); // Usa a função de segurança
          errorList.appendChild(errorLi);
          errorContainer.style.display = 'block';

          // Força a aba com o erro a ser exibida
          const tab = new bootstrap.Tab(firstErrorTab);
          tab.show();

          // Rola a tela para o topo para o usuário ver a mensagem de erro
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        }
      });
    }

    // Função de segurança (caso não esteja no escopo global)
    function htmlspecialchars(str) {
      if (typeof str !== 'string') return '';
      return str.replace(/[&<>"']/g, match => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      } [match]));
    }

  });
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>