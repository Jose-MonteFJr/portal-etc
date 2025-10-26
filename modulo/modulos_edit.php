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
    if ($ordem <= 0) $errors[] = 'Ordem deve ser um número maior que zero.';

    // Checagem de unicidade no banco
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM modulo WHERE ordem = ? AND id_curso = ? AND id_modulo != ?");
        $stmt->execute([$ordem, $id_curso, $id_modulo]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'O número de ordem deste módulo já existe neste curso.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('UPDATE modulo SET id_curso=?,nome=?, ordem=? WHERE id_modulo=?');
            $stmt->execute([$id_curso, $nome, $ordem, $id_modulo]);

            flash_set('success', 'Módulo atualizado com sucesso.');
            // Redireciona de volta para a view do curso
            header('Location: modulos_view.php?id_curso=' . $id_curso);
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
    <div class="content">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Editar Módulo #<?php echo (int)$modulo['id_modulo']; ?></h2>
                        <a class="btn btn-outline-secondary btn-sm" href="modulos_view.php?id_curso=<?php echo (int)$id_curso; ?>">Voltar</a>
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
                                            <option value="0" disabled <?php echo ($id_curso === 0) ? 'selected' : ''; ?>>Selecione um curso</option>
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
                                <a href="modulos_view.php?id_curso=<?php echo (int)$id_curso; ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            </div>
                        </div>
                    </form>

                    <div class="card border-danger shadow-sm mt-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Zona de Perigo</h5>
                        </div>
                        <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                            <div>
                                <strong>Excluir este módulo</strong>
                                <p class="mb-sm-0 text-muted small">Uma vez excluído, o módulo não poderá ser recuperado. Esta ação é permanente.</p>
                            </div>

                            <button type="button" class="btn btn-danger w-100 w-sm-auto mt-2 mt-sm-0 delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#confirmDeleteModal"
                                data-form-action="modulos_delete.php"
                                data-item-id="<?php echo (int)$modulo['id_modulo']; ?>"
                                data-item-name="<?php echo htmlspecialchars($modulo['nome']); ?>"
                                data-id-field="id_modulo">
                                <i class="bi bi-trash-fill"></i> Excluir Módulo
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>