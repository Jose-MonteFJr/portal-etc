<?php
require '../protect.php'; // Ajuste o caminho
require '../helpers.php';
require '../config/db.php';

// NOVO: Busca as turmas que o professor leciona
$turmas_do_professor = [];
if ($_SESSION['tipo'] === 'professor') {
    // CORRIGIDO: Busca na tabela 'horario_aula' e usa 'DISTINCT'
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.id_turma, t.nome, c.nome AS nome_curso
        FROM horario_aula h
        JOIN turma t ON h.id_turma = t.id_turma
        JOIN curso c ON t.id_curso = c.id_curso
        WHERE h.id_professor = ?
        ORDER BY c.nome ASC, t.nome ASC
    ");
    $stmt->execute([$_SESSION['id_usuario']]);
    $turmas_do_professor = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../partials/portal_header.php'; // Inclui seu layout principal
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <button id="prev-month-btn" class="btn btn-primary prev"><i class="bi bi-chevron-left"></i></button>
                            <h5 class="mb-0 current-month" id="current-month-year"></h5>
                            <button id="next-month-btn" class="btn btn-primary prev"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <div class="card-body">
                            <div class="calendar-weekdays">
                                <div>Dom</div>
                                <div>Seg</div>
                                <div>Ter</div>
                                <div>Qua</div>
                                <div>Qui</div>
                                <div>Sex</div>
                                <div>Sáb</div>
                            </div>
                            <div class="calendar-grid" id="calendar-days">
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <button class="btn btn-outline-primary btn-sm prev" id="today-btn">Hoje</button>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control form-control-sm" id="date-input" placeholder="MM/AAAA" maxlength="7">
                                <button class="btn btn-primary btn-sm prev" id="goto-btn">Ir</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-calendar-event me-2"></i>
                                <span id="selected-date-header">Selecione um dia</span>
                            </h6>
                            <button id="prepare-add-btn" class="btn btn-success btn-sm" data-bs-toggle="collapse" data-bs-target="#add-event-form">
                                <i class="bi bi-plus-lg"></i> Novo
                            </button>
                        </div>

                        <div class="collapse p-3" id="add-event-form">
                            <input type="hidden" id="event-id">

                            <div class="mb-2">
                                <label for="event-title" class="form-label small">Título</label>
                                <input type="text" id="event-title" class="form-control form-control-sm">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col">
                                    <label for="event-start-time" class="form-label small">Início</label>
                                    <input type="time" id="event-start-time" class="form-control form-control-sm">
                                </div>
                                <div class="col">
                                    <label for="event-end-time" class="form-label small">Fim</label>
                                    <input type="time" id="event-end-time" class="form-control form-control-sm">
                                </div>
                            </div>
                            <?php if ($_SESSION['tipo'] === 'professor'): ?>
                                <div class="mb-2">
                                    <label for="event-turma-alvo" class="form-label small">Enviar para Turma (Opcional):</label>
                                    <select id="event-turma-alvo" class="form-select form-select-sm">
                                        <option value="">-- Apenas para mim (Pessoal) --</option>
                                        <?php foreach ($turmas_do_professor as $turma): ?>
                                            <option value="<?php echo $turma['id_turma']; ?>">
                                                <?php echo htmlspecialchars($turma['nome_curso'] . ' - ' . $turma['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text small">Se nenhuma turma for selecionada, o lembrete será pessoal.</div>
                                </div>
                            <?php endif; ?>
                            <button id="add-event-btn" class="btn btn-primary btn-sm w-100 prev">Adicionar Lembrete</button>
                        </div>

                        <div class="list-group list-group-flush" id="events-list">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-success btn-lg rounded-circle shadow fab"
            data-bs-toggle="collapse"
            data-bs-target="#add-event-form"
            title="Novo Lembrete">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</div>

<script>
    const LOGGED_IN_USER_ID = <?php echo (int)$_SESSION['id_usuario']; ?>;
</script>
<script src="../partials/js/calendario.js"></script>
<?php include '../partials/footer.php'; // Ajuste o caminho 
?>