<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Loteria - Área do Apostador</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #4e73df;
            position: sticky;
            top: 56px;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 0;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        
        .content-wrapper {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fc;
        }
        
        .navbar-brand {
            font-weight: 600;
        }
        
        footer {
            background-color: #f8f9fc;
            border-top: 1px solid #e3e6f0;
            padding: 1rem 0;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <!-- Barra de navegação superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ticket-alt me-2"></i>Sistema de Loteria
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Apostador'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-cog me-2"></i>Meu Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Barra lateral -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Painel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'fazer_apostas' ? 'active' : ''; ?>" href="fazer_apostas.php">
                                <i class="fas fa-ticket-alt"></i> Fazer Apostas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'minhas_apostas' ? 'active' : ''; ?>" href="minhas_apostas.php">
                                <i class="fas fa-list"></i> Minhas Apostas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'perfil' ? 'active' : ''; ?>" href="perfil.php">
                                <i class="fas fa-user-cog"></i> Meu Perfil
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Conteúdo principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper">
                <?php echo $content ?? ''; ?>
                
                <!-- Rodapé -->
                <footer class="mt-5 text-center">
                    <div class="container">
                        <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Sistema de Loteria. Todos os direitos reservados.</p>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS (Bundle with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 