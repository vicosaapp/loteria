<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: index.php');
    exit;
}

// Buscar informações do dashboard
try {
    // Total de clientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE revendedor_id = ? AND tipo = 'apostador'");
    $stmt->execute([$_SESSION['usuario_id']]);
    $total_clientes = $stmt->fetchColumn();

    // Total de apostas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM apostas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE u.revendedor_id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $total_apostas = $stmt->fetchColumn();

    // Total de comissões
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor_comissao), 0) 
        FROM apostas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE u.revendedor_id = ? AND a.status = 'aprovada'
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $total_comissoes = $stmt->fetchColumn();

    // Apostas pendentes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM apostas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE u.revendedor_id = ? AND a.status = 'pendente'
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostas_pendentes = $stmt->fetchColumn();

    // Apostas recentes
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
            JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE 
            u.revendedor_id = ?
        ORDER BY 
            a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostas_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Erro no dashboard: " . $e->getMessage());
    $error = "Erro ao carregar dados do dashboard. Por favor, tente novamente mais tarde.";
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
    
    <div class="row">
        <!-- Cards de estatísticas adaptáveis para mobile -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
            <div class="card h-100 dashboard-card card-clients">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase">TOTAL DE CLIENTES</h6>
                            <h2 class="stat-value"><?php echo $total_clientes; ?></h2>
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
                            <h2 class="stat-value"><?php echo $total_apostas; ?></h2>
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
                            <h2 class="stat-value">R$ <?php echo number_format($total_comissoes, 2, ',', '.'); ?></h2>
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
                            <h2 class="stat-value"><?php echo $apostas_pendentes; ?></h2>
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
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Apostas Recentes</h5>
                    <a href="apostas.php" class="btn btn-sm btn-primary">Ver Todas</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Jogo</th>
                                    <th>Números</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-center">Data</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($apostas_recentes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">Nenhuma aposta recente encontrada.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($apostas_recentes as $aposta): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($aposta['cliente_nome'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($aposta['jogo_nome'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="numbers-container">
                                                    <?php 
                                                    $numeros = explode(',', $aposta['numeros'] ?? '');
                                                    foreach ($numeros as $numero) {
                                                        echo '<span class="numero-bolinha">' . str_pad(trim($numero), 2, '0', STR_PAD_LEFT) . '</span>';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <strong>R$ <?php echo number_format(($aposta['valor_aposta'] ?? 0), 2, ',', '.'); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($aposta['created_at'])): ?>
                                                    <?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php 
                                                    echo $aposta['status'] == 'aprovada' ? 'success' : 
                                                        ($aposta['status'] == 'rejeitada' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($aposta['status'] ?? ''); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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