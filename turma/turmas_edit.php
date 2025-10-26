<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$id_turma = (int)($_GET['id_turma'] ?? 0);
if ($id_turma === 0) {
    flash_set('danger', 'ID da turma inválido.');
    header('Location: turmas_view.php');
    exit;
}

// CORRIGIDO: A consulta agora busca TODAS as colunas necessárias, incluindo id_modulo_atual
$stmt = $pdo->prepare('SELECT id_turma, id_curso, nome, ano, semestre, turno, status, id_modulo_atual FROM turma WHERE id_turma = ?');
$stmt->execute([$id_turma]);
$turma = $stmt->fetch(PDO::FETCH_ASSOC); // Usando FETCH_ASSOC para garantir a consistência

if (!$turma) {
    flash_set('danger', 'Turma não encontrada.');
    header('Location: turmas_view.php');
    exit;
}

// Busca a lista de módulos disponíveis para o curso desta turma
$stmt_modulos = $pdo->prepare("SELECT id_modulo, nome FROM modulo WHERE id_curso = ? ORDER BY ordem ASC");
$stmt_modulos->execute([$turma['id_curso']]);
$modulos_disponiveis = $stmt_modulos->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$id_curso = $turma['id_curso'];
$nome = $turma['nome'];
$ano = $turma['ano'];
$semestre = $turma['semestre'];
$turno = $turma['turno'];
$status = $turma['status'];
$id_modulo_atual = $turma['id_modulo_atual'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura dados do formulário
    $id_curso   = $turma['id_curso'];
    $ano   = (int)($_POST['ano'] ?? 0);
    $semestre   = trim($_POST['semestre'] ?? '');
    $nome   = trim($_POST['nome'] ?? '');
    $turno   = trim($_POST['turno'] ?? '');
    $status   = trim($_POST['status'] ?? '');
    $id_modulo_atual = (int)($_POST['id_modulo_atual'] ?? 0);

    // --- Validações ---
    if ($ano === 0) $errors[] = 'Ano é obrigatório.';
    if ($semestre === '') $errors[] = 'Semestre é obrigatório.';
    if ($nome === '') $errors[] = 'Nome da turma é obrigatório.';
    if ($turno === '') $errors[] = 'Turno é obrigatório.';
    if ($status === '') $errors[] = 'Status é obrigatório.';

    // Checagem de unicidade no banco
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM turma WHERE id_curso = ? AND ano = ? AND semestre = ? AND turno = ? AND id_turma != ?");
        $stmt->execute([$id_curso, $ano, $semestre, $turno, $id_turma]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Já existe uma turma cadastrada para este curso, ano, semestre e turno.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('UPDATE turma SET nome=?, ano=?, semestre=?, turno=?, status=?, id_modulo_atual=? WHERE id_turma=?');
            // Mantém a lógica de salvar NULL se for 0
            $id_modulo_para_salvar = $id_modulo_atual > 0 ? $id_modulo_atual : null;

            // O id_curso não está no SET, pois não o alteramos.
            $stmt->execute([$nome, $ano, $semestre, $turno, $status, $id_modulo_para_salvar, $id_turma]);

            flash_set('success', 'Turma atualizada com sucesso.');
            // Redireciona de volta para a view do curso
            header('Location: turmas_view.php?id_curso=' . $id_curso);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erro ao salvar: ' . $e->getMessage();
        }
    }
}

$cursos = $pdo->query("SELECT id_curso, nome FROM curso ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../partials/admin_header.php';
?>

<div class="main">
    <div class="content mt-5">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Editar Turma #<?php echo (int)$turma['id_turma']; ?></h2>
                        <a class="btn btn-outline-secondary btn-sm" href="turmas_view.php?id_curso=<?php echo (int)$turma['id_curso']; ?>">Voltar</a>
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
                                        <select name="id_curso" id="id_curso" class="form-select" required disabled>
                                            <option value="" disabled>Selecione um curso...</option>
                                            <?php foreach ($cursos as $curso): ?>
                                                <option value="<?php echo (int)$curso['id_curso']; ?>"
                                                    <?php echo ((int)$curso['id_curso'] === $id_curso ? 'selected' : ''); ?>>
                                                    <?php echo htmlspecialchars($curso['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">O curso de uma turma não pode ser alterado após a criação.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="ano" class="form-label">Ano</label>
                                        <select name="ano" id="ano" class="form-select" required>
                                            <option value="" disabled>Selecione o ano</option>
                                            <?php
                                            // CORRIGIDO: Lógica robusta para garantir que o ano da turma apareça
                                            $ano_atual = date('Y');
                                            $ano_turma = (int)$turma['ano'];
                                            $anos_disponiveis = [];

                                            for ($i = $ano_atual - 2; $i <= $ano_atual + 1; $i++) {
                                                $anos_disponiveis[] = $i;
                                            }

                                            if (!in_array($ano_turma, $anos_disponiveis)) {
                                                $anos_disponiveis[] = $ano_turma;
                                            }
                                            sort($anos_disponiveis);

                                            foreach ($anos_disponiveis as $a):
                                            ?>
                                                <option value="<?php echo $a; ?>" <?php echo ($a == $ano ? 'selected' : ''); ?>>
                                                    <?php echo $a; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="semestre" class="form-label">Semestre</label>
                                        <select name="semestre" id="semestre" class="form-select" required>
                                            <option value="" disabled>Selecione o semestre</option>
                                            <option value="1" <?php echo ($semestre === '1' ? 'selected' : ''); ?>>1º Semestre</option>
                                            <option value="2" <?php echo ($semestre === '2' ? 'selected' : ''); ?>>2º Semestre</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="turno" class="form-label">Turno</label>
                                        <select name="turno" id="turno" class="form-select" required>
                                            <option value="" disabled>Selecione o turno</option>
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

                                    <div class="col-12">
                                        <label for="id_modulo_atual" class="form-label">Módulo Atual (Opcional)</label>
                                        <select name="id_modulo_atual" id="id_modulo_atual" class="form-select">
                                            <option value="0">-- Nenhum / Não Aplicável --</option>
                                            <?php foreach ($modulos_disponiveis as $modulo): ?>
                                                <option value="<?php echo (int)$modulo['id_modulo']; ?>"
                                                    <?php echo ((int)$modulo['id_modulo'] === (int)$id_modulo_atual ? 'selected' : ''); ?>>
                                                    <?php echo htmlspecialchars($modulo['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Define em qual módulo esta turma está atualmente (para controle de progresso).</div>
                                    </div>

                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="turmas_view.php?id_curso=<?php echo (int)$turma['id_curso']; ?>" class="btn btn-secondary">Cancelar</a>
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
                                <strong>Excluir esta turma</strong>
                                <p class="mb-sm-0 text-muted small">Uma vez excluída, a turma não poderá ser recuperada. Esta ação é permanente.</p>
                            </div>

                            <button type="button" class="btn btn-danger w-100 w-sm-auto mt-2 mt-sm-0 delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#confirmDeleteModal"
                                data-form-action="turmas_delete.php"
                                data-item-id="<?php echo (int)$turma['id_turma']; ?>"
                                data-item-name="<?php echo htmlspecialchars($turma['nome']); ?>"
                                data-id-field="id_turma">
                                <i class="bi bi-trash-fill"></i> Excluir Turma
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../partials/footer.php'; ?>