<?php
//Inclui o arquivo de proteção, só loga se for um usuário cadastrado
require __DIR__ . '/protect.php';

//Verifica se o usuário é admin
if ($_SESSION['tipo'] === 'secretaria') {
  header('Location: admin.php');
  exit;
}

// partials/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$userName = $_SESSION['nome_completo'] ?? null;
$userRole = $_SESSION['tipo'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sidebar + Navbar</title>
  <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
  <link href="partials/css/style.css" rel="stylesheet">
  <link rel="stylesheet" href="partials/css/style.css">
</head>
<body>
  <!-- =================== NAVBAR FIXA =================== -->
   <!-- Navbar fixa no topo -->
    <nav class="top-navbar">
        <button id="toggle-btn">
            <i class="lni lni-grid-alt"></i>
        </button>
        <a class="logo" href="#">ETC</a>
    </nav>
    
    <div class="wrapper">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="sidebar-logo">
                <a href="#">ETC</a>
            </div>
            
            <ul id="sidebar-nav">
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link">
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
                            <a href="#" class="sidebar-link">Declarações</a>
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

        <!-- Conteúdo principal -->
        <div class="main">
            <div class="content">
                <h1 class="text-3xl font-bold">Bem-vindo ao Portal do Aluno</h1>
                <p class="text-gray-700 mt-4">Começar a criar a pagina principal.</p>
            </div>
        </div>
    </div>

  <script src="partials/js/script.js"></script>

<?php include __DIR__ . '/partials/footer.php'; ?>