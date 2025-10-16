<?php
// horarios_definicao_delete.php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('horarios_definicao.php');
}

csrf_check();
$id_definicao = (int)($_POST['id_definicao'] ?? 0);

if ($id_definicao <= 0) {
    flash_set('danger', 'ID inválido.');
    header('Location: horarios_definicao.php');
    exit;
}

try {
    // Adicione aqui uma verificação se este horário está em uso, se necessário
    $stmt = $pdo->prepare('DELETE FROM definicao_horario WHERE id_definicao = ?');
    $stmt->execute([$id_definicao]);

    flash_set('success', 'Definição de horário excluída com sucesso!');
    
} catch (PDOException $e) {
    flash_set('danger', 'Erro ao excluir o horário. Verifique se ele não está em uso.');
}

header('Location: horarios_definicao.php');
exit;