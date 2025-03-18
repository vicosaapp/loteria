<?php
require_once 'config/database.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Removida a verificação de sessão e redirecionamento

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
        'gradient' => 'linear-gradient(135deg,rgb(3, 41, 166),rgb(8, 56, 158))', // Verde Original
        'accent' => '#03a64d'
    ],
    [
        'gradient' => 'linear-gradient(135deg,rgb(201, 188, 9),rgb(184, 197, 2))', // Vermelho
        'accent' => '#FF6B6B'
    ],
    [
        'gradient' => 'linear-gradient(135deg, #4834D4, #686DE0)', // Roxo
        'accent' => '#4834D4'
    ],
    [
        'gradient' => 'linear-gradient(135deg, #FF9F43, #FFA94D)', // Laranja
        'accent' => '#FF9F43'
    ],
    [
        'gradient' => 'linear-gradient(135deg,rgb(6, 102, 212),rgb(20, 90, 219))', // Azul
        'accent' => '#22A6B3'
    ],
    [
        'gradient' => 'linear-gradient(135deg,rgb(248, 151, 39),rgb(238, 137, 5))', // Rosa
        'accent' => '#F368E0'
    ],
    [
        'gradient' => 'linear-gradient(135deg,rgb(243, 241, 247),rgb(250, 250, 250))', // Amarelo
        'accent' => '#FFB142'
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Loteria - Sua Sorte Está Aqui!</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    
    <style>
        :root {
            --primary-green: #03a64d;
            --secondary-green: #2d8e59;
            --primary-gradient: linear-gradient(135deg, #03a64d, #2d8e59);
            --hover-gradient: linear-gradient(135deg, #2d8e59, #03a64d);
            --text-light: #ffffff;
            --text-dark: #2c3e50;
            --background-light: #f8f9fc;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
        }

        .navbar {
            background: var(--primary-gradient);
            padding: 0.5rem 0;
            box-shadow: 0 2px 15px rgba(3, 166, 77, 0.2);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            padding: 0;
        }

        .navbar-brand img {
            height: 50px;
            width: auto;
        }

        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
        }

        /* Banner Styles */
        .swiper {
            width: 100%;
            height: 500px;
        }

        .swiper-slide {
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .banner-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            width: 80%;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .banner-content h2 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .banner-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        /* Cards Styles */
        .game-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .game-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .game-card .card-header {
            padding: 1.2rem 0.8rem;
            position: relative;
            overflow: hidden;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .game-card .card-header h3 {
            font-size: 1.1rem;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            color: white;
        }

        .game-info {
            padding: 1.2rem 0.8rem;
            background: white;
        }

        .prize-container {
            text-align: center;
            margin-bottom: 0.8rem;
            padding: 0.8rem;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .prize-value {
            font-size: 1.4rem;
            font-weight: 700;
            display: block;
            line-height: 1.2;
        }

        .prize-label {
            color: #666;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-top: 2px;
        }

        .game-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            padding: 0.4rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .game-stat-item {
            display: flex;
            align-items: center;
            color: #555;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .game-stat-item i {
            font-size: 0.9rem;
            margin-right: 6px;
            padding: 5px;
            border-radius: 50%;
        }

        .btn-apostar {
            padding: 0.6rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            width: 100%;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-apostar i {
            margin-left: 6px;
            font-size: 0.8rem;
        }

        @media (max-width: 1400px) {
            .col-jogos {
                flex: 0 0 16.666667%;
                max-width: 16.666667%;
            }
        }

        @media (max-width: 1200px) {
            .col-jogos {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }

        @media (max-width: 992px) {
            .col-jogos {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }

        @media (max-width: 768px) {
            .col-jogos {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        @media (max-width: 576px) {
            .col-jogos {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        /* Features Section */
        .features-section {
            padding: 5rem 0;
            background: white;
        }

        .feature-item {
            text-align: center;
            padding: 2rem;
        }

        .feature-icon {
            font-size: 3rem;
            color: #03a64d;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        /* Footer */
        .footer {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem 0;
        }

        .btn-custom {
            background: var(--primary-gradient);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            color: white;
        }

        .btn-custom:hover {
            background: var(--hover-gradient);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(3, 166, 77, 0.3);
        }

        .stats-counter {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .swiper-pagination-bullet-active {
            background: #03a64d;
        }

        /* Atualizando links do footer */
        .footer a {
            color: var(--text-light) !important;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            color: var(--primary-green) !important;
            text-decoration: none;
        }

        .feature-item:hover .feature-icon {
            color: #2d8e59;
            transform: scale(1.1);
        }

        /* Estilizando os botões de navegação */
        .swiper-button-next,
        .swiper-button-prev {
            color: white;
            background: rgba(3, 166, 77, 0.5);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: rgba(45, 142, 89, 0.8);
        }

        .swiper-button-next::after,
        .swiper-button-prev::after {
            font-size: 20px;
        }

        /* Estilizando a paginação */
        .swiper-pagination-bullet {
            width: 12px;
            height: 12px;
            background: rgba(255, 255, 255, 0.7);
            opacity: 1;
        }

        /* Estilos para seção de Jogos em Destaque */
        .section-title {
            position: relative;
            margin-bottom: 60px;
            padding-bottom: 20px;
            text-align: center;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title p {
            color: #666;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="assets/img/logo.png" alt="LotoMinas" class="img-fluid">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/apostador">Área do Apostador</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/revendedor">Revendedor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary ms-2 px-4" href="/cadastro">Cadastre-se</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Banner Rotativo -->
    <div class="swiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide" style="background-image: linear-gradient(rgba(3, 166, 77, 0.7), rgba(45, 142, 89, 0.7)), url('assets/img/banner/banner1.jpg');">
                <div class="banner-content">
                    <h2>Bem-vindo ao LotoMinas</h2>
                    <p>Aposte nos melhores jogos e concorra a prêmios incríveis!</p>
                    <a href="/apostador" class="btn btn-primary btn-custom">Comece a Apostar</a>
                </div>
            </div>
            <div class="swiper-slide" style="background-image: linear-gradient(rgba(3, 166, 77, 0.7), rgba(45, 142, 89, 0.7)), url('assets/img/banner/banner2.jpg');">
                <div class="banner-content">
                    <h2>Prêmios Garantidos</h2>
                    <p>Milhares de ganhadores toda semana no LotoMinas. O próximo pode ser você!</p>
                    <a href="/cadastro" class="btn btn-success btn-custom">Cadastre-se Agora</a>
                </div>
            </div>
            <div class="swiper-slide" style="background-image: linear-gradient(rgba(3, 166, 77, 0.7), rgba(45, 142, 89, 0.7)), url('assets/img/banner/banner3.jpg');">
                <div class="banner-content">
                    <h2>Jogos Exclusivos</h2>
                    <p>As melhores chances de ganhar estão aqui no LotoMinas!</p>
                    <a href="/jogos" class="btn btn-warning btn-custom">Ver Jogos</a>
                </div>
            </div>
        </div>
        <div class="swiper-pagination"></div>
        <!-- Adicionar navegação -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>

    <!-- Jogos em Destaque -->
    <section class="py-5">
        <div class="container-fluid px-4">
            <div class="section-title">
                <h2>Jogos em Destaques</h2>
                <p>Escolha seus jogos favoritos e comece a ganhar</p>
            </div>
            
            <div class="row">
                <?php foreach ($jogos as $index => $jogo): 
                    $currentColor = $cardColors[$index % count($cardColors)];
                ?>
                    <div class="col-jogos" style="flex: 0 0 14.285714%; max-width: 14.285714%;">
                        <div class="game-card">
                            <div class="card-header" style="background: <?php echo $currentColor['gradient']; ?>">
                                <h3 class="mb-0"><?php echo htmlspecialchars($jogo['nome']); ?></h3>
                            </div>
                            <div class="game-info">
                                <div class="prize-container">
                                    <span class="prize-value" style="color: <?php echo $currentColor['accent']; ?>">
                                        R$ <?php echo number_format($jogo['valor_premio_max'], 2, ',', '.'); ?>
                                    </span>
                                    <span class="prize-label">Prêmio Máximo</span>
                                </div>
                                
                                <div class="game-stats">
                                    <div class="game-stat-item">
                                        <i class="fas fa-trophy" style="background: <?php echo $currentColor['accent']; ?>20; color: <?php echo $currentColor['accent']; ?>"></i>
                                        <span><?php echo $jogo['ganhadores_semana']; ?> ganhadores</span>
                                    </div>
                                    <div class="game-stat-item">
                                        <i class="fas fa-ticket-alt" style="background: <?php echo $currentColor['accent']; ?>20; color: <?php echo $currentColor['accent']; ?>"></i>
                                        <span>R$ <?php echo number_format($jogo['valor_aposta_min'], 2, ',', '.'); ?></span>
                                    </div>
                                </div>
                                
                                <a href="/apostador/jogar/<?php echo $jogo['id']; ?>" class="btn btn-apostar" style="background: <?php echo $currentColor['gradient']; ?>">
                                    Apostar <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt feature-icon"></i>
                        <h4>100% Seguro</h4>
                        <p>Suas apostas são protegidas e seus dados estão seguros conosco.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item">
                        <i class="fas fa-money-bill-wave feature-icon"></i>
                        <h4>Pagamento Rápido</h4>
                        <p>Receba seus prêmios de forma rápida e segura.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item">
                        <i class="fas fa-headset feature-icon"></i>
                        <h4>Suporte 24/7</h4>
                        <p>Nossa equipe está sempre pronta para ajudar você.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Sobre o LotoMinas</h5>
                    <p>Sistema de loteria confiável e transparente, oferecendo as melhores chances de ganhar em Minas Gerais.</p>
                </div>
                <div class="col-md-4">
                    <h5>Links Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="/como-jogar" class="text-white">Como Jogar</a></li>
                        <li><a href="/resultados" class="text-white">Resultados</a></li>
                        <li><a href="/regulamento" class="text-white">Regulamento</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contato</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> contato@loteria.test</li>
                        <li><i class="fas fa-phone me-2"></i> (00) 0000-0000</li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4 mb-4 border-light">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 LotoMinas. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
        const swiper = new Swiper('.swiper', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            }
        });
    </script>
</body>
</html>

<?php require_once 'includes/layout.php'; ?> 