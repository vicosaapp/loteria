<?php
require_once 'config/database.php';
session_start(); // Garantir que a sessão está iniciada
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Se não estiver logado, redireciona para login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Busca informações do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
} catch(PDOException $e) {
    die("Erro ao buscar usuário: " . $e->getMessage());
}

// Define o título da página
$pageTitle = 'Sistema de Loteria';
require_once 'includes/header.php';

// Verificar se o usuário está logado
$usuarioLogado = isset($_SESSION['usuario_id']);

try {
    // Buscar jogos ativos com mais informações
    $stmt = $pdo->query("
        SELECT 
            j.*, 
            COALESCE((
                SELECT COUNT(*) 
                FROM apostas 
                WHERE tipo_jogo_id = j.id
            ), 0) as total_apostas,
            
            COALESCE((
                SELECT COUNT(*) 
                FROM ganhadores g 
                INNER JOIN apostas a ON g.aposta_id = a.id 
                WHERE a.tipo_jogo_id = j.id 
                AND g.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ), 0) as ganhadores_semana,
            
            COALESCE((
                SELECT MIN(ca.valor_aposta) 
                FROM configuracoes_apostas ca
                WHERE ca.tipo_jogo_id = j.id 
                AND ca.status = 'ativo'
            ), j.valor) as valor_aposta_min,
            
            COALESCE((
                SELECT MAX(ca.valor_premio) 
                FROM configuracoes_apostas ca
                WHERE ca.tipo_jogo_id = j.id 
                AND ca.status = 'ativo'
            ), j.premio) as valor_premio_max
        FROM jogos j 
        WHERE j.status = 1 
        ORDER BY j.created_at DESC
    ");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro ao buscar jogos: " . $e->getMessage());
}

