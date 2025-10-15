<?php
require     '../protect.php'; // Ajuste o caminho
require     '../config/db.php';
require     '../helpers.php';

$id_usuario_logado = $_SESSION['id_usuario'];
$tipo_usuario_logado = $_SESSION['tipo'];
$destinatarios = [];

try {
    if ($tipo_usuario_logado === 'secretaria' || $tipo_usuario_logado === 'coordenador') {
        // REGRA: Secretaria/Coordenador pode enviar para TODOS (exceto eles mesmos).
        $stmt = $pdo->prepare("SELECT id_usuario, nome_completo, tipo FROM usuario WHERE id_usuario != ? ORDER BY nome_completo ASC");
        $stmt->execute([$id_usuario_logado]);
        $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tipo_usuario_logado === 'professor') {
        // REGRA: Professor pode enviar para alunos de suas turmas e para a administração.
        $sql = "
            -- Alunos das turmas do professor
            SELECT u.id_usuario, u.nome_completo, u.tipo
            FROM usuario u
            JOIN aluno a ON u.id_usuario = a.id_usuario
            WHERE a.id_turma IN (SELECT id_turma FROM alocacao_professor WHERE id_usuario = ?)
            
            UNION
            
            -- Administração
            SELECT id_usuario, nome_completo, tipo
            FROM usuario
            WHERE tipo IN ('secretaria', 'coordenador')
            
            ORDER BY nome_completo ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_usuario_logado]);
        $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tipo_usuario_logado === 'aluno') {
        // REGRA: Aluno pode enviar para colegas da mesma turma, seus professores e a administração.
        // Primeiro, descobre a turma do aluno logado.
        $stmt_turma = $pdo->prepare("SELECT id_turma FROM aluno WHERE id_usuario = ?");
        $stmt_turma->execute([$id_usuario_logado]);
        $id_turma_aluno = $stmt_turma->fetchColumn();

        if ($id_turma_aluno) {
            $sql = "
                -- Colegas da mesma turma (exceto ele mesmo)
                SELECT u.id_usuario, u.nome_completo, u.tipo
                FROM usuario u
                JOIN aluno a ON u.id_usuario = a.id_usuario
                WHERE a.id_turma = ? AND u.id_usuario != ?

                UNION

                -- Professores da sua turma
                SELECT u.id_usuario, u.nome_completo, u.tipo
                FROM usuario u
                JOIN alocacao_professor ap ON u.id_usuario = ap.id_usuario
                WHERE ap.id_turma = ?

                UNION

                -- Administração
                SELECT id_usuario, nome_completo, tipo
                FROM usuario
                WHERE tipo IN ('secretaria', 'coordenador')

                ORDER BY tipo ASC, nome_completo ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_turma_aluno, $id_usuario_logado, $id_turma_aluno]);
            $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    die("Erro ao buscar destinatários: " . $e->getMessage());
}

include '../partials/portal_header.php'; // Ajuste o caminho
?>

<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <form method="post" action="nova_mensagem_action.php" id="form-mensagem">
                        <?php csrf_input(); ?>
                        <input type="hidden" name="id_destinatario" id="id_destinatario_hidden">

                        <div class="card shadow-sm">
                            <div class="card-header d-flex align-items-center gap-2">
                                <strong class="me-2">Para:</strong>
                                <div id="destinatario-selecionado" class="d-none">
                                    <span class="badge text-bg-primary d-flex align-items-center">
                                        <span id="destinatario-nome"></span>
                                        <button type="button" class="btn-close btn-close-white ms-2" id="trocar-destinatario-btn" aria-label="Close"></button>
                                    </span>
                                </div>
                            </div>

                            <div id="area-selecao-contato">
                                <div class="p-3 border-bottom">
                                    <input type="search" id="busca-contato-input" class="form-control" placeholder="Buscar por nome...">
                                </div>
                                <div class="list-group list-group-flush" id="lista-contatos" style="max-height: 300px; overflow-y: auto;">
                                    <?php if (empty($destinatarios)): ?>
                                        <div class="list-group-item">Nenhum destinatário disponível.</div>
                                    <?php else: ?>
                                        <?php foreach ($destinatarios as $destinatario): ?>
                                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center gap-3 contato-item" 
                                               data-id="<?php echo (int)$destinatario['id_usuario']; ?>" 
                                               data-nome="<?php echo htmlspecialchars($destinatario['nome_completo']); ?>">
                                                <img src="/portal-etc/partials/img/avatar_padrao.png" class="rounded-circle" style="width: 40px; height: 40px;">
                                                <div>
                                                    <strong class="mb-0"><?php echo htmlspecialchars($destinatario['nome_completo']); ?></strong>
                                                    <small class="d-block text-muted"><?php echo htmlspecialchars(ucfirst($destinatario['tipo'])); ?></small>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div id="area-escrita-mensagem" class="d-none p-3">
                                <div class="mb-3">
                                    <label for="assunto" class="form-label">Assunto:</label>
                                    <input type="text" name="assunto" id="assunto" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="conteudo" class="form-label">Mensagem:</label>
                                    <textarea name="conteudo" id="conteudo" class="form-control" rows="8" required></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscaInput = document.getElementById('busca-contato-input');
    const listaContatos = document.getElementById('lista-contatos');
    const contatoItems = listaContatos.querySelectorAll('.contato-item');
    
    const areaSelecao = document.getElementById('area-selecao-contato');
    const areaEscrita = document.getElementById('area-escrita-mensagem');
    const destinatarioSelecionado = document.getElementById('destinatario-selecionado');
    const destinatarioNome = document.getElementById('destinatario-nome');
    const hiddenInput = document.getElementById('id_destinatario_hidden');
    const trocarBtn = document.getElementById('trocar-destinatario-btn');

    // Lógica da Busca em Tempo Real
    buscaInput.addEventListener('input', function() {
        const termoBusca = this.value.toLowerCase();
        contatoItems.forEach(item => {
            const nome = item.querySelector('strong').textContent.toLowerCase();
            if (nome.includes(termoBusca)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Lógica ao Selecionar um Contato
    listaContatos.addEventListener('click', function(event) {
        const itemClicado = event.target.closest('.contato-item');
        if (itemClicado) {
            event.preventDefault();
            
            const id = itemClicado.dataset.id;
            const nome = itemClicado.dataset.nome;
            
            // Preenche os campos
            hiddenInput.value = id;
            destinatarioNome.textContent = nome;
            
            // Altera a visibilidade das seções
            areaSelecao.classList.add('d-none');
            destinatarioSelecionado.classList.remove('d-none');
            areaEscrita.classList.remove('d-none');
        }
    });

    // Lógica do botão "Trocar Destinatário" (o 'x' no badge)
    trocarBtn.addEventListener('click', function() {
        // Limpa os campos
        hiddenInput.value = '';
        destinatarioNome.textContent = '';
        
        // Altera a visibilidade de volta ao estado inicial
        areaSelecao.classList.remove('d-none');
        destinatarioSelecionado.classList.add('d-none');
        areaEscrita.classList.add('d-none');
        
        // Limpa a busca
        buscaInput.value = '';
        contatoItems.forEach(item => { item.style.display = 'flex'; });
    });
});
</script>
<?php include '../partials/footer.php'; // Ajuste o caminho ?>