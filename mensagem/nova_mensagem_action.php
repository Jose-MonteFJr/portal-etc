<?php
// nova_mensagem_action.php
require '../protect.php';
require '../config/db.php';
require '../helpers.php'; // Garante que a função criar_notificacao() esteja disponível

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

$pdo->beginTransaction();
try {
    // 1. Cria a conversa
    $stmt_conversa = $pdo->prepare("INSERT INTO conversa (assunto) VALUES (?)");
    $stmt_conversa->execute([$assunto]);
    $id_conversa = $pdo->lastInsertId();

    // 2. Adiciona os participantes
    $stmt_participantes = $pdo->prepare("INSERT INTO participante_conversa (id_conversa, id_usuario, status_leitura) VALUES (?, ?, ?), (?, ?, ?)");
    $stmt_participantes->execute([$id_conversa, $id_remetente, 'lida', $id_conversa, $id_destinatario, 'nao lida']);
    
    // 3. Insere a primeira mensagem
    $stmt_mensagem = $pdo->prepare("INSERT INTO mensagem (id_conversa, id_usuario_remetente, conteudo) VALUES (?, ?, ?)");
    $stmt_mensagem->execute([$id_conversa, $id_remetente, $conteudo]);

    // =============================================================
    // == NOVO: CRIA A NOTIFICAÇÃO PARA O DESTINATÁRIO            ==
    // =============================================================
    $nome_remetente = $_SESSION['nome_completo'];
    $mensagem_notificacao = "Você recebeu uma nova mensagem de " . $nome_remetente;
    $link_notificacao = "/portal-etc/mensagem/ver_conversa.php?id_conversa=" . $id_conversa; // Ajuste o caminho se necessário
    
    // Chama a função para criar a notificação
    criar_notificacao($pdo, $id_destinatario, $mensagem_notificacao, $link_notificacao);
    // =============================================================
    
    $pdo->commit();

    flash_set('success', 'Mensagem enviada com sucesso!');
    redirect('caixa_de_entrada.php');

} catch (Exception $e) {
    $pdo->rollBack();
    flash_set('danger', 'Erro ao enviar a mensagem: ' . $e->getMessage());
    redirect('nova_mensagem.php');
}