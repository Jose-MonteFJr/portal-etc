<?php
/* Debug */
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();

// Busca todas as turmas
$stmt = $pdo->query("SELECT id_turma, nome, ano, semestre, turno FROM turma ORDER BY ano DESC, semestre DESC, nome ASC");
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$tem_turmas = count($turmas) > 0;

//Variavel de array vazio para receber futuros erros
$errors = [];
$nome_completo = $email = $tipo = $cpf = $telefone = $data_nascimento = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // Proteção CSRF

    // Captura dados do formulário
    $nome_completo   = trim($_POST['nome_completo'] ?? '');
    $cpf             = trim($_POST['cpf'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $telefone        = trim($_POST['telefone'] ?? '');
    $data_nascimento = trim($_POST['data_nascimento'] ?? '');
    $tipo            = $_POST['tipo'] ?? 'aluno';
    $id_turma      = $_POST['id_turma'] ?? null;
    $data_ingresso = $_POST['data_ingresso'] ?? null;

    // --- Validações ---
    if ($nome_completo === '') $errors[] = 'Nome completo é obrigatório.';

    // CPF: formato e dígito verificador
    if ($cpf === '') $errors[] = 'CPF é obrigatório.';

    // E-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-mail inválido.';
    }

    // Senha
    if (strlen($password) < 8) {
        $errors[] = 'Senha deve ter pelo menos 8 caracteres.';
    }

    // Telefone
    if ($telefone === '') $errors[] = 'Telefone é obrigatório.';

    // Data de nascimento
    if ($data_nascimento === '') {
        $errors[] = 'Data de nascimento é obrigatória.';
    } elseif (!DateTime::createFromFormat('Y-m-d', $data_nascimento)) {
        $errors[] = 'Data de nascimento inválida.';
    }

    // Tipo de usuário
    if (!in_array($tipo, ['secretaria','aluno', 'professor', 'coordenador'], true)) {
        $errors[] = 'Perfil inválido.';
    }
    // --- Validações específicas para aluno ---
    if ($tipo === 'aluno' && $tem_turmas) {
      if (!$id_turma) $errors[] = 'Selecione a turma do aluno.';
      if (!$data_ingresso) $errors[] = 'Data de ingresso é obrigatória.';
      elseif (!DateTime::createFromFormat('Y-m-d', $data_ingresso)) {
          $errors[] = 'Data de ingresso inválida.';
      }
  }

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

    // --- Se houver erros ---
    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_data'] = $_POST;
        header('Location: users_create.php');
        exit;
    }

    // --- Inserção no banco ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO usuario 
        (nome_completo, cpf, email, password_hash, telefone, data_nascimento, tipo) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nome_completo, $cpf, $email, $password_hash, $telefone, $data_nascimento, $tipo]);

    $id_usuario = $pdo->lastInsertId();

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
      <?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if (!$tem_turmas): ?>
  <div class="alert alert-warning">
    Não há turmas cadastradas. 
    <a href="turma\turmas_create.php" class="alert-link">Cadastre uma turma</a> antes de adicionar alunos.
  </div>
<?php endif; ?>

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
    <div class="col-md-6">
      <label class="form-label">Telefone: </label>
      <input type="tel" name="telefone" maxlength="20" class="form-control" placeholder="(XX) XXXXX-XXXX" value="<?php echo htmlspecialchars($telefone); ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Data de nascimento: </label>
      <input type="date" name="data_nascimento" class="form-control" value="<?php echo htmlspecialchars($data_nascimento); ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Perfil: </label>
      <select name="tipo" id="perfil" class="form-select" onchange="mostrarCampos()">
        <option value="aluno" <?php echo $tipo==='aluno'?'selected':''; ?>>Aluno</option>
        <option value="secretaria" <?php echo $tipo==='secretaria'?'selected':''; ?>>Secretaria</option>
        <option value="professor" <?php echo $tipo==='professor'?'selected':''; ?>>Professor</option>
        <option value="coordenador" <?php echo $tipo==='coordenador'?'selected':''; ?>>Coordenador</option>
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
            <select name="id_turma" class="form-select" required>
                <?php foreach ($turmas as $turma): ?>
                    <option value="<?php echo $turma['id_turma']; ?>">
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
    <button class="btn btn-primary">Salvar</button>
  </div>
</form>

<script>
  // Máscara para cpf
  const cpfInput = document.getElementById('cpf');
  cpfInput.addEventListener('input', function(e) {
    let v = this.value.replace(/\D/g,'');
    v = v.replace(/(\d{3})(\d)/,'$1.$2');
    v = v.replace(/(\d{3})(\d)/,'$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/,'$1-$2');
    this.value = v;
  });

  // Máscara para telefone
  const telInput = document.querySelector('input[name="telefone"]');
  telInput.addEventListener('input', function(e) {
    let v = this.value.replace(/\D/g,''); // remove tudo que não é número
    if (v.length > 10) { // celular 11 dígitos
      v = v.replace(/^(\d{2})(\d{5})(\d{4}).*/,'($1) $2-$3');
    } else if (v.length > 5) { // telefone fixo 10 dígitos
      v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/,'($1) $2-$3');
    } else if (v.length > 2) {
      v = v.replace(/^(\d{2})(\d{0,5})/,'($1) $2');
    } else if (v.length > 0) {
      v = v.replace(/^(\d*)/,'($1');
    }
    this.value = v;
  });

  function mostrarCampos() {
    document.getElementById("campos-aluno").style.display = "none";
 
    let tipo = document.getElementById("perfil").value;
    if(tipo === "aluno") {
        document.getElementById("campos-aluno").style.display = "block";
    } 
}
window.addEventListener("DOMContentLoaded", mostrarCampos);
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>