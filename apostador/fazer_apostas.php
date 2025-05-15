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
    $stmt = $pdo->query("SELECT id, nome, codigo, minimo_numeros, maximo_numeros, numeros_disponiveis FROM jogos WHERE status = 1 ORDER BY nome");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar todos os valores_jogos para uso no JavaScript
    $stmtValores = $pdo->query("SELECT jogo_id, dezenas, valor_aposta, valor_premio FROM valores_jogos ORDER BY jogo_id, dezenas, valor_aposta");
    $todosValoresJogos = $stmtValores->fetchAll(PDO::FETCH_ASSOC);

    // Organizar os valores_jogos por jogo_id para facilitar o acesso no JS
    $valoresJogosParaJs = [];
    foreach ($todosValoresJogos as $valor) {
        $valoresJogosParaJs[$valor['jogo_id']][] = $valor;
    }

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
                                    data-codigo="<?php echo htmlspecialchars($jogo['codigo'] ?? ''); ?>"
                                    data-min-numeros="<?php echo htmlspecialchars($jogo['minimo_numeros'] ?? '0'); ?>"
                                    data-max-numeros="<?php echo htmlspecialchars($jogo['maximo_numeros'] ?? '0'); ?>"
                                    data-numeros-disponiveis="<?php echo htmlspecialchars($jogo['numeros_disponiveis'] ?? '0'); ?>">
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
                    <div class="mb-2">
                        <span id="contador-dezenas" class="badge bg-primary">0 dezenas selecionadas</span>
                        <button id="btn-limpar" class="btn btn-sm btn-outline-secondary ms-2" disabled>
                            <i class="fas fa-eraser"></i> Limpar
                        </button>
                        <button id="btn-gerar-aleatorio" class="btn btn-sm btn-outline-primary ms-2" disabled>
                            <i class="fas fa-random"></i> Gerar aleatório
                        </button>
                    </div>
                    
                    <!-- Campo oculto para armazenar os números selecionados -->
                    <input type="hidden" id="numeros-selecionados" value="">
                    
                    <div id="numeros-container" class="mb-3">
                        <div class="alert alert-info">
                            Selecione um jogo para ver os números disponíveis
                        </div>
                    </div>
                    
                    <div class="alert alert-info" id="info-selecao">
                        <div><i class="fas fa-info-circle"></i> Números selecionados: <span id="numeros-display">Nenhum</span></div>
                        <div id="info-premiacao" class="mt-2 d-none">
                            <strong>Valor da premiação:</strong> <span id="valor-premiacao">R$ 0,00</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mt-3">
                    <label for="valor-aposta" class="form-label">Valor da Aposta</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <select id="valor-aposta" class="form-control" disabled>
                            <option value="">Selecione o valor</option>
                        </select>
                    </div>
                    <small class="form-text text-muted">O valor define a premiação</small>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12 d-flex justify-content-end">
                    <button id="btn-adicionar" class="btn btn-primary" disabled>
                        <i class="fas fa-plus"></i> Adicionar Aposta
                    </button>
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
                <button id="btn-salvar-apostas" class="btn btn-success" disabled>
                    <i class="fas fa-save"></i> Salvar Apostas
                </button>
                <button id="btn-teste" class="btn btn-info ms-2">
                    <i class="fas fa-bug"></i> Testar
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
                <h5 class="modal-title">Confirmar Apostas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Deseja confirmar e salvar suas apostas?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Nota:</strong> Após salvar, suas apostas ficarão com status "pendente" 
                    até que sejam aprovadas pelo administrador. Após a aprovação, você receberá 
                    o comprovante por WhatsApp.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-apostas">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Resultado -->
<div class="modal fade" id="resultadoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resultado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="resultadoModalBody">
                <!-- Conteúdo preenchido via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Arquivos JavaScript -->
<script src="assets/js/fazer-apostas.js?v=1.0.5"></script>
<script>
    // Passar dados para o JavaScript
    const apostadorNome = "<?php echo htmlspecialchars($apostador['nome'] ?? ''); ?>";
    const apostadorId = <?php echo intval($_SESSION['usuario_id']); ?>;
    const valoresJogos = <?php echo json_encode($valoresJogosParaJs); ?>;
    
    // Debug: verificar valores disponíveis
    console.log('Valores disponíveis por jogo:', valoresJogos);
    
    // Verificar se há valores configurados para apostas
    document.addEventListener('DOMContentLoaded', function() {
        if (Object.keys(valoresJogos).length === 0) {
            console.error('Não há valores de apostas configurados no sistema!');
            alert('Atenção: Não há valores de apostas configurados no sistema. Entre em contato com o administrador.');
        }
        
        // Adicionar console.log para depuração
        console.log('DOM carregado!');
        
        // Verificar se os elementos existem
        console.log('Elementos DOM:');
        console.log('- jogoSelect:', document.getElementById('jogo'));
        console.log('- btnAdicionar:', document.getElementById('btn-adicionar'));
        console.log('- apostasContainer:', document.getElementById('apostas-container'));
        console.log('- btnSalvarApostas:', document.getElementById('btn-salvar-apostas'));
        
        // Adicionar listener para debugar clique no botão adicionar
        document.getElementById('btn-adicionar').addEventListener('click', function() {
            console.log('Botão adicionar clicado!');
            
            // Verificar estado após clique
            setTimeout(() => {
                console.log('Estado das apostas:', window.estado ? window.estado.apostas : 'estado não definido');
                console.log('HTML do container de apostas:', document.getElementById('apostas-container').innerHTML);
            }, 100);
        });
        
        // Adicionar funcionalidade para o botão de teste
        document.getElementById('btn-teste').addEventListener('click', function() {
            console.log('Botão teste clicado!');
            
            // Criar uma aposta de teste
            if (!window.estado) {
                window.estado = {
                    jogoId: 1,
                    apostas: []
                };
            }
            
            // Adicionar uma aposta de teste
            window.estado.apostas.push({
                dezenas: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                valor: 10.0,
                premio: 5000.0
            });
            
            // Chamar a função para atualizar a lista de apostas
            atualizarListaApostas();
            
            console.log('Estado após teste:', window.estado);
        });
    });
</script>

<style>
.numeros-grid {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 10px;
}

.numero-item {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s ease;
    border: 2px solid #ddd;
}

.numero-item:hover {
    background-color: #e0e0e0;
    transform: scale(1.05);
}

.numero-item.selected, .numero-item.selecionado {
    background-color: #4e73df;
    color: white;
    border-color: #2980b9;
    transform: scale(1.1);
}

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

@media (max-width: 768px) {
    .numeros-grid {
        grid-template-columns: repeat(6, 1fr);
    }
}

@media (max-width: 576px) {
    .numeros-grid {
        grid-template-columns: repeat(5, 1fr);
    }
    
    .numero-item {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
    
    /* Garantir que botões importantes sejam totalmente visíveis em telas pequenas */
    #btn-salvar-apostas, #btn-confirmar-apostas {
        width: 100%;
        margin-top: 8px;
        margin-bottom: 80px; /* Adicionar margem inferior para evitar que seja cortado ou escondido */
        font-size: 1rem;
        padding: 10px;
    }
    
    /* Ajustar container de botões para layout vertical em telas pequenas */
    .mt-3 {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .mt-3 .me-2 {
        margin-right: 0 !important;
    }
    
    /* Garantir que modais fiquem bem posicionados */
    .modal-dialog {
        margin: 10px;
    }
}

@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-5px); }
    100% { transform: translateX(0); }
}

.shake-animation {
    animation: shake 0.5s;
}
</style>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 