<?php
// Forçar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

try {
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

    // Buscar apostas importadas do revendedor atual
    $stmt = $pdo->prepare("
        SELECT 
            ai.id,
            ai.jogo_nome,
            ai.numeros,
            ai.valor_aposta,
            ai.valor_premio as valor_premio,
            ai.created_at,
            u.nome as apostador_nome,
            ai.whatsapp
        FROM 
            apostas_importadas ai
            INNER JOIN usuarios u ON ai.usuario_id = u.id
        WHERE 
            ai.revendedor_id = ?
        ORDER BY 
            ai.created_at DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostas_importadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Erro ao buscar apostas: " . $e->getMessage());
    $error = "Erro ao carregar os dados. Por favor, tente novamente.";
}

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
                                    <div class="numeros-container">
                                        <?php 
                                        $numeros = explode(',', $aposta['numeros']);
                                        sort($numeros); // Ordena os números
                                        foreach ($numeros as $numero) {
                                            $num = str_pad(trim($numero), 2, '0', STR_PAD_LEFT);
                                            echo "<span class='numero-bolinha'>$num</span>";
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

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-ticket-alt"></i> Apostas Importadas
        </h1>
        <a href="importar_apostas.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Importar Aposta
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($apostas_importadas)): ?>
                <div class="alert alert-info">
                    Nenhuma aposta encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Apostador</th>
                                <th>WhatsApp</th>
                                <th>Jogo</th>
                                <th>Números</th>
                                <th>Valor Aposta</th>
                                <th>Valor Prêmio</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apostas_importadas as $aposta): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($aposta['apostador_nome']); ?></td>
                                    <td>
                                        <?php if ($aposta['whatsapp']): ?>
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $aposta['whatsapp']); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-success">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($aposta['jogo_nome']); ?></td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalNumeros<?php echo $aposta['id']; ?>">
                                            Ver números
                                        </button>
                                    </td>
                                    <td>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($aposta['valor_premio'], 2, ',', '.'); ?></td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger"
                                                onclick="confirmarExclusao(<?php echo $aposta['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal para exibir os números -->
                                <div class="modal fade" id="modalNumeros<?php echo $aposta['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Números da Aposta</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <pre class="mb-0"><?php echo htmlspecialchars($aposta['numeros']); ?></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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

function confirmarExclusao(id) {
    if (confirm('Tem certeza que deseja excluir esta aposta?')) {
        window.location.href = `excluir_aposta.php?id=${id}`;
    }
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

/* Estilo para as bolinhas de números */
.numero-bolinha {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 28px !important;
    height: 28px !important;
    min-width: 28px !important;
    background-color: #4e73df !important;
    color: white !important;
    border-radius: 50% !important;
    margin: 2px !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    padding: 0 !important;
    line-height: 1 !important;
    text-align: center !important;
}

.numeros-container {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 3px !important;
    padding: 4px !important;
    max-width: 100% !important;
    margin: 0 !important;
    min-width: 200px !important;
}

/* Ajustes na tabela */
.table td {
    vertical-align: middle !important;
    padding: 0.75rem !important;
}

.table th {
    white-space: nowrap !important;
}

/* Coluna de números mais larga */
.table th:nth-child(4),
.table td:nth-child(4) {
    min-width: 280px !important;
    max-width: 400px !important;
}
</style>

<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- CSS responsivo para mobile -->
<link rel="stylesheet" href="../assets/css/mobile.css">

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/jquery-3.6.0.min.js"></script>

<!-- JavaScript para suporte mobile -->
<script src="../assets/js/mobile.js"></script>
</body>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 