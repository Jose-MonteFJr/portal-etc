<?php
require     '../protect.php';
require     '../config/db.php';
require     '../helpers.php';
ensure_admin();

// --- LÓGICA DO BACK-END ---

// 1. Parâmetros de busca e paginação
$q       = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// 2. Construção do WHERE dinâmico
$clauses = [];
$params  = [];

if ($q !== '') {
    // Busca no título ou na descrição do aviso
    $clauses[] = "(a.titulo LIKE ? OR a.descricao LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

// 3. Consulta de contagem para a paginação
$countSql = "SELECT COUNT(a.id_aviso) 
             FROM aviso a
             $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$pages  = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// 4. Consulta principal para buscar os avisos
$sql = "SELECT
            a.id_aviso,
            a.titulo,
            a.caminho_imagem,
            -- Pega os primeiros 100 caracteres da descrição para uma prévia
            SUBSTRING(a.descricao, 1, 100) AS previa_descricao,
            a.created_at,
            u.nome_completo AS nome_autor
        FROM aviso a
        -- Junta com a tabela 'usuario' para pegar o nome de quem postou
        JOIN usuario u ON a.id_usuario_autor = u.id_usuario
        $whereSql
        ORDER BY a.created_at DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../partials/header.php'; // Ajuste o caminho
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h4 mb-0">Gerenciamento de Avisos</h2>
    <a class="btn btn-success" href="avisos_create.php">+ Novo Aviso</a>
</div>

<?php flash_show(); ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-center">
            <div class="col-md-10">
                <label for="q" class="form-label visually-hidden">Buscar</label>
                <input type="text" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar por título ou descrição do aviso...">
            </div>
            <div class="col-md-2 d-flex">
                <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                <a href="avisos_view.php" class="btn btn-outline-secondary ms-2" title="Limpar Filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Avisos publicados (<?php echo $total; ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagem</th>
                        <th>Título</th>
                        <th>Prévia da Descrição</th>
                        <th>Autor</th>
                        <th>Publicado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avisos)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum aviso encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avisos as $aviso): ?>
                            <tr>
                                <td>#<?php echo (int)$aviso['id_aviso']; ?></td>

                                <td>
                                    <?php if (!empty($aviso['caminho_imagem'])): ?>
                                        <img src="../uploads/avisos/<?php echo htmlspecialchars($aviso['caminho_imagem']); ?>"
                                            alt="Imagem do aviso" class="img-thumbnail"
                                            style="width: 100px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($aviso['titulo']); ?></td>
                                <td class="text-muted small">
                                    <?php echo htmlspecialchars($aviso['previa_descricao']); ?>...
                                </td>
                                <td><?php echo htmlspecialchars($aviso['nome_autor']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($aviso['created_at'])); ?></td>
                                <td class="text-end text-nowrap">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-secondary" href="avisos_edit.php?id_aviso=<?php echo (int)$aviso['id_aviso']; ?>">Editar</a>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#confirmDeleteModal"
                                            data-form-action="avisos_delete.php"
                                            data-item-id="<?php echo (int)$aviso['id_aviso']; ?>"
                                            data-item-name="<?php echo htmlspecialchars($aviso['titulo']); ?>"
                                            data-id-field="id_aviso">
                                            Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL DE EXCLUIR -->

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="confirmDeleteModalLabel">Confirmar Exclusão</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBodyMessage">
                Você tem certeza que deseja excluir este item?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Confirmar Exclusão</button>
            </div>
        </div>
    </div>
</div>

<?php include '../partials/footer.php';  ?>