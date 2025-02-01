<?php
session_start();

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <style>
        body {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 10px 20px;
            margin: 5px 0;
        }
        .sidebar .nav-link:hover {
            background-color: #34495e;
        }
        .sidebar .nav-link.active {
            background-color: #3498db;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background-color: #f4f6f9;
        }
        .logo {
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #34495e;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h4>Painel Admin</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gerenciar_jogos.php' ? 'active' : ''; ?>" href="gerenciar_jogos.php">
                <i class="fas fa-gamepad"></i> Gerenciar Jogos
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'resultados.php' ? 'active' : ''; ?>" href="gerenciar_resultados.php">
                <i class="fas fa-trophy"></i> Resultados
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gerenciar_apostas.php' ? 'active' : ''; ?>" href="gerenciar_apostas.php">
                <i class="fas fa-ticket-alt"></i> Gerenciar Apostas
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
                <i class="fas fa-users"></i> Usuários
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : ''; ?>" href="configuracoes.php">
                <i class="fas fa-cog"></i> Configurações
            </a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
</body>
</html> 