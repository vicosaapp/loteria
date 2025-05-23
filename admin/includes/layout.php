<?php
// Definir valor padrão para $content
$content = $content ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Loteria</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.1.3/css/rowGroup.bootstrap5.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #2c3e50;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }

        .sidebar-header h1 {
            font-size: 18px;
            margin: 0;
            font-weight: normal;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-nav a.active {
            background: #3498db;
            color: white;
        }

        .sidebar-nav i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-footer a:hover {
            color: white;
        }

        .sidebar-footer i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        }

        .page-header {
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header h1, .sidebar-nav span {
                display: none;
            }
            
            .sidebar-nav a {
                justify-content: center;
                padding: 15px 0;
            }
            
            .sidebar-nav i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Logo" class="logo">
                <h1>Administração</h1>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="index.php" class="<?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="gerenciar_jogos.php" class="<?php echo $currentPage == 'jogos' ? 'active' : ''; ?>">
                            <i class="fas fa-gamepad"></i>
                            <span>Gerenciar Jogos</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'importar_apostas' ? 'active' : ''; ?>" href="importar_apostas.php">
                            <i class="fas fa-file-import"></i> <span>Importar Apostas</span>
                        </a>
                    <li>
                        <a href="gerenciar_resultados.php" class="<?php echo $currentPage == 'resultados' ? 'active' : ''; ?>">
                            <i class="fas fa-trophy"></i>
                            <span>Resultados</span>
                        </a>
                    </li>
                    <li>
                        <a href="gerenciar_concursos.php" class="<?php echo $currentPage == 'concursos' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Gerenciar Concursos</span>
                        </a>
                    </li>
                </ul>
                    <li>
                        <a href="gerenciar_apostas.php" class="<?php echo $currentPage == 'apostas' ? 'active' : ''; ?>">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Gerenciar Apostas</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="gerenciar_usuarios.php" class="<?php echo $currentPage == 'usuarios' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Usuários</span>
                        </a>
                    </li>

                    <li>
                        <a href="adicionar_revendedor.php" class="<?php echo $currentPage == 'revendedores' ? 'active' : ''; ?>">
                            <i class="fas fa-user-tie"></i>
                            <span>Revendedores</span>
                        </a>
                    </li>
                    
                    <li class="<?php echo $currentPage === 'configuracoes' ? 'active' : ''; ?>">
                        <a href="configuracoes.php">
                            <i class="fas fa-cog"></i>
                            <span>Configurações</span>
                        </a>
                    </li>

            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <?php echo $content; ?>
        </div>
    </div>
    
    <!-- jQuery primeiro, depois Popper.js, depois Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.1.3/js/dataTables.rowGroup.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>