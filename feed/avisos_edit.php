<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

// 1. BUSCA INICIAL DOS DADOS DO AVISO
$id_aviso = (int)($_GET['id_aviso'] ?? 0);
if ($id_aviso === 0) {
    flash_set('danger', 'ID do aviso inválido.');
    header('Location: feed.php');
    exit;
}

// Busca o aviso no banco de dados
$stmt = $pdo->prepare('SELECT * FROM aviso WHERE id_aviso = ?');
$stmt->execute([$id_aviso]);
$aviso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aviso) {
    flash_set('danger', 'Aviso não encontrado.');
    header('Location: avisos_view.php');
    exit;
}

// Inicializa variáveis para o formulário com os dados do banco
$errors = [];
$titulo = $aviso['titulo'];
$descricao = $aviso['descricao'];
$imagem_atual = $aviso['caminho_imagem'];

// 2. LÓGICA PARA PROCESSAR O FORMULÁRIO DE ATUALIZAÇÃO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Captura os dados do formulário
    $titulo    = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $remover_imagem = isset($_POST['remover_imagem']);

    // Validações
    if (empty($titulo)) $errors[] = "O título é obrigatório.";
    if (empty($descricao)) $errors[] = "A descrição é obrigatória.";

    $nome_arquivo_final = $imagem_atual; // Por padrão, mantém a imagem antiga
    $target_dir = "../uploads/avisos/"; // Define o diretório de upload

    // Lógica para REMOVER a imagem
    if ($remover_imagem && $imagem_atual) {
        $caminho_completo = __DIR__ . '../uploads/avisos/' . $imagem_atual;
        if (file_exists($caminho_completo)) {
            unlink($caminho_completo); // Deleta o arquivo físico
        }
        $nome_arquivo_final = null; // Limpa o nome do arquivo
    }
    // Lógica para SUBSTITUIR a imagem (se uma nova foi enviada)
    elseif (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {

        // --- LÓGICA DE UPLOAD COMPLETA (copiada de create.php) ---
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
            $novo_nome_arquivo = 'aviso_' . $id_aviso . '_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $novo_nome_arquivo;

            if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file)) {
                // Se o upload da nova imagem deu certo, deleta a antiga
                if ($imagem_atual && file_exists($target_dir . $imagem_atual)) {
                    unlink($target_dir . $imagem_atual);
                }
                $nome_arquivo_final = $novo_nome_arquivo; // Define o novo nome do arquivo
            } else {
                $errors[] = "Erro ao fazer upload da nova imagem.";
            }
        }
        // --- FIM DA LÓGICA DE UPLOAD COMPLETA ---
    }

    // Se não houver erros, atualiza o banco
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE aviso SET titulo = ?, descricao = ?, caminho_imagem = ? WHERE id_aviso = ?"
            );
            $stmt->execute([$titulo, $descricao, $nome_arquivo_final, $id_aviso]);

            flash_set('success', 'Aviso atualizado com sucesso!');
            header('Location: feed.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erro ao salvar as alterações: ' . $e->getMessage();
        }
    }
}

include '../partials/admin_header.php'; // Ajuste o caminho
?>

<div class="main">
    <div class="content">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-megaphone-fill fs-4 text-primary"></i>
                            <h2 class="h4 mb-0">Editar Aviso #<?php echo (int)$aviso['id_aviso']; ?></h2>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="../admin.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="feed.php">Feed de Avisos</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Editar</li>
                            </ol>
                        </nav>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="avisos_edit.php?id_aviso=<?php echo (int)$id_aviso; ?>" enctype="multipart/form-data">
                        <?php csrf_input(); ?>
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0">Editar Conteúdo do Aviso</h5>
                            </div>
                            <div class="card-body p-4">

                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título do Aviso</label>
                                    <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo htmlspecialchars($titulo); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea name="descricao" id="descricao" class="form-control" rows="8" required><?php echo htmlspecialchars($descricao); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="imagemInput" class="form-label">Substituir Imagem (Opcional)</label>
                                    <input class="form-control" type="file" id="imagemInput" name="imagem" accept="image/jpeg, image/png, image/gif">
                                    <div class="form-text">Envie uma nova imagem (JPG, PNG, GIF) de até 2MB para substituir a atual.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Imagem Atual:</label>
                                    <div>
                                        <?php if ($imagem_atual): ?>
                                            <img id="imagemPreview" src="../uploads/avisos/<?php echo htmlspecialchars($imagem_atual); ?>" alt="Imagem Atual" class="img-fluid rounded" style="max-height: 200px; display: block;">
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="remover_imagem" id="remover_imagem">
                                                <label class="form-check-label" for="remover_imagem">
                                                    Remover imagem atual (e não substituir)
                                                </label>
                                            </div>
                                        <?php else: ?>
                                            <p id="imagemPreview" class="text-muted small" style="display: block;">Nenhuma imagem cadastrada.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer text-end">
                                <a href="feed.php" class="btn btn-secondary">Cancelar</a>
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
                                <strong>Excluir este aviso</strong>
                                <p class="mb-sm-0 text-muted small">Uma vez excluído, o aviso e todos os seus comentários/curtidas serão perdidos permanentemente.</p>
                            </div>

                            <button type="button" class="btn btn-danger w-100 w-sm-auto mt-2 mt-sm-0 delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#confirmDeleteModal"
                                data-form-action="avisos_delete.php"
                                data-item-id="<?php echo (int)$aviso['id_aviso']; ?>"
                                data-item-name="<?php echo htmlspecialchars($aviso['titulo']); ?>"
                                data-id-field="id_aviso">
                                <i class="bi bi-trash-fill"></i> Excluir Aviso
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imagemInput = document.getElementById('imagemInput');
        let imagemPreview = document.getElementById('imagemPreview'); // A variável agora é 'let' para poder ser reatribuída
        const removerCheckbox = document.getElementById('remover_imagem');

        imagemInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    // Se o elemento de preview for um parágrafo...
                    if (imagemPreview.tagName === 'P') {
                        const newImg = document.createElement('img');
                        newImg.id = 'imagemPreview';
                        newImg.className = 'img-fluid rounded';
                        newImg.style.maxHeight = '200px';

                        // Substitui o <p> pelo novo <img>
                        imagemPreview.parentNode.replaceChild(newImg, imagemPreview);

                        // PONTO CRUCIAL: Atualiza a variável para apontar para o novo <img>
                        imagemPreview = newImg;
                    }

                    // Agora, atualiza o 'src' da imagem (seja a original ou a nova)
                    imagemPreview.src = e.target.result;
                    imagemPreview.style.display = 'block';
                }

                reader.readAsDataURL(file);

                if (removerCheckbox) {
                    removerCheckbox.checked = false;
                }
            }
        });
    });
</script>

<?php include '../partials/footer.php'; // Ajuste o caminho 
?>