<?php
// Forçar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
session_start();

// Verificar se é apostador (usuário)
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'usuario') {
    header('Location: ../login.php');
    exit;
}

// Verificar se a coluna 'codigo' existe na tabela jogos
try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM jogos LIKE 'codigo'");
    if ($checkColumn->rowCount() == 0) {
        // A coluna não existe, vamos criá-la
        $pdo->exec("ALTER TABLE jogos ADD COLUMN codigo VARCHAR(10) NULL AFTER nome");
        
        // Adicionar códigos padrão para jogos existentes
        $jogosCodigosMap = [
            'Lotofácil' => 'LF',
            'Mega-Sena' => 'MS',
            'Quina' => 'QN',
            'Lotomania' => 'LM',
            'Timemania' => 'TM',
            'Dia de Sorte' => 'DI',
            'Mais Milionária' => 'MM'
        ];
        
        // Atualizar cada jogo com seu código
        foreach ($jogosCodigosMap as $nome => $codigo) {
            $stmt = $pdo->prepare("UPDATE jogos SET codigo = ? WHERE nome LIKE ?");
            $stmt->execute([$codigo, '%' . $nome . '%']);
        }
    }
} catch (PDOException $e) {
    error_log("Erro ao verificar/criar coluna 'codigo': " . $e->getMessage());
}

