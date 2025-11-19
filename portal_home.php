<?php
//Inclui o arquivo de proteção, só loga se for um usuário cadastrado
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

//Verifica se o usuário é admin
if ($_SESSION['tipo'] === 'secretaria') {
  header('Location: admin.php');
  exit;
}
include __DIR__ . '/partials/portal_header.php';
?>

<?php flash_show(); ?>
<!-- Conteúdo principal -->
<div class="main">
    <div class="content">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <h1 class="text-3xl font-bold">Bem vindo(a) <?php echo htmlspecialchars($userName); ?>!</h1>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>