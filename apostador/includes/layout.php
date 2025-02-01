<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loteria App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f0f2f5;
            color: #1a1a1a;
            padding-bottom: 80px; /* Espaço para o menu inferior */
        }
        
        /* Header do App */
        .app-header {
            background: #4e73df;
            color: white;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .app-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .user-balance {
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        /* Conteúdo Principal */
        .app-content {
            margin-top: 60px;
            padding: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Menu de Navegação Inferior */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #858796;
            font-size: 0.8rem;
            padding: 5px 0;
        }
        
        .nav-item.active {
            color: #4e73df;
        }
        
        .nav-item i {
            font-size: 1.2rem;
            margin-bottom: 4px;
        }
        
        /* Cards e Grids */
        .app-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .app-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .app-card-header h2 {
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 600;
        }
        
        /* Botões */
        .app-button {
            background: #4e73df;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .app-button:hover {
            background: #2e59d9;
        }
        
        /* Responsividade */
        @media (min-width: 768px) {
            body {
                padding-bottom: 0;
                padding-left: 80px;
            }
            
            .bottom-nav {
                left: 0;
                top: 0;
                bottom: 0;
                width: 80px;
                flex-direction: column;
                padding: 20px 0;
            }
            
            .app-header {
                left: 80px;
            }
        }
        
        /* Menu Lateral */
        .side-menu {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            background: white;
            z-index: 1000;
            transition: left 0.3s ease;
            box-shadow: 2px 0 8px rgba(0,0,0,0.1);
        }
        
        .side-menu.active {
            left: 0;
        }
        
        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .menu-overlay.active {
            display: block;
        }
        
        .menu-header {
            background: #4e73df;
            color: white;
            padding: 20px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #4e73df;
        }
        
        .user-info h2 {
            font-size: 1.2rem;
            margin: 0;
            font-weight: 600;
        }
        
        .user-info p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .menu-items {
            padding: 15px 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #2d3748;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .menu-item:hover {
            background: #f8f9fc;
        }
        
        .menu-item i {
            width: 24px;
            margin-right: 15px;
            font-size: 1.1rem;
        }
        
        .menu-divider {
            height: 1px;
            background: #edf2f7;
            margin: 10px 0;
        }
        
        /* Ajuste no Header */
        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
        }
        
        .app-header {
            padding: 15px;
        }
    </style>
</head>
<body>
    <!-- Menu Lateral -->
    <div class="side-menu" id="sideMenu">
        <div class="menu-header">
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <h2><?php echo htmlspecialchars($_SESSION['nome']); ?></h2>
                    <p>R$ <?php echo number_format($_SESSION['saldo'] ?? 0, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>
        
        <nav class="menu-items">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Início</span>
            </a>
            <a href="minhas_apostas.php" class="menu-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Minhas Apostas</span>
            </a>
            <a href="meus_premios.php" class="menu-item">
                <i class="fas fa-trophy"></i>
                <span>Meus Prêmios</span>
            </a>
            <a href="carteira.php" class="menu-item">
                <i class="fas fa-wallet"></i>
                <span>Carteira</span>
            </a>
            
            <div class="menu-divider"></div>
            
            <a href="configuracoes.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
            <a href="ajuda.php" class="menu-item">
                <i class="fas fa-question-circle"></i>
                <span>Ajuda</span>
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </nav>
    </div>
    
    <!-- Overlay do Menu -->
    <div class="menu-overlay" id="menuOverlay"></div>

    <!-- Header do App -->
    <header class="app-header">
        <button class="menu-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </button>
        <h1><?php echo $pageTitle ?? 'Loteria App'; ?></h1>
        <div class="user-balance">
            R$ <?php echo number_format($_SESSION['saldo'] ?? 0, 2, ',', '.'); ?>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="app-content">
        <?php echo $content; ?>
    </main>

    <!-- Menu de Navegação Inferior -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Início</span>
        </a>
        <a href="minhas_apostas.php" class="nav-item <?php echo $currentPage === 'apostas' ? 'active' : ''; ?>">
            <i class="fas fa-ticket-alt"></i>
            <span>Apostas</span>
        </a>
        <a href="meus_premios.php" class="nav-item <?php echo $currentPage === 'premios' ? 'active' : ''; ?>">
            <i class="fas fa-trophy"></i>
            <span>Prêmios</span>
        </a>
        <a href="carteira.php" class="nav-item <?php echo $currentPage === 'carteira' ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i>
            <span>Carteira</span>
        </a>
        <a href="configuracoes.php" class="nav-item <?php echo $currentPage === 'configuracoes' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Config</span>
        </a>
    </nav>

    <script>
    function toggleMenu() {
        const menu = document.getElementById('sideMenu');
        const overlay = document.getElementById('menuOverlay');
        menu.classList.toggle('active');
        overlay.classList.toggle('active');
    }
    
    // Fechar menu ao clicar no overlay
    document.getElementById('menuOverlay').addEventListener('click', toggleMenu);
    
    // Fechar menu ao pressionar ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const menu = document.getElementById('sideMenu');
            const overlay = document.getElementById('menuOverlay');
            if (menu.classList.contains('active')) {
                menu.classList.remove('active');
                overlay.classList.remove('active');
            }
        }
    });
    </script>
</body>
</html> 