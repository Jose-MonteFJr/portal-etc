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
    <link href="/portal-etc/partials/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/portal-etc/partials/img/portal-etc-logo.png" type="image/png">
    <link rel="stylesheet" href="/portal-etc/partials/icons/css/bootstrap-icons.min.css">
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
            <i class="bi bi-grid"></i>
        </button>
        <a class="logo" href="/portal-etc/portal_home.php">
            <img src="/portal-etc/partials/img/portal-etc-logo.png" alt="Logo portal etc" width="70px">
        </a>
        <div class="ms-auto d-flex align-items-center">

            <div class="dropdown me-3">
                <a href="#" class="nav-link" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notificações">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notification-count" style="display: none;"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-center" aria-labelledby="notificationDropdown" id="notification-list">
                    <li><a class="dropdown-item text-center" href="#">Nenhuma notificação</a></li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-center text-muted small" href="#" id="clear-notifications-btn">
                            <i class="bi bi-check2-all"></i> Limpar notificações lidas
                        </a>
                    </li>
                </ul>
            </div>

            <button id="themeToggle" class="btn btn-sm btn-outline-primary me-3" type="button" title="Alternar tema">
                <i class="bi bi-brightness-high-fill"></i>
            </button>

            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" title="Perfil">
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
            <div class="sidebar-logo"></div>

            <ul id="sidebar-nav">
                <li class="sidebar-item">
                    <a href="/portal-etc/portal_home.php" class="sidebar-link" title="Home">
                        <i class="bi bi-house-door fs-3"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" title="Carga horária">
                        <i class="bi bi-alarm fs-4"></i>
                        <span>Carga horária</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link" title="Agenda">
                        <i class="bi bi-calendar-event fs-4"></i>
                        <span>Agenda</span>
                    </a>
                </li>
                <!-- Dropdown auto atendimento -->
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link has-dropdown collapsed"
                        data-bs-toggle="collapse" data-bs-target="#auto-atendimento"
                        aria-expanded="false" aria-controls="auto-atendimento" title="Auto atendimento">

                        <i class="bi bi-person-gear fs-4"></i>
                        <span>Auto Atendimento</span>

                        <i class="bi bi-plus-lg ms-auto dropdown-icon fs-6"></i>
                    </a>

                    <ul id="auto-atendimento" class="sidebar-dropdown collapse">
                        <li class="sidebar-item">
                            <a href="/portal-etc/solicitacao/solicitacoes_view_aluno_matricula.php" class="sidebar-link" title="Matrículas">
                                <i class="bi bi-person-vcard fs-5"></i> Matrículas
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="/portal-etc/solicitacao/solicitacoes_view_aluno.php" class="sidebar-link" title="Soicitações">
                                <i class="bi bi-file-earmark-text fs-5"></i> Solicitações
                            </a>
                        </li>
                    </ul>

                </li>
                <div class="sidebar-item">
                    <a href="#" class="sidebar-link" title="Avisos">
                        <i class="bi bi-megaphone fs-4"></i>
                        <span>Avisos</span>
                    </a>
                </div>
                <div class="sidebar-footer">
                    <a href="#" class="sidebar-link" title="Chat">
                        <i class="bi bi-chat-dots fs-4"></i>
                        <span>Chat</span>
                    </a>
                </div>
            </ul>
        </aside>