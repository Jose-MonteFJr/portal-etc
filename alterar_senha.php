<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

$errors = [];
$id_usuario_logado = $_SESSION['id_usuario'];
$tipo_usuario = $_SESSION['tipo'];

// 1. Define os caminhos dinâmicos com base no tipo de usuário
$link_perfil = 'admin.php'; // Fallback
$header_path = __DIR__ . '/partials/admin_header.php'; // Fallback
$dashboard_link = 'admin.php'; // Fallback

if ($tipo_usuario === 'aluno') {
    $link_perfil = 'perfil_aluno.php';
    $header_path = __DIR__ . '/partials/portal_header.php';
    $dashboard_link = 'portal_home.php';
} elseif ($tipo_usuario === 'secretaria') {
    $link_perfil = 'perfil_secretaria.php';
    // header_path e dashboard_link já estão corretos
}
// Adicione outros tipos (professor, etc.) aqui se necessário

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
    } else {
        // Validações de Força (Regex)
        if (strlen($nova_senha) < 8) {
            $errors[] = "A nova senha deve ter no mínimo 8 caracteres.";
        }
        if (!preg_match('/[A-Z]/', $nova_senha)) {
            $errors[] = "A nova senha deve ter pelo menos uma letra maiúscula (A-Z).";
        }
        if (!preg_match('/[a-z]/', $nova_senha)) {
            $errors[] = "A nova senha deve ter pelo menos uma letra minúscula (a-z).";
        }
        if (!preg_match('/[0-9]/', $nova_senha)) {
            $errors[] = "A nova senha deve ter pelo menos um número (0-9).";
        }
        if (!preg_match('/[\W_]/', $nova_senha)) { // \W = Símbolo, _ = Underscore
            $errors[] = "A nova senha deve ter pelo menos um símbolo (@, #, $...).";
        }
    }

    // 3. Verifica se a senha atual está correta
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT password_hash FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$id_usuario_logado]);
        $user = $stmt->fetch();

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
            header('Location: ' . $link_perfil); // Redireciona de volta para o perfil
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erro ao alterar a senha: " . $e->getMessage();
        }
    }
}


include $header_path;
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-key-fill fs-4 text-warning"></i>
                            <h2 class="h4 mb-0">Alterar Senha</h2>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($dashboard_link); ?>">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($link_perfil); ?>">Meu Perfil</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Alterar Senha</li>
                            </ol>
                        </nav>
                    </div>

                    <?php flash_show(); ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="alterar_senha.php">
                        <?php csrf_input(); ?>
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">Dados de Acesso</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label for="senha_atual" class="form-label">Senha Atual</label>
                                    <input type="password" name="senha_atual" id="senha_atual" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="nova_senha" class="form-label">Nova Senha</label>
                                    <input type="password" name="nova_senha" id="nova_senha" class="form-control" required>

                                    <div class="mt-2">
                                        <div class="progress" style="height: 5px;">
                                            <div id="nova-senha-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                        </div>
                                        <span id="nova-senha-strength-text" class="form-text small">
                                            Mínimo 8 caracteres, 1 maiúscula (A-Z), 1 minúscula (a-z), 1 número (0-9) e 1 símbolo (@, #, $...).
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmar_nova_senha" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" name="confirmar_nova_senha" id="confirmar_nova_senha" class="form-control" required>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <a href="<?php echo htmlspecialchars($link_perfil); ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/portal-etc/partials/js/password-strength.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializa o medidor de força nos elementos corretos desta página
        initializePasswordStrength(
            'nova_senha', // O ID do input de senha
            'nova-senha-strength-bar', // O ID da barra
            'nova-senha-strength-text' // O ID do texto
        );
    });
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>