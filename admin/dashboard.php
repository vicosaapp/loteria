<?php
require_once '../config/database.php';

// Buscar estatísticas
try {
    // Total de usuários (excluindo revendedores)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'usuario'");
    $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de revendedores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'revendedor'");
    $totalRevendedores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de jogos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM jogos");
    $totalJogos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $totalUsuarios = 0;
    $totalRevendedores = 0;
    $totalJogos = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Loteria</title>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="sb-nav-fixed">
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Dashboard</h1>
                    <div class="row mt-4">
                        <!-- Card Usuários -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Usuários Cadastrados</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalUsuarios; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Revendedores -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Revendedores</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalRevendedores; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Jogos -->
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Jogos Disponíveis</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalJogos; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-gamepad fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include __DIR__ . '/includes/layout.php'; ?>
        </div>
    </div>
</body>
</html> 