<?php
// partials/header.php
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
    <link rel="stylesheet" href="/portal-etc/partials/icons/css/bootstrap-icons.min.css">
    <link href="/portal-etc/partials/css/style.css" rel="stylesheet">
    <title>Portal ETC</title>
</head>

<body>
    <?php flash_show(); // Adicione a chamada da função bem aqui 
    ?>
    <!-- =================== NAVBAR FIXA =================== -->
    <!-- Navbar fixa no topo -->
<nav class="top-navbar d-flex justify-content-between align-items-center">
    <!-- Item da Esquerda: Logo -->
    <a class="logo" href="/portal-etc/portal_home.php">
        <img src="/portal-etc/partials/img/portal-etc-logo.png" alt="Logo portal etc" width="70px">
    </a>

    <!-- Item Central: Menu Dropdown Simplificado -->
    <div class="dropdown">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="menuPrincipal" data-bs-toggle="dropdown" aria-expanded="false">
            Acadêmicos
        </button>
        <ul class="dropdown-menu" aria-labelledby="menuPrincipal">
            <li><a class="dropdown-item" href="/portal-etc/solicitacao/solicitacoes_view_admin.php">Ver solicitações</a></li>
            <li><a class="dropdown-item" href="/portal-etc/curso/cursos_view.php">Ver cursos</a></li>
            <li><a class="dropdown-item" href="/portal-etc/turma/turmas_view.php">Ver turmas</a></li>
            <li><a class="dropdown-item" href="/portal-etc/modulo/modulos_view.php">Ver módulos</a></li>
            <li><a class="dropdown-item" href="/portal-etc/feed/avisos_view.php">Ver avisos</a></li>
            <li><a class="dropdown-item" href="/portal-etc/grade_horaria/horarios_definicao.php">Ver grade horária</a></li>
        </ul>
    </div>
    <!-- Item da Direita: Botões de Tema e Perfil -->
    <div class="d-flex align-items-center">
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
                    <a class="dropdown-item" href="/portal-etc/profile.php">
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
<div class="container">