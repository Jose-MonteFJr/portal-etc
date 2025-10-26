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


<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0">Editar Definição de Horário</h2>
            <a href="horarios_definicao.php" class="btn btn-sm btn-outline-secondary">Cancelar</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post">
                    <?php csrf_input(); ?>
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
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<?php include '../partials/footer.php'; // Ajuste o caminho 
?>