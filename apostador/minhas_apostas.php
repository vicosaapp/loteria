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

try {
    // Buscar apostas do usuário
    $stmt = $pdo->prepare("
        SELECT a.*, j.nome as jogo_nome 
        FROM apostas a
        LEFT JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE a.usuario_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erro ao carregar apostas: " . $e->getMessage());
    $erro = "Erro ao carregar suas apostas. Por favor, tente novamente.";
}

// Define a página atual para o menu
$currentPage = 'minhas_apostas';

// Carrega a view
ob_start();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-list"></i> Minhas Apostas
        </h1>
        <a href="fazer_apostas.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Aposta
        </a>
    </div>
    
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (empty($apostas)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0">Você ainda não fez nenhuma aposta.</p>
                    <a href="fazer_apostas.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> Fazer Nova Aposta
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Jogo</th>
                                <th>Números</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apostas as $aposta): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($aposta['jogo_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($aposta['numeros']); ?></td>
                                    <td>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        switch ($aposta['status']) {
                                            case 'pendente':
                                                $statusClass = 'badge bg-warning text-dark';
                                                $statusText = 'Pendente';
                                                break;
                                            case 'aprovada':
                                                $statusClass = 'badge bg-success';
                                                $statusText = 'Aprovada';
                                                break;
                                            case 'rejeitada':
                                                $statusClass = 'badge bg-danger';
                                                $statusText = 'Rejeitada';
                                                break;
                                            default:
                                                $statusClass = 'badge bg-secondary';
                                                $statusText = $aposta['status'];
                                                break;
                                        }
                                        ?>
                                        <span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
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

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 