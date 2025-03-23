<?php
/**
 * Arquivo de layout principal para o painel do revendedor
 */

// Sidebar deve estar escondida em telas pequenas inicialmente
$sidebarClass = 'sidebar';

// Adicionar o metaviewport se não existir
if (!isset($metaViewport)) {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $pageTitle ?? 'Painel Revendedor' ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <!-- CSS responsivo para mobile -->
    <link href="../assets/css/mobile.css" rel="stylesheet">
    
    <?php if (isset($headContent)) echo $headContent; ?>
</head>
<body>
    <!-- Botão para mostrar/esconder a barra lateral no mobile -->
    <button class="toggle-sidebar d-md-none">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para quando o menu lateral estiver aberto -->
    <div class="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <div class="<?= $sidebarClass ?>">
        <div class="sidebar-header">
            <img src="../assets/img/logo.png" alt="LotoMinas" class="sidebar-logo">
            <h3><?= htmlspecialchars($_SESSION['nome'] ?? 'Revendedor') ?></h3>
            <span class="text-muted">Revendedor</span>
        </div>
        
        <!-- Menu de navegação -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'dashboard' ? 'active' : '' ?>" href="index.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'clientes' ? 'active' : '' ?>" href="clientes.php">
                    <i class="fas fa-users"></i> Meus Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'apostas' ? 'active' : '' ?>" href="apostas.php">
                    <i class="fas fa-ticket-alt"></i> Apostas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'comissoes' ? 'active' : '' ?>" href="comissoes.php">
                    <i class="fas fa-dollar-sign"></i> Comissões
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'importar' ? 'active' : '' ?>" href="importar_apostas.php">
                    <i class="fas fa-file-import"></i> Importar Apostas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage == 'resultados' ? 'active' : '' ?>" href="resultados.php">
                    <i class="fas fa-trophy"></i> Resultados
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Conteúdo principal -->
    <div class="main-content">
        <?= $content ?? '' ?>
    </div>
    
    <!-- Navegação móvel -->
    <div class="mobile-nav">
        <a href="index.php" class="mobile-nav-item <?= $currentPage == 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="apostas.php" class="mobile-nav-item <?= $currentPage == 'apostas' ? 'active' : '' ?>">
            <i class="fas fa-ticket-alt"></i>
            <span>Apostas</span>
        </a>
        <a href="clientes.php" class="mobile-nav-item <?= $currentPage == 'clientes' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Clientes</span>
        </a>
        <a href="importar_apostas.php" class="mobile-nav-item <?= $currentPage == 'importar' ? 'active' : '' ?>">
            <i class="fas fa-file-import"></i>
            <span>Importar</span>
        </a>
        <a href="resultados.php" class="mobile-nav-item <?= $currentPage == 'resultados' ? 'active' : '' ?>">
            <i class="fas fa-trophy"></i>
            <span>Resultados</span>
        </a>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/mobile.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Clicar no botão de toggle para mostrar/esconder a barra lateral
        const toggleBtn = document.querySelector('.toggle-sidebar');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('active');
            });
        }
        
        // Clicar no overlay para esconder a barra lateral
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('active');
            });
        }
        
        // Clicar em links da barra lateral para escondê-la em dispositivos móveis
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('active');
                }
            });
        });
    });
    </script>
    
    <?php if (isset($footerContent)) echo $footerContent; ?>
</body>
</html> 