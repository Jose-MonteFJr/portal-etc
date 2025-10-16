<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('turmas_view.php');
}
csrf_check();

$id_turma = (int)($_POST['id_turma'] ?? 0);
$horarios = $_POST['horario'] ?? [];

if ($id_turma === 0) {
    flash_set('danger', 'Turma inválida.');
    header('Location: turmas_view.php');
    exit;
}

// Inicia uma transação para garantir a integridade dos dados
$pdo->beginTransaction();
try {
    // 1. Apaga a grade horária antiga desta turma para evitar duplicatas
    $stmt_delete = $pdo->prepare("DELETE FROM horario_aula WHERE id_turma = ?");
    $stmt_delete->execute([$id_turma]);

    // Prepara o statement de inserção para ser reutilizado no loop
    $stmt_insert = $pdo->prepare(
        "INSERT INTO horario_aula (id_turma, dia_semana, horario, id_disciplina, id_professor, sala) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    // 2. Itera sobre os dados enviados pelo formulário
    foreach ($horarios as $label_horario => $dias) {
        foreach ($dias as $dia_semana => $dados_aula) {
            
            // Só insere se uma disciplina E um professor foram selecionados
            if (!empty($dados_aula['id_disciplina']) && !empty($dados_aula['id_professor'])) {
                $stmt_insert->execute([
                    $id_turma,
                    $dia_semana,
                    $label_horario,
                    $dados_aula['id_disciplina'],
                    $dados_aula['id_professor'],
                    trim($dados_aula['sala'])
                ]);
            }
        }
    }

    // 3. Se tudo deu certo, confirma as alterações
    $pdo->commit();
    flash_set('success', 'Grade horária salva com sucesso!');

} catch (Exception $e) {
    // Se algo deu errado, desfaz tudo
    $pdo->rollBack();
    flash_set('danger', 'Erro ao salvar a grade horária: ' . $e->getMessage());
}

// Redireciona de volta para a página de edição
header('Location: montar_horario.php?id_turma=' . $id_turma);
exit;