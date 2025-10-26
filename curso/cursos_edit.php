<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$id_curso = (int)($_GET['id_curso'] ?? 0);
$stmt = $pdo->prepare('SELECT id_curso, nome, descricao FROM curso WHERE id_curso=?');

$stmt->execute([$id_curso]);
$curso = $stmt->fetch();
if (!$curso) {
  flash_set('danger', 'Curso não encontrado.');
  header('Location: cursos_view.php');
  exit;
}

$errors = [];
$nome = $curso['nome'];
$descricao = $curso['descricao'];


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

include '../partials/admin_header.php';
?>


<div class="main">
  <div class="content mt-5">
    <div class="container-fluid mt-4">
      <div class="row justify-content-center">
        <div class="col-lg-9">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0">Editar Curso #<?php echo (int)$curso['id_curso']; ?></h2>
            <a class="btn btn-outline-secondary btn-sm" href="cursos_view.php">Voltar</a>
          </div>

          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
            </div>
          <?php endif; ?>

          <form method="post" autocomplete="off">
            <?php csrf_input(); ?>
            <div class="card shadow-sm">
              <div class="card-header">
                <h5 class="mb-0">Dados do Curso</h5>
              </div>

              <div class="card-body p-4">
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
              </div>

              <div class="card-footer text-end">
                <a href="cursos_view.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
              </div>
            </div>
          </form>

          <div class="card border-danger shadow-sm mt-4">
            <div class="card-header bg-danger">
              <h5 class="mb-0">Zona de Perigo</h5>
            </div>
            <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
              <div>
                <strong>Excluir este curso</strong>
                <p class="mb-sm-0 text-muted small">Uma vez excluído, o curso não poderá ser recuperado. Esta ação é permanente.</p>
              </div>

              <button type="button" class="btn btn-danger w-100 w-sm-auto mt-2 mt-sm-0 delete-btn"
                data-bs-toggle="modal"
                data-bs-target="#confirmDeleteModal"
                data-form-action="cursos_delete.php"
                data-item-id="<?php echo (int)$curso['id_curso']; ?>"
                data-item-name="<?php echo htmlspecialchars($curso['nome']); ?>"
                data-id-field="id_curso">
                <i class="bi bi-trash-fill"></i> Excluir Curso
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../partials/footer.php'; ?>