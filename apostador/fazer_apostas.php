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
    // Buscar jogos disponíveis
    $stmt = $pdo->query("SELECT id, nome, codigo FROM jogos WHERE status = 1 ORDER BY nome");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar dados do apostador
    $stmt = $pdo->prepare("SELECT id, nome, whatsapp, revendedor_id FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar dados do revendedor do apostador
    if ($apostador['revendedor_id']) {
        $stmt = $pdo->prepare("SELECT id, nome, whatsapp, telefone FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$apostador['revendedor_id']]);
        $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
        $telefoneRevendedor = !empty($revendedor['whatsapp']) ? $revendedor['whatsapp'] : $revendedor['telefone'];
    }
} catch (Exception $e) {
    error_log("Erro ao carregar dados: " . $e->getMessage());
    $erro = "Erro ao carregar os dados. Por favor, tente novamente.";
}

// Define a página atual para o menu
$currentPage = 'fazer_apostas';

// Carrega a view
ob_start();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-ticket-alt"></i> Fazer Apostas
        </h1>
    </div>
    
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="jogo" class="form-label">Selecione o Jogo</label>
                    <select id="jogo" class="form-control form-select">
                        <option value="">Selecione um jogo</option>
                        <?php foreach ($jogos as $jogo): ?>
                            <option value="<?php echo $jogo['id']; ?>" 
                                    data-codigo="<?php echo htmlspecialchars($jogo['codigo'] ?? ''); ?>">
                                <?php echo htmlspecialchars($jogo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="min-dezenas" class="form-label">Mínimo de Dezenas</label>
                    <input type="text" id="min-dezenas" class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label for="max-dezenas" class="form-label">Máximo de Dezenas</label>
                    <input type="text" id="max-dezenas" class="form-control" readonly>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">Selecione as Dezenas</label>
                    <div id="numeros-container" class="mb-3">
                        <div class="alert alert-info">
                            Selecione um jogo para ver os números disponíveis
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <span id="contador-dezenas" class="me-3">0 dezenas selecionadas</span>
                        <button id="btn-limpar" class="btn btn-secondary me-2" disabled>
                            <i class="fas fa-eraser"></i> Limpar
                        </button>
                        <button id="btn-adicionar" class="btn btn-primary" disabled>
                            <i class="fas fa-plus"></i> Adicionar Aposta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Apostas Adicionadas</h5>
        </div>
        <div class="card-body">
            <div id="apostas-container">
                <div class="alert alert-info">
                    Nenhuma aposta adicionada ainda
                </div>
            </div>
            
            <div class="mt-3">
                <button id="btn-remover-todos" class="btn btn-danger me-2" disabled>
                    <i class="fas fa-trash"></i> Remover Todas
                </button>
                <button id="btn-enviar-whatsapp" class="btn btn-success" disabled>
                    <i class="fab fa-whatsapp"></i> Enviar apostas
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmacaoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Envio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Suas apostas serão enviadas para o revendedor via WhatsApp.</p>
                <p>Deseja continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-envio">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Arquivos JavaScript -->
<script src="assets/js/fazer-apostas.js"></script>
<script>
    // Passar dados para o JavaScript
    const revendedorWhatsApp = "<?php echo htmlspecialchars($telefoneRevendedor ?? ''); ?>";
    const apostadorNome = "<?php echo htmlspecialchars($apostador['nome'] ?? ''); ?>";
</script>

<style>
.numero-bolinha {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: #e9ecef;
    color: #495057;
    border-radius: 50%;
    margin: 5px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.numero-bolinha:hover {
    background-color: #dee2e6;
}

.numero-bolinha.selecionado {
    background-color: #4e73df;
    color: white;
}

.numeros-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: flex-start;
}

.aposta-item {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 10px;
    position: relative;
}

.aposta-numeros {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.btn-remover-aposta {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
}

.aposta-numero {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    margin: 3px;
    font-weight: 600;
    font-size: 14px;
}
</style>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 