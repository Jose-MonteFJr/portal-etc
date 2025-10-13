<?php
require '../protect.php'; // Ajuste o caminho
require  '../helpers.php';

include '../partials/portal_header.php'; // Inclui seu layout principal
?>

<head>
    <link rel="stylesheet" href="../partials/css/calendario.css">
</head>
<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <button id="prev-month-btn" class="btn btn-primary"><i class="bi bi-chevron-left"></i></button>
                            <h5 class="mb-0" id="current-month-year"></h5>
                            <button id="next-month-btn" class="btn btn-primary"><i class="bi bi-chevron-right"></i></button>
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
                    </div>
                </div>

                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0" id="selected-date-header">Selecione um dia</h6>
                            <button class="btn btn-success btn-sm" data-bs-toggle="collapse" data-bs-target="#add-event-form">
                                <i class="bi bi-plus-lg"></i> Novo
                            </button>
                        </div>

                        <div class="collapse p-3" id="add-event-form">
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
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="event-global-check">
                                    <label class="form-check-label small" for="event-global-check">
                                        Aviso para todos
                                    </label>
                                </div>
                            <?php endif; ?>
                            <button id="add-event-btn" class="btn btn-primary btn-sm w-100">Adicionar Lembrete</button>
                        </div>

                        <div class="list-group list-group-flush" id="events-list">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const LOGGED_IN_USER_ID = <?php echo (int)$_SESSION['id_usuario']; ?>;
</script>
<script src="../partials/js/calendario.js"></script>
<?php include '../partials/footer.php'; // Ajuste o caminho 
?>