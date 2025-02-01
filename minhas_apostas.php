<?php
require_once 'config/database.php';
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Buscar informações do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        // Se não encontrar o usuário, fazer logout
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch(PDOException $e) {
    die("Erro ao buscar usuário: " . $e->getMessage());
}

try {
    // Buscar apostas do usuário
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            j.nome as jogo_nome,
            j.dezenas_premiar,
            j.total_numeros,
            COALESCE(j.premio, 0) as premio_possivel,
            COALESCE(a.valor_aposta, 0) as valor_aposta,
            COALESCE(j.nome, 'Jogo não encontrado') as jogo_nome
        FROM apostas a
        LEFT JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE a.usuario_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro ao buscar apostas: " . $e->getMessage());
}

// Função helper para tratar strings
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Função helper para formatar valor
function formatMoney($value) {
    return number_format(floatval($value), 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Apostas - Loteria Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .app-header {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .nav-link.btn-sair {
            background: rgba(220,53,69,0.1);
            color: #fff;
        }

        .nav-link.btn-sair:hover {
            background: rgba(220,53,69,0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 25px;
            margin-right: 1rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-name {
            font-weight: 500;
            color: white;
        }

        /* Ajuste para o conteúdo principal */
        .main-content {
            margin-top: 80px;
            padding: 2rem;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .aposta-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
            overflow: hidden;
        }

        .aposta-card:hover {
            transform: translateY(-5px);
        }

        .aposta-header {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
            padding: 1rem;
            position: relative;
        }

        .aposta-body {
            padding: 1.5rem;
        }

        .numeros-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .numero-bola {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #2d3748;
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pendente {
            background: #ffd700;
            color: #000;
        }

        .status-aprovada {
            background: #28a745;
            color: white;
        }

        .status-rejeitada {
            background: #dc3545;
            color: white;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }

        .info-label {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: #2d3748;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .empty-icon {
            font-size: 4rem;
            color: #4e73df;
            margin-bottom: 1rem;
        }

        .btn-light {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }

        .btn-light:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
            transform: translateY(-2px);
        }

        .page-header .btn-lg {
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            border-radius: 50px;
        }

        .navbar {
            background: linear-gradient(135deg, #4e73df, #224abe);
            padding: 1rem 0;
        }

        .navbar-brand {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            margin: 0 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }

        .nav-link.active {
            color: white !important;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header class="app-header">
        <a href="index.php" class="logo">
            <i class="fas fa-star"></i>
            Loteria Online
        </a>
        
        <nav class="nav-menu">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($usuario['nome']); ?></span>
                </div>
                
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Início
                </a>
                
                <a href="minhas_apostas.php" class="nav-link active">
                    <i class="fas fa-ticket-alt"></i>
                    Minhas Apostas
                </a>
                
                <a href="logout.php" class="nav-link btn-sair">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="main-content">
        <div class="page-header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-ticket-alt me-2"></i>Minhas Apostas</h1>
                        <p class="mb-0">Acompanhe todas as suas apostas realizadas</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-5">
            <?php if (empty($apostas)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3>Nenhuma aposta encontrada</h3>
                    <p class="text-muted">Você ainda não realizou nenhuma aposta. Que tal começar agora?</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Fazer Nova Aposta
                    </a>
                </div>
            <?php else: ?>
                <?php foreach($apostas as $aposta): ?>
                    <div class="aposta-card">
                        <div class="aposta-header">
                            <h5 class="mb-0">
                                <i class="fas fa-gamepad me-2"></i>
                                <?php echo e($aposta['jogo_nome']); ?>
                            </h5>
                            <span class="status-badge status-<?php echo e($aposta['status']); ?>">
                                <?php 
                                $statusIcon = [
                                    'pendente' => 'clock',
                                    'aprovada' => 'check-circle',
                                    'rejeitada' => 'times-circle'
                                ][$aposta['status'] ?? 'pendente'];
                                ?>
                                <i class="fas fa-<?php echo $statusIcon; ?> me-1"></i>
                                <?php echo ucfirst(e($aposta['status'])); ?>
                            </span>
                        </div>
                        <div class="aposta-body">
                            <div class="numeros-grid">
                                <?php 
                                $numeros = !empty($aposta['numeros']) ? explode(',', $aposta['numeros']) : [];
                                foreach($numeros as $numero): 
                                ?>
                                    <div class="numero-bola"><?php echo intval($numero); ?></div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar me-1"></i>
                                        Data da Aposta
                                    </div>
                                    <div class="info-value">
                                        <?php echo date('d/m/Y H:i', strtotime($aposta['created_at'] ?? 'now')); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        Valor da Aposta
                                    </div>
                                    <div class="info-value">
                                        R$ <?php echo formatMoney($aposta['valor_aposta']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-trophy me-1"></i>
                                        Prêmio Possível
                                    </div>
                                    <div class="info-value">
                                        R$ <?php echo formatMoney($aposta['premio_possivel']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 