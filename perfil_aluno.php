<?php
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

// Garante que apenas alunos acessem esta página de perfil de aluno
if ($_SESSION['tipo'] !== 'aluno') {
    flash_set('danger', 'Acesso negado.');
    header('Location: admin.php');
    exit;
}

// 1. Prepara e executa a consulta SQL otimizada
$id_usuario_logado = $_SESSION['id_usuario'];

$sql = "SELECT
            u.nome_completo, u.email, u.telefone, u.foto_perfil,
            a.matricula, a.status_academico, a.data_ingresso,
            t.nome AS nome_turma, t.turno,
            c.nome AS nome_curso,
            e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep
        FROM usuario u
        LEFT JOIN aluno a ON u.id_usuario = a.id_usuario
        LEFT JOIN turma t ON a.id_turma = t.id_turma
        LEFT JOIN curso c ON t.id_curso = c.id_curso
        LEFT JOIN endereco e ON u.id_usuario = e.id_usuario
        WHERE u.id_usuario = ?";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario_logado]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
    flash_set('danger', 'Não foi possível carregar os dados do perfil.');
    header('Location: portal_home.php');
    exit;
}

// 2. Prepara as variáveis para exibição
$foto_path = !empty($aluno['foto_perfil'])
             ? '/portal-etc/uploads/perfil/' . $aluno['foto_perfil']
             : '/portal-etc/partials/img/avatar_padrao.png';

// Monta o endereço formatado
$endereco_formatado = 'Endereço não cadastrado.';
if (!empty($aluno['logradouro'])) {
    $endereco_formatado = $aluno['logradouro'] . ', ' . $aluno['numero'];
    if (!empty($aluno['complemento'])) {
        $endereco_formatado .= ' - ' . $aluno['complemento'];
    }
    $endereco_formatado .= '. ' . $aluno['bairro'] . ', ' . $aluno['cidade'] . ' - ' . $aluno['estado'];
}

include __DIR__ . '/partials/portal_header.php';
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h4 mb-0">Meu Perfil</h2>
                    </div>

                    <?php flash_show(); ?>

                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto de Perfil" 
                                     class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                                <h5 class="mt-3 mb-0"><?php echo htmlspecialchars($aluno['nome_completo']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars($aluno['email']); ?></p>
                                <a href="foto_aluno.php" class="btn btn-sm btn-outline-primary">Alterar Foto</a>
                            </div>

                            <ul class="nav nav-tabs nav-fill mb-3" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pessoal-tab" data-bs-toggle="tab" data-bs-target="#pessoal-tab-pane" type="button" role="tab">Dados Pessoais</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="academico-tab" data-bs-toggle="tab" data-bs-target="#academico-tab-pane" type="button" role="tab">Dados Acadêmicos</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="pessoal-tab-pane" role="tabpanel">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                                            <strong>Telefone:</strong>
                                            <span><?php echo htmlspecialchars($aluno['telefone'] ?? 'Não informado'); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                                            <strong>Endereço:</strong>
                                            <span class="text-md-end"><?php echo htmlspecialchars($endereco_formatado); ?></span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="tab-pane fade" id="academico-tab-pane" role="tabpanel">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                                            <strong>Matrícula:</strong>
                                            <span><?php echo htmlspecialchars($aluno['matricula'] ?? 'Não informada'); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                                            <strong>Status:</strong>
                                            <span class="badge text-bg-info align-self-start align-self-md-center"><?php echo htmlspecialchars(ucwords($aluno['status_academico'] ?? 'N/A')); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                                            <strong>Curso:</strong>
                                            <span><?php echo htmlspecialchars($aluno['nome_curso'] ?? 'Não informado'); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                                            <strong>Turma:</strong>
                                            <span><?php echo htmlspecialchars($aluno['nome_turma'] ?? 'Não informado'); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="editar_perfil_aluno.php" class="btn btn-sm btn-secondary">Editar Dados Pessoais</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>