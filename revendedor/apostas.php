<?php
// Forçar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// Verificar o modo de manutenção
require_once __DIR__ . '/verificar_manutencao.php';

// Verificar se não há sessão ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Adicionar filtro para últimas apostas (se recentes=true na URL)
if (isset($_GET['recentes']) && $_GET['recentes'] == 'true') {
    // Obter timestamp das últimas 24 horas
    $ultimas24h = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $where .= " AND a.created_at >= ?";
    $params[] = $ultimas24h;
}

if (isset($_GET['ultimas']) && is_numeric($_GET['ultimas'])) {
    $limite = (int)$_GET['ultimas'];
    $limite = min(max($limite, 1), 100); // Limitar entre 1 e 100
} else {
    $limite = 50; // Valor padrão
}

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
    // Log dos parâmetros de filtro para depuração
    error_log("Filtros: " . json_encode([
        'revendedor_id' => $_SESSION['usuario_id'],
        'ultimas' => $_GET['ultimas'] ?? 'não definido',
        'recentes' => $_GET['recentes'] ?? 'não definido',
        'data_inicio' => $_GET['data_inicio'] ?? 'não definido',
        'data_fim' => $_GET['data_fim'] ?? 'não definido',
        'cliente' => $_GET['cliente'] ?? 'não definido',
        'status' => $_GET['status'] ?? 'não definido'
    ]));
    
    // Consulta simplificada para apostas manuais
    $baseQuery = "
        SELECT 
            a.*,
            u.nome as nome_apostador,
            j.nome as nome_jogo
        FROM apostas a
        JOIN usuarios u ON a.usuario_id = u.id
        JOIN jogos j ON a.tipo_jogo_id = j.id
        $where
        ORDER BY a.created_at DESC
    ";
    
    // Se o parâmetro 'ultimas' estiver presente, simplificar a consulta
    if (isset($_GET['ultimas']) && is_numeric($_GET['ultimas'])) {
        $ultimas = (int)$_GET['ultimas'];
        $query = $baseQuery . " LIMIT " . $ultimas;
    } else {
        $query = $baseQuery . " LIMIT " . $limite;
    }
    
    error_log("Executando consulta: $query");
    error_log("Parâmetros da consulta: " . json_encode($params));
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Número de apostas encontradas: " . count($apostas));

    // Para cada aposta, definir valor_premio caso não exista
    foreach ($apostas as &$aposta) {
        if (!isset($aposta['valor_premio']) || $aposta['valor_premio'] === null) {
            $aposta['valor_premio'] = 0;
        }
    }
    unset($aposta);
    
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
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ai.id,
                ai.usuario_id,
                ai.jogo_nome,
                ai.numeros,
                ai.valor_aposta,
                ai.valor_premio,
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
            LIMIT ?
        ");
        
        // Se o parâmetro 'ultimas' estiver presente, usar esse valor como limite
        $limite_importadas = isset($_GET['ultimas']) && is_numeric($_GET['ultimas']) ? 
                            (int)$_GET['ultimas'] : 
                            50; // Valor padrão
        
        $stmt->execute([$_SESSION['usuario_id'], $limite_importadas]);
        $apostas_importadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Número de apostas importadas encontradas: " . count($apostas_importadas));
    } catch (Exception $e) {
        error_log("Erro ao buscar apostas importadas: " . $e->getMessage());
        // Não definir a variável de erro para não mostrar a mensagem
        $apostas_importadas = [];
    }
    
    // Para cada aposta importada, definir valores padrão
    foreach ($apostas_importadas as &$aposta) {
        // Garantir que valor_premio seja um número
        if (!isset($aposta['valor_premio']) || $aposta['valor_premio'] === null) {
            $aposta['valor_premio'] = 0;
        }
    }
    unset($aposta); // Importante: quebrar a referência do foreach

} catch (Exception $e) {
    error_log("Erro ao buscar apostas: " . $e->getMessage());
    $error = "Erro ao carregar os dados. Por favor, tente novamente.";
    // Inicializar apostas como array vazio para evitar erros de undefined
    $apostas = [];
    $apostas_importadas = [];
}

// Garantir que as variáveis estejam definidas mesmo em caso de erro
if (!isset($apostas)) $apostas = [];
if (!isset($apostas_importadas)) $apostas_importadas = [];

