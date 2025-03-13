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

try {
    // Debug da conexão
    if (!$pdo) {
        throw new Exception("Erro na conexão com o banco de dados");
    }

    // Buscar jogos disponíveis
    $stmt = $pdo->query("SELECT id, nome FROM jogos WHERE status = 1 ORDER BY nome");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar apostadores vinculados ao revendedor atual
    $stmt = $pdo->prepare("
        SELECT id, nome, whatsapp, telefone 
        FROM usuarios 
        WHERE tipo = 'usuario' 
        AND revendedor_id = ?
        ORDER BY nome ASC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar dados do revendedor
    $stmtRevendedor = $pdo->prepare("
        SELECT id, nome 
        FROM usuarios 
        WHERE id = ? 
        LIMIT 1
    ");
    $stmtRevendedor->execute([$_SESSION['usuario_id']]);
    $revendedor = $stmtRevendedor->fetch(PDO::FETCH_ASSOC);

    // Criar array para dados dos apostadores em JavaScript
    $apostadoresData = [];
    foreach ($apostadores as $apostador) {
        $apostadoresData[$apostador['id']] = [
            'whatsapp' => $apostador['whatsapp'] ?: $apostador['telefone'] ?: ''
        ];
    }

} catch (Exception $e) {
    error_log("Erro na importação de apostas: " . $e->getMessage());
    $error = "Erro ao carregar os dados. Por favor, tente novamente.";
}

// Array para armazenar mensagens de debug
$debug_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos obrigatórios
        if (empty($_POST['apostador'])) {
            throw new Exception("Selecione um apostador");
        }
        
        if (empty($_POST['valor_aposta']) || empty($_POST['apostas'])) {
            throw new Exception("Valor da aposta e apostas são obrigatórios");
        }

        // Processar valores
        $usuario_id = intval($_POST['apostador']);
        $revendedor_id = $_SESSION['usuario_id']; // Usa o ID do revendedor logado
        $whatsapp = !empty($_POST['whatsapp']) ? $_POST['whatsapp'] : null;
        
        // Tratar valor da aposta
        $valor_aposta = str_replace(['R$', ' '], '', $_POST['valor_aposta']);
        $valor_aposta = str_replace(',', '.', $valor_aposta);
        $valor_aposta = number_format(floatval($valor_aposta), 2, '.', '');
        
        // Tratar valor do prêmio
        $valor_premio = str_replace(['R$', ' '], '', $_POST['valor_premiacao']);
        $valor_premio = str_replace(',', '.', $valor_premio);
        $valor_premio = number_format(floatval($valor_premio), 2, '.', '');
        
        // Verificar se o apostador pertence ao revendedor atual
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND revendedor_id = ?");
        $stmt->execute([$usuario_id, $revendedor_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Este apostador não está vinculado à sua conta");
        }
        
        // Preparar SQL
        $sql = "INSERT INTO apostas_importadas 
                (usuario_id, jogo_nome, numeros, valor_aposta, valor_premio, revendedor_id, whatsapp) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $usuario_id,
            trim(explode("\n", $_POST['apostas'])[0]), // Nome do jogo
            $_POST['apostas'],
            $valor_aposta,
            $valor_premio,
            $revendedor_id,
            $whatsapp
        ]);
        
        header('Location: importar_apostas.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Define a página atual para o menu
$currentPage = 'importar_apostas';

// Carrega a view
ob_start();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-import"></i> Importar Apostas
        </h1>
    </div>
        
    <div class="debug-info" style="background:#f4f6f9; padding: 0px; margin: 0px 0; border-radius: 0px; max-height: 0px; overflow-y: auto;">
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Aposta importada com sucesso!</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" class="mt-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="apostador" class="form-label">Apostador</label>
                        <select class="form-control form-select" id="apostador" name="apostador" required onchange="atualizarWhatsApp()">
                            <option value="">Selecione um apostador</option>
                            <?php foreach ($apostadores as $apostador): ?>
                                <option value="<?php echo $apostador['id']; ?>" 
                                        data-whatsapp="<?php echo htmlspecialchars($apostador['whatsapp'] ?: $apostador['telefone'] ?: ''); ?>">
                                    <?php echo htmlspecialchars($apostador['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="whatsapp" class="form-label">WhatsApp</label>
                        <input type="text" class="form-control" id="whatsapp" name="whatsapp" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor da premiação</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" id="valor_premiacao" name="valor_premiacao" class="form-control" value="0,00" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor por Aposta</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" id="valor_aposta" name="valor_aposta" class="form-control" value="1.00" step="1.00">
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Cole as apostas aqui</label>
                    <textarea id="apostas" name="apostas" class="form-control" rows="10"></textarea>
                    <small class="form-text text-muted">Na primeira linha, coloque o nome do jogo. Nas linhas seguintes, coloque os números.</small>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-secondary" onclick="visualizarApostas()">
                        <i class="fas fa-eye"></i> Visualizar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Dados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para visualização -->
<div class="modal fade" id="visualizarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visualizar Apostas</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="resumoApostas"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Função para atualizar WhatsApp
function atualizarWhatsApp() {
    const apostadorSelect = document.getElementById('apostador');
    const whatsappInput = document.getElementById('whatsapp');
    const selectedOption = apostadorSelect.options[apostadorSelect.selectedIndex];
    
    if (selectedOption) {
        whatsappInput.value = selectedOption.dataset.whatsapp || '';
    } else {
        whatsappInput.value = '';
    }
}

// Função para formatar valor
function formatarValor(valor) {
    return valor.replace(/[^\d,]/g, '').replace(',', '.');
}

// Função para formatar valor em moeda brasileira
function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Função para calcular premiação
async function calcularPremiacao() {
    const textarea = document.querySelector('textarea');
    const valorApostaInput = document.querySelector('input[id^="valor_aposta"]');
    const premiacaoInput = document.querySelector('input[name="valor_premiacao"]');
    const debugDiv = document.querySelector('.debug-info');
    const texto = textarea.value.trim();
    
    debugDiv.innerHTML = ''; // Limpar debug anterior
    
    try {
        if (!texto || !valorApostaInput.value) {
            premiacaoInput.value = '0,00';
            return;
        }

        const response = await fetch('ajax/buscar_jogo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                nome: texto,
                valor_aposta: valorApostaInput.value
            })
        });

        const data = await response.json();
        
        if (data.success) {
            // Formatar o valor do prêmio para exibição
            const valorPremio = parseFloat(data.jogo.valor_premio);
            
            // Dividir por 100 para corrigir os dígitos extras
            const valorPremioCorrigido = valorPremio / 100;
            
            premiacaoInput.value = valorPremioCorrigido.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            // Atualizar informações de debug
            const valorBaseAposta = parseFloat(data.jogo.debug.valor_base_aposta);
            const valorBasePremio = parseFloat(data.jogo.debug.valor_base_premio) / 100;
            
            debugDiv.innerHTML = `
                <p>Valor base da aposta: R$ ${valorBaseAposta.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                <p>Valor base do prêmio: R$ ${valorBasePremio.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                <p>Multiplicador: ${data.jogo.debug.multiplicador}</p>
            `;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        premiacaoInput.value = '0,00';
        debugDiv.innerHTML = `<p class="text-danger">Erro: ${error.message}</p>`;
    }
}

// Adicionar os event listeners
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelector('textarea');
    const valorApostaInput = document.querySelector('input[id^="valor_aposta"]');
    
    if (textarea) {
        textarea.addEventListener('input', debounce(calcularPremiacao, 500));
        textarea.addEventListener('paste', () => setTimeout(calcularPremiacao, 100));
    }
    
    if (valorApostaInput) {
        valorApostaInput.addEventListener('input', debounce(calcularPremiacao, 500));
    }
});

