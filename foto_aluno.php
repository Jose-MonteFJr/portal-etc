<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Verifica se um arquivo foi enviado sem erros
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {

        $target_dir = "uploads/perfil/"; // Crie esta subpasta para organizar
        $file_info = pathinfo($_FILES["foto_perfil"]["name"]);
        $file_extension = strtolower($file_info['extension']);
        $allowed_types = ['jpg', 'jpeg', 'png'];

        // 1. VALIDAÇÃO ROBUSTA
        $check = getimagesize($_FILES["foto_perfil"]["tmp_name"]);
        if ($check === false) {
            flash_set('danger', 'O arquivo enviado não é uma imagem válida.');
        } elseif (!in_array($file_extension, $allowed_types)) {
            flash_set('danger', 'Apenas imagens JPG, JPEG e PNG são permitidas.');
        } elseif ($_FILES["foto_perfil"]["size"] > 1000000) { // Limite de 1MB
            flash_set('danger', 'A imagem é muito grande (limite de 1MB).');
        } else {
            // 2. BUSCA A FOTO ANTIGA PARA EXCLUÍ-LA DEPOIS
            $stmt = $pdo->prepare("SELECT foto_perfil FROM usuario WHERE id_usuario = ?");
            $stmt->execute([$_SESSION['id_usuario']]);
            $foto_antiga = $stmt->fetchColumn();

            // 3. CRIA UM NOME DE ARQUIVO ÚNICO E SEGURO
            $nome_arquivo_final = 'perfil_' . $_SESSION['id_usuario'] . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $nome_arquivo_final;

            // 4. MOVE O NOVO ARQUIVO E ATUALIZA O BANCO
            if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $target_file)) {

                $stmt = $pdo->prepare("UPDATE usuario SET foto_perfil = ? WHERE id_usuario = ?");
                $stmt->execute([$nome_arquivo_final, $_SESSION['id_usuario']]);

                // 5. SE TUDO DEU CERTO, EXCLUI A FOTO ANTIGA (se existir)
                if ($foto_antiga && file_exists($target_dir . $foto_antiga)) {
                    unlink($target_dir . $foto_antiga);
                }

                // Atualiza a sessão para refletir a nova foto imediatamente
                $_SESSION['foto_perfil'] = $nome_arquivo_final;

                flash_set('success', 'Foto de perfil atualizada com sucesso!');
            } else {
                flash_set('danger', 'Ocorreu um erro ao salvar a nova foto.');
            }
        }
    } else {
        flash_set('danger', 'Nenhum arquivo foi enviado ou ocorreu um erro no upload.');
    }

    header('Location: foto_aluno.php');
    exit;
}


$stmt = $pdo->prepare("SELECT nome_completo, email, foto_perfil FROM usuario WHERE id_usuario = ?");
$stmt->execute([$_SESSION['id_usuario']]);
$usuario_atual = $stmt->fetch();

$foto_path = !empty($usuario_atual['foto_perfil'])
    ? 'uploads/perfil/' . $usuario_atual['foto_perfil']
    : 'partials/img/avatar_padrao.png'; // Caminho para uma imagem padrão

include __DIR__ . '/partials/portal_header.php';
?>
<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                            <h4 class="mb-2 mb-sm-0">Minha Foto de Perfil</h4>
                            <a class="btn btn-outline-secondary btn-sm" href="perfil_aluno.php">Voltar</a>
                        </div>
                        <div class="card-body">
                            <?php flash_show(); ?>

                            <form method="post" action="foto_aluno.php" enctype="multipart/form-data">
                                <?php csrf_input(); ?>

                                <div class="text-center mb-3">
                                    <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto de Perfil"
                                        id="foto_perfil_preview"
                                        class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                </div>

                                <div class="mb-3">
                                    <label for="foto_perfil_input" class="form-label">Alterar foto de perfil</label>
                                    <input class="form-control" type="file" id="foto_perfil_input" name="foto_perfil"
                                        accept="image/jpeg, image/png, image/jpg">
                                    <div class="form-text">Envie uma imagem JPG, JPEG ou PNG de até 1MB.</div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Salvar Foto</button>
                                </div>
                            </form>

                            <hr>
                            <form method="post" action="remover_foto.php" onsubmit="return confirm('Tem certeza que deseja remover sua foto de perfil?');">
                                <?php csrf_input(); ?>
                                <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2">
                                    <span class="text-muted small text-center text-sm-start">Deseja voltar a usar o avatar padrão?</span>
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-sm-auto">Remover Foto</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Seleciona os elementos do formulário
        const inputFoto = document.getElementById('foto_perfil_input');
        const imgPreview = document.getElementById('foto_perfil_preview');

        // 2. Adiciona um "ouvinte" para o evento 'change' no campo de arquivo
        inputFoto.addEventListener('change', function(event) {

            // Pega o primeiro arquivo que o usuário selecionou
            const file = event.target.files[0];

            // 3. Verifica se um arquivo foi realmente selecionado
            if (file) {
                // Cria um objeto FileReader para ler o arquivo
                const reader = new FileReader();

                // 4. Define o que acontece QUANDO o arquivo for lido
                reader.onload = function(e) {
                    // Atualiza o atributo 'src' da imagem com o resultado da leitura
                    imgPreview.src = e.target.result;
                }

                // 5. Manda o FileReader LER o arquivo como uma URL de dados
                reader.readAsDataURL(file);
            }
        });
    });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>