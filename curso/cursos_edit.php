<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$id_curso = (int)($_GET['id_curso'] ?? 0);
$stmt = $pdo->prepare('SELECT id_curso, nome, carga_horaria FROM curso WHERE id_curso=?');

$stmt->execute([$id_curso]);
$user = $stmt->fetch();
if (!$user) {
  flash_set('danger', 'Curso não encontrado.');
  header('Location: cursos_view.php');
  exit;
}

$errors = [];
$nome = $user['nome'];
$carga_horaria = $user['carga_horaria'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura dados do formulário
    $nome            = trim($_POST['nome'] ?? '');
    $carga_horaria   = trim($_POST['carga_horaria'] ?? '');

    // --- Validações ---
    if ($nome === '') $errors[] = 'Nome do curso é obrigatório.';
    if ($carga_horaria === '') $errors[] = 'Carga horária do curso é obrigatório.';

        // Checagem de unicidade no banco
    if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM curso WHERE nome = ? AND id_curso != ?");
    $stmt->execute([$nome, $id_curso]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Já existe outro curso cadastrado com este nome.';
    }
}

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('UPDATE curso SET nome=?, carga_horaria=? WHERE id_curso=?');
            $stmt->execute([$nome, $carga_horaria, $id_curso]);

            flash_set('success', 'Curso atualizado com sucesso.');
            header('Location: cursos_view.php');
            exit;            
        } catch (PDOException $e) {
                  $errors[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

include '../partials/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Editar Curso #<?php echo (int)$user['id_curso']; ?></h2>
  <a class="btn btn-outline-secondary btn-sm" href="cursos_view.php">Voltar</a>
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
            <label class="form-label" for="nome">Nome do curso: </label>
            <input type="text" name="nome" id="nome" maxlength="150" class="form-control" placeholder="Ex: Engenharia de Software" value="<?php echo htmlspecialchars($nome); ?>" required>
        </div> 

        <div class="col-md-6">
            <label class="form-label" for="carga_horaria">Carga horária: </label>
            <input type="number" name="carga_horaria" id="carga_horaria" class="form-control" placeholder="Ex: 1200" min="1" value="<?php echo htmlspecialchars($carga_horaria); ?>" required>
        </div>        
    </div>
  <div class="mt-3 text-end">
    <input type="reset" class="btn btn-danger" value="Limpar">
    <button class="btn btn-primary">Salvar</button>
  </div>
</form>

<?php include '../partials/footer.php'; ?>