// Array de cores para os cards
$cardColors = [
    [
        'gradient' => 'linear-gradient(135deg, #4e73df, #224abe)',
        'accent' => '#4e73df'
    ],
    [
        'gradient' => 'linear-gradient(135deg, #2ecc71, #27ae60)',
        'accent' => '#2ecc71'
    ],
    [
        'gradient' => 'linear-gradient(135deg, #e74c3c, #c0392b)',
        'accent' => '#e74c3c'
    ],
    [
        'gradient' => 'linear-gradient(135deg, #9b59b6, #8e44ad)',
        'accent' => '#9b59b6'
    ],
    [
        'gradient' => 'linear-gradient(135deg, #f1c40f, #f39c12)',
        'accent' => '#f1c40f'
    ],
    [
        'gradient' => 'linear-gradient(135deg, #1abc9c, #16a085)',
        'accent' => '#1abc9c'
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loteria Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            color: #1a1a1a;
        }
        
        /* Header */
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
        
        /* Conteúdo Principal */
        .main-content {
            margin-top: 70px;
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Hero Section */
        .hero {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .hero h1 {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .hero p {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }
        
        .cta-button {
            background: #4e73df;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78,115,223,0.3);
        }
        
        /* Grid de Jogos */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .game-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .game-header {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .game-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1));
            transform: skewX(-15deg);
        }
        
        .game-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .game-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .game-body {
            padding: 25px;
        }
        
        .game-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .game-stat {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6e7687;
        }
        
        .stat-value {
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 600;
        }
        
        .game-description {
            color: #4a5568;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #edf2f7;
        }
        
        .game-footer {
            padding: 20px 25px;
            background: #f8f9fc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }
        
        .prize-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .prize-label {
            font-size: 0.85rem;
            color: #6e7687;
        }
        
        .prize-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
        }
        
        .play-button {
            background: #4e73df;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .play-button:hover {
            background: #224abe;
            transform: translateY(-2px);
        }
        
        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
        }
        
        .last-winners {
            margin-top: 15px;
            font-size: 0.9rem;
            color: #4a5568;
        }
        
        .winners-count {
            color: #4e73df;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .hero {
                padding: 30px 20px;
            }
            
            .hero h1 {
                font-size: 1.5rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
        }
        
        /* Estilo dinâmico para os cards */
        <?php foreach($cardColors as $index => $colors): ?>
        .game-card.color-<?php echo $index; ?> .game-header {
            background: <?php echo $colors['gradient']; ?>;
        }
        
        .game-card.color-<?php echo $index; ?> .play-button {
            background: <?php echo $colors['accent']; ?>;
        }
        
        .game-card.color-<?php echo $index; ?> .play-button:hover {
            background: <?php echo $colors['accent']; ?>;
            filter: brightness(90%);
        }
        
        .game-card.color-<?php echo $index; ?> .game-stat i,
        .game-card.color-<?php echo $index; ?> .winners-count {
            color: <?php echo $colors['accent']; ?>;
        }
        <?php endforeach; ?>
        
        /* Ajuste no hover dos cards */
        <?php foreach($cardColors as $index => $colors): ?>
        .game-card.color-<?php echo $index; ?>:hover {
            border-color: <?php echo $colors['accent']; ?>;
        }
        <?php endforeach; ?>

        .numeros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
            gap: 10px;
            padding: 15px;
        }

        .numero-btn {
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: #f0f2f5;
            color: #2d3748;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .numero-btn:hover {
            background: #e2e8f0;
        }

        .numero-btn.selected {
            background: #4e73df;
            color: white;
        }

        .info-card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .info-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
        }

        /* Estilos para o Modal */
        .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .game-banner {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #dee2e6;
        }

        .prize-highlight {
            margin-bottom: 1.5rem;
        }

        .prize-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .prize-value {
            font-size: 2rem;
            font-weight: 700;
            color: #4e73df;
        }

        .game-rules {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .instructions {
            font-size: 0.95rem;
        }

        .instructions ul {
            padding-left: 1.2rem;
            margin-top: 0.5rem;
        }

        .instructions li {
            margin-bottom: 0.3rem;
        }

        .section-title {
            color: #495057;
            font-weight: 600;
        }

        .numeros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
            gap: 8px;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .numero-btn {
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: white;
            color: #495057;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .numero-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .numero-btn.selected {
            background: #4e73df;
            color: white;
            transform: scale(1.1);
        }

        .selected-display {
            min-height: 60px;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .selected-display .placeholder {
            color: #6c757d;
            font-style: italic;
        }

        .selected-number {
            background: #4e73df;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            animation: popIn 0.3s ease;
        }

        .bet-info {
            color: #6c757d;
        }

        .selected-count {
            font-weight: 600;
            color: #4e73df;
        }

        @keyframes popIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
                
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    Início
                </a>
                
                <a href="minhas_apostas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'minhas_apostas.php' ? 'active' : ''; ?>">
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

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <h1>Aposte e Ganhe na Loteria Online</h1>
            <p>Faça suas apostas de forma rápida, segura e com as melhores chances de ganhar!</p>
            <a href="register.php" class="cta-button">Comece a Apostar</a>
        </section>

        <!-- Grid de Jogos -->
        <div class="games-grid">
            <?php foreach($jogos as $index => $jogo): 
                $colorIndex = $index % count($cardColors);
            ?>
                <div class="game-card color-<?php echo $colorIndex; ?>">
                    <div class="game-header">
                        <h2 class="game-title">
                            <i class="fas fa-star"></i>
                            <?php echo htmlspecialchars($jogo['nome']); ?>
                        </h2>
                        <p class="game-subtitle">
                            <i class="fas fa-check-circle"></i>
                            Escolha <?php echo $jogo['dezenas_premiar']; ?> números
                        </p>
                        <div class="status-badge">
                            <i class="fas fa-fire"></i> Ativo
                        </div>
                    </div>
                    
                    <div class="game-body">
                        <div class="game-description">
                            Aposte em <?php echo $jogo['dezenas_premiar']; ?> números entre 1 e <?php echo $jogo['total_numeros']; ?> 
                            e concorra a prêmios incríveis! Quanto mais você apostar, maiores suas chances de ganhar.
                        </div>
                        
                        <div class="game-info-grid">
                            <div class="game-stat">
                                <span class="stat-label">Total de Números</span>
                                <span class="stat-value">
                                    <i class="fas fa-hashtag"></i>
                                    <?php echo $jogo['total_numeros']; ?>
                                </span>
                            </div>
                            
                            <div class="game-stat">
                                <span class="stat-label">Apostas Realizadas</span>
                                <span class="stat-value">
                                    <i class="fas fa-users"></i>
                                    <?php echo number_format($jogo['total_apostas'], 0, ',', '.'); ?>
                                </span>
                            </div>
                            
                            <div class="game-stat">
                                <span class="stat-label">Valor da Aposta</span>
                                <span class="stat-value">
                                    <i class="fas fa-ticket-alt"></i>
                                    A partir de R$ <?php echo number_format($jogo['valor_aposta_min'] ?? 0, 2, ',', '.'); ?>
                                </span>
                            </div>
                            
                            <div class="game-stat">
                                <span class="stat-label">Chance de Ganhar</span>
                                <span class="stat-value">
                                    <i class="fas fa-percentage"></i>
                                    1 em <?php echo number_format($jogo['total_numeros'] / $jogo['dezenas_premiar'], 0, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="last-winners">
                            <span class="winners-count">
                                <?php echo rand(1, 5); ?> ganhadores
                            </span> 
                            nos últimos 7 dias
                        </div>
                    </div>
                    
                    <div class="game-footer">
                        <div class="prize-info">
                            <span class="prize-label">Prêmio Máximo</span>
                            <span class="prize-value">
                                R$ <?php echo number_format($jogo['valor_premio_max'] ?? 0, 2, ',', '.'); ?>
                            </span>
                        </div>
                        
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <button type="button" class="play-button" data-bs-toggle="modal" data-bs-target="#modalAposta<?php echo $jogo['id']; ?>" data-numero-maximo="<?php echo $jogo['dezenas_premiar']; ?>" data-valor-aposta="<?php echo $jogo['valor']; ?>">
                                <i class="fas fa-play-circle"></i>
                                Apostar Agora
                            </button>
                        <?php else: ?>
                            <a href="./login.php?redirect=apostador/fazer_aposta&jogo_id=<?php echo $jogo['id']; ?>" class="play-button">
                                <i class="fas fa-play-circle"></i>
                                Apostar Agora
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modal de Apostas -->
                <div class="modal fade" id="modalAposta<?php echo $jogo['id']; ?>" 
                     data-numero-maximo="<?php echo $jogo['dezenas_premiar']; ?>"
                     data-valor-aposta="<?php echo $jogo['valor']; ?>"
                     tabindex="-1" 
                     aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header" style="background: linear-gradient(135deg, #4e73df, #224abe); color: white;">
                                <h5 class="modal-title">
                                    <i class="fas fa-star me-2"></i>
                                    <?php echo htmlspecialchars($jogo['nome']); ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            
                            <!-- Banner do Jogo -->
                            <div class="game-banner p-4 text-center bg-light">
                                <div class="prize-highlight mb-3">
                                    <div class="prize-label">Prêmio Máximo</div>
                                    <div class="prize-value">R$ <?php echo number_format($jogo['premio'], 2, ',', '.'); ?></div>
                                </div>
                                <div class="game-rules">
                                    <span class="badge bg-primary me-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Escolha <?php echo $jogo['dezenas_premiar']; ?> números
                                    </span>
                                    <span class="badge bg-success">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        Valor: R$ <?php echo number_format($jogo['valor'], 2, ',', '.'); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="modal-body">
                                <!-- Instruções -->
                                <div class="instructions alert alert-info mb-4">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        Como Jogar
                                    </h6>
                                    <ul class="mb-0">
                                        <li>Selecione exatamente <?php echo $jogo['dezenas_premiar']; ?> números</li>
                                        <li>Os números vão de 1 até <?php echo $jogo['total_numeros']; ?></li>
                                        <li>Quanto mais acertos, maior o prêmio</li>
                                        <li>Boa sorte!</li>
                                    </ul>
                                </div>

                                <!-- Grid de Números -->
                                <div class="numbers-container">
                                    <h6 class="section-title mb-3">
                                        <i class="fas fa-th me-2"></i>
                                        Escolha seus números
                                    </h6>
                                    <div class="numeros-grid" id="numerosGrid<?php echo $jogo['id']; ?>">
                                        <?php for($i = 1; $i <= $jogo['total_numeros']; $i++): ?>
                                            <button class="numero-btn" data-numero="<?php echo $i; ?>">
                                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <!-- Números Selecionados -->
                                <div class="selected-numbers mt-4">
                                    <h6 class="section-title mb-3">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Números Selecionados
                                    </h6>
                                    <div class="selected-display" id="selectedNumbers<?php echo $jogo['id']; ?>">
                                        <div class="placeholder">Nenhum número selecionado</div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <div class="bet-info">
                                        <span class="selected-count">0</span> de <?php echo $jogo['dezenas_premiar']; ?> números selecionados
                                    </div>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                            <i class="fas fa-times me-2"></i>
                                            Cancelar
                                        </button>
                                        <button type="button" class="btn btn-primary" id="btnConfirmarAposta<?php echo $jogo['id']; ?>" disabled>
                                            <i class="fas fa-check me-2"></i>
                                            Confirmar Aposta
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para inicializar cada modal de jogo
        function initializeGameModal(jogoId, numeroMaximo) {
            const numerosGrid = document.getElementById(`numerosGrid${jogoId}`);
            const btnConfirmar = document.getElementById(`btnConfirmarAposta${jogoId}`);
            const selectedDisplay = document.getElementById(`selectedNumbers${jogoId}`);
            let numerosEscolhidos = [];

            function atualizarDisplay() {
                selectedDisplay.innerHTML = '';
                if (numerosEscolhidos.length === 0) {
                    selectedDisplay.innerHTML = '<div class="placeholder">Nenhum número selecionado</div>';
                } else {
                    numerosEscolhidos.sort((a, b) => a - b).forEach(numero => {
                        const div = document.createElement('div');
                        div.className = 'selected-number';
                        div.textContent = String(numero).padStart(2, '0');
                        selectedDisplay.appendChild(div);
                    });
                }

                // Atualizar contador no modal específico
                const countElements = document.querySelectorAll(`#modalAposta${jogoId} .selected-count`);
                countElements.forEach(el => el.textContent = numerosEscolhidos.length);

                // Habilitar/desabilitar botão confirmar
                btnConfirmar.disabled = numerosEscolhidos.length !== numeroMaximo;
            }

            if (numerosGrid) {
                numerosGrid.addEventListener('click', function(e) {
                    if (e.target.classList.contains('numero-btn')) {
                        const numero = parseInt(e.target.dataset.numero);
                        
                        if (e.target.classList.contains('selected')) {
                            e.target.classList.remove('selected');
                            numerosEscolhidos = numerosEscolhidos.filter(n => n !== numero);
                        } else if (numerosEscolhidos.length < numeroMaximo) {
                            e.target.classList.add('selected');
                            numerosEscolhidos.push(numero);
                        }
                        
                        atualizarDisplay();
                    }
                });
            }

            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', function() {
                    if (numerosEscolhidos.length === numeroMaximo) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Processando...',
                            text: 'Salvando sua aposta',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Enviar aposta
                        fetch('ajax/salvar_aposta.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                jogo_id: jogoId,
                                numeros: numerosEscolhidos.sort((a, b) => a - b),
                                valor: document.querySelector(`#modalAposta${jogoId}`).dataset.valorAposta
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Erro na requisição');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Fechar o modal de aposta
                                const modalInstance = bootstrap.Modal.getInstance(document.getElementById(`modalAposta${jogoId}`));
                                modalInstance.hide();

                                // Mostrar mensagem de sucesso
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Aposta Realizada!',
                                    text: 'Sua aposta foi registrada com sucesso.',
                                    showDenyButton: true,
                                    confirmButtonText: 'Ver Minhas Apostas',
                                    denyButtonText: 'Fazer Nova Aposta',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'minhas_apostas.php';
                                    } else {
                                        location.reload();
                                    }
                                });
                            } else {
                                throw new Error(data.message || 'Erro ao realizar aposta');
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Ops!',
                                text: error.message || 'Ocorreu um erro ao processar sua aposta. Tente novamente.',
                                confirmButtonText: 'Tentar Novamente'
                            });
                        });
                    }
                });
            }

            // Reset modal ao fechar
            const modal = document.getElementById(`modalAposta${jogoId}`);
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function () {
                    numerosEscolhidos = [];
                    modal.querySelectorAll('.numero-btn').forEach(btn => btn.classList.remove('selected'));
                    atualizarDisplay();
                });
            }
        }

        // Inicializar todos os modais de jogos
        document.querySelectorAll('[id^="modalAposta"]').forEach(modal => {
            const jogoId = modal.id.replace('modalAposta', '');
            const numeroMaximo = parseInt(modal.dataset.numeroMaximo);
            initializeGameModal(jogoId, numeroMaximo);
        });
    });
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?> 