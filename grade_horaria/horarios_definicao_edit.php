<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

// 1. Busca os dados atuais para preencher o formulário
$id_definicao = (int)($_GET['id_definicao'] ?? 0);
if ($id_definicao === 0) {
    flash_set('danger', 'ID inválido.');
    header('Location: horarios_definicao.php');
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM definicao_horario WHERE id_definicao = ?');
$stmt->execute([$id_definicao]);
$definicao = $stmt->fetch();
if (!$definicao) {
    flash_set('danger', 'Definição de horário não encontrada.');
    header('Location: horarios_definicao.php');
    exit;
}

// Inicializa variáveis com os dados do banco
$errors = [];
$turno = $definicao['turno'];
$horario_label = $definicao['horario_label'];
$hora_inicio = $definicao['hora_inicio'];
$hora_fim = $definicao['hora_fim'];

// 2. Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura os novos dados
    $turno = trim($_POST['turno'] ?? '');
    $horario_label = trim($_POST['horario_label'] ?? '');
    $hora_inicio = trim($_POST['hora_inicio'] ?? '');
    $hora_fim = trim($_POST['hora_fim'] ?? '');

    // Validações
    if (empty($turno) || empty($horario_label) || empty($hora_inicio) || empty($hora_fim)) {
        $errors[] = "Todos os campos são obrigatórios.";
    }

    // Validação de unicidade (ignorando o registro atual)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM definicao_horario WHERE turno = ? AND horario_label = ? AND id_definicao != ?");
        $stmt->execute([$turno, $horario_label, $id_definicao]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Este bloco de horário já foi definido para o turno selecionado.";
        }
    }

    // Se tudo estiver certo, atualiza o banco
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE definicao_horario SET turno = ?, horario_label = ?, hora_inicio = ?, hora_fim = ? WHERE id_definicao = ?"
            );
            $stmt->execute([$turno, $horario_label, $hora_inicio, $hora_fim, $id_definicao]);

            flash_set('success', 'Definição de horário atualizada com sucesso!');
            header('Location: horarios_definicao.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erro ao salvar as alterações: " . $e->getMessage();
        }
    }
}

include '../partials/admin_header.php'; // Ajuste o caminho
?>


<div class="main">
    <div class="content mt-5">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-alarm-fill fs-4 text-secondary"></i>
                            <h2 class="h4 mb-0">Editar Definição #<?php echo (int)$definicao['id_definicao']; ?></h2>
                        </div>

                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="../admin.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="horarios_definicao.php">Definições de Horário</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Editar</li>
                            </ol>
                        </nav>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <?php csrf_input(); ?>
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">Dados da Definição</h5>
                            </div>

                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="turno" class="form-label">Turno</label>
                                        <select name="turno" id="turno" class="form-select" required>
                                            <option value="matutino" <?php echo ($turno === 'matutino' ? 'selected' : ''); ?>>Matutino</option>
                                            <option value="vespertino" <?php echo ($turno === 'vespertino' ? 'selected' : ''); ?>>Vespertino</option>
                                            <option value="noturno" <?php echo ($turno === 'noturno' ? 'selected' : ''); ?>>Noturno</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="horario_label" class="form-label">Bloco</label>
                                        <select name="horario_label" id="horario_label" class="form-select" required>
                                            <option value="primeiro" <?php echo ($horario_label === 'primeiro' ? 'selected' : ''); ?>>1º Horário</option>
                                            <option value="segundo" <?php echo ($horario_label === 'segundo' ? 'selected' : ''); ?>>2º Horário</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="hora_inicio" class="form-label">Início</label>
                                        <input type="time" name="hora_inicio" id="hora_inicio" value="<?php echo htmlspecialchars($hora_inicio); ?>" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="hora_fim" class="form-label">Fim</label>
                                        <input type="time" name="hora_fim" id="hora_fim" value="<?php echo htmlspecialchars($hora_fim); ?>" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="horarios_definicao.php" class="btn btn-secondary">Cancelar</a>
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
                                <strong>Excluir esta definição</strong>
                                <p class="mb-sm-0 text-muted small">Isto pode afetar grades horárias que dependem desta definição.</p>
                            </div>

                            <button type"button" class="btn btn-danger w-100 w-sm-auto mt-2 mt-sm-0 delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#confirmDeleteModal"
                                data-form-action="horarios_definicao_delete.php"
                                data-item-id="<?php echo (int)$definicao['id_definicao']; ?>"
                                data-item-name="<?php echo htmlspecialchars(ucfirst($turno) . ' - ' . $horario_label . ' Horário'); ?>"
                                data-id-field="id_definicao">
                                <i class="bi bi-trash-fill"></i> Excluir Definição
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>


<?php include '../partials/footer.php'; // Ajuste o caminho 
?>