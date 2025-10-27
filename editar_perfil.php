<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

// Pega o ID do usuário que está logado
$id_usuario_logado = $_SESSION['id_usuario'];
$tipo_usuario = $_SESSION['tipo'];

// ==========================================================
// == INÍCIO DAS ALTERAÇÕES PARA REUTILIZAÇÃO ==
// ==========================================================

// 1. Define os caminhos dinâmicos com base no tipo de usuário
$link_perfil = 'admin.php'; // Fallback
$header_path = __DIR__ . '/partials/admin_header.php'; // Fallback

if ($tipo_usuario === 'aluno') {
    $link_perfil = 'perfil_aluno.php';
    $header_path = __DIR__ . '/partials/portal_header.php';
} elseif ($tipo_usuario === 'secretaria') {
    $link_perfil = 'perfil_secretaria.php';
    $header_path = __DIR__ . '/partials/admin_header.php';
}
// Adicione outros tipos (professor, etc.) aqui se necessário

// ==========================================================
// == FIM DAS ALTERAÇÕES ==
// ==========================================================


// 1. BUSCA OS DADOS ATUAIS DO USUÁRIO E ENDEREÇO PARA PREENCHER O FORMULÁRIO
$sql_busca = "SELECT u.nome_completo, u.email, u.telefone,
                     e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep, e.id_endereco
              FROM usuario u
              LEFT JOIN endereco e ON u.id_usuario = e.id_usuario
              WHERE u.id_usuario = ?";

$stmt = $pdo->prepare($sql_busca);
$stmt->execute([$id_usuario_logado]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    flash_set('danger', 'Usuário não encontrado.');
    header('Location: ' . $link_perfil); // CORRIGIDO: Link dinâmico
    exit;
}

// Inicializa variáveis com os dados do banco para a primeira exibição
$errors = [];
$nome = $usuario['nome_completo'];
$email = $usuario['email'];
$telefone = $usuario['telefone'];
$logradouro = $usuario['logradouro'] ?? '';
$numero = $usuario['numero'] ?? '';
$complemento = $usuario['complemento'] ?? '';
$bairro = $usuario['bairro'] ?? '';
$cidade = $usuario['cidade'] ?? '';
$estado = $usuario['estado'] ?? '';
$cep = $usuario['cep'] ?? '';

