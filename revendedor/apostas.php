<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

// Adicionar mensagem de depuração
error_log("Página de apostas carregada pelo revendedor ID: " . $_SESSION['usuario_id']);

// Processar filtros
$where = "WHERE u.revendedor_id = ?";
$params = [$_SESSION['usuario_id']];

if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $where .= " AND DATE(a.created_at) >= ?";
    $params[] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $where .= " AND DATE(a.created_at) <= ?";
    $params[] = $_GET['data_fim'];
}

if (isset($_GET['cliente']) && !empty($_GET['cliente'])) {
    $where .= " AND u.id = ?";
    $params[] = $_GET['cliente'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where .= " AND a.status = ?";
    $params[] = $_GET['status'];
}

// Buscar apostas com filtros
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        u.nome as nome_apostador,
        j.nome as nome_jogo,
        j.valor as valor_minimo,
        j.premio as premio_maximo
    FROM apostas a
    JOIN usuarios u ON a.usuario_id = u.id
    JOIN jogos j ON a.tipo_jogo_id = j.id
    $where
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
$apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar clientes para o filtro
$stmt = $pdo->prepare("
    SELECT id, nome 
    FROM usuarios 
    WHERE revendedor_id = ? 
    AND tipo = 'apostador' 
    ORDER BY nome
");
$stmt->execute([$_SESSION['usuario_id']]);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar jogos disponíveis
$stmt = $pdo->query("SELECT * FROM jogos WHERE status = 1 ORDER BY nome");
$jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define a página atual
$currentPage = 'apostas';

// Carrega a view
ob_start();
?>

<!-- Cabeçalho -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-ticket-alt"></i> Gerenciar Apostas
        </h1>
        <a href="criar_aposta.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Nova Aposta
        </a>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Data Início</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?php echo $_GET['data_inicio'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Fim</label>
                    <input type="date" name="data_fim" class="form-control" value="<?php echo $_GET['data_fim'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <select name="cliente" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>" <?php echo (isset($_GET['cliente']) && $_GET['cliente'] == $cliente['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendente" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                        <option value="aprovada" <?php echo (isset($_GET['status']) && $_GET['status'] == 'aprovada') ? 'selected' : ''; ?>>Aprovada</option>
                        <option value="rejeitada" <?php echo (isset($_GET['status']) && $_GET['status'] == 'rejeitada') ? 'selected' : ''; ?>>Rejeitada</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Apostas -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Cliente</th>
                            <th>Jogo</th>
                            <th>Números</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apostas as $aposta): ?>
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
                                    <span class="badge bg-light text-dark">
                                        <?php echo htmlspecialchars($aposta['numeros']); ?>
                                    </span>
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
                                <td>
                                    <button class="btn btn-sm btn-info me-1" onclick="verDetalhes(<?php echo htmlspecialchars(json_encode($aposta)); ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($aposta['status'] == 'pendente'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="cancelarAposta(<?php echo $aposta['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($apostas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle"></i> Nenhuma aposta encontrada
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detalhes -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Aposta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cliente</label>
                            <p id="detalheCliente"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jogo</label>
                            <p id="detalheJogo"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Data/Hora</label>
                            <p id="detalheData"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <p id="detalheStatus"></p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Números Apostados</label>
                            <p id="detalheNumeros"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Valor Apostado</label>
                            <p id="detalheValor"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Possível Prêmio</label>
                            <p id="detalhePremio"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function atualizarValores(jogoId) {
    const option = document.querySelector(`select[name="jogo_id"] option[value="${jogoId}"]`);
    if (option) {
        const valorMinimo = parseFloat(option.dataset.min).toFixed(2);
        document.getElementById('valorMinimo').textContent = valorMinimo.replace('.', ',');
        document.querySelector('input[name="valor"]').min = valorMinimo;
    }
}

function salvarAposta() {
    const form = document.getElementById('formNovaAposta');
    const formData = new FormData(form);
    
    fetch('ajax/salvar_aposta.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Aposta registrada com sucesso!'
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Erro ao registrar aposta');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
    });
}

function verDetalhes(aposta) {
    document.getElementById('detalheCliente').textContent = aposta.nome_apostador;
    document.getElementById('detalheJogo').textContent = aposta.nome_jogo;
    document.getElementById('detalheData').textContent = new Date(aposta.created_at).toLocaleString();
    document.getElementById('detalheStatus').innerHTML = `
        <span class="badge bg-${aposta.status == 'aprovada' ? 'success' : (aposta.status == 'rejeitada' ? 'danger' : 'warning')}">
            ${aposta.status.charAt(0).toUpperCase() + aposta.status.slice(1)}
        </span>
    `;
    document.getElementById('detalheNumeros').textContent = aposta.numeros;
    document.getElementById('detalheValor').textContent = `R$ ${parseFloat(aposta.valor_aposta).toFixed(2).replace('.', ',')}`;
    document.getElementById('detalhePremio').textContent = `R$ ${parseFloat(aposta.premio_maximo).toFixed(2).replace('.', ',')}`;
    
    new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
}

function cancelarAposta(id) {
    Swal.fire({
        title: 'Confirmar cancelamento',
        text: 'Tem certeza que deseja cancelar esta aposta?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, cancelar!',
        cancelButtonText: 'Não'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/cancelar_aposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Aposta cancelada com sucesso!'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao cancelar aposta');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message
                });
            });
        }
    });
}
</script>

<style>
.card {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.table > :not(caption) > * > * {
    padding: 1rem;
}

.badge {
    padding: 0.5rem 0.8rem;
    font-weight: 500;
}

.btn-success {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)) !important;
    border: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, var(--secondary-green), var(--primary-green)) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 