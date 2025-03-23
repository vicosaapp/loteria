<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<!-- Cabeçalho com informações do revendedor -->
<div class="welcome-section mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Bem-vindo, <?php echo htmlspecialchars($revendedor['nome']); ?></h1>
            <p class="mb-0 text-muted">
                <i class="fas fa-chart-line"></i> Painel de Controle do Revendedor
            </p>
        </div>
        <div class="text-end">
            <p class="mb-1">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($revendedor['email']); ?>
            </p>
            <?php if (!empty($revendedor['whatsapp'])): ?>
            <p class="mb-0">
                <i class="fab fa-whatsapp text-success"></i> <?php echo htmlspecialchars($revendedor['whatsapp']); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Forçar não cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <!-- Card Total de Clientes -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card h-100 card-stats">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column">
                        <div class="text-xs fw-bold text-uppercase mb-1 text-muted">
                            Total de Clientes
                        </div>
                        <div class="h3 mb-0 fw-bold text-gray-800">
                            <?php echo number_format($stats['total_clientes']); ?>
                        </div>
                    </div>
                    <div class="icon-circle bg-primary">
                        <i class="fas fa-users text-white"></i>
                    </div>
                </div>
                <div class="mt-3 small text-success">
                    <a href="clientes.php" class="text-primary text-decoration-none">
                        <i class="fas fa-arrow-right"></i> Ver detalhes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Total de Apostas -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card h-100 card-stats">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column">
                        <div class="text-xs fw-bold text-uppercase mb-1 text-muted">
                            Total de Apostas
                        </div>
                        <div class="h3 mb-0 fw-bold text-gray-800">
                            <?php echo number_format($stats['total_apostas']); ?>
                        </div>
                    </div>
                    <div class="icon-circle bg-info">
                        <i class="fas fa-ticket-alt text-white"></i>
                    </div>
                </div>
                <div class="mt-3 small text-success">
                    <a href="apostas.php" class="text-info text-decoration-none">
                        <i class="fas fa-arrow-right"></i> Ver apostas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Apostas Pendentes -->
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card h-100 card-stats">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column">
                        <div class="text-xs fw-bold text-uppercase mb-1 text-muted">
                            Apostas Pendentes
                        </div>
                        <div class="h3 mb-0 fw-bold text-gray-800">
                            <?php echo number_format($stats['apostas_pendentes']); ?>
                        </div>
                    </div>
                    <div class="icon-circle bg-warning">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                </div>
                <div class="mt-3 small text-warning">
                    <?php if ($stats['apostas_pendentes'] > 0): ?>
                        <i class="fas fa-exclamation-circle"></i> Requer sua atenção
                    <?php else: ?>
                        <i class="fas fa-check-circle"></i> Tudo em dia
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex gap-2">
                    <a href="cadastrar_cliente.php" class="btn btn-success flex-fill">
                        <i class="fas fa-user-plus"></i> Novo Cliente
                    </a>
                    <a href="apostas.php?nova=1" class="btn btn-primary flex-fill">
                        <i class="fas fa-plus-circle"></i> Nova Aposta
                    </a>
                    <a href="comissoes.php" class="btn btn-info text-white flex-fill">
                        <i class="fas fa-coins"></i> Minhas Comissões
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Últimas Apostas -->
<div class="card shadow-sm mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-primary">
            <i class="fas fa-history"></i> Últimas Apostas
        </h6>
        <a href="apostas.php" class="btn btn-sm btn-primary">
            <i class="fas fa-list"></i> Ver Todas
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Jogo</th>
                        <th>Números</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimas_apostas as $aposta): ?>
                        <tr>
                            <td>
                                <div class="small text-muted"><?php echo date('d/m/Y', strtotime($aposta['created_at'])); ?></div>
                                <div class="small"><?php echo date('H:i', strtotime($aposta['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($aposta['nome_apostador']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($aposta['nome_jogo']); ?></td>
                            <td>
                                <div class="numeros-container">
                                    <?php 
                                    $numeros = explode(',', $aposta['numeros']);
                                    foreach ($numeros as $numero) {
                                        echo '<span class="numero-bolinha">' . trim($numero) . '</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="fw-bold">R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $aposta['status'] == 'aprovada' ? 'success' : 
                                        ($aposta['status'] == 'rejeitada' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($aposta['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ultimas_apostas)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle"></i> Nenhuma aposta registrada ainda
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
:root {
    --primary-green: #03a64d;
    --secondary-green: #2d8e59;
}

.welcome-section {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    padding: 2rem;
    border-radius: 0.5rem;
    color: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.welcome-section p {
    color: rgba(255, 255, 255, 0.9) !important;
}

.card-stats {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card-stats:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.icon-circle {
    height: 3rem;
    width: 3rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.bg-primary { background: linear-gradient(135deg, #4e73df, #224abe) !important; }
.bg-success { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)) !important; }
.bg-info { background: linear-gradient(135deg, #36b9cc, #258391) !important; }
.bg-warning { background: linear-gradient(135deg, #f6c23e, #dfa408) !important; }

.btn-success { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)) !important; border: none; }
.btn-success:hover { background: linear-gradient(135deg, var(--secondary-green), var(--primary-green)) !important; }

.table > :not(caption) > * > * {
    padding: 1rem;
}

.badge {
    padding: 0.5rem 0.8rem;
    font-weight: 500;
}

/* Estilo para as bolinhas de números */
.numero-bolinha {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    margin: 2px;
    font-weight: bold;
    font-size: 14px;
}

.numeros-container {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    max-width: 400px;
}
</style> 