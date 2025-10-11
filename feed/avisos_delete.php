<?php
// 1. SEGURANÇA E INCLUDES PADRÃO
require     '../protect.php'; // Ajuste o caminho conforme sua estrutura
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

// Garante que a requisição seja do tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('avisos_view.php');
}

csrf_check();

// 2. CAPTURA E VALIDAÇÃO DO ID
$id_aviso = (int)($_POST['id_aviso'] ?? 0);
if ($id_aviso <= 0) {
    flash_set('danger', 'ID do aviso é inválido.');
    header('Location: avisos_view.php');
    exit;
}

// 3. BUSCA O NOME DO ARQUIVO DA IMAGEM (ANTES DE DELETAR O REGISTRO)
try {
    $stmt_select = $pdo->prepare('SELECT caminho_imagem FROM aviso WHERE id_aviso = ?');
    $stmt_select->execute([$id_aviso]);
    $aviso = $stmt_select->fetch();

    if (!$aviso) {
        flash_set('danger', 'Aviso não encontrado.');
        header('Location: avisos_view.php');
        exit;
    }
    $nome_arquivo = $aviso['caminho_imagem'];

    // 4. EXECUTA A EXCLUSÃO NO BANCO DE DADOS
    $stmt_delete = $pdo->prepare('DELETE FROM aviso WHERE id_aviso = ?');
    $stmt_delete->execute([$id_aviso]);

    // 5. EXCLUI O ARQUIVO DE IMAGEM DO SERVIDOR (SE EXISTIR)
    if ($nome_arquivo) {
        $caminho_completo = '../uploads/avisos/' . $nome_arquivo;
        if (file_exists($caminho_completo)) {
            unlink($caminho_completo);
        }
    }

    // 6. FEEDBACK E REDIRECIONAMENTO
    flash_set('success', 'Aviso excluído com sucesso!');
    header('Location: avisos_view.php');
    exit;

} catch (PDOException $e) {
    flash_set('danger', 'Ocorreu um erro ao excluir o aviso.');
    header('Location: avisos_view.php');
    exit;
}