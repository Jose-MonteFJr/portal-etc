<?php
// partials/portal_header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$userName = $_SESSION['nome_completo'] ?? null;
$userRole = $_SESSION['tipo'] ?? null;

$foto_usuario_logado = !empty($_SESSION['foto_perfil'])
    ? '/portal-etc/uploads/perfil/' . $_SESSION['foto_perfil']
    : '/portal-etc/partials/img/avatar_padrao.png';
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
    <link href="/portal-etc/partials/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/portal-etc/partials/img/portal-etc-logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="/portal-etc/partials/css/style.css" rel="stylesheet">
    <title>Portal ETC</title>
</head>

<body>
    <?php flash_show(); // Adicione a chamada da função bem aqui 
    ?>
    <!-- =================== NAVBAR FIXA =================== -->
    <!-- Navbar fixa no topo -->
    <nav class="top-navbar">
        <button id="toggle-btn">
            <i class="lni lni-grid-alt"></i>
        </button>
        <a class="logo" href="/portal-etc/portal_home.php">
            <img src="/portal-etc/partials/img/portal-etc-logo.png" alt="Logo portal etc" width="70px">
        </a>
        <div class="ms-auto d-flex align-items-center">

            <button id="themeToggle" class="btn btn-sm btn-outline-primary me-3" type="button" title="Alternar tema">
                <i class="bi bi-brightness-high-fill"></i>
            </button>

            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="me-2 d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['nome_completo']); ?></span>
                    <img src="<?php echo htmlspecialchars($foto_usuario_logado); ?>" alt="Foto do Usuário"
                        class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="/portal-etc/perfil_aluno.php">
                            <i class="bi bi-person-fill me-2"></i> Meu Perfil
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="/portal-etc/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>



    <div class="wrapper">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="sidebar-logo">
                <a href="#">ETC</a>
            </div>

            <ul id="sidebar-nav">
                <li class="sidebar-item">
                    <a href="/portal-etc/portal_home.php" class="sidebar-link">
                        <i class="lni lni-home"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link">
                        <i class="lni lni-agenda"></i>
                        <span>Carga horária</span>
                    </a>
                </li>
                <!-- Dropdown: Solicitações -->
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link has-dropdown collapsed" data-toggle="collapse" data-target="#solicitacoes">
                        <i class="lni lni-lock"></i>
                        <span>Solicitações</span>
                    </a>
                    <ul id="solicitacoes" class="sidebar-dropdown collapse">
                        <li class="sidebar-item">
                            <a href="/portal-etc/solicitacao/solicitacoes_view_aluno.php" class="sidebar-link">Declarações</a>
                        </li>
                        <li class="sidebar-item">
                            <a href="#" class="sidebar-link">Certificados</a>
                        </li>
                    </ul>
                </li>
                <!-- Dropdown multi-nível -->
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link has-dropdown collapsed" data-toggle="collapse" data-target="#auto-atendimento">
                        <i class="lni lni-layers"></i>
                        <span>Auto Atendimento</span>
                    </a>
                    <ul id="auto-atendimento" class="sidebar-dropdown collapse">
                        <li class="sidebar-item">
                            <a href="#" class="sidebar-link has-dropdown collapsed" data-toggle="collapse" data-target="#matriculas">Matrículas</a>
                            <ul id="matriculas" class="sidebar-dropdown collapse">
                                <li class="sidebar-item">
                                    <a href="#" class="sidebar-link">Rematrículas</a>
                                </li>
                                <li class="sidebar-item">
                                    <a href="#" class="sidebar-link">Trancar curso</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <div class="sidebar-footer">
                    <a href="#" class="sidebar-link">
                        <i class="lni lni-popup"></i>
                        <span>Feed</span>
                    </a>
                </div>
                <!-- Rodapé da sidebar -->
<!--                 <div class="sidebar-footer">
                    <a href="/portal-etc/logout.php" class="sidebar-link">
                        <i class="lni lni-exit"></i>
                        <span>Sair</span>
                    </a>
                </div> -->
            </ul>
        </aside>