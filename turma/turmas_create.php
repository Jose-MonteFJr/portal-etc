<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$errors = [];
$id_curso = 0;
$nome = '';
$ano = 0;
$semestre = '';
$turno = '';
$status = 'aberta'; // Padrão lógico

if (isset($_GET['id_curso'])) {
    $id_curso = (int)$_GET['id_curso'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); // Proteção CSRF

    // Captura dados do formulário
    $id_curso   = (int)($_POST['id_curso'] ?? 0);
    $ano   = (int)($_POST['ano'] ?? 0);
    $semestre   = (int)($_POST['semestre'] ?? 0);
    $nome   = trim($_POST['nome'] ?? '');
    $turno   = trim($_POST['turno'] ?? '');
    $status   = trim($_POST['status'] ?? '');

    // --- Validações ---
    if ($id_curso === 0) $errors[] = 'Curso é obrigatório.';
    if ($ano === 0) $errors[] = 'Ano é obrigatório.';
    if ($semestre === 0) $errors[] = 'Semestre é obrigatório.';
    if ($nome === '') $errors[] = 'Nome da turma é obrigatório.';
    if ($turno === '') $errors[] = 'Turno é obrigatório.';
    if ($status === '') $errors[] = 'Status é obrigatório.';

    // Checagem de unicidade no banco
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM turma WHERE id_curso = ? AND ano = ? AND semestre = ? AND turno = ?");
        $stmt->execute([$id_curso, $ano, $semestre, $turno]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Já existe uma turma cadastrada para este curso, ano, semestre e turno.';
        }
    }

    if (!$errors) {
        try {
            // --- Inserção no banco ---
            $stmt = $pdo->prepare("
            INSERT INTO turma 
            (id_curso, nome, ano, semestre, turno, status) 
            VALUES (?, ?, ?, ?, ?, ?)
    ");
            $stmt->execute([$id_curso, $nome, $ano, $semestre, $turno, $status]);

            flash_set('success', 'Turma cadastrada com sucesso!');
            header('Location: turmas_view.php?id_curso=' . $id_curso);
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
                        <h2 class="h4 mb-0">Nova Turma</h2>
                        <a class="btn btn-outline-secondary btn-sm" href="turmas_view.php<?php echo ($id_curso > 0) ? '?id_curso=' . $id_curso : ''; ?>">Voltar</a>
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
                                <h5 class="mb-0">Dados da Turma</h5>
                            </div>

                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="nome">Nome da Turma (Apelido)</label>
                                        <input type="text" name="nome" id="nome" maxlength="100" class="form-control"
                                            placeholder="Ex: T-INF-2025.1-NOT ou Turma A - Noturno"
                                            value="<?php echo htmlspecialchars($nome); ?>" required>
                                    </div>

                                    <div class="col-12">
                                        <label for="id_curso" class="form-label">Curso</label>
                                        <select name="id_curso" id="id_curso" class="form-select" required>
                                            <option value="" disabled <?php echo ($id_curso === 0) ? 'selected' : ''; ?>>Selecione um curso...</option>
                                            <?php foreach ($cursos as $curso): ?>
                                                <option value="<?php echo (int)$curso['id_curso']; ?>"
                                                    <?php echo ((int)$curso['id_curso'] === $id_curso ? 'selected' : ''); ?>>
                                                    <?php echo htmlspecialchars($curso['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="ano" class="form-label">Ano</label>
                                        <select name="ano" id="ano" class="form-select" required>
                                            <option value="" disabled <?php echo ($ano === 0) ? 'selected' : ''; ?>>Selecione o ano</option>
                                            <?php
                                            $ano_atual = date('Y');
                                            for ($i = $ano_atual - 1; $i <= $ano_atual + 1; $i++):
                                            ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($i == $ano ? 'selected' : ''); ?>>
                                                    <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="semestre" class="form-label">Semestre</label>
                                        <select name="semestre" id="semestre" class="form-select" required>
                                            <option value="" disabled <?php echo ($semestre === '') ? 'selected' : ''; ?>>Selecione o semestre</option>
                                            <option value="1" <?php echo ($semestre === '1' ? 'selected' : ''); ?>>1º Semestre</option>
                                            <option value="2" <?php echo ($semestre === '2' ? 'selected' : ''); ?>>2º Semestre</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="turno" class="form-label">Turno</label>
                                        <select name="turno" id="turno" class="form-select" required>
                                            <option value="" disabled <?php echo ($turno === '') ? 'selected' : ''; ?>>Selecione o turno</option>
                                            <option value="matutino" <?php echo ($turno === 'matutino' ? 'selected' : ''); ?>>Matutino</option>
                                            <option value="vespertino" <?php echo ($turno === 'vespertino' ? 'selected' : ''); ?>>Vespertino</option>
                                            <option value="noturno" <?php echo ($turno === 'noturno' ? 'selected' : ''); ?>>Noturno</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-select" required>
                                            <option value="aberta" <?php echo ($status === 'aberta' ? 'selected' : ''); ?>>Aberta (Inscrições)</option>
                                            <option value="fechada" <?php echo ($status === 'fechada' ? 'selected' : ''); ?>>Fechada (Em curso)</option>
                                        </select>
                                    </div>

                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="turmas_view.php<?php echo ($id_curso > 0) ? '?id_curso=' . $id_curso : ''; ?>" class="btn btn-secondary">Cancelar</a>
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