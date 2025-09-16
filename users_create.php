<?php
/* Debug */
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();
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
   <div id="campos-aluno" style="display:none;" class="row g-3 mt-3">
  <div class="col-md-6">
    <label class="form-label">Matrícula:</label>
    <input type="text" name="matricula" class="form-control">
  </div>

  <div class="col-md-6">
    <label class="form-label">Curso:</label>
    <input type="text" name="curso" class="form-control">
  </div>
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