// Função debounce para evitar múltiplas chamadas
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function visualizarApostas() {
    const texto = document.getElementById('apostas').value.trim();
    const linhas = texto.split('\n').filter(linha => linha.trim());
    
    if (linhas.length < 2) {
        Swal.fire('Erro', 'Formato inválido', 'error');
        return;
    }
    
    const nomeJogo = linhas[0];
    const apostas = linhas.slice(1).filter(linha => linha.trim());
    
    let html = `
        <div class="alert alert-info">
            <strong>Jogo:</strong> ${nomeJogo}<br>
            <strong>Total de Apostas:</strong> ${apostas.length}
        </div>
        <div class="apostas-preview">
    `;
    
    apostas.forEach((aposta, index) => {
        html += `
            <div class="aposta-item">
                <strong>Aposta ${index + 1}:</strong><br>
                ${aposta}
            </div>
        `;
    });
    
    html += '</div>';
    
    document.getElementById('resumoApostas').innerHTML = html;
    const modal = new bootstrap.Modal(document.getElementById('visualizarModal'));
    modal.show();
}
</script>

<style>
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.form-control {
    border: 1px solid #d1d3e2;
    border-radius: 5px;
    padding: 0.375rem 0.75rem;
}

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%234e73df' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 12px;
    padding-right: 2rem;
}

.btn {
    padding: 0.375rem 1rem;
    font-size: 0.9rem;
    border-radius: 5px;
    margin-right: 10px;
}

.btn-secondary {
    background-color: #858796;
    border-color: #858796;
    color: white;
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
}

textarea {
    resize: vertical;
}

.input-group-text {
    background-color: #4e73df;
    color: white;
    border: 1px solid #4e73df;
}

#valor_aposta {
    text-align: right;
}

.apostas-preview {
    max-height: 400px;
    overflow-y: auto;
}

.aposta-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    margin-bottom: 10px;
}

.aposta-item:last-child {
    border-bottom: none;
}
</style>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 