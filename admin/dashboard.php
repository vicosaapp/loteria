<?php
session_start();
require_once '../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'dashboard';

// Busca estatísticas
try {
    // Total de apostas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM apostas");
    $totalApostas = $stmt->fetch()['total'];
    
    // Apostas pendentes
    $stmt = $pdo->query("SELECT COUNT(*) as pendentes FROM apostas WHERE status = 'pendente'");
    $apostasPendentes = $stmt->fetch()['pendentes'];
    
    // Apostas aprovadas
    $stmt = $pdo->query("SELECT COUNT(*) as aprovadas FROM apostas WHERE status = 'aprovada'");
    $apostasAprovadas = $stmt->fetch()['aprovadas'];
    
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as usuarios FROM usuarios WHERE tipo = 'usuario'");
    $totalUsuarios = $stmt->fetch()['usuarios'];
    
} catch(PDOException $e) {
    die("Erro ao buscar estatísticas: " . $e->getMessage());
}

// Inicia o buffer de saída
ob_start();
?>

<div class="page-header">
    <h1>Dashboard</h1>
</div>

<div class="container-fluid">
    <h2 class="mb-4">Dashboard</h2>
    
    <div class="dashboard-stats">
        <!-- Card Total de Jogos -->
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">TOTAL DE JOGOS</div>
                    <div class="stat-value"><?php echo $totalApostas; ?></div>
                </div>
            </div>
        </div>

        <!-- Card Jogos Ativos -->
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">JOGOS ATIVOS</div>
                    <div class="stat-value"><?php echo $apostasPendentes; ?></div>
                </div>
            </div>
        </div>

        <!-- Card Total de Apostas -->
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">TOTAL DE APOSTAS</div>
                    <div class="stat-value"><?php echo $apostasAprovadas; ?></div>
                </div>
            </div>
        </div>

        <!-- Card Valor Total Apostado -->
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">VALOR TOTAL APOSTADO</div>
                    <div class="stat-value">R$ <?php echo number_format($totalApostas * 10, 2, ',', '.'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção de Apostas Pendentes -->
    <div class="pending-bets mt-4">
        <h3>Últimas Apostas Pendentes</h3>
        <?php
        try {
            $stmt = $pdo->query("
                SELECT a.*, u.nome as usuario_nome 
                FROM apostas a 
                JOIN usuarios u ON a.usuario_id = u.id 
                WHERE a.status = 'pendente' 
                ORDER BY a.created_at DESC 
                LIMIT 5
            ");
            $apostasRecentes = $stmt->fetchAll();
            
            if ($apostasRecentes): ?>
                <table>
                    <tr>
                        <th>Data</th>
                        <th>Usuário</th>
                        <th>Números</th>
                        <th>Ações</th>
                    </tr>
                    <?php foreach($apostasRecentes as $aposta): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($aposta['usuario_nome']); ?></td>
                        <td><?php echo $aposta['numeros']; ?></td>
                        <td>
                            <a href="gerenciar_apostas.php?id=<?php echo $aposta['id']; ?>" class="btn-primary">
                                Gerenciar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>Não há apostas pendentes.</p>
            <?php endif;
            
        } catch(PDOException $e) {
            echo "Erro ao buscar apostas recentes: " . $e->getMessage();
        }
        ?>
    </div>
</div>

<style>
.dashboard-stats {
    display: flex;
    flex-direction: row;
    gap: 140px;
    margin: 20px 0;
    flex-wrap: nowrap;
}

.stat-card {
    flex: 1;
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    min-width: 200px;
    position: relative;
}

.stat-card:nth-child(1) { border-left: 8px solid #4e73df; }
.stat-card:nth-child(2) { border-left: 8px solid #1cc88a; }
.stat-card:nth-child(3) { border-left: 8px solid #36b9cc; }
.stat-card:nth-child(4) { border-left: 8px solid #f6c23e; }

.stat-content {
    display: flex;
    align-items: flex-start;
}

.stat-icon {
    font-size: 1.5em;
    color: #dddfeb;
    margin-right: 15px;
    margin-top: 5px;
}

.stat-info {
    flex-grow: 1;
}

.stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    font-weight: bold;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #5a5c69;
}

.pending-bets {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    margin-top: 20px;
}

.pending-bets h3 {
    font-size: 1.2rem;
    margin-bottom: 15px;
}

.btn-primary {
    background: #3498db;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover {
    background: #2980b9;
}
</style>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 