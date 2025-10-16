<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

// --- 1. BUSCA DE DADOS ESSENCIAIS ---

// Pega o ID da turma pela URL. Essencial para saber qual grade estamos montando.
$id_turma = (int)($_GET['id_turma'] ?? 0);
if ($id_turma === 0) {
    flash_set('danger', 'Turma não especificada.');
    header('Location: turmas_view.php');
    exit;
}

try {
    // Busca informações da turma (nome, curso, turno)
    $stmt_turma = $pdo->prepare("SELECT t.*, c.nome AS nome_curso 
                                FROM turma t 
                                JOIN curso c ON t.id_curso = c.id_curso 
                                WHERE t.id_turma = ?");
    $stmt_turma->execute([$id_turma]);
    $turma = $stmt_turma->fetch();
    if (!$turma) {
        flash_set('danger', 'Turma não encontrada.');
        header('Location: turmas_view.php');
        exit;
    }

    // Busca os blocos de horário para o turno desta turma (ex: 19:00-20:50, etc.)
    $stmt_definicoes = $pdo->prepare("SELECT * FROM definicao_horario WHERE turno = ? ORDER BY horario_label ASC");
    $stmt_definicoes->execute([$turma['turno']]);
    $definicoes_horario = $stmt_definicoes->fetchAll(PDO::FETCH_ASSOC);

    // Busca as disciplinas disponíveis para o curso desta turma
    $stmt_disciplinas = $pdo->prepare("
        SELECT d.id_disciplina, d.nome FROM disciplina d
        JOIN modulo m ON d.id_modulo = m.id_modulo
        WHERE m.id_curso = ? ORDER BY d.nome ASC
    ");
    $stmt_disciplinas->execute([$turma['id_curso']]);
    $disciplinas_disponiveis = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);

    // Busca todos os professores disponíveis
    $stmt_professores = $pdo->query("SELECT id_usuario, nome_completo FROM usuario WHERE tipo = 'professor' ORDER BY nome_completo ASC");
    $professores_disponiveis = $stmt_professores->fetchAll(PDO::FETCH_ASSOC);

    // Busca a grade horária que JÁ EXISTE para esta turma
    $stmt_horario_atual = $pdo->prepare("SELECT * FROM horario_aula WHERE id_turma = ?");
    $stmt_horario_atual->execute([$id_turma]);
    $aulas_salvas = $stmt_horario_atual->fetchAll(PDO::FETCH_ASSOC);

    // Organiza as aulas salvas em um array fácil de usar no HTML
    $horarios_organizados = [];
    foreach ($aulas_salvas as $aula) {
        $horarios_organizados[$aula['horario']][$aula['dia_semana']] = [
            'id_disciplina' => $aula['id_disciplina'],
            'id_professor'  => $aula['id_professor'],
            'sala'          => $aula['sala']
        ];
    }
} catch (PDOException $e) {
    die("Erro ao carregar dados para montar o horário: " . $e->getMessage());
}

include '../partials/header.php'; // Ajuste o caminho
?>

<div class="container-fluid mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h4 mb-0">Montar Grade Horária</h2>
            <small class="text-muted">Turma: <?php echo htmlspecialchars($turma['nome_curso'] . ' - ' . $turma['nome']); ?></small>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="/portal-etc/turma/turmas_view.php">Voltar</a>
    </div>

    <form method="post" action="montar_horario_action.php" onsubmit="return confirm('Tem certeza que deseja salvar esta grade horária?');">
        <?php csrf_input(); ?>
        <input type="hidden" name="id_turma" value="<?php echo (int)$turma['id_turma']; ?>">

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered text-center mb-0 align-middle">
                        <thead class="table">
                            <tr>
                                <th style="width: 12%;">Horário</th>
                                <th>2ª Feira</th>
                                <th>3ª Feira</th>
                                <th>4ª Feira</th>
                                <th>5ª Feira</th>
                                <th>6ª Feira</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($definicoes_horario as $def):
                                $label = $def['horario_label']; // 'primeiro' ou 'segundo'
                            ?>
                                <tr>
                                    <td class="fw-bold">
                                        <?php echo ucfirst($label); ?> Horário<br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($def['hora_inicio'])) . ' - ' . date('H:i', strtotime($def['hora_fim'])); ?></small>
                                    </td>
                                    <?php
                                    $dias_semana = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
                                    foreach ($dias_semana as $dia):
                                        // Pega os dados já salvos para esta célula, se existirem
                                        $aula_salva = $horarios_organizados[$label][$dia] ?? null;
                                    ?>
                                        <td style="min-width: 200px;">
                                            <div class="mb-2">
                                                <select name="horario[<?php echo $label; ?>][<?php echo $dia; ?>][id_disciplina]" class="form-select form-select-sm">
                                                    <option value="">-- Disciplina --</option>
                                                    <?php foreach ($disciplinas_disponiveis as $disc): ?>
                                                        <option value="<?php echo $disc['id_disciplina']; ?>" <?php echo ($aula_salva && $aula_salva['id_disciplina'] == $disc['id_disciplina']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($disc['nome']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <select name="horario[<?php echo $label; ?>][<?php echo $dia; ?>][id_professor]" class="form-select form-select-sm">
                                                    <option value="">-- Professor --</option>
                                                    <?php foreach ($professores_disponiveis as $prof): ?>
                                                        <option value="<?php echo $prof['id_usuario']; ?>" <?php echo ($aula_salva && $aula_salva['id_professor'] == $prof['id_usuario']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($prof['nome_completo']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <input type="text" name="horario[<?php echo $label; ?>][<?php echo $dia; ?>][sala]" class="form-control form-control-sm" placeholder="Sala" value="<?php echo htmlspecialchars($aula_salva['sala'] ?? ''); ?>">
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Salvar Grade Horária</button>
        </div>
    </form>

</div>

<?php include '../partials/footer.php'; // Ajuste o caminho 
?>