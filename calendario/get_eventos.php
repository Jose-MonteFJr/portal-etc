<?php
// calendario/get_eventos.php
session_start();
require '../config/db.php'; // Ajuste o caminho

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([]); 
    exit;
}

$id_usuario_logado = $_SESSION['id_usuario'];
$tipo_usuario = $_SESSION['tipo'];
$id_turma_aluno = null;

// CORREÇÃO: Inicializa $params apenas com o primeiro placeholder
$params = [':id_usuario_logado' => $id_usuario_logado]; 
$sql = "SELECT * FROM evento_calendario WHERE (id_usuario_criador = :id_usuario_logado AND tipo = 'pessoal')";

// Se o usuário for um aluno, descobre a turma dele
if ($tipo_usuario === 'aluno') {
    $stmt_turma = $pdo->prepare("SELECT id_turma FROM aluno WHERE id_usuario = ?");
    $stmt_turma->execute([$id_usuario_logado]);
    $id_turma_aluno = $stmt_turma->fetchColumn();

    if ($id_turma_aluno) {
        $sql .= " OR (tipo = 'global' AND id_turma_alvo = :id_turma_aluno)";
        $params[':id_turma_aluno'] = $id_turma_aluno; // Adiciona o segundo placeholder
    }
} 
elseif ($tipo_usuario === 'professor') {
    // CORREÇÃO: Usa um nome de placeholder ÚNICO (:id_prof_subquery) para a subconsulta
    $sql .= " OR (tipo = 'global' AND id_turma_alvo IN (
                SELECT DISTINCT id_turma FROM horario_aula WHERE id_professor = :id_prof_subquery
            ))";
    // Adiciona o valor para o novo placeholder
    $params[':id_prof_subquery'] = $id_usuario_logado; 
} 
elseif ($tipo_usuario === 'secretaria' || $tipo_usuario === 'coordenador') {
    $sql .= " OR tipo = 'global'";
}

try {
    $stmt = $pdo->prepare($sql);
    // Agora o $params tem o número correto de variáveis para a consulta
    $stmt->execute($params); 
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($eventos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}