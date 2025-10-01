<?php
//Inclui o arquivo de proteção, só loga se for um usuário cadastrado
require __DIR__ . '/protect.php';

//Verifica se o usuário é admin
if ($_SESSION['tipo'] === 'secretaria') {
  header('Location: admin.php');
  exit;
}
include __DIR__ . '/partials/portal_header.php';
?>

        <!-- Conteúdo principal -->
        <div class="main">
            <div class="content">
                <h1 class="text-3xl font-bold">Solicitações</h1>
                <a href="solicitacoes_create.php">Nova solicitação</a>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/partials/footer.php'; ?>