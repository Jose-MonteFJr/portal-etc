<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

// Apenas aceita requisições do tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('foto_aluno.php');
}

csrf_check();

try {
    $id_usuario = $_SESSION['id_usuario'];
    $target_dir = "uploads/perfil/";

    // 1. Busca o nome do arquivo da foto atual no banco de dados
    $stmt = $pdo->prepare("SELECT foto_perfil FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $foto_antiga = $stmt->fetchColumn();

    // 2. Se existir uma foto antiga, exclui o arquivo físico do servidor
    if ($foto_antiga && file_exists($target_dir . $foto_antiga)) {
        unlink($target_dir . $foto_antiga);
    }

    // 3. Atualiza o banco de dados, definindo o campo foto_perfil como NULL
    $stmt = $pdo->prepare("UPDATE usuario SET foto_perfil = NULL WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);

    // 4. Limpa a foto da sessão para que a mudança reflita imediatamente no layout
    $_SESSION['foto_perfil'] = null;

    flash_set('success', 'Sua foto de perfil foi removida com sucesso.');
} catch (PDOException $e) {
    flash_set('danger', 'Ocorreu um erro ao remover a foto: ' . $e->getMessage());
}

// 5. Redireciona o usuário de volta para a página de perfil
header('Location: foto_aluno.php');
exit;
