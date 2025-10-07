<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

$errors = [];
$id_usuario_logado = $_SESSION['id_usuario'];

// Lógica para processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Captura as senhas do formulário
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_nova_senha = $_POST['confirmar_nova_senha'] ?? '';

    // 2. Validações Essenciais
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_nova_senha)) {
        $errors[] = "Todos os campos de senha são obrigatórios.";
    } elseif ($nova_senha !== $confirmar_nova_senha) {
        $errors[] = "A nova senha e a confirmação não coincidem.";
    } elseif (strlen($nova_senha) < 8) {
        $errors[] = "A nova senha deve ter no mínimo 8 caracteres.";
    }

    // 3. Verifica se a senha atual está correta
    if (empty($errors)) {
        // Busca o hash da senha atual no banco de dados
        $stmt = $pdo->prepare("SELECT password_hash FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$id_usuario_logado]);
        $user = $stmt->fetch();

        // Compara a senha digitada com o hash salvo usando password_verify()
        if (!$user || !password_verify($senha_atual, $user['password_hash'])) {
            $errors[] = "A senha atual informada está incorreta.";
        }
    }

    // 4. Se tudo estiver certo, atualiza a senha no banco
    if (empty($errors)) {
        try {
            // CRIA UM NOVO HASH SEGURO para a nova senha
            $novo_password_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            // ATUALIZA o banco de dados com o novo hash
            $stmt = $pdo->prepare("UPDATE usuario SET password_hash = ? WHERE id_usuario = ?");
            $stmt->execute([$novo_password_hash, $id_usuario_logado]);

            flash_set('success', 'Sua senha foi alterada com sucesso!');
            header('Location: perfil_aluno.php'); // Redireciona de volta para o perfil
            exit;

        } catch (PDOException $e) {
            $errors[] = "Erro ao alterar a senha: " . $e->getMessage();
        }
    }
}


include __DIR__ . '/partials/portal_header.php';
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Alterar Senha</h2>
                        <a class="btn btn-outline-secondary btn-sm" href="perfil_aluno.php">Voltar ao Perfil</a>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div>
                    <?php endif; ?>

                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="post" action="/portal-etc/alterar_senha.php">
                                <?php csrf_input(); ?>
                                
                                <div class="mb-3">
                                    <label for="senha_atual" class="form-label">Senha Atual</label>
                                    <input type="password" name="senha_atual" id="senha_atual" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nova_senha" class="form-label">Nova Senha</label>
                                    <input type="password" name="nova_senha" id="nova_senha" class="form-control" required>
                                    <div class="form-text">A senha deve ter no mínimo 8 caracteres.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirmar_nova_senha" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" name="confirmar_nova_senha" id="confirmar_nova_senha" class="form-control" required>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
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