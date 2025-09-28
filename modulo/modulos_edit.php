<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$id_modulo = (int)($_GET['id_modulo'] ?? 0);
$stmt = $pdo->prepare('SELECT id_modulo, id_curso, nome, ordem FROM modulo WHERE id_modulo=?');

$stmt->execute([$id_modulo]);
$modulo = $stmt->fetch();
if (!$modulo) {
  flash_set('danger', 'Módulo não encontrado.');
  header('Location: modulos_view.php');
  exit;
}

$errors = [];
$id_curso = $modulo['id_curso'];
$nome = $modulo['nome'];
$ordem = $modulo['ordem'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); 
    
        // Captura dados do formulário
    $id_curso   = (int)($_POST['id_curso'] ?? 0);
    $nome   = trim($_POST['nome'] ?? '');
    $ordem   = (int)($_POST['ordem'] ?? 0);

        // --- Validações ---
    if ($id_curso === 0) $errors[] = 'Curso é obrigatório.';
    if ($nome === '') $errors[] = 'Nome do módulo é obrigatório.';
    if ($ordem === 0) $errors[] = 'Ordem é obrigatório.';

        // Checagem de unicidade no banco
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM modulo WHERE nome = ? AND id_curso = ? AND id_modulo !=?");
        $stmt->execute([$nome, $id_curso, $id_modulo]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Módulo já cadastrado neste curso.';
    }  
    
    if (!$errors) {
        try {
            $stmt = $pdo->prepare('UPDATE modulo SET id_curso=?,nome=?, ordem=? WHERE id_modulo=?');
            $stmt->execute([$id_curso, $nome, $ordem, $id_modulo]);

            flash_set('success', 'Módulo atualizado com sucesso.');
            header('Location: modulos_view.php');
            exit;            
        } catch (PDOException $e) {
                  $errors[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}    

$cursos = $pdo->query("SELECT id_curso, nome FROM curso ORDER BY nome ASC")->fetchAll();
    
include '../partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Editar Módulo #<?php echo (int)$modulo['id_modulo']; ?></h2>
  <a class="btn btn-outline-secondary btn-sm" href="modulos_view.php">Voltar</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- FORMULÁRIO -->

<form method="post" class="card shadow-sm p-3">
    <?php csrf_input(); ?>
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label" for="nome">Nome do módulo: </label>
            <input type="text" name="nome" id="nome" maxlength="150" class="form-control" placeholder="Ex: Módulo I - Fundamentos" value="<?php echo htmlspecialchars($nome); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="ordem">Ordem do módulo: </label>
            <input type="number" name="ordem" id="ordem" class="form-control" placeholder="Ex: 1" value="<?php echo htmlspecialchars($ordem); ?>" min="1" max="3" required>
        </div>
        <div class="col-md-6">
            <label for="id_curso" class="form-label">Cursos: </label>
            <select name="id_curso" id="id_curso" class="form-select" required>
                <option value="" disabled <?php echo ($id_curso === '') ? 'selected' : ''; ?>>Selecione um curso</option>
                <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo (int)$curso['id_curso']; ?>" <?php echo ((int)$curso['id_curso'] === $id_curso ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($curso['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mt-3 text-end">
        <input type="reset" class="btn btn-danger" value="Limpar">
        <button class="btn btn-primary">Salvar</button>
    </div>
</form>

<?php include '../partials/footer.php'; ?>