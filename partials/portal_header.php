<?php
// partials/portal_header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$userName = $_SESSION['nome_completo'] ?? null;
$userRole = $_SESSION['tipo'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
  <link href="/portal-etc/partials/css/style.css" rel="stylesheet">
  <!-- <link href="/portal-etc/partials/css/bootstrap.min.css" rel="stylesheet"> -->
  <link rel="icon" href="/portal-etc/partials/img/portal-etc-logo.png" type="image/png">
  <title>Portal ETC</title>
</head>
<body>
  <!-- =================== NAVBAR FIXA =================== -->
   <!-- Navbar fixa no topo -->
    <nav class="top-navbar">
        <button id="toggle-btn">
            <i class="lni lni-grid-alt"></i>
        </button>
        <a class="logo" href="portal_home.php">
            <img src="/portal-etc/partials/img/portal-etc-logo.png" alt="Logo portal etc" width="70px">
        </a>
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
                            <a href="/portal-etc/solicitacoes_view_aluno.php" class="sidebar-link">Declarações</a>
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
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link">
                        <i class="lni lni-popup"></i>
                        <span>Feed</span>
                    </a>
                </li>
                <!-- Rodapé da sidebar -->
                <div class="sidebar-footer">
                    <a href="/portal-etc/logout.php" class="sidebar-link">
                        <i class="lni lni-exit"></i>
                        <span>Sair</span>
                    </a>
                </div>
            </ul>
        </aside>