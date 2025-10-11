<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

// 1. BUSCA INICIAL DOS DADOS DO AVISO
$id_aviso = (int)($_GET['id_aviso'] ?? 0);
if ($id_aviso === 0) {
    flash_set('danger', 'ID do aviso inválido.');
    header('Location: avisos_view.php');
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
        // ... (lógica de validação do upload, igual à da página de criação) ...
        // ... (cria nome único, move o arquivo, e se tudo der certo:)

        // Exemplo simplificado da lógica de upload:
        $target_dir = "../uploads/avisos/";
        $file_info = pathinfo($_FILES["imagem"]["name"]);
        $file_extension = strtolower($file_info['extension']);
        $novo_nome_arquivo = 'aviso_' . $id_aviso . '_' . time() . '.' . $file_extension;

        if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_dir . $novo_nome_arquivo)) {
            // Se o upload da nova imagem deu certo, deleta a antiga
            if ($imagem_atual && file_exists($target_dir . $imagem_atual)) {
                unlink($target_dir . $imagem_atual);
            }
            $nome_arquivo_final = $novo_nome_arquivo; // Define o novo nome do arquivo
        } else {
            $errors[] = "Erro ao fazer upload da nova imagem.";
        }
    }

    // Se não houver erros, atualiza o banco
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE aviso SET titulo = ?, descricao = ?, caminho_imagem = ? WHERE id_aviso = ?"
            );
            $stmt->execute([$titulo, $descricao, $nome_arquivo_final, $id_aviso]);

            flash_set('success', 'Aviso atualizado com sucesso!');
            header('Location: avisos_view.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Erro ao salvar as alterações: ' . $e->getMessage();
        }
    }
}

include '../partials/header.php'; // Ajuste o caminho
?>

<div class="mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Editar Aviso #<?php echo (int)$aviso['id_aviso']; ?></h2>
                <a class="btn btn-outline-secondary btn-sm" href="avisos_view.php">Cancelar</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post" action="avisos_edit.php?id_aviso=<?php echo (int)$id_aviso; ?>" enctype="multipart/form-data">
                        <?php csrf_input(); ?>

                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título do Aviso</label>
                            <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo htmlspecialchars($titulo); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea name="descricao" id="descricao" class="form-control" rows="8"><?php echo htmlspecialchars($descricao); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="imagemInput" class="form-label">Substituir Imagem (Opcional)</label>
                            <input class="form-control" type="file" id="imagemInput" name="imagem" accept="image/jpeg, image/png, image/gif">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Imagem Atual:</label>
                            <div>
                                <?php if ($imagem_atual): ?>
                                    <img id="imagemPreview" src="../uploads/avisos/<?php echo htmlspecialchars($imagem_atual); ?>" alt="Imagem Atual" class="img-fluid rounded" style="max-height: 200px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remover_imagem" id="remover_imagem">
                                        <label class="form-check-label" for="remover_imagem">
                                            Remover imagem atual
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <p id="imagemPreview" class="text-muted small">Nenhuma imagem cadastrada.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
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