try {
    // Buscar dados do apostador
    $stmt = $pdo->prepare("SELECT id, nome, whatsapp, revendedor_id FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar dados do revendedor do apostador
    if ($apostador['revendedor_id']) {
        $stmt = $pdo->prepare("SELECT id, nome, whatsapp, telefone FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$apostador['revendedor_id']]);
        $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Buscar apostas do usuário
    $stmt = $pdo->prepare("
        SELECT a.*, j.nome as jogo_nome 
        FROM apostas a
        LEFT JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE a.usuario_id = ?
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostasRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar o total de apostas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apostas WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $totalApostas = $stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Erro ao carregar dados: " . $e->getMessage());
    $erro = "Erro ao carregar os dados. Por favor, tente novamente.";
}

// Define a página atual para o menu
$currentPage = 'dashboard';

// Carrega a view
ob_start();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">
            <i class="fas fa-home me-2 text-primary"></i> Bem-vindo, <?php echo htmlspecialchars($apostador['nome'] ?? 'Apostador'); ?>
        </h1>
    </div>
    
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <!-- Cards de resumo -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary h-100">
                <div class="card-body position-relative overflow-hidden">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="icon-circle bg-primary text-white">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs fw-bold text-uppercase mb-1 text-muted">
                                Total de Apostas
                            </div>
                            <div class="h2 mb-0 fw-bold"><?php echo $totalApostas; ?></div>
                        </div>
                    </div>
                    <div class="position-absolute opacity-15 bottom-0 end-0 m-2">
                        <i class="fas fa-ticket-alt fa-4x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success h-100">
                <div class="card-body position-relative overflow-hidden">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="icon-circle bg-success text-white">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs fw-bold text-uppercase mb-1 text-muted">
                                Data de Hoje
                            </div>
                            <div class="h5 mb-0 fw-bold"><?php echo date('d/m/Y'); ?></div>
                        </div>
                    </div>
                    <div class="position-absolute opacity-15 bottom-0 end-0 m-2">
                        <i class="fas fa-calendar-alt fa-4x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isset($revendedor)): ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info h-100">
                <div class="card-body position-relative overflow-hidden">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="icon-circle bg-info text-white">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs fw-bold text-uppercase mb-1 text-muted">
                                Seu Revendedor
                            </div>
                            <div class="h5 mb-0 fw-bold text-truncate"><?php echo htmlspecialchars($revendedor['nome']); ?></div>
                            <?php if (!empty($revendedor['whatsapp'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $revendedor['whatsapp']); ?>" class="btn btn-sm btn-outline-success mt-2">
                                    <i class="fab fa-whatsapp"></i> Contatar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="position-absolute opacity-15 bottom-0 end-0 m-2">
                        <i class="fas fa-user-tie fa-4x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning h-100">
                <div class="card-body position-relative overflow-hidden">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="icon-circle bg-warning text-white">
                                <i class="fas fa-bolt"></i>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs fw-bold text-uppercase mb-1 text-muted">
                                Ação Rápida
                            </div>
                            <a href="fazer_apostas.php" class="btn btn-primary mt-1">
                                <i class="fas fa-plus-circle"></i> Nova Aposta
                            </a>
                        </div>
                    </div>
                    <div class="position-absolute opacity-15 bottom-0 end-0 m-2">
                        <i class="fas fa-bolt fa-4x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Apostas Recentes -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="fas fa-history me-1"></i> Apostas Recentes
                    </h6>
                    <a href="minhas_apostas.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-list me-1"></i> Ver Todas
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($apostasRecentes)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-ticket-alt fa-4x text-muted opacity-25"></i>
                            </div>
                            <p class="text-muted mb-3">Você ainda não fez nenhuma aposta.</p>
                            <a href="fazer_apostas.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Fazer Nova Aposta
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Jogo</th>
                                        <th>Números</th>
                                        <th>Valor</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($apostasRecentes as $aposta): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-medium"><?php echo htmlspecialchars($aposta['jogo_nome']); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap">
                                                    <?php 
                                                    $numeros = explode(',', $aposta['numeros']);
                                                    foreach ($numeros as $num) {
                                                        echo '<span class="badge rounded-pill bg-light text-dark border me-1 mb-1">' . 
                                                             str_pad($num, 2, '0', STR_PAD_LEFT) . 
                                                             '</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-muted small">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Ações Rápidas -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="fas fa-star me-1"></i> Ações Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="fazer_apostas.php" class="card bg-gradient-primary text-white border-0 h-100 action-card">
                                <div class="card-body d-flex flex-column align-items-center text-center p-3">
                                    <div class="action-icon mb-3">
                                        <i class="fas fa-ticket-alt fa-2x"></i>
                                    </div>
                                    <h5 class="card-title mb-0">Apostar</h5>
                                    <p class="card-text small">Faça suas apostas</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-6">
                            <a href="minhas_apostas.php" class="card bg-gradient-success text-white border-0 h-100 action-card">
                                <div class="card-body d-flex flex-column align-items-center text-center p-3">
                                    <div class="action-icon mb-3">
                                        <i class="fas fa-list fa-2x"></i>
                                    </div>
                                    <h5 class="card-title mb-0">Histórico</h5>
                                    <p class="card-text small">Ver suas apostas</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-6">
                            <a href="perfil.php" class="card bg-gradient-info text-white border-0 h-100 action-card">
                                <div class="card-body d-flex flex-column align-items-center text-center p-3">
                                    <div class="action-icon mb-3">
                                        <i class="fas fa-user-cog fa-2x"></i>
                                    </div>
                                    <h5 class="card-title mb-0">Perfil</h5>
                                    <p class="card-text small">Atualizar dados</p>
                                </div>
                            </a>
                        </div>
                        
                        <?php if (isset($revendedor) && !empty($revendedor['whatsapp'])): ?>
                        <div class="col-md-6">
                            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $revendedor['whatsapp']); ?>" target="_blank" class="card bg-gradient-warning text-white border-0 h-100 action-card">
                                <div class="card-body d-flex flex-column align-items-center text-center p-3">
                                    <div class="action-icon mb-3">
                                        <i class="fab fa-whatsapp fa-2x"></i>
                                    </div>
                                    <h5 class="card-title mb-0">Contato</h5>
                                    <p class="card-text small">Falar com revendedor</p>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para a página inicial */
.border-left-primary {
    border-left: 4px solid var(--primary-color);
}

.border-left-success {
    border-left: 4px solid var(--success-color);
}

.border-left-info {
    border-left: 4px solid var(--accent-color);
}

.border-left-warning {
    border-left: 4px solid var(--warning-color);
}

.icon-circle {
    height: 3rem;
    width: 3rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-circle i {
    font-size: 1.5rem;
}

.opacity-15 {
    opacity: 0.15;
}

.action-card {
    transition: all 0.3s ease;
    overflow: hidden;
}

.action-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.action-icon {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.bg-gradient-primary {
    background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
}

.bg-gradient-success {
    background: linear-gradient(45deg, var(--success-color), #31c564);
}

.bg-gradient-info {
    background: linear-gradient(45deg, var(--accent-color), #3db5f7);
}

.bg-gradient-warning {
    background: linear-gradient(45deg, var(--warning-color), #f59e0b);
}
</style>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 