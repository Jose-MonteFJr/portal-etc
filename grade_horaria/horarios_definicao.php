<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

$errors = [];
// Inicializa variáveis para repopular o formulário em caso de erro
$turno = $horario_label = $hora_inicio = $hora_fim = '';

// --- LÓGICA DE BACK-END PARA ADICIONAR UM NOVO HORÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura os dados do formulário
    $turno         = trim($_POST['turno'] ?? '');
    $horario_label = trim($_POST['horario_label'] ?? '');
    $hora_inicio   = trim($_POST['hora_inicio'] ?? '');
    $hora_fim      = trim($_POST['hora_fim'] ?? '');

    // Validações
    if (empty($turno) || empty($horario_label) || empty($hora_inicio) || empty($hora_fim)) {
        $errors[] = "Todos os campos são obrigatórios.";
    }
    // Validação de unicidade
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM definicao_horario WHERE turno = ? AND horario_label = ?");
        $stmt->execute([$turno, $horario_label]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Este bloco de horário já foi definido para o turno selecionado.";
        }
    }

    // Se não houver erros, insere no banco
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO definicao_horario (turno, horario_label, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$turno, $horario_label, $hora_inicio, $hora_fim]);

            flash_set('success', 'Definição de horário cadastrada com sucesso!');
            header('Location: horarios_definicao.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erro ao salvar no banco de dados: " . $e->getMessage();
        }
    }
}

// --- LÓGICA PARA BUSCAR OS HORÁRIOS JÁ CADASTRADOS ---
$definicoes = $pdo->query("SELECT * FROM definicao_horario ORDER BY turno, horario_label ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../partials/header.php'; // Ajuste o caminho
?>

<div class="row justify-content-center">
    <div class="col-lg-10">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0">Definições de Horários</h2>
            <a href="/portal-etc/turma/turmas_view.php" class="btn btn-sm btn-outline-secondary">Voltar para Turmas</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
            </div>
        <?php endif; ?>
        <?php flash_show(); ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Cadastrar Novo Bloco de Horário</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php csrf_input(); ?>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="turno" class="form-label">Turno</label>
                            <select name="turno" id="turno" class="form-select" required>
                                <option value="" disabled <?php echo empty($turno) ? 'selected' : ''; ?>>Selecione...</option>
                                <option value="matutino" <?php echo ($turno === 'matutino' ? 'selected' : ''); ?>>Matutino</option>
                                <option value="vespertino" <?php echo ($turno === 'vespertino' ? 'selected' : ''); ?>>Vespertino</option>
                                <option value="noturno" <?php echo ($turno === 'noturno' ? 'selected' : ''); ?>>Noturno</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="horario_label" class="form-label">Bloco</label>
                            <select name="horario_label" id="horario_label" class="form-select" required>
                                <option value="" disabled <?php echo empty($horario_label) ? 'selected' : ''; ?>>Selecione...</option>
                                <option value="primeiro" <?php echo ($horario_label === 'primeiro' ? 'selected' : ''); ?>>1º Horário</option>
                                <option value="segundo" <?php echo ($horario_label === 'segundo' ? 'selected' : ''); ?>>2º Horário</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="hora_inicio" class="form-label">Início</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" value="<?php echo htmlspecialchars($hora_inicio); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label for="hora_fim" class="form-label">Fim</label>
                            <input type="time" name="hora_fim" id="hora_fim" value="<?php echo htmlspecialchars($hora_fim); ?>" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Adicionar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">Horários Cadastrados</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Turno</th>
                                <th>Bloco</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($definicoes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Nenhum horário cadastrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($definicoes as $def): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(ucfirst($def['turno'])); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($def['horario_label'])); ?> Horário</td>
                                        <td><?php echo date('H:i', strtotime($def['hora_inicio'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($def['hora_fim'])); ?></td>
                                        <td class="text-end text-nowrap">
                                            <div class="btn-group">
                                                <a href="horarios_definicao_edit.php?id_definicao=<?php echo (int)$def['id_definicao']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    Editar
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#confirmDeleteModal"
                                                    data-item-id="<?php echo (int)$def['id_definicao']; ?>"
                                                    data-item-name="<?php echo htmlspecialchars(ucfirst($def['horario_label'])) . ' Horário (' . ucfirst($def['turno']) . ')'; ?>"
                                                    data-form-action="horarios_definicao_delete.php"
                                                    data-id-field="id_definicao">
                                                    Excluir
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include '../partials/footer.php'; // Ajuste o caminho 
?>