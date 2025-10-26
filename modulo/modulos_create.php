<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$errors = [];
$id_curso = 0;
$nome = $ordem = '';

// Lógica para pré-selecionar o curso se viermos da view de módulos de um curso específico
if (isset($_GET['id_curso'])) {
    $id_curso = (int)$_GET['id_curso'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // Proteção CSRF

    // Captura dados do formulário
    $id_curso   = (int)($_POST['id_curso'] ?? 0);
    $nome   = trim($_POST['nome'] ?? '');
    $ordem   = (int)($_POST['ordem'] ?? 0);

    // --- Validações ---
    if ($id_curso === 0) $errors[] = 'Curso é obrigatório.';
    if ($nome === '') $errors[] = 'Nome do módulo é obrigatório.';
    if ($ordem <= 0) $errors[] = 'Ordem deve ser um número maior que zero.';

    // Checagem de unicidade no banco
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM modulo WHERE ordem = ? AND id_curso = ?");
        $stmt->execute([$ordem, $id_curso]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Ordem do módulo já cadastrado neste curso.';
    }

    if (!$errors) {
        try {
            // --- Inserção no banco ---
            $stmt = $pdo->prepare("
            INSERT INTO modulo 
            (id_curso, nome, ordem) 
            VALUES (?, ?, ?)
    ");
            $stmt->execute([$id_curso, $nome, $ordem]);

            flash_set('success', 'Módulo cadastrado com sucesso!'); // Usando helper de flash
            header('Location: modulos_view.php?id_curso=' . $id_curso); // Volta para a view do curso
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

$cursos = $pdo->query("SELECT id_curso, nome FROM curso ORDER BY nome ASC")->fetchAll();

include '../partials/admin_header.php';
?>

<div class="main">
    <div class="content mt-5">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Novo Módulo</h2>
                        <a class="btn btn-outline-secondary btn-sm" href="modulos_view.php">Voltar</a>
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
                                <h5 class="mb-0">Dados do Módulo</h5>
                            </div>

                            <div class="card-body p-4">
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
                                            <option value="" disabled <?php echo ($id_curso === 0) ? 'selected' : ''; ?>>Selecione um curso</option>
                                            <?php foreach ($cursos as $curso): ?>
                                                <option value="<?php echo (int)$curso['id_curso']; ?>" <?php echo ((int)$curso['id_curso'] === $id_curso ? 'selected' : ''); ?>>
                                                    <?php echo htmlspecialchars($curso['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="modulos_view.php" class="btn btn-secondary">Cancelar</a>
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