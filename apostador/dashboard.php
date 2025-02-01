<?php
require_once '../config/database.php';

// Define a página atual para o menu
$currentPage = 'dashboard';
$pageTitle = 'Início';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'usuario') {
    header('Location: ../login.php');
    exit;
}

try {
    // Buscar informações do usuário
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM apostas WHERE usuario_id = u.id) as total_apostas,
               (SELECT COUNT(*) FROM apostas WHERE usuario_id = u.id AND status = 'ganhadora') as apostas_ganhas,
               (SELECT COALESCE(SUM(premio), 0) FROM ganhadores WHERE usuario_id = u.id) as total_premios
        FROM usuarios u 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar últimas apostas
    $stmt = $pdo->prepare("
        SELECT a.*, j.nome as jogo_nome
        FROM apostas a
        JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE a.usuario_id = ?
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $ultimas_apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar últimos prêmios
    $stmt = $pdo->prepare("
        SELECT g.*, r.numeros as numeros_sorteados, j.nome as jogo_nome
        FROM ganhadores g
        JOIN resultados r ON g.resultado_id = r.id
        JOIN jogos j ON r.tipo_jogo_id = j.id
        WHERE g.usuario_id = ?
        ORDER BY g.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $ultimos_premios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Erro ao buscar informações: " . $e->getMessage());
}

ob_start();
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.stat-icon {
    width: 45px;
    height: 45px;
    background: #4e73df;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon i {
    font-size: 20px;
    color: white;
}

.stat-info h3 {
    margin: 0;
    font-size: 1.2rem;
    color: #2d3748;
    font-weight: 600;
}

.stat-info p {
    margin: 3px 0 0;
    color: #858796;
    font-size: 0.9rem;
}

.section-title {
    font-size: 1.1rem;
    color: #2d3748;
    margin: 25px 0 15px;
    font-weight: 600;
}

.list-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.list-item {
    padding: 15px;
    border-bottom: 1px solid #f0f2f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.list-item:last-child {
    border-bottom: none;
}

.list-item-info {
    flex: 1;
}

.list-item-title {
    font-weight: 500;
    color: #2d3748;
    margin-bottom: 4px;
}

.list-item-subtitle {
    font-size: 0.9rem;
    color: #858796;
}

.list-item-value {
    font-weight: 600;
    color: #4e73df;
}

.numbers-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin: 8px 0;
}

.number-ball {
    width: 28px;
    height: 28px;
    background: #4e73df;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.aprovada {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-badge.ganhadora {
    background: #fdf6b2;
    color: #c27803;
}

.status-badge.pendente {
    background: #e8eaf6;
    color: #3f51b5;
}

.empty-state {
    text-align: center;
    padding: 30px 20px;
    color: #858796;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 10px;
    opacity: 0.5;
}
</style>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-ticket-alt"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $usuario['total_apostas']; ?></h3>
            <p>Apostas</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-trophy"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $usuario['apostas_ganhas']; ?></h3>
            <p>Ganhos</p>
        </div>
    </div>
</div>

<!-- Últimas Apostas -->
<h2 class="section-title">Últimas Apostas</h2>
<div class="list-card">
    <?php if (empty($ultimas_apostas)): ?>
        <div class="empty-state">
            <i class="fas fa-ticket-alt"></i>
            <p>Você ainda não fez nenhuma aposta</p>
        </div>
    <?php else: ?>
        <?php foreach ($ultimas_apostas as $aposta): ?>
            <div class="list-item">
                <div class="list-item-info">
                    <div class="list-item-title"><?php echo htmlspecialchars($aposta['jogo_nome']); ?></div>
                    <div class="numbers-grid">
                        <?php foreach (explode(',', $aposta['numeros']) as $numero): ?>
                            <div class="number-ball"><?php echo $numero; ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="list-item-subtitle"><?php echo date('d/m/Y', strtotime($aposta['created_at'])); ?></div>
                </div>
                <span class="status-badge <?php echo $aposta['status']; ?>">
                    <?php echo ucfirst($aposta['status']); ?>
                </span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Últimos Prêmios -->
<h2 class="section-title">Últimos Prêmios</h2>
<div class="list-card">
    <?php if (empty($ultimos_premios)): ?>
        <div class="empty-state">
            <i class="fas fa-trophy"></i>
            <p>Você ainda não ganhou nenhum prêmio</p>
        </div>
    <?php else: ?>
        <?php foreach ($ultimos_premios as $premio): ?>
            <div class="list-item">
                <div class="list-item-info">
                    <div class="list-item-title"><?php echo htmlspecialchars($premio['jogo_nome']); ?></div>
                    <div class="list-item-subtitle"><?php echo date('d/m/Y', strtotime($premio['created_at'])); ?></div>
                </div>
                <div class="list-item-value">
                    R$ <?php echo number_format($premio['premio'], 2, ',', '.'); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 