// 2. LÓGICA PARA PROCESSAR O FORMULÁRIO (QUANDO ENVIADO VIA POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura os dados submetidos (e sobrescreve as variáveis para repopulação)
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $logradouro = trim($_POST['logradouro'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $cep = trim($_POST['cep'] ?? '');

    // Validações
    if (empty($nome)) $errors[] = "O nome é obrigatório.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "O e-mail informado é inválido.";

    // Verifica se o novo e-mail já está em uso por OUTRO usuário
    $stmt_email = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE email = ? AND id_usuario != ?");
    $stmt_email->execute([$email, $id_usuario_logado]);
    if ($stmt_email->fetchColumn() > 0) {
        $errors[] = "Este e-mail já está em uso por outra conta.";
    }

    if (empty($errors)) {
        // 3. USA UMA TRANSAÇÃO PARA ATUALIZAR AS DUAS TABELAS COM SEGURANÇA
        $pdo->beginTransaction();
        try {
            // Atualiza a tabela 'usuario'
            $stmt_user = $pdo->prepare("UPDATE usuario SET nome_completo = ?, email = ?, telefone = ? WHERE id_usuario = ?");
            $stmt_user->execute([$nome, $email, $telefone, $id_usuario_logado]);

            // Lógica de "UPSERT" para o endereço (atualiza se existe, insere se não existe)
            if ($usuario['id_endereco']) { // Se já existia um endereço, atualiza
                $stmt_addr = $pdo->prepare("UPDATE endereco SET logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, estado=?, cep=? WHERE id_usuario = ?");
                $stmt_addr->execute([$logradouro, $numero, $complemento, $bairro, $cidade, $estado, $cep, $id_usuario_logado]);
            } else { // Se não existia, insere um novo
                $stmt_addr = $pdo->prepare("INSERT INTO endereco (id_usuario, logradouro, numero, complemento, bairro, cidade, estado, cep) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_addr->execute([$id_usuario_logado, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $cep]);
            }

            // Se tudo deu certo, confirma as alterações
            $pdo->commit();

            // Atualiza o nome na sessão para refletir a mudança imediatamente
            $_SESSION['nome_completo'] = $nome;

            flash_set('success', 'Dados atualizados com sucesso!');
            header('Location: ' . $link_perfil);
            exit;
        } catch (Exception $e) {
            // Se algo deu errado, desfaz tudo
            $pdo->rollBack();
            $errors[] = "Erro ao salvar os dados: " . $e->getMessage();
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
                            <i class="bi bi-person-vcard-fill fs-4 text-primary"></i>
                            <h2 class="h4 mb-0">Editar Meus Dados</h2>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?php echo ($tipo_usuario === 'aluno') ? 'portal_home.php' : 'admin.php'; ?>">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($link_perfil); ?>">Meu Perfil</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Editar</li>
                            </ol>
                        </nav>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="editar_perfil.php" onsubmit="return confirm('Tem certeza que deseja salvar as alterações?');">
                        <?php csrf_input(); ?>
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">Dados Pessoais</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <label for="nome" class="form-label">Nome Completo</label>
                                        <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($nome); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">E-mail</label>
                                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telefone" class="form-label">Telefone</label>
                                        <input type="text" name="telefone" id="telefone" class="form-control" value="<?php echo htmlspecialchars($telefone); ?>" maxlength="15">
                                    </div>
                                </div>

                                <h5 class="mt-4">Endereço</h5>
                                <hr>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="cep" class="form-label">CEP</label>
                                        <div class="input-group">
                                            <input type="text" name="cep" id="cep" class="form-control" value="<?php echo htmlspecialchars($cep); ?>" maxlength="9">
                                            <span class="input-group-text" id="spinner" style="display: none;">
                                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            </span>
                                        </div>
                                        <div id="cep-error" class="text-danger small mt-1"></div>
                                    </div>

                                    <div class="col-md-8">
                                        <label for="logradouro" class="form-label">Logradouro</label>
                                        <input type="text" name="logradouro" id="logradouro" class="form-control" value="<?php echo htmlspecialchars($logradouro); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="numero" class="form-label">Número</label>
                                        <input type="text" name="numero" id="numero" class="form-control" value="<?php echo htmlspecialchars($numero); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <label for="complemento" class="form-label">Complemento</label>
                                        <input type="text" name="complemento" id="complemento" class="form-control" value="<?php echo htmlspecialchars($complemento); ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <label for="bairro" class="form-label">Bairro</label>
                                        <input type="text" name="bairro" id="bairro" class="form-control" value="<?php echo htmlspecialchars($bairro); ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <label for="cidade" class="form-label">Cidade</label>
                                        <input type="text" name="cidade" id="cidade" class="form-control" value="<?php echo htmlspecialchars($cidade); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="estado" class="form-label">Estado</label>
                                        <input type="text" name="estado" id="estado" class="form-control" value="<?php echo htmlspecialchars($estado); ?>" maxlength="2">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <a href="<?php echo htmlspecialchars($link_perfil); ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Máscara para telefone
        const telInput = document.querySelector('input[name="telefone"]');
        if (telInput) {
            telInput.addEventListener('input', function(e) {
                let v = this.value.replace(/\D/g, '');
                if (v.length > 10) {
                    v = v.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
                } else {
                    v = v.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                }
                this.value = v;
            });
        }

        // Máscara e API para CEP
        const cepInput = document.getElementById("cep");
        if (cepInput) {
            const preencherFormulario = (endereco) => {
                document.getElementById("logradouro").value = endereco.logradouro;
                document.getElementById("bairro").value = endereco.bairro;
                document.getElementById("cidade").value = endereco.localidade;
                document.getElementById("estado").value = endereco.uf;
            };

            const pesquisarCep = async () => {
                const spinner = document.getElementById("spinner");
                const cepError = document.getElementById("cep-error");
                spinner.style.display = 'inline-block';
                cepError.textContent = "";

                const cep = cepInput.value.replace(/\D/g, "");

                if (cep.length === 8) {
                    const url = `https://viacep.com.br/ws/${cep}/json/`;
                    try {
                        const response = await fetch(url);
                        const endereco = await response.json();
                        if (endereco.hasOwnProperty("erro")) {
                            cepError.textContent = "CEP não encontrado!";
                        } else {
                            preencherFormulario(endereco);
                        }
                    } catch (error) {
                        cepError.textContent = "Erro ao buscar o CEP.";
                    }
                } else if (cep.length > 0) {
                    cepError.textContent = "CEP incompleto.";
                }
                spinner.style.display = 'none';
            };

            cepInput.addEventListener("input", function() {
                this.value = this.value.replace(/\D/g, "").replace(/^(\d{5})(\d)/, "$1-$2");
            });

            cepInput.addEventListener("blur", pesquisarCep);
        }
    });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>