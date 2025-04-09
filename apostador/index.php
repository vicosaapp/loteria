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
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt"></i> Painel do Apostador
        </h1>
    </div>
    
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Apostas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalApostas; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Apostas Recentes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Apostas Recentes</h6>
            <a href="minhas_apostas.php" class="btn btn-sm btn-primary">
                Ver Todas
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($apostasRecentes)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0">Você ainda não fez nenhuma aposta.</p>
                    <a href="fazer_apostas.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> Fazer Nova Aposta
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
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
                                    <td><?php echo htmlspecialchars($aposta['jogo_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($aposta['numeros']); ?></td>
                                    <td>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Ação Rápida -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Ações Rápidas</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <a href="fazer_apostas.php" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-ticket-alt mr-2"></i> Fazer Nova Aposta
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="perfil.php" class="btn btn-secondary btn-lg btn-block">
                        <i class="fas fa-user-cog mr-2"></i> Atualizar Meus Dados
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 