<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

$id_solicitacao = (int)($_GET['id_solicitacao'] ?? 0);
if ($id_solicitacao === 0) {
    flash_set('danger', 'ID da solicitação inválido.');
    header('Location: solicitacoes_view_admin.php');
    exit;
}

$sql = "SELECT s.*, u.nome_completo AS nome_aluno 
              FROM solicitacao s
              JOIN aluno a ON s.id_aluno = a.id_aluno
              JOIN usuario u ON a.id_usuario = u.id_usuario
              WHERE s.id_solicitacao = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_solicitacao]);
$solicitacao = $stmt->fetch();

if (!$solicitacao) {
    flash_set('danger', 'Solicitação não encontrada.');
    header('Location: solicitacoes_view_admin.php');
    exit;
}

// Inicializa variáveis para o formulário com os dados do banco
$errors = [];
$status_atual = $solicitacao['status'];
$observacao_atual = $solicitacao['observacao'];

// 2. Lógica para processar a ATUALIZAÇÃO (quando o formulário é enviado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura os novos dados do formulário
    $novo_status    = trim($_POST['status'] ?? '');
    $nova_observacao = trim($_POST['observacao'] ?? '');

    // Validação
    $status_validos = ['pendente', 'em análise', 'aprovada', 'rejeitada', 'concluída'];
    if (!in_array($novo_status, $status_validos)) {
        $errors[] = 'Status inválido selecionado.';
    }

    if (empty($errors)) {
        try {
            // Prepara e executa a consulta UPDATE
            $stmt = $pdo->prepare(
                "UPDATE solicitacao 
                 SET status = ?, observacao = ? 
                 WHERE id_solicitacao = ?"
            );
            $stmt->execute([$novo_status, $nova_observacao, $id_solicitacao]);

            flash_set('success', 'Solicitação atualizada com sucesso!');
            header('Location: solicitacoes_view_admin.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = "Erro ao atualizar a solicitação: " . $e->getMessage();
        }
    }
}

include '../partials/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Analisar Solicitação #<?php echo (int)$solicitacao['id_solicitacao']; ?></h2>
                <a class="btn btn-outline-secondary btn-sm" href="solicitacoes_view_admin.php">Voltar para a Lista</a>
            </div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?>
    </ul>
  </div>
<?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header">Detalhes do Pedido</div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Aluno:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($solicitacao['nome_aluno']); ?></dd>

                        <dt class="col-sm-3">Tipo:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars(ucwords($solicitacao['tipo'])); ?></dd>

                        <dt class="col-sm-3">Data do Pedido:</dt>
                        <dd class="col-sm-9"><?php echo date('d/m/Y à\s H:i', strtotime($solicitacao['created_at'])); ?></dd>
                        
                        <dt class="col-sm-3">Observação do Aluno:</dt>
                        <dd class="col-sm-9">
                            <p class="mb-0 fst-italic">"<?php echo nl2br(htmlspecialchars($solicitacao['observacao'] ?? 'Nenhuma observação fornecida.')); ?>"</p>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header fw-bold">Ação da Secretaria</div>
                <div class="card-body">
                    <form method="post">
                        <?php csrf_input(); ?>

                        <div class="mb-3">
                            <label for="status" class="form-label">Alterar Status</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="pendente" <?php echo ($status_atual === 'pendente' ? 'selected' : ''); ?>>Pendente</option>
                                <option value="em análise" <?php echo ($status_atual === 'em análise' ? 'selected' : ''); ?>>Em Análise</option>
                                <option value="aprovada" <?php echo ($status_atual === 'aprovada' ? 'selected' : ''); ?>>Aprovada</option>
                                <option value="rejeitada" <?php echo ($status_atual === 'rejeitada' ? 'selected' : ''); ?>>Rejeitada</option>
                                <option value="concluída" <?php echo ($status_atual === 'concluída' ? 'selected' : ''); ?>>Concluída</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observacao" class="form-label">Observação / Justificativa (Opcional)</label>
                            <textarea name="observacao" id="observacao" class="form-control" rows="4" 
                                      placeholder="Se necessário, adicione uma observação para o aluno. Ex: Documentação aprovada."><?php echo htmlspecialchars($observacao_atual); ?></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Atualizar Solicitação</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>