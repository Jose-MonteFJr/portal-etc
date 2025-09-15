<?php
//Inclui o arquivo de proteção, só loga se for um usuário cadastrado
require __DIR__ . '/protect.php';

//Verifica se o usuário é admin
if ($_SESSION['tipo'] === 'secretaria') {
  header('Location: admin.php');
  exit;
}
//Inclui o cabeçalho 
include __DIR__ . '/partials/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Página do Usuário</h2>
  <span class="badge text-bg-secondary">Perfil: Aluno</span>
</div>

<div class="alert alert-success">
  <strong>Bem-vindo(a)!</strong> Você está logado como <u><?php echo htmlspecialchars($_SESSION['nome_completo']); ?></u>.
</div>

<p>Este é um exemplo simples. No canto superior (navbar) aparece o nome do usuário que está logado e o botão de sair.</p>
<!-- inclue o footer no código -->
<?php include __DIR__ . '/partials/footer.php'; ?>