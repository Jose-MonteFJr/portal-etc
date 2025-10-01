<?php
//Inclui o arquivo de proteção, só loga se for um usuário cadastrado
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

if ($_SESSION['tipo'] !== 'aluno') {
    flash_set('danger', 'Acesso negado. Apenas alunos podem criar solicitações.');
    // Redireciona para a página inicial do perfil do usuário logado (ex: admin.php ou user.php)
    header('Location: ' . ($_SESSION['tipo'] === 'secretaria' ? '../admin.php' : '../user.php'));
    exit;
}

$errors = [];
$tipo = $observacao = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Captura os dados do formulário
    $tipo       = trim($_POST['tipo'] ?? '');
    $observacao = trim($_POST['observacao'] ?? '');

    // 2. Validação dos dados
    $tipos_validos = ['renovação de matrícula', 'emissão de diploma', 'emissão de certificado'];
    if (empty($tipo)) {
        $errors[] = 'O tipo da solicitação é obrigatório.';
    } elseif (!in_array($tipo, $tipos_validos)) {
        $errors[] = 'Tipo de solicitação inválido.';
    }

    // 3. Se não houver erros de validação, prossegue para salvar
    if (empty($errors)) {
        try {
            // Busca o id_aluno correspondente ao id_usuario da sessão
            $stmt_aluno = $pdo->prepare("SELECT id_aluno FROM aluno WHERE id_usuario = ?");
            $stmt_aluno->execute([$_SESSION['id_usuario']]);
            $id_aluno = $stmt_aluno->fetchColumn();

            if (!$id_aluno) {
                // Situação rara, mas é uma boa segurança
                throw new Exception("Perfil de aluno não encontrado para este usuário.");
            }

            // 4. Insere a solicitação no banco de dados
            $stmt = $pdo->prepare(
                "INSERT INTO solicitacao (id_aluno, tipo, observacao) VALUES (?, ?, ?)"
            );

            $stmt->execute([$id_aluno, $tipo, $observacao]);

            flash_set('success', 'Sua solicitação foi enviada com sucesso!');
            header('Location: solicitacoes_view_aluno.php'); // Redireciona para a lista de solicitações
            exit;

        } catch (Exception $e) {
            $errors[] = 'Erro ao salvar a solicitação: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/partials/portal_header.php';
?>

        <!-- Conteúdo principal -->
        <div class="main">
            <div class="content">
                <h1 class="text-3xl font-bold">Nova solicitação</h1>
            </div>

            <div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Nova Solicitação</h2>
                <a class="btn btn-outline-secondary btn-sm" href="solicitacoes_view_aluno.php">Minhas Solicitações</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" action="">
                        <?php csrf_input(); ?>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Solicitação</label>
                            <select name="tipo" id="tipo" class="form-select" required>
                                <option value="" disabled <?php echo empty($tipo) ? 'selected' : ''; ?>>Selecione o que você deseja solicitar...</option>
                                <option value="renovação de matrícula" <?php echo ($tipo === 'renovação de matrícula' ? 'selected' : ''); ?>>Renovação de Matrícula</option>
                                <option value="emissão de diploma" <?php echo ($tipo === 'emissão de diploma' ? 'selected' : ''); ?>>Emissão de Diploma</option>
                                <option value="emissão de certificado" <?php echo ($tipo === 'emissão de certificado' ? 'selected' : ''); ?>>Emissão de Certificado</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacao" class="form-label">Observações (Opcional)</label>
                            <textarea name="observacao" id="observacao" class="form-control" rows="5" placeholder="Se necessário, adicione aqui qualquer informação relevante para a sua solicitação."><?php echo htmlspecialchars($observacao); ?></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

        </div>
    </div>

<?php include __DIR__ . '/partials/footer.php'; ?>