<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$errors = [];
$id_modulo = (int)($_GET['id_modulo'] ?? 0);
$nome = $carga_horaria = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // Proteção CSRF

    // Captura dados do formulário
    $id_modulo   = (int)($_POST['id_modulo'] ?? 0);
    $nome   = trim($_POST['nome'] ?? '');
    $carga_horaria   = (int)($_POST['carga_horaria'] ?? 0);

    // --- Validações ---
    if ($id_modulo === 0) $errors[] = 'Módulo é obrigatório.';
    if ($nome === '') $errors[] = 'Nome da disciplina é obrigatório.';
    if ($carga_horaria === 0) $errors[] = 'Carga horária é obrigatório.';

    // Checagem de unicidade no banco
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM disciplina WHERE nome = ? AND id_modulo = ?");
        $stmt->execute([$nome, $id_modulo]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Disciplina já cadastrado neste módulo.';
    }

    if (!$errors) {
        try {
            // --- Inserção no banco ---
            $stmt = $pdo->prepare("
            INSERT INTO disciplina 
            (id_modulo, nome, carga_horaria) 
            VALUES (?, ?, ?)
    ");
            $stmt->execute([$id_modulo, $nome, $carga_horaria]);

            $_SESSION['success'] = 'Disciplina cadastrada com sucesso!';
            header('Location: disciplinas_view.php?id_modulo=' . $id_modulo);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

$modulos = $pdo->query("SELECT id_modulo, nome FROM modulo ORDER BY nome ASC")->fetchAll();

include '../partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="h4 mb-0">Nova disciplina</h2>
    <a class="btn btn-outline-secondary btn-sm" href="disciplinas_view.php?id_modulo=<?php echo $id_modulo; ?>">Voltar</a>
</div>

<!-- Lista de erros na tela -->

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- FORMULÁRIO  -->

<form method="post" class="card shadow-sm p-3">
    <?php csrf_input(); ?>
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label" for="nome">Nome da disciplina: </label>
            <input type="text" name="nome" id="nome" maxlength="150" class="form-control" placeholder="Ex: Lógica de Programação" value="<?php echo htmlspecialchars($nome); ?>" required>
        </div>
        <div class="col-md-6">
            <label for="id_modulo" class="form-label">Módulo: </label>
            <select name="id_modulo" id="id_modulo" class="form-select" required>
                <option value="" disabled <?php echo empty($id_modulo) ? 'selected' : ''; ?>>Selecione um módulo</option>
                <?php foreach ($modulos as $modulo): ?>
                    <option value="<?php echo (int)$modulo['id_modulo']; ?>"
                        <?php echo ((int)$modulo['id_modulo'] === $id_modulo ? 'selected' : ''); ?>> <?php echo htmlspecialchars($modulo['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="carga_horaria">Carga horária (horas): </label>
            <input type="number" name="carga_horaria" id="carga_horaria" class="form-control" placeholder="Ex: 80" value="<?php echo htmlspecialchars($carga_horaria); ?>" min="1" required>
        </div>
    </div>
    <div class="mt-3 text-end">
        <input type="reset" class="btn btn-danger" value="Limpar">
        <button class="btn btn-primary">Salvar</button>
    </div>
</form>

<?php include '../partials/footer.php'; ?>