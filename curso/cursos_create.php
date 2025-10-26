<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

//Variavel de array vazio para receber futuros erros
$errors = [];
$nome = $descricao = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check(); // Proteção CSRF

  // Captura dados do formulário
  $nome   = trim($_POST['nome'] ?? '');
  $descricao   = trim($_POST['descricao'] ?? '');

  // --- Validações ---
  if ($nome === '') $errors[] = 'Nome do curso é obrigatório.';

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
            (nome, descricao) 
            VALUES (?, ?)
    ");
      $stmt->execute([$nome, $descricao]);

      $_SESSION['success'] = 'Curso cadastrado com sucesso!';
      header('Location: cursos_view.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = 'Erro ao salvar: ' . $e->getMessage();
    }
  }
}

// Cabeçalho

include '../partials/admin_header.php';
?>
<div class="main">
  <div class="content">
    <div class="container mt-4">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0">Novo Curso</h2> <a class="btn btn-outline-secondary btn-sm" href="cursos_view.php">Voltar</a>
          </div>

          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
              </ul>
            </div>
          <?php endif; ?>
          <?php flash_show(); // Exibe as mensagens de sucesso/erro da sessão 
          ?>

          <form method="post" class="card shadow-sm p-4"> <?php csrf_input(); ?>
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label" for="nome">Nome do curso:</label>
                <input type="text" name="nome" id="nome" maxlength="150" class="form-control" placeholder="Ex: Técnico em Informática" value="<?php echo htmlspecialchars($nome); ?>" required>
              </div>

              <div class="col-12">
                <label class="form-label" for="descricao">Descrição (Opcional):</label>
                <textarea name="descricao" id="descricao" class="form-control" rows="5" placeholder="Digite uma breve descrição sobre os objetivos e o público-alvo do curso."><?php echo htmlspecialchars($descricao ?? ''); ?></textarea>
              </div>
            </div>
            <div class="mt-4 text-end"> <a href="cursos_view.php" class="btn btn-secondary">Cancelar</a>
              <button type="submit" class="btn btn-primary">Salvar Curso</button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../partials/footer.php'; ?>