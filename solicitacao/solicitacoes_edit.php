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

$sql = "SELECT s.*, u.nome_completo AS nome_aluno, u.id_usuario  
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
$observacao_atual_secretaria = $solicitacao['observacao_secretaria'];
$observacao_atual_aluno = $solicitacao['observacao_aluno'];

// 2. Lógica para processar a ATUALIZAÇÃO (quando o formulário é enviado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura os novos dados do formulário
    $novo_status    = trim($_POST['status'] ?? '');
    $nova_observacao = trim($_POST['observacao_secretaria'] ?? '');
    $nome_arquivo_final = $solicitacao['caminho_arquivo']; // Mantém o arquivo antigo por padrão

    // --- LÓGICA DE UPLOAD DO ARQUIVO ---
    // Verifica se um arquivo foi enviado sem erros
    if (isset($_FILES['arquivo_pdf']) && $_FILES['arquivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        $file_info = pathinfo($_FILES["arquivo_pdf"]["name"]);
        $file_extension = strtolower($file_info['extension']);

        // Validação do arquivo
        if ($file_extension !== 'pdf') {
            $errors[] = "Apenas arquivos PDF são permitidos.";
        } elseif ($_FILES["arquivo_pdf"]["size"] > 5000000) { // Limite de 5MB
            $errors[] = "O arquivo é muito grande (limite de 5MB).";
        } else {
            // SEGURANÇA: Cria um nome de arquivo único para evitar substituições e caracteres inválidos
            $nome_arquivo_final = 'solicitacao_' . $id_solicitacao . '_' . time() . '.pdf';
            $target_file = $target_dir . $nome_arquivo_final;

            if (!move_uploaded_file($_FILES["arquivo_pdf"]["tmp_name"], $target_file)) {
                $errors[] = "Ocorreu um erro ao mover o arquivo enviado.";
                $nome_arquivo_final = $solicitacao['caminho_arquivo']; // Reverte para o arquivo antigo em caso de erro
            }
        }
    }

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
                 SET status = ?, observacao_secretaria = ?, caminho_arquivo = ? 
                 WHERE id_solicitacao = ?"
            );
            $stmt->execute([$novo_status, $nova_observacao, $nome_arquivo_final, $id_solicitacao]);

            // --- <<< INÍCIO DO NOVO BLOCO DE NOTIFICAÇÃO INTELIGENTE >>> ---

            // 1. Obter o id_usuario do aluno para quem enviaremos a notificação
            $stmt_user = $pdo->prepare("SELECT id_usuario FROM aluno WHERE id_aluno = ?");
            $stmt_user->execute([$solicitacao['id_aluno']]);
            $id_usuario_destino = $stmt_user->fetchColumn();

            if ($id_usuario_destino) {
                // 2. Definir as categorias de solicitação (mesma lógica da listagem)
                $tipos_documento = ['emissão de certificado', 'emissão de diploma'];
                $tipos_matricula = ['renovação de matrícula', 'trancamento de matrícula'];

                // 3. Determinar o link e a mensagem com base no TIPO da solicitação
                $tipo_solicitacao = $solicitacao['tipo'];
                $link_destino = '/portal-etc/solicitacao/solicitacoes_view_aluno.php'; // Link padrão de fallback

                if (in_array($tipo_solicitacao, $tipos_documento)) {
                    $link_destino = '/portal-etc/solicitacao/solicitacoes_view_aluno.php';
                } elseif (in_array($tipo_solicitacao, $tipos_matricula)) {
                    $link_destino = '/portal-etc/solicitacao/solicitacoes_view_aluno_matricula.php';
                }

                // 4. (Bônus) Criar uma mensagem mais descritiva
                $mensagem = sprintf(
                    "Sua solicitação de %s foi atualizada para %s.",
                    ($tipo_solicitacao), // Ex: "Certificado"
                    ($novo_status)       // Ex: "Aprovada"
                );

                // 5. Chamar a função para criar a notificação com os dados dinâmicos
                criar_notificacao($pdo, $id_usuario_destino, $mensagem, $link_destino);
            }

            // --- <<< FIM DO NOVO BLOCO DE NOTIFICAÇÃO >>> ---

            flash_set('success', 'Solicitação atualizada e aluno notificado com sucesso!');
            header('Location: solicitacoes_view_admin.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erro ao atualizar a solicitação: " . $e->getMessage();
        }
    }
}

include '../partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0">Analisar Solicitação #<?php echo (int)$solicitacao['id_solicitacao']; ?></h2>
            <a class="btn btn-outline-secondary btn-sm" href="solicitacoes_view_admin.php">Voltar para a Lista</a>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
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
                        <p class="mb-0 fst-italic">"<?php echo nl2br(htmlspecialchars($observacao_atual_aluno ?? 'Nenhuma observação fornecida.')); ?>"</p>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-bold">Ação da Secretaria</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
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
                        <label for="arquivo_pdf" class="form-label">Anexar Documento (PDF)</label>
                        <input class="form-control" type="file" id="arquivo_pdf" name="arquivo_pdf" accept=".pdf">

                        <?php if (!empty($solicitacao['caminho_arquivo'])): ?>
                            <div class="form-text mt-2">
                                Arquivo atual:
                                <a href="../uploads/<?php echo htmlspecialchars($solicitacao['caminho_arquivo']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($solicitacao['caminho_arquivo']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="observacao_secretaria" class="form-label">Observação / Justificativa (Opcional)</label>
                        <textarea name="observacao_secretaria" id="observacao_secretaria" class="form-control" rows="4"
                            placeholder="Se necessário, adicione uma observação para o aluno. Ex: Documentação aprovada."><?php echo htmlspecialchars($observacao_atual_secretaria); ?></textarea>
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