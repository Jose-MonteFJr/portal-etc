<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$errors = [];
$id_modulo_contexto = (int)($_GET['id_modulo'] ?? 0);
$id_modulo = $id_modulo_contexto;
$nome = $carga_horaria = '';

$id_curso_contexto = 0;
if ($id_modulo_contexto > 0) {
    $stmt = $pdo->prepare("SELECT id_curso FROM modulo WHERE id_modulo = ?");
    $stmt->execute([$id_modulo_contexto]);
    $id_curso_contexto = (int)$stmt->fetchColumn();
}

// Se não houver contexto, não podemos continuar
if ($id_curso_contexto === 0) {
    flash_set('danger', 'Contexto de curso ou módulo inválido.');
    header('Location: ../modulo/modulos_view.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id_modulo, nome FROM modulo WHERE id_curso = ? ORDER BY ordem ASC");
$stmt->execute([$id_curso_contexto]);
$modulos = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // Proteção CSRF

    // Captura dados do formulário
    $id_modulo   = (int)($_POST['id_modulo'] ?? 0);
    $nome   = trim($_POST['nome'] ?? '');
    $carga_horaria   = (int)($_POST['carga_horaria'] ?? 0);

    // --- Validações ---
    if ($id_modulo === 0) $errors[] = 'Módulo é obrigatório.';
    if ($nome === '') $errors[] = 'Nome da disciplina é obrigatório.';
    if ($carga_horaria <= 0) $errors[] = 'Carga horária deve ser maior que zero.';

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

            flash_set('success', 'Disciplina cadastrada com sucesso!');
            header('Location: disciplinas_view.php?id_modulo=' . $id_modulo);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

$modulos = $pdo->query("SELECT id_modulo, nome FROM modulo ORDER BY nome ASC")->fetchAll();

include '../partials/admin_header.php';
?>

<div class="main">
    <div class="content mt-5">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Nova Disciplina</h2>
                        <a class="btn btn-outline-secondary btn-sm" href="disciplinas_view.php?id_modulo=<?php echo $id_modulo_contexto; ?>">Voltar</a>
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
                                <h5 class="mb-0">Dados da Disciplina</h5>
                            </div>

                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label" for="nome">Nome da disciplina: </label>
                                        <input type="text" name="nome" id="nome" maxlength="150" class="form-control" placeholder="Ex: Lógica de Programação" value="<?php echo htmlspecialchars($nome); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="id_modulo" class="form-label">Módulo: </label>
                                        <select name="id_modulo" id="id_modulo" class="form-select" required>
                                            <option value="" disabled <?php echo ($id_modulo === 0) ? 'selected' : ''; ?>>Selecione um módulo</option>
                                            <?php foreach ($modulos as $modulo): ?>
                                                <option value="<?php echo (int)$modulo['id_modulo']; ?>"
                                                    <?php echo ((int)$modulo['id_modulo'] === $id_modulo ? 'selected' : ''); ?>>
                                                    <?php echo htmlspecialchars($modulo['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="carga_horaria">Carga horária (horas): </label>
                                        <input type="number" name="carga_horaria" id="carga_horaria" class="form-control" placeholder="Ex: 80" value="<?php echo htmlspecialchars($carga_horaria); ?>" min="1" required>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="disciplinas_view.php?id_modulo=<?php echo $id_modulo_contexto; ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Salvar</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>