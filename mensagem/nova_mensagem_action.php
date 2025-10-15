<?php
// nova_mensagem_action.php
require '../protect.php';
require '../config/db.php';
require '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('nova_mensagem.php');
}
csrf_check();

$id_remetente = $_SESSION['id_usuario'];
$id_destinatario = (int)($_POST['id_destinatario'] ?? 0);
$assunto = trim($_POST['assunto'] ?? '');
$conteudo = trim($_POST['conteudo'] ?? '');

if ($id_destinatario === 0 || empty($assunto) || empty($conteudo)) {
    flash_set('danger', 'Todos os campos são obrigatórios.');
    redirect('nova_mensagem.php');
}

// Inicia a transação
$pdo->beginTransaction();
try {
    // 1. Cria a conversa
    $stmt_conversa = $pdo->prepare("INSERT INTO conversa (assunto) VALUES (?)");
    $stmt_conversa->execute([$assunto]);
    $id_conversa = $pdo->lastInsertId();

    // 2. Adiciona os participantes (remetente e destinatário)
    $stmt_participantes = $pdo->prepare("INSERT INTO participante_conversa (id_conversa, id_usuario, status_leitura) VALUES (?, ?, ?), (?, ?, ?)");
    // O remetente já leu. O destinatário, não.
    $stmt_participantes->execute([$id_conversa, $id_remetente, 'lida', $id_conversa, $id_destinatario, 'nao lida']);
    
    // 3. Insere a primeira mensagem
    $stmt_mensagem = $pdo->prepare("INSERT INTO mensagem (id_conversa, id_usuario_remetente, conteudo) VALUES (?, ?, ?)");
    $stmt_mensagem->execute([$id_conversa, $id_remetente, $conteudo]);

    // Se tudo deu certo, confirma a transação
    $pdo->commit();

    flash_set('success', 'Mensagem enviada com sucesso!');
    redirect('caixa_de_entrada.php'); // Redireciona para a futura caixa de entrada

} catch (Exception $e) {
    // Se algo deu errado, desfaz tudo
    $pdo->rollBack();
    flash_set('danger', 'Erro ao enviar a mensagem: ' . $e->getMessage());
    redirect('nova_mensagem.php');
}