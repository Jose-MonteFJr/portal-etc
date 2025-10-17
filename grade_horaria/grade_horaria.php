<?php
// 1. INCLUDES E SEGURANÇA PADRÃO
require     '../protect.php'; // Ajuste o caminho conforme sua estrutura
require     '../config/db.php';
require     '../helpers.php';

// Garante que apenas usuários do tipo 'aluno' possam acessar esta página
if ($_SESSION['tipo'] !== 'aluno') {
    // Para outros perfis, você pode redirecionar para o painel de admin ou mostrar um erro
    flash_set('danger', 'Acesso negado. Esta página é exclusiva para alunos.');
    header('Location: ../admin.php'); // Ajuste o redirecionamento se necessário
    exit;
}

// 2. INICIALIZAÇÃO DAS VARIÁVEIS
$id_usuario_logado = $_SESSION['id_usuario'];
$info_turma = null;         // Guardará os dados da turma do aluno
$definicoes_horario = [];   // Guardará os blocos de horário (ex: 19:00 - 20:50)
$horarios_organizados = []; // O "mapa" final da grade para usar no HTML

try {
    // 3. PASSO DA INVESTIGAÇÃO: DESCOBRIR A TURMA DO ALUNO
    // A consulta junta as tabelas 'aluno', 'turma' e 'curso' para pegar todas as infos de uma vez
    $stmt_aluno = $pdo->prepare("
    SELECT 
        a.id_turma, 
        t.nome AS nome_turma, 
        t.turno, 
        c.nome AS nome_curso,
        m.nome AS nome_modulo  -- NOVA INFORMAÇÃO SENDO BUSCADA
    FROM aluno a
    JOIN turma t ON a.id_turma = t.id_turma
    JOIN curso c ON t.id_curso = c.id_curso
    -- NOVO JOIN: Busca o nome do módulo atual da turma
    LEFT JOIN modulo m ON t.id_modulo_atual = m.id_modulo 
    WHERE a.id_usuario = ?
");
    $stmt_aluno->execute([$id_usuario_logado]);
    $info_turma = $stmt_aluno->fetch(PDO::FETCH_ASSOC);

    // 4. VERIFICAÇÃO DE SEGURANÇA: O aluno está em uma turma?
    if ($info_turma && !empty($info_turma['id_turma'])) {

        $id_turma_aluno = $info_turma['id_turma'];
        $turno_aluno = $info_turma['turno'];

        // 5. BUSCA OS HORÁRIOS PADRÃO PARA O TURNO DO ALUNO
        // Com base no turno (ex: 'noturno'), busca os horários de início e fim das aulas
        $stmt_definicoes = $pdo->prepare("SELECT horario_label, hora_inicio, hora_fim FROM definicao_horario WHERE turno = ? ORDER BY horario_label ASC");
        $stmt_definicoes->execute([$turno_aluno]);
        $definicoes_horario = $stmt_definicoes->fetchAll(PDO::FETCH_ASSOC);

        // 6. BUSCA A GRADE HORÁRIA COMPLETA DA TURMA
        // Esta é a consulta principal, que junta a grade com as disciplinas e os professores
        $stmt_horario = $pdo->prepare("
            SELECT 
                h.dia_semana, 
                h.horario, 
                h.sala,
                d.nome AS nome_disciplina,
                p.nome_completo AS nome_professor
            FROM horario_aula h
            JOIN disciplina d ON h.id_disciplina = d.id_disciplina
            JOIN usuario p ON h.id_professor = p.id_usuario
            WHERE h.id_turma = ?
        ");
        $stmt_horario->execute([$id_turma_aluno]);
        $aulas = $stmt_horario->fetchAll(PDO::FETCH_ASSOC);

        // 7. ORGANIZA OS DADOS PARA O HTML
        // Transforma a lista de aulas em um "mapa" fácil de consultar
        foreach ($aulas as $aula) {
            $horarios_organizados[$aula['horario']][$aula['dia_semana']] = [
                'disciplina' => $aula['nome_disciplina'],
                'professor'  => $aula['nome_professor'],
                'sala'       => $aula['sala']
            ];
        }
    }
    // Se o aluno não estiver em uma turma, as variáveis $definicoes_horario e $horarios_organizados
    // permanecerão vazias, e o HTML mostrará uma mensagem de aviso.

} catch (PDOException $e) {
    // Em caso de erro grave no banco de dados, exibe uma mensagem
    die("Erro ao carregar a grade horária: " . $e->getMessage());
}

// Inclui o cabeçalho do seu portal
include '../partials/portal_header.php'; // Ajuste o caminho
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">

            <?php if (!$info_turma || !$info_turma['id_turma']): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="alert alert-warning text-center shadow-sm">
                            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Atenção</h4>
                            <p>Você não está matriculado em nenhuma turma no momento.</p>
                            <p class="mb-0">Assim que sua matrícula for efetivada em uma turma, sua grade horária aparecerá aqui.</p>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-header text-center">
                        <h4 class="mb-1"><?php echo htmlspecialchars($info_turma['nome_curso']); ?></h4>

                        <?php if (!empty($info_turma['nome_modulo'])): ?>
                            <h6 class="mb-1 fw-normal text-muted">Módulo: <?php echo htmlspecialchars($info_turma['nome_modulo']); ?></h6>
                        <?php endif; ?>

                        <p class="mb-0 text-muted">Turma: <?php echo htmlspecialchars($info_turma['nome_turma']); ?></p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 15%;">Horário</th>
                                        <th>2ª Feira</th>
                                        <th>3ª Feira</th>
                                        <th>4ª Feira</th>
                                        <th>5ª Feira</th>
                                        <th>6ª Feira</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($definicoes_horario as $definicao):
                                        $label = $definicao['horario_label']; // 'primeiro' ou 'segundo'
                                    ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <?php echo ucfirst($label); ?> Horário<br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($definicao['hora_inicio'])) . ' - ' . date('H:i', strtotime($definicao['hora_fim'])); ?></small>
                                            </td>

                                            <?php
                                            $dias_semana = ['segunda', 'terca', 'quarta', 'quinta', 'sexta'];
                                            foreach ($dias_semana as $dia):
                                                // Busca no nosso "mapa" a aula para este dia e horário
                                                $aula = $horarios_organizados[$label][$dia] ?? null;
                                            ?>
                                                <td>
                                                    <?php if ($aula): ?>
                                                        <strong class="d-block mb-1"><?php echo htmlspecialchars($aula['disciplina']); ?></strong>
                                                        <small class="text-muted d-block">Prof. <?php echo htmlspecialchars($aula['professor']); ?></small>
                                                        <span class="badge bg-secondary mt-1">Sala: <?php echo htmlspecialchars($aula['sala']); ?></span>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../partials/footer.php'; // Ajuste o caminho 
?>