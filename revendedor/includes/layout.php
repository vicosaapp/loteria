<?php if (!isset($content)) $content = ''; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Revendedor</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #2c3e50;
            --secondary-blue: #34495e;
            --highlight-blue: #3498db;
            --primary-green: #2ecc71;
            --secondary-green: #27ae60;
            --background-light: #f4f6f9;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--background-light);
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--primary-blue);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
        }
        
        .sidebar .nav-link {
            color: #fff;
            padding: 12px 20px;
            margin: 5px 0;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            background-color: var(--secondary-blue);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background-color: var(--highlight-blue);
            border-left: 4px solid white;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            min-width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn-success {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        
        .btn-success:hover {
            background-color: var(--secondary-green);
            border-color: var(--secondary-green);
        }
        
        .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid var(--secondary-blue);
        }
        
        .logo img {
            max-width: 100%;
            height: auto;
        }
        
        .user-info {
            padding: 15px 20px;
            border-bottom: 1px solid var(--secondary-blue);
            margin-bottom: 15px;
        }
        
        .user-info .name {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .user-info .role {
            font-size: 0.8rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/img/logo.png" alt="Logo" onerror="this.src='../assets/img/logo-placeholder.png'">
        </div>
        
        <div class="user-info">
            <div class="name"><?php echo htmlspecialchars($_SESSION['nome'] ?? 'Revendedor'); ?></div>
            <div class="role">Revendedor</div>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link <?php echo $currentPage == 'clientes' ? 'active' : ''; ?>" href="clientes.php">
                <i class="fas fa-users"></i> Meus Clientes
            </a>
            <a class="nav-link <?php echo $currentPage == 'apostas' ? 'active' : ''; ?>" href="apostas.php">
                <i class="fas fa-ticket-alt"></i> Apostas
            </a>
            <a class="nav-link <?php echo $currentPage == 'comissoes' ? 'active' : ''; ?>" href="comissoes.php">
                <i class="fas fa-dollar-sign"></i> Comissões
            </a>
            <a class="nav-link <?php echo $currentPage == 'importar_apostas' ? 'active' : ''; ?>" href="importar_apostas.php">
                <i class="fas fa-file-import"></i> Importar Apostas
            </a>
            <a class="nav-link <?php echo $currentPage == 'resultados' ? 'active' : ''; ?>" href="resultados.php">
                <i class="fas fa-trophy"></i> Resultados
            </a>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php echo $content; ?>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html> 