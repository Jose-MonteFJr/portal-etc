<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

//Variavel de array vazio para receber futuros erros
$errors = [];
$nome = $carga_horaria = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check(); // Proteção CSRF

    // Captura dados do formulário
      $nome   = trim($_POST['nome'] ?? '');
      $carga_horaria   = trim($_POST['carga_horaria'] ?? '');

    // --- Validações ---
    if ($nome === '') $errors[] = 'Nome do curso é obrigatório.';
    if ($carga_horaria === '') $errors[] = 'Carga horária do curso é obrigatória.';

    // Checagem de unicidade no banco
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM curso WHERE nome = ?");
        $stmt->execute([$nome]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Curso já cadastrado.';
    }

    if (!$errors) {
        try {
        // --- Inserção no banco ---
        $stmt = $pdo->prepare("
            INSERT INTO curso 
            (nome, carga_horaria) 
            VALUES (?, ?)
    ");
        $stmt->execute([$nome, (int)$carga_horaria]);

        $_SESSION['success'] = 'Curso cadastrado com sucesso!';
        header('Location: cursos_view.php');
        exit;

        } catch (PDOException $e) {
            $errors[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

    // Cabeçalho

include '../partials/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Nova Turma</h2>
  <a class="btn btn-outline-secondary btn-sm" href="cursos_view.php">Voltar</a>
</div>

    <!-- Lista de erros na tela -->

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
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