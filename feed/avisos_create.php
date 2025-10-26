<?php
require     '../protect.php';
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

$errors = [];
// Inicializa variáveis para repopular o formulário em caso de erro
$titulo = '';
$descricao = '';

// --- LÓGICA DO BACK-END (Processa o envio do formulário) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Captura os dados de texto
    $titulo    = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    // 2. Validações básicas
    if (empty($titulo)) {
        $errors[] = "O título do aviso é obrigatório.";
    }
    if (empty($descricao)) {
        $errors[] = "A descrição do aviso é obrigatória.";
    }

    $nome_arquivo_final = null; // Inicia como nulo

    // 3. LÓGICA DE UPLOAD DA IMAGEM (se um arquivo foi enviado)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {

        $target_dir = "../uploads/avisos/";
        $file_info = pathinfo($_FILES["imagem"]["name"]);
        $file_extension = strtolower($file_info['extension']);
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        // Validações do arquivo
        $check = getimagesize($_FILES["imagem"]["tmp_name"]);
        if ($check === false) {
            $errors[] = 'O arquivo enviado não é uma imagem válida.';
        } elseif (!in_array($file_extension, $allowed_types)) {
            $errors[] = 'Apenas imagens JPG, JPEG, PNG e GIF são permitidas.';
        } elseif ($_FILES["imagem"]["size"] > 2000000) { // Limite de 2MB
            $errors[] = 'A imagem é muito grande (limite de 2MB).';
        }

        // Se o arquivo for válido, move para a pasta de uploads
        if (empty($errors)) {
            // Cria um nome de arquivo único e seguro
            $nome_arquivo_final = 'aviso_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $nome_arquivo_final;

            if (!move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file)) {
                $errors[] = "Ocorreu um erro ao salvar a imagem no servidor.";
                $nome_arquivo_final = null; // Reseta em caso de erro
            }
        }
    }

    // 4. Se não houver nenhum erro (nem de validação, nem de upload)
    if (empty($errors)) {
        try {
            // Insere o aviso no banco de dados
            $stmt = $pdo->prepare(
                "INSERT INTO aviso (id_usuario_autor, titulo, descricao, caminho_imagem) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $_SESSION['id_usuario'], // O ID do admin logado
                $titulo,
                $descricao,
                $nome_arquivo_final // Será o nome do arquivo ou NULL
            ]);

            // =============================================================
            // == NOVO: Enviar notificação para todos os alunos           ==
            // =============================================================
            $mensagem_notificacao = "Novo aviso no feed: \"" . substr($titulo, 0, 50) . "...\"";
            $link_notificacao = "/portal-etc/feed/feed.php"; // Link para a página do feed

            // Chama a função para enviar para o grupo 'aluno'
            criar_notificacao_para_grupo($pdo, 'aluno', $mensagem_notificacao, $link_notificacao);
            // =============================================================

            flash_set('success', 'Aviso publicado e alunos notificados!');
            header('Location: avisos_view.php'); // Redireciona para a lista de avisos
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erro ao salvar o aviso no banco de dados: ' . $e->getMessage();
        }
    }
}

include '../partials/admin_header.php';
?>

<div class="mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Publicar Novo Aviso</h2>
                <a class="btn btn-outline-secondary btn-sm" href="avisos_view.php">Ver Todos os Avisos</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" action="avisos_create.php" enctype="multipart/form-data" onsubmit="return confirm('Tem certeza que deseja postar o aviso?');">
                        <?php csrf_input(); ?>

                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título do Aviso</label>
                            <input type="text" name="titulo" id="titulo" class="form-control"
                                value="<?php echo htmlspecialchars($titulo); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea name="descricao" id="descricao" class="form-control" rows="8"
                                required><?php echo htmlspecialchars($descricao); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="imagemInput" class="form-label">Imagem (Opcional)</label>
                            <input class="form-control" type="file" id="imagemInput" name="imagem" accept="image/jpeg, image/jpeg, image/png, image/gif">
                            <div class="form-text">Envie uma imagem (JPG, JPEG, PNG, GIF) de até 2MB.</div>
                        </div>

                        <div class="mb-3">
                            <img id="imagemPreview" src="#" alt="Preview da Imagem" class="img-fluid rounded" style="display: none; max-height: 200px;">
                        </div>
                        <div class="text-end">
                            <a href="avisos_view.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Publicar Aviso</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imagemInput = document.getElementById('imagemInput');
        const imagemPreview = document.getElementById('imagemPreview');

        imagemInput.addEventListener('change', function(event) {
            const file = event.target.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    imagemPreview.src = e.target.result;
                    imagemPreview.style.display = 'block'; // Mostra a imagem
                }

                reader.readAsDataURL(file);
            }
        });
    });
</script>
<?php include '../partials/footer.php'; ?>