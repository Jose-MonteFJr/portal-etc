<?php
// USA OS CAMINHOS RELATIVOS COM __DIR__
require __DIR__ . '/protect.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/helpers.php';

// 1. SEGURANÇA: Garante que apenas a secretaria acesse
if ($_SESSION['tipo'] !== 'secretaria') {
  flash_set('danger', 'Acesso negado.');
  header('Location: admin.php');
  exit;
}

// 2. Prepara e executa a consulta SQL (simplificada)
$id_usuario_logado = $_SESSION['id_usuario'];

$sql = "SELECT
            u.nome_completo, u.email, u.telefone, u.foto_perfil,
            e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep
        FROM usuario u
        LEFT JOIN endereco e ON u.id_usuario = e.id_usuario
        WHERE u.id_usuario = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario_logado]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC); // Renomeado de $aluno para $perfil

if (!$perfil) {
  flash_set('danger', 'Não foi possível carregar os dados do perfil.');
  header('Location: admin.php');
  exit;
}

// 3. Prepara as variáveis para exibição (lógica idêntica)
$foto_path = !empty($perfil['foto_perfil'])
  ? '/portal-etc/uploads/perfil/' . $perfil['foto_perfil']
  : '/portal-etc/partials/img/avatar_padrao.png';

// Monta o endereço formatado (lógica idêntica)
$endereco_formatado = 'Endereço não cadastrado.';
if (!empty($perfil['logradouro'])) {
  $endereco_formatado = $perfil['logradouro'] . ', ' . $perfil['numero'];
  if (!empty($perfil['complemento'])) {
    $endereco_formatado .= ' - ' . $perfil['complemento'];
  }
  $endereco_formatado .= '. ' . $perfil['bairro'] . ', ' . $perfil['cidade'] . ' - ' . $perfil['estado'];
}

// 4. HEADER: Carrega o header do admin
include __DIR__ . '/partials/admin_header.php';
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
                <h5 class="mt-3 mb-0"><?php echo htmlspecialchars($perfil['nome_completo']); ?></h5>
                <p class="text-muted small"><?php echo htmlspecialchars($perfil['email']); ?></p>
                <a href="foto_edit.php" class="btn btn-sm btn-outline-primary">Alterar Foto</a>
              </div>

              <h5 class="mb-3 border-bottom pb-2">Dados Pessoais e Contato</h5>
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                  <strong>Email:</strong>
                  <span><?php echo htmlspecialchars($perfil['email'] ?? 'Não informado'); ?></span>
                </li>
                <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                  <strong>Telefone:</strong>
                  <span><?php echo htmlspecialchars($perfil['telefone'] ?? 'Não informado'); ?></span>
                </li>
                <li class="list-group-item d-flex flex-column flex-md-row justify-content-md-between">
                  <strong>Endereço:</strong>
                  <span class="text-md-end"><?php echo htmlspecialchars($endereco_formatado); ?></span>
                </li>
              </ul>
            </div>

            <div class="card-footer text-end">
              <a href="editar_perfil.php" class="btn btn-sm btn-secondary">Editar Dados Pessoais</a>
              <a href="alterar_senha.php" class="btn btn-sm btn-outline-danger">Alterar Senha</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>