// Define a página atual
$currentPage = 'apostas';

// Carrega a view
ob_start();
?>

<!-- Cabeçalho -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-ticket-alt"></i> Apostas Manuais
        </h1>
        <div>
            <a href="criar_aposta.php" class="btn btn-success me-2">
                <i class="fas fa-plus"></i> Nova Aposta
            </a>
            <a href="importar_apostas.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Importar Aposta
            </a>
        </div>
    </div>

    <?php if (isset($_GET['ultimas'])): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-filter me-2"></i> <strong>Filtro aplicado:</strong> Mostrando apenas as <?php echo htmlspecialchars($_GET['ultimas']); ?> apostas mais recentes
        </div>
        <a href="apostas.php" class="btn btn-outline-info">
            <i class="fas fa-times"></i> Remover filtro
        </a>
    </div>
    <?php endif; ?>

    <!-- Lista de Apostas -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($apostas)): ?>
                <div class="alert alert-info m-3">
                    Nenhuma aposta manual encontrada.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Jogo</th>
                            <th>Números</th>
                            <th>Valor</th>
                            <th>Valor Prêmio</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apostas as $aposta): ?>
                            <tr class="<?php echo $aposta['valor_premio'] > 0 ? 'aposta-premiada' : ''; ?>">
                                <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                                <td><?php echo $aposta['nome_apostador']; ?></td>
                                <td><?php echo $aposta['nome_jogo']; ?></td>
                                <td>
                                    <button type="button" 
                                           class="btn btn-sm btn-info" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#modalNumerosManuais<?php echo $aposta['id']; ?>">
                                        Ver números
                                    </button>
                                </td>
                                <td>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                                <td class="<?php echo $aposta['valor_premio'] > 0 ? 'text-premio' : ''; ?>">
                                    <?php if ($aposta['valor_premio'] > 0): ?>
                                        <div class="premio-container">
                                            <div>R$ <?php echo number_format($aposta['valor_premio'], 2, ',', '.'); ?></div>
                                            <span class="badge-premio"><i class="fas fa-trophy"></i></span>
                                    </div>
                                    <?php else: ?>
                                        R$ 0,00
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../admin/gerar_comprovante.php?usuario_id=<?php echo $aposta['usuario_id']; ?>&jogo=<?php echo rawurlencode($aposta['nome_jogo']); ?>&aposta_id=<?php echo $aposta['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           target="_blank">
                                            <i class="fas fa-file-alt"></i> Comprovante
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="excluirAposta(<?php echo $aposta['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Modal para exibir os números da aposta manual -->
                            <div class="modal fade" id="modalNumerosManuais<?php echo $aposta['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Números da Aposta</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php
                                            // Formatação dos números para visualização mais amigável
                                            $numeros = explode(',', $aposta['numeros']);
                                            ?>
                                            <div class="jogo-numeros-container text-center">
                                                <?php foreach ($numeros as $numero): ?>
                                                <span class="jogo-numero"><?php echo str_pad(trim($numero), 2, '0', STR_PAD_LEFT); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <hr>
                                            <p><strong>Números originais:</strong></p>
                                            <pre class="mb-0"><?php echo $aposta['numeros']; ?></pre>
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
                            <label class="form-label fw-bold">Prêmio</label>
                            <p id="detalhePremio" class="premio-valor"></p>
                        </div>
                        <div class="col-12" id="premioContainer" style="display: none;">
                            <div class="alert alert-success">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-trophy fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Parabéns! Esta aposta foi premiada!</h5>
                                        <p class="mb-0">O apostador ganhou <span id="detalhePremioValor" class="fw-bold"></span></p>
                                    </div>
                                </div>
                            </div>
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
        </h1>
    </div>

    <?php if (isset($_GET['ultimas'])): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-filter me-2"></i> <strong>Filtro aplicado:</strong> Mostrando apenas as <?php echo htmlspecialchars($_GET['ultimas']); ?> apostas mais recentes
        </div>
        <a href="apostas.php" class="btn btn-outline-info">
            <i class="fas fa-times"></i> Remover filtro
        </a>
    </div>
    <?php endif; ?>


    <div class="alert alert-info">
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
    
    const valorPremio = parseFloat(aposta.valor_premio);
    document.getElementById('detalhePremio').textContent = `R$ ${valorPremio.toFixed(2).replace('.', ',')}`;
    
    if (valorPremio > 0) {
        document.getElementById('detalhePremio').classList.add('text-premio');
        document.getElementById('detalhePremioValor').textContent = `R$ ${valorPremio.toFixed(2).replace('.', ',')}`;
        document.getElementById('premioContainer').style.display = 'block';
    } else {
        document.getElementById('detalhePremio').classList.remove('text-premio');
        document.getElementById('premioContainer').style.display = 'none';
    }
    
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

function excluirAposta(id) {
    if (confirm('Tem certeza que deseja excluir esta aposta?')) {
        fetch('ajax/excluir_aposta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `id=${encodeURIComponent(id)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erro ao excluir a aposta');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir a aposta');
        });
    }
}

