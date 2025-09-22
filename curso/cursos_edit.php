<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$id_curso = (int)($_GET['id_curso'] ?? 0);
$stmt = $pdo->prepare('SELECT id_curso, nome, descricao FROM curso WHERE id_curso=?');

$stmt->execute([$id_curso]);
$user = $stmt->fetch();
if (!$user) {
  flash_set('danger', 'Curso não encontrado.');
  header('Location: cursos_view.php');
  exit;
}

$errors = [];
$nome = $user['nome'];
$descricao = $user['descricao'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura dados do formulário
    $nome            = trim($_POST['nome'] ?? '');
    $descricao   = trim($_POST['descricao'] ?? '');

    // --- Validações ---
    if ($nome === '') $errors[] = 'Nome do curso é obrigatório.';

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
            $stmt = $pdo->prepare('UPDATE curso SET nome=?, descricao=? WHERE id_curso=?');
            $stmt->execute([$nome, $descricao, $id_curso]);

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
        <div class="col-md-12">
            <label class="form-label" for="nome">Nome do curso: </label>
            <input type="text" name="nome" id="nome" maxlength="150" class="form-control" placeholder="Ex: Técnico em Informática" value="<?php echo htmlspecialchars($nome); ?>" required>
        </div> 

        <div class="col-12">
            <label class="form-label" for="descricao">Descrição:</label>
            <textarea name="descricao" id="descricao" class="form-control" rows="4" placeholder="Digite uma breve descrição sobre o curso."><?php echo htmlspecialchars($descricao ?? ''); ?></textarea>
        </div>     
    </div>
  <div class="mt-3 text-end">
    <input type="reset" class="btn btn-danger" value="Limpar">
    <button class="btn btn-primary">Salvar</button>
  </div>
</form>

<?php include '../partials/footer.php'; ?>