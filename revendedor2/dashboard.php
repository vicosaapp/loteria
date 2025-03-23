<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
$config_path = '../config/database.php';
$layout_path = 'includes/layout.php';

// Buscar informações do dashboard
try {
    // Inicializar variáveis com valores padrão
    $total_clientes = 0;
    $total_apostas = 0;
    $total_comissoes = 0;
    $apostas_pendentes = 0;
    $apostas_recentes = [];

    // Verificar se a sessão do revendedor está ativa
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        throw new Exception("Sessão do revendedor não está ativa. Faça login novamente.");
    }

    // Verificar conexão com o banco de dados
    $pdo->query("SELECT 1");

    // Verificar se o revendedor existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
    $stmt->execute([$_SESSION['usuario_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("Revendedor não encontrado no sistema.");
    }

    // Total de clientes - consulta mais segura
$stmt = $pdo->prepare("
        SELECT COUNT(*) 
    FROM usuarios 
        WHERE revendedor_id = ? 
        AND tipo = 'usuario'
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $total_clientes = $stmt->fetchColumn() ?: 0;

    // Verificar se a tabela apostas existe
    $tables = $pdo->query("SHOW TABLES LIKE 'apostas'")->fetchAll();
    if (count($tables) === 0) {
        throw new Exception("Tabela 'apostas' não encontrada no banco de dados.");
    }

    // Total de apostas - consulta mais segura - ajustada para usar revendedor_id diretamente
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM apostas
        WHERE revendedor_id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
    $total_apostas = $stmt->fetchColumn() ?: 0;

    // Total de comissões - usar a tabela apostas_importadas também
    // Como não há campo valor_comissao, calcular a partir da comissão do revendedor e valor das apostas
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(a.valor_aposta * u.comissao / 100), 0) as comissao
        FROM apostas a 
        JOIN usuarios u ON u.id = ?
        WHERE a.revendedor_id = ? 
        AND a.status = 'aprovada'
    ");
    $stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id']]);
    $comissao_apostas = $stmt->fetchColumn() ?: 0;
    
    // Pegar comissões das apostas importadas também
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(a.valor_aposta * u.comissao / 100), 0) as comissao
        FROM apostas_importadas a 
        JOIN usuarios u ON u.id = ?
        WHERE a.revendedor_id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id']]);
    $comissao_importadas = $stmt->fetchColumn() ?: 0;
    
    $total_comissoes = $comissao_apostas + $comissao_importadas;

    // Apostas pendentes - consulta mais segura
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM apostas
        WHERE revendedor_id = ? 
        AND status = 'pendente'
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostas_pendentes = $stmt->fetchColumn() ?: 0;

    // Verificar se a tabela jogos existe
    $tables = $pdo->query("SHOW TABLES LIKE 'jogos'")->fetchAll();
    if (count($tables) === 0) {
        throw new Exception("Tabela 'jogos' não encontrada no banco de dados.");
    }

    // Apostas recentes - consulta mais segura - incluir apostas padrão e importadas
    try {
        // Apostas regulares
$stmt = $pdo->prepare("
    SELECT 
                a.id, 
                a.created_at, 
                a.numeros, 
                a.valor_aposta, 
                a.status,
                u.nome as cliente_nome,
                j.nome as jogo_nome
            FROM 
                apostas a 
                JOIN usuarios u ON a.usuario_id = u.id 
                LEFT JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE 
                a.revendedor_id = ?
            ORDER BY 
                a.created_at DESC
            LIMIT 5
");
$stmt->execute([$_SESSION['usuario_id']]);
        $apostas_regulares = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Apostas importadas
$stmt = $pdo->prepare("
    SELECT 
                a.id, 
                a.created_at, 
                a.numeros, 
                a.valor_aposta, 
                'aprovada' as status,
                u.nome as cliente_nome,
                a.jogo_nome as jogo_nome
            FROM 
                apostas_importadas a 
    JOIN usuarios u ON a.usuario_id = u.id
            WHERE 
                a.revendedor_id = ?
            ORDER BY 
                a.created_at DESC
            LIMIT 5
");
$stmt->execute([$_SESSION['usuario_id']]);
        $apostas_importadas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Combinar e ordenar por data
        $apostas_recentes = array_merge($apostas_regulares, $apostas_importadas);
        usort($apostas_recentes, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Limitar a 5 resultados
        $apostas_recentes = array_slice($apostas_recentes, 0, 5);
    } catch (Exception $e) {
        // Se houver erro na consulta de apostas recentes, apenas registre e continue
        error_log("Erro nas apostas recentes: " . $e->getMessage());
        $apostas_recentes = [];
    }

} catch (Exception $e) {
    // Log detalhado do erro
    error_log("Erro no dashboard [Detalhado]: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
    $error = "Erro ao carregar dados do dashboard: " . $e->getMessage();
    
    // Garantir que todas as variáveis tenham valores padrão em caso de erro
    $total_clientes = 0;
    $total_apostas = 0;
    $total_comissoes = 0;
    $apostas_pendentes = 0;
    $apostas_recentes = [];
}

// Define a página atual
$currentPage = 'dashboard';
$pageTitle = 'Dashboard';

// Inicia o buffer de saída
ob_start();
?>

<div class="container-fluid">
    <div class="dashboard-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="welcome-message">Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Revendedor'); ?>!</p>
    </div>
    
    <!-- Exibir mensagem de erro, se existir -->
    <?php if (isset($error) && !empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Cards de estatísticas adaptáveis para mobile -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card h-100 dashboard-card card-clients">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase">TOTAL DE CLIENTES</h6>
                            <h2 class="stat-value"><?php echo intval($total_clientes); ?></h2>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <a href="clientes.php" class="card-link">Ver detalhes <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card h-100 dashboard-card card-bets">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase">TOTAL DE APOSTAS</h6>
                            <h2 class="stat-value"><?php echo intval($total_apostas); ?></h2>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                    <a href="apostas.php" class="card-link">Ver apostas <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card h-100 dashboard-card card-commission">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase">COMISSÕES</h6>
                            <h2 class="stat-value">R$ <?php echo number_format((float)$total_comissoes, 2, ',', '.'); ?></h2>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <a href="comissoes.php" class="card-link">Ver detalhes <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card h-100 dashboard-card card-pending">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase">APOSTAS PENDENTES</h6>
                            <h2 class="stat-value"><?php echo intval($apostas_pendentes); ?></h2>
                        </div>
                        <div class="icon-container">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <span class="badge bg-success py-2 px-3 rounded-pill">
                        <i class="fas fa-check me-1"></i> Tudo em dia
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Seção de apostas recentes -->


<style>
    /* Estilos específicos do Dashboard */
    .dashboard-header {
        margin-bottom: 1.5rem;
    }
    
    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }
    
    .welcome-message {
        font-size: 1rem;
        color: #7f8c8d;
        margin-bottom: 0;
    }
    
    .dashboard-card {
        transition: all 0.3s ease;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .card-title {
        font-size: 0.8rem;
        color: #7f8c8d;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .icon-container {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .card-clients .icon-container {
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }
    
    .card-bets .icon-container {
        background-color: rgba(46, 204, 113, 0.1);
        color: #2ecc71;
    }
    
    .card-commission .icon-container {
        background-color: rgba(155, 89, 182, 0.1);
        color: #9b59b6;
    }
    
    .card-pending .icon-container {
        background-color: rgba(241, 196, 15, 0.1);
        color: #f1c40f;
    }
    
    .card-link {
        color: #3498db;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .card-link:hover {
        color: #2980b9;
        text-decoration: underline;
    }
    
    /* Estilos para as bolinhas de números */
    .numero-bolinha {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        background-color: #3498db;
        color: white;
        border-radius: 50%;
        margin: 0 2px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .numbers-container {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
    }
    
    @media (max-width: 768px) {
        .numbers-container {
            max-width: 150px;
            overflow: hidden;
        }
        
        .stat-value {
            font-size: 1.5rem;
        }
        
        .icon-container {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }
    }
</style>

<?php
// Captura a saída e define o conteúdo
$content = ob_get_clean();

// Inclui o layout
require_once 'includes/layout.php';
?> 