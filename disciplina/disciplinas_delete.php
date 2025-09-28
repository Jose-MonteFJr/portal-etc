<?php
require  '../protect.php';
require  '../config/db.php';
require  '../helpers.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redireciona para a lista principal de módulos se não for POST
    header('Location: ../modulo/modulos_view.php');
    exit;
}

csrf_check();
$id_disciplina = (int)($_POST['id_disciplina'] ?? 0);

if ($id_disciplina <= 0) {
    flash_set('danger', 'ID da disciplina é inválido.');
    header('Location: ../modulo/modulos_view.php'); // Volta para a lista de módulos
    exit;
}

// NOVO: Busca a disciplina para obter o id_modulo e verificar se ela existe
$stmt = $pdo->prepare('SELECT id_modulo FROM disciplina WHERE id_disciplina = ?');
$stmt->execute([$id_disciplina]);
$disciplina = $stmt->fetch();

if (!$disciplina) {
    flash_set('danger', 'Disciplina não encontrada.');
    header('Location: ../modulo/modulos_view.php'); // Volta para a lista de módulos
    exit;
}
// Guarda o ID do módulo para o redirecionamento
$id_modulo_para_redirect = $disciplina['id_modulo'];


// ALTERADO: Agora que já sabemos que a disciplina existe, podemos excluir com segurança
try {
    $stmt = $pdo->prepare('DELETE FROM disciplina WHERE id_disciplina = ?');
    $stmt->execute([$id_disciplina]);
    
    flash_set('success', 'Disciplina excluída com sucesso!');

} catch (PDOException $e) {
    flash_set('danger', 'Ocorreu um erro ao excluir a disciplina.');
}

// ALTERADO: Redireciona para a página de disciplinas do módulo correto
header('Location: disciplinas_view.php?id_modulo=' . $id_modulo_para_redirect);
exit;