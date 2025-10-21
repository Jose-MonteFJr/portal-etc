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
$params = [':id_usuario_logado' => $id_usuario_logado];

// Se o usuário for um aluno, descobre a turma dele
if ($tipo_usuario === 'aluno') {
    $stmt_turma = $pdo->prepare("SELECT id_turma FROM aluno WHERE id_usuario = ?");
    $stmt_turma->execute([$id_usuario_logado]);
    $id_turma_aluno = $stmt_turma->fetchColumn();
}

// --- CONSTRUÇÃO DA CONSULTA SQL DINÂMICA (CORRIGIDA) ---
    
// 1. Base da consulta: Todos os eventos PESSOAIS do usuário logado
$sql = "SELECT * FROM evento_calendario WHERE (id_usuario_criador = :id_usuario_logado AND tipo = 'pessoal')";

if ($tipo_usuario === 'aluno' && $id_turma_aluno) {
    // 2. Aluno: Vê seus pessoais E os globais da sua turma
    $sql .= " OR (tipo = 'global' AND id_turma_alvo = :id_turma_aluno)";
    $params[':id_turma_aluno'] = $id_turma_aluno;
} 
elseif ($tipo_usuario === 'professor') {
    // 3. Professor: Vê seus pessoais E os globais das turmas que ele leciona
    // CORRIGIDO: Agora busca na tabela 'horario_aula'
    $sql .= " OR (tipo = 'global' AND id_turma_alvo IN (
                SELECT DISTINCT id_turma FROM horario_aula WHERE id_professor = :id_usuario_logado
            ))";
    // O parâmetro :id_usuario_logado já está em $params, então não precisamos adicionar de novo.
} 
elseif ($tipo_usuario === 'secretaria' || $tipo_usuario === 'coordenador') {
    // 4. Admin: Vê seus pessoais E TODOS os globais
    $sql .= " OR tipo = 'global'";
}
// --- FIM DA CONSTRUÇÃO ---

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($eventos);

} catch (PDOException $e) {
    // Se a consulta falhar, retorna um erro JSON, não HTML
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}