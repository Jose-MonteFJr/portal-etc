<?php
//Inclui o arquivo de proteção, só loga se for um usuário cadastrado
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';

if ($_SESSION['tipo'] !== 'aluno') {
    flash_set('danger', 'Acesso negado. Apenas alunos podem criar solicitações.');
    // Redireciona para a página inicial do perfil do usuário logado (ex: admin.php ou user.php)
    header('Location: ' . ($_SESSION['tipo'] === 'secretaria' ? '../admin.php' : '../portal_home.php'));
    exit;
}

$errors = [];
$tipo = $observacao = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Captura os dados do formulário
    $tipo       = trim($_POST['tipo'] ?? '');
    $observacao = trim($_POST['observacao_aluno'] ?? '');

    // 2. Validação dos dados
    $tipos_validos = ['emissão de diploma', 'emissão de certificado'];
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
                "INSERT INTO solicitacao (id_aluno, tipo, observacao_aluno) VALUES (?, ?, ?)"
            );

            $stmt->execute([$id_aluno, $tipo, $observacao]);

            flash_set('success', 'Sua solicitação foi enviada com sucesso!');
            header('Location: solicitacoes_view_aluno.php');
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Erro ao salvar a solicitação: ' . $e->getMessage();
        }
    }
}

include '../partials/portal_header.php';
?>

<!-- Conteúdo principal -->
<div class="main">
    <div class="content">

        <div class="page-container">
            <div class="page-header">
                <h1>Nova Solicitação</h1>
                <a class="header-link" href="solicitacoes_view_aluno.php">Minhas Solicitações</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <ul>
                        <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="post" onsubmit="return confirm('Tem certeza que deseja enviar a solicitação?');">
                    <?php csrf_input(); ?>

                    <div class="form-group">
                        <label for="tipo">Tipo de Solicitação</label>
                        <select name="tipo" id="tipo" class="form-input" required>
                            <option value="" disabled <?php echo empty($tipo) ? 'selected' : ''; ?>>Selecione o que você deseja solicitar...</option>
                            <option value="emissão de diploma" <?php echo ($tipo === 'emissão de diploma' ? 'selected' : ''); ?>>Emissão de Diploma</option>
                            <option value="emissão de certificado" <?php echo ($tipo === 'emissão de certificado' ? 'selected' : ''); ?>>Emissão de Certificado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="observacao_aluno">Observações (Opcional)</label>
                        <textarea name="observacao_aluno" id="observacao_aluno" class="form-input" rows="5" placeholder="Se necessário, adicione aqui qualquer informação relevante..."><?php echo htmlspecialchars($observacao); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn">Enviar Solicitação</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>


<?php include '../partials/footer.php'; ?>