function confirmarExclusao(id) {
    Swal.fire({
        title: 'Confirmar exclusão',
        text: "Tem certeza que deseja excluir esta aposta?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/excluir_aposta_importada.php', {
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
                        title: 'Excluída!',
                        text: 'Aposta excluída com sucesso!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erro!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire('Erro!', 'Ocorreu um erro ao excluir a aposta.', 'error');
            });
        }
    });
}

// Função auxiliar para converter nome do jogo para classe CSS
function getJogoClass(jogoNome) {
    // Remove acentos e converte para minúsculas
    const nome = jogoNome.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, '-');
    
    return 'jogo-' + nome;
}

// Aplicar as classes aos contêineres de números ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Para as apostas manuais
    const modaisNumerosManuais = document.querySelectorAll('[id^=modalNumerosManuais]');
    modaisNumerosManuais.forEach(modal => {
        const jogoNome = modal.closest('tr').querySelector('td:nth-child(3)').textContent.trim();
        const numeroContainer = modal.querySelector('.jogo-numeros-container');
        if (numeroContainer) {
            numeroContainer.classList.add(getJogoClass(jogoNome));
        }
    });
    
    // Para as apostas importadas
    const modaisNumerosImportados = document.querySelectorAll('[id^=modalNumeros]:not([id^=modalNumerosManuais])');
    modaisNumerosImportados.forEach(modal => {
        const jogoNome = modal.closest('tr').querySelector('td:nth-child(4)').textContent.trim();
        const numeroContainer = modal.querySelector('.jogo-numeros-container');
        if (numeroContainer) {
            numeroContainer.classList.add(getJogoClass(jogoNome));
        }
    });
});
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

/* Estilos para as bolinhas de números */
.jogo-numero {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    color: white;
    border-radius: 50%;
    margin: 5px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Cores específicas para cada tipo de jogo */
.jogo-lotofacil .jogo-numero {
    background-color: #9c27b0; /* Roxo para Lotofácil */
}

.jogo-mega-sena .jogo-numero {
    background-color: #209869; /* Verde para Mega-Sena */
}

.jogo-dia-de-sorte .jogo-numero,
.jogo-lotomania .jogo-numero {
    background-color: #fd7e14; /* Laranja para Dia de Sorte e Lotomania */
}

.jogo-quina .jogo-numero {
    background-color: #260085; /* Azul escuro para Quina */
}

.jogo-timemania .jogo-numero {
    background-color: #209869; /* Verde para Timemania */
}

.jogo-mais-milionaria .jogo-numero {
    background-color: #9c27b0; /* Roxo para Mais Milionária */
}

/* Padrão para outros jogos não especificados */
.jogo-numero {
    background-color: #007bff; /* Azul padrão */
}

/* Formatação para a exibição de números no modal */
.jogo-numeros-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
}

/* Estilos para a tabela de apostas */
.table th {
    background-color: #f8f9fa;
    color: #495057;
}

.badge-premio {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #ffc107;
    color: #212529;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    margin-left: 8px;
    font-size: 12px;
}

.text-premio {
    color: #28a745;
    font-weight: bold;
}

.premio-container {
    display: flex;
    align-items: center;
}

.aposta-premiada {
    background-color: rgba(40, 167, 69, 0.1);
}

.table {
    margin-bottom: 0;
}

.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    vertical-align: middle;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}

.bg-success {
    background-color: #28a745 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
}

.btn-info {
    color: #fff;
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.btn-danger {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn:hover {
    opacity: 0.9;
}

@media print {
    .btn-group {
        display: none;
    }
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