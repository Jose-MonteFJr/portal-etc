<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';
ensure_admin();

$id_usuario = (int)($_GET['id'] ?? 0);
if ($id_usuario === 0) {
  http_response_code(400); // Bad Request
  echo json_encode(['error' => 'ID de usuário inválido.']);
  exit;
}

try {
  // CORRIGIDO: Adicionado "a.matricula" à consulta SELECT
  $stmt = $pdo->prepare('
        SELECT 
            u.id_usuario, u.nome_completo, u.cpf, u.email, u.telefone, 
            DATE_FORMAT(u.data_nascimento, "%d/%m/%Y") AS data_nascimento_formatada,
            TIMESTAMPDIFF(YEAR, u.data_nascimento, CURDATE()) AS idade, -- <-- ADICIONADO AQUI
            u.tipo, u.status, 
            e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep, 
            t.id_turma, t.nome AS nome_turma, t.ano, t.semestre, t.turno, 
            a.matricula,
            DATE_FORMAT(a.data_ingresso, "%d/%m/%Y") AS data_ingresso, 
            a.status_academico 
        FROM usuario u 
        LEFT JOIN aluno a ON a.id_usuario = u.id_usuario 
        LEFT JOIN turma t ON t.id_turma = a.id_turma 
        LEFT JOIN endereco e ON e.id_usuario = u.id_usuario 
        WHERE u.id_usuario = ?
    ');

  $stmt->execute([$id_usuario]);

  // CORRIGIDO: Usando FETCH_ASSOC para um JSON limpo
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  // CORRIGIDO: Lógica de erro com 'exit' para não enviar JSON duplicado
  if (!$user) {
    http_response_code(404); // Not Found
    echo json_encode(["error" => "Usuário não encontrado"]);
    exit;
  }

  header('Content-Type: application/json');
  echo json_encode($user);
} catch (PDOException $e) {
  http_response_code(500); // Internal Server Error
  echo json_encode(['error' => 'Erro de banco de dados: ' . $e->getMessage()]);
}
