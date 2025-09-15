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

  /*$first_name = trim($_POST['first_name'] ?? '');
  $last_name  = trim($_POST['last_name'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $password   = $_POST['password'] ?? '';
  $role       = $_POST['role'] ?? 'user';*/
// Continuar a partir daqui!
  if ($first_name === '') $errors[] = 'Primeiro nome é obrigatório.';
  if ($last_name === '')  $errors[] = 'Sobrenome é obrigatório.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
  if (!in_array($role, ['admin','user'], true)) $errors[] = 'Perfil inválido.';

  if (!$errors) {
    try {
      if ($password) {
        if (strlen($password) < 6) $errors[] = 'Senha deve ter pelo menos 6 caracteres.';
      }
      if (!$errors) {
        // verificar duplicidade de e-mail em outro ID
        $chk = $pdo->prepare('SELECT id FROM users WHERE email=? AND id<>?');
        $chk->execute([$email, $id]);
        if ($chk->fetch()) {
          $errors[] = 'Já existe um usuário com este e-mail.';
        } else {
          if ($password) {
            $stmt = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, role=?, password_hash=? WHERE id=?');
            $stmt->execute([$first_name, $last_name, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
          } else {
            $stmt = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, role=? WHERE id=?');
            $stmt->execute([$first_name, $last_name, $email, $role, $id]);
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
  <h2 class="h4 mb-0">Editar Usuário #<?php echo (int)$user['id']; ?></h2>
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
      <label class="form-label">Primeiro Nome</label>
      <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Sobrenome</label>
      <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">E-mail</label>
      <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Perfil</label>
      <select name="role" class="form-select">
        <option value="user" <?php echo $role==='user'?'selected':''; ?>>User</option>
        <option value="admin" <?php echo $role==='admin'?'selected':''; ?>>Admin</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Nova Senha (opcional)</label>
      <input type="password" name="password" class="form-control" placeholder="Deixe em branco para manter">
    </div>
  </div>
  <div class="mt-3 text-end">
    <button class="btn btn-primary">Salvar</button>
  </div>
</form>

<?php include __DIR__ . '/partials/footer.php'; ?>
