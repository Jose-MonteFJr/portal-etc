<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();
// Dados para trocar - nome, email, telefone e o tipo
$id_usuario = (int)($_GET['id_usuario'] ?? 0);
$stmt = $pdo->prepare('SELECT id_usuario, nome_completo, email, telefone, tipo FROM usuario WHERE id_usuario=?');

$stmt->execute([$id_usuario]);
$user = $stmt->fetch();
if (!$user) {
  flash_set('danger', 'Usuário não encontrado.');
  header('Location: admin.php');
  exit;
}

$errors = [];
$nome_completo = $user['nome_completo'];
$email = $user['email'];
$telefone = $user['telefone'];
$tipo = $user['tipo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  // Captura dados do formulário
    $nome_completo   = trim($_POST['nome_completo'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $telefone        = trim($_POST['telefone'] ?? '');
    $tipo            = $_POST['tipo'] ?? 'aluno';

// --- Validações ---
    if ($nome_completo === '') $errors[] = 'Nome completo é obrigatório.';

// E-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-mail inválido.';
    }

// Telefone
    if ($telefone === '') $errors[] = 'Telefone é obrigatório.';

// Tipo de usuário
    if (!in_array($tipo, ['secretaria','aluno', 'professor', 'coordenador'], true)) {
        $errors[] = 'Perfil inválido.';
    }

  if (!$errors) {
    try {
      if ($password) {
        if (strlen($password) < 8) $errors[] = 'Senha deve ter pelo menos 8 caracteres.';
      }
      if (!$errors) {
        // verificar duplicidade de e-mail em outro ID
        $chk = $pdo->prepare('SELECT id_usuario FROM usuario WHERE email=? AND id_usuario<>?');
        $chk->execute([$email, $id_usuario]);
        
        if ($chk->fetch()) {
          $errors[] = 'Já existe um usuário com este e-mail.';
        } else {
          if ($password) {
            $stmt = $pdo->prepare('UPDATE usuario SET nome_completo=?, email=?, telefone=?, tipo=?, password_hash=? WHERE id_usuario=?');
            $stmt->execute([$nome_completo, $email, $telefone, $tipo, password_hash($password, PASSWORD_DEFAULT), $id_usuario]);
          } else {
            $stmt = $pdo->prepare('UPDATE usuario SET nome_completo=?, email=?, telefone=?, tipo=? WHERE id_usuario=?');
            $stmt->execute([$nome_completo, $email, $telefone, $tipo, $id_usuario]);
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

include __DIR__ . '/partials/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Editar Usuário #<?php echo (int)$user['id_usuario']; ?></h2>
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
      <label class="form-label">E-mail: </label>
      <input type="email" name="email" maxlength="150" class="form-control" placeholder="exemplo@exemplo.com" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Telefone: </label>
      <input type="tel" name="telefone" maxlength="20" class="form-control" placeholder="(XX) XXXXX-XXXX" value="<?php echo htmlspecialchars($telefone); ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Perfil: </label>
      <select name="tipo" class="form-select">
        <option value="aluno" <?php echo $tipo==='aluno'?'selected':''; ?>>Aluno</option>
        <option value="secretaria" <?php echo $tipo==='secretaria'?'selected':''; ?>>Secretaria</option>
        <option value="professor" <?php echo $tipo==='professor'?'selected':''; ?>>Professor</option>
        <option value="coordenador" <?php echo $tipo==='coordenador'?'selected':''; ?>>Coordenador</option>
      </select>
    </div>

    <!-- NÃO ALTERAR -->
    <div class="col-md-3">
      <label class="form-label">Nova Senha: (opcional)</label>
      <input type="password" name="password" class="form-control" placeholder="Deixe em branco para manter">
    </div>
  </div>
  <div class="mt-3 text-end">
    <button class="btn btn-primary">Salvar</button>
  </div>
</form>
<script>
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
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
