<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();
//Variavel de array vazio para receber futuros erros
$errors = [];
$nome_completo = $email = $tipo = $cpf = $telefone = $data_nascimento = $cep = $logradouro = $numero = $complemento = $bairro = $cidade = $estado = '';

// Busca todas as turmas
$stmt = $pdo->query("SELECT id_turma, nome, ano, semestre, turno FROM turma ORDER BY ano DESC, semestre DESC, nome ASC");
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tem_turmas = count($turmas) > 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check(); // Proteção CSRF

  // Captura dados do formulário
  // Dados usuário
  $nome_completo   = trim($_POST['nome_completo'] ?? '');
  $cpf             = trim($_POST['cpf'] ?? '');
  $email           = trim($_POST['email'] ?? '');
  $password        = $_POST['password'] ?? '';
  $telefone        = trim($_POST['telefone'] ?? '');
  $data_nascimento = trim($_POST['data_nascimento'] ?? '');
  $tipo            = $_POST['tipo'] ?? 'aluno';
  // Dados aluno
  $id_turma      = $_POST['id_turma'] ?? null;
  $data_ingresso = $_POST['data_ingresso'] ?? null;
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

  // E-mail
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';

  // Senha
  if (strlen($password) < 8) $errors[] = 'Senha deve ter pelo menos 8 caracteres.';

  // Telefone
  if ($telefone === '') $errors[] = 'Telefone é obrigatório.';

  // Data de nascimento
  if ($data_nascimento === '') {
    $errors[] = 'Data de nascimento é obrigatória.';
  } elseif (!DateTime::createFromFormat('Y-m-d', $data_nascimento)) {
    $errors[] = 'Data de nascimento inválida.';
  }

  // Tipo de usuário
  if (!in_array($tipo, ['secretaria', 'aluno', 'professor', 'coordenador'], true)) $errors[] = 'Perfil inválido.';

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

  // --- Checagem de unicidade no banco ---
  if (empty($errors)) {
    // CPF
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetchColumn() > 0) $errors[] = 'CPF já cadastrado.';

    // E-mail
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) $errors[] = 'E-mail já cadastrado.';
  }

  if (!$errors) {
    try {
      // --- Inserção no banco ---
      $password_hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("
        INSERT INTO usuario 
        (nome_completo, cpf, email, password_hash, telefone, data_nascimento, tipo) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
      $stmt->execute([$nome_completo, $cpf, $email, $password_hash, $telefone, $data_nascimento, $tipo]);

      $id_usuario = $pdo->lastInsertId();

      // --- Inserção na tabela endereco ---
      $stmt = $pdo->prepare("
    INSERT INTO endereco 
    (id_usuario, logradouro, numero, complemento, bairro, cidade, estado, cep)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
      $stmt->execute([$id_usuario, $logradouro, $numero, $complemento ?: null, $bairro, $cidade, $estado, $cep]);

      // --- Inserção na tabela aluno, se for aluno ---
      if ($tipo === 'aluno' && $tem_turmas) {
        $stmt = $pdo->prepare("
          INSERT INTO aluno (id_usuario, data_ingresso, status_academico, id_turma)
          VALUES (?, ?, 'cursando', ?)
      ");
        $stmt->execute([$id_usuario, $data_ingresso, $id_turma]);
      }

      $_SESSION['success'] = 'Usuário cadastrado com sucesso!';
      header('Location: admin.php');
      exit;
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') { // Se já existe um e-mail cadastrado
        $errors[] = 'Já existe um usuário com este e-mail.';
      } else {
        $errors[] = 'Erro ao salvar: ' . $e->getMessage();
      }
    }
  }
}

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Novo Usuário</h2>
  <a class="btn btn-outline-secondary btn-sm" href="admin.php">Voltar</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (!$tem_turmas): ?>
  <div class="alert alert-warning">
    Não há turmas cadastradas.
    <a href="turma\turmas_create.php" class="alert-link">Cadastre uma turma</a> antes de adicionar alunos.
  </div>
<?php endif; ?>

<!-- Formulario  -->

<form method="post" class="card shadow-sm p-3">
  <?php csrf_input(); ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Nome completo: </label>
      <input type="text" name="nome_completo" maxlength="150" class="form-control" placeholder="Digite o nome" value="<?php echo htmlspecialchars($nome_completo); ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Cpf: </label>
      <input type="text" id="cpf" name="cpf" maxlength="14" class="form-control" placeholder="XXX.XXX.XXX-XX" value="<?php echo htmlspecialchars($cpf); ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">E-mail: </label>
      <input type="email" name="email" maxlength="150" class="form-control" placeholder="exemplo@exemplo.com" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Telefone: </label>
      <input type="tel" name="telefone" maxlength="20" class="form-control" placeholder="(XX) XXXXX-XXXX" value="<?php echo htmlspecialchars($telefone); ?>" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Data de nascimento: </label>
      <input type="date" name="data_nascimento" class="form-control" value="<?php echo htmlspecialchars($data_nascimento); ?>" required>
    </div>

    <!-- CAMPOS ENDEREÇO  -->

    <div class="col-md-6">
      <label class="form-label">Cep: </label>
      <div class="input-group">
        <input type="text" name="cep" id="cep" class="form-control" placeholder="00000-000" maxlength="9" value="<?php echo htmlspecialchars($cep); ?>" required>

         <span class="input-group-text" id="spinner" style="display: none;">
            <div class="spinner-grow spinner-grow-sm text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </span>
      </div>
    </div>

    <div id="cep-error" class="text-danger small mt-1"></div>

    <div class="col-md-6">
      <label class="form-label">Logradouro: </label>
      <input type="text" name="logradouro" id="logradouro" class="form-control" value="<?php echo htmlspecialchars($logradouro); ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Número: </label>
      <input type="text" name="numero" id="numero" class="form-control" value="<?php echo htmlspecialchars($numero); ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Complemento: </label>
      <input type="text" name="complemento" id="complemento" class="form-control" value="<?php echo htmlspecialchars($complemento); ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Bairro: </label>
      <input type="text" name="bairro" id="bairro" class="form-control" value="<?php echo htmlspecialchars($bairro); ?>" required>
    </div>

    <div class="col-md-6">
      <label class="form-label">Cidade: </label>
      <input type="text" name="cidade" id="cidade" class="form-control" value="<?php echo htmlspecialchars($cidade); ?>" required>
    </div>

    <!-- SELECT ESTADOS -->
    <div class="col-md-6">
      <label class="form-label">Estados: </label>
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

    <!-- SELECT PERFIL -->
    <div class="col-md-3">
      <label class="form-label">Perfil: </label>
      <select name="tipo" id="perfil" class="form-select" onchange="mostrarCampos()">
        <option value="aluno" <?php echo $tipo === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
        <option value="secretaria" <?php echo $tipo === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
        <option value="professor" <?php echo $tipo === 'professor' ? 'selected' : ''; ?>>Professor</option>
        <option value="coordenador" <?php echo $tipo === 'coordenador' ? 'selected' : ''; ?>>Coordenador</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Senha: </label>
      <input type="password" name="password" class="form-control" placeholder="Conter no mínimo 8 dígitos" required>
    </div>
  </div>

  <!-- Campo aluno específico -->
  <div id="campos-aluno" style="<?php echo $tem_turmas ? 'display:block;' : 'display:none;'; ?>" class="row g-3 mt-3">
    <?php if ($tem_turmas): ?>
      <div class="col-md-6">
        <label class="form-label">Turma:</label>
        <select name="id_turma" class="form-select">
          <option value="">Selecione</option>
          <?php foreach ($turmas as $turma): ?>
            <option value="<?php echo $turma['id_turma']; ?>" <?php echo (isset($_POST['id_turma']) && $_POST['id_turma'] == $turma['id_turma']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($turma['nome'] . " - " . $turma['ano'] .  " - " . $turma['semestre'] . "º Semestre - " . $turma['turno']); ?>
            </option>

          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Data de ingresso:</label>
        <input type="date" name="data_ingresso" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
      </div>
    <?php endif; ?>
  </div>

  <div class="mt-3 text-end">
    <input type="reset" class="btn btn-danger" value="Limpar">
    <button class="btn btn-primary">Salvar</button>
  </div>
</form>

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

  // Mostrar campos aluno
  function mostrarCampos() {
    document.getElementById("campos-aluno").style.display = "none";

    let tipo = document.getElementById("perfil").value;
    if (tipo === "aluno") {
      document.getElementById("campos-aluno").style.display = "block";
    }
  }
  window.addEventListener("DOMContentLoaded", mostrarCampos);
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>