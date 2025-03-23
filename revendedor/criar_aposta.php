<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar dados
        if (empty($_POST['cliente_id']) || empty($_POST['jogo_id']) || empty($_POST['numeros']) || empty($_POST['valor_aposta'])) {
            throw new Exception("Todos os campos são obrigatórios");
        }
        
        $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
        $jogo_id = filter_input(INPUT_POST, 'jogo_id', FILTER_VALIDATE_INT);
        
        // Substitui FILTER_SANITIZE_STRING que foi depreciado
        $numeros = htmlspecialchars($_POST['numeros'] ?? '', ENT_QUOTES, 'UTF-8');
        
        $valor_aposta = filter_input(INPUT_POST, 'valor_aposta', FILTER_VALIDATE_FLOAT);
        $valor_premio = filter_input(INPUT_POST, 'premio', FILTER_VALIDATE_FLOAT) ?: 0;
        
        // Verificar se o cliente pertence ao revendedor
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND revendedor_id = ?");
        $stmt->execute([$cliente_id, $_SESSION['usuario_id']]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Cliente não encontrado ou não pertence a este revendedor");
        }
        
        // Inserir aposta
        $stmt = $pdo->prepare("
            INSERT INTO apostas (
                usuario_id, 
                tipo_jogo_id, 
                numeros, 
                valor_aposta, 
                valor_premio,
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'aprovada', NOW())
        ");
        
        $result = $stmt->execute([
            $cliente_id,
            $jogo_id,
            $numeros,
            $valor_aposta,
            $valor_premio
        ]);
        
        if (!$result) {
            throw new Exception("Erro ao salvar a aposta");
        }
        
        $mensagem = "Aposta registrada com sucesso!";
        $tipo_mensagem = "success";
        
    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar clientes do revendedor
$stmt = $pdo->prepare("
    SELECT id, nome 
    FROM usuarios 
    WHERE revendedor_id = ?
    ORDER BY nome
");
$stmt->execute([$_SESSION['usuario_id']]);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar jogos disponíveis
$stmt = $pdo->query("
    SELECT 
        id, 
        nome, 
        minimo_numeros,
        maximo_numeros, 
        numeros_disponiveis,
        dezenas,
        valor, 
        premio 
    FROM jogos 
    WHERE status = 1 
    ORDER BY nome
");
$jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter valores dos jogos para usar no JavaScript
$valoresJogos = [];
foreach ($jogos as $jogo) {
    $stmtValores = $pdo->prepare("
        SELECT jogo_id, dezenas, valor_aposta, valor_premio
        FROM valores_jogos
        WHERE jogo_id = ?
        ORDER BY dezenas, valor_aposta
    ");
    $stmtValores->execute([$jogo['id']]);
    $valores = $stmtValores->fetchAll(PDO::FETCH_ASSOC);
    
    $valoresJogos[$jogo['id']] = $valores;
}

// Define a página atual
$currentPage = 'apostas';

// Carrega a view
ob_start();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus-circle"></i> Nova Aposta
        </h1>
        <a href="apostas.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?>" role="alert">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="" id="formAposta">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select name="cliente_id" id="cliente_id" class="form-control form-select" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>">
                                    <?php echo htmlspecialchars($cliente['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="jogo_id" class="form-label">Jogo</label>
                        <select name="jogo_id" id="jogo_id" class="form-control form-select" required onchange="carregarConfigJogo()">
                            <option value="">Selecione um jogo</option>
                            <?php foreach ($jogos as $jogo): ?>
                                <option value="<?php echo $jogo['id']; ?>" 
                                        data-min-numeros="<?php echo $jogo['minimo_numeros'] ?? 6; ?>"
                                        data-max-numeros="<?php echo $jogo['maximo_numeros'] ?? 15; ?>"
                                        data-qtd-dezenas="<?php echo $jogo['numeros_disponiveis'] ?? 60; ?>">
                                    <?php echo htmlspecialchars($jogo['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Selecione os números</label>
                        <div class="mb-2">
                            <span id="selecaoInfo" class="badge bg-primary">Selecione os números</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="limparSelecao()">
                                <i class="fas fa-eraser"></i> Limpar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="gerarNumerosAleatorios()">
                                <i class="fas fa-random"></i> Gerar aleatório
                            </button>
                        </div>
                        
                        <!-- Campo oculto para armazenar os números selecionados -->
                        <input type="hidden" name="numeros" id="numerosInput" required>
                        
                        <!-- Campo oculto para armazenar o valor do prêmio -->
                        <input type="hidden" name="premio" id="premioInput">
                        
                        <!-- Grade com as bolinhas de números -->
                        <div class="numeros-grid mb-3" id="numerosGrid">
                            <!-- Os números serão gerados dinamicamente pelo JavaScript -->
                        </div>
                        
                        <div class="alert alert-info">
                            <div><i class="fas fa-info-circle"></i> Números selecionados: <span id="numerosDisplay">Nenhum</span></div>
                            <div id="infoPremiacao" class="mt-2 d-none">
                                <strong>Valor da premiação:</strong> <span id="valorPremiacao">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="valor_aposta" class="form-label">Valor da Aposta</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <select name="valor_aposta" id="valor_aposta" class="form-control" required>
                                <option value="">Selecione o valor</option>
                            </select>
                        </div>
                        <small class="form-text text-muted">O valor define a premiação</small>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">
                        <i class="fas fa-save"></i> Confirmar Aposta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

.numero-item.selected {
    background-color: #3498db;
    color: white;
    border-color: #2980b9;
    transform: scale(1.1);
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
}
</style>

<script>
let numerosSelected = [];
let minNumeros = 6; // Valor padrão
let maxNumeros = 15; // Valor padrão
let qtdDezenas = 60; // Valor padrão
let valoresJogos = <?php echo json_encode($valoresJogos); ?>;
let jogoAtual = null;

function carregarConfigJogo() {
    const jogoSelect = document.getElementById('jogo_id');
    const jogoOption = jogoSelect.options[jogoSelect.selectedIndex];
    
    if (jogoOption && jogoOption.value) {
        jogoAtual = jogoOption.value;
        minNumeros = parseInt(jogoOption.dataset.minNumeros || 6);
        maxNumeros = parseInt(jogoOption.dataset.maxNumeros || 15);
        qtdDezenas = parseInt(jogoOption.dataset.qtdDezenas || 60);
        
        document.getElementById('selecaoInfo').textContent = 
            `Selecione de ${minNumeros} a ${maxNumeros} números`;
        
        // Atualizar a grade de números conforme o jogo selecionado
        atualizarGradeNumeros();
        
        // Limpar select de valores
        const valorSelect = document.getElementById('valor_aposta');
        valorSelect.innerHTML = '<option value="">Selecione o valor</option>';
        
        // Limpar seleção anterior ao trocar de jogo
        limparSelecao();
    }
}

function atualizarGradeNumeros() {
    const gridEl = document.getElementById('numerosGrid');
    gridEl.innerHTML = '';
    
    for (let i = 1; i <= qtdDezenas; i++) {
        const numeroEl = document.createElement('div');
        numeroEl.className = 'numero-item';
        numeroEl.setAttribute('data-numero', i);
        numeroEl.textContent = i < 10 ? `0${i}` : `${i}`;
        numeroEl.onclick = function() { toggleNumero(this, i); };
        
        gridEl.appendChild(numeroEl);
    }
}

function toggleNumero(element, numero) {
    const index = numerosSelected.indexOf(numero);
    
    if (index === -1) {
        // Se o número não está selecionado e não atingimos o máximo, adicione-o
        if (numerosSelected.length < maxNumeros) {
            numerosSelected.push(numero);
            element.classList.add('selected');
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Limite atingido',
                text: `Você só pode selecionar até ${maxNumeros} números!`
            });
        }
    } else {
        // Se o número já está selecionado, remova-o
        numerosSelected.splice(index, 1);
        element.classList.remove('selected');
    }
    
    atualizarDisplayNumeros();
}

function limparSelecao() {
    numerosSelected = [];
    document.querySelectorAll('.numero-item.selected').forEach(el => {
        el.classList.remove('selected');
    });
    atualizarDisplayNumeros();
}

function gerarNumerosAleatorios() {
    // Limpar seleção atual
    limparSelecao();
    
    // Gerar números aleatórios baseados na quantidade mínima
    const numerosPossiveis = Array.from({length: qtdDezenas}, (_, i) => i + 1);
    const numerosAleatorios = [];
    
    // Escolher minNumeros aleatoriamente
    for (let i = 0; i < minNumeros; i++) {
        const indiceAleatorio = Math.floor(Math.random() * numerosPossiveis.length);
        numerosAleatorios.push(numerosPossiveis[indiceAleatorio]);
        numerosPossiveis.splice(indiceAleatorio, 1);
    }
    
    // Selecionar esses números na interface
    numerosAleatorios.forEach(numero => {
        const element = document.querySelector(`.numero-item[data-numero="${numero}"]`);
        if (element) {
            numerosSelected.push(numero);
            element.classList.add('selected');
        }
    });
    
    atualizarDisplayNumeros();
}

function atualizarDisplayNumeros() {
    const displayEl = document.getElementById('numerosDisplay');
    const inputEl = document.getElementById('numerosInput');
    const valorSelect = document.getElementById('valor_aposta');
    const infoPremiacaoEl = document.getElementById('infoPremiacao');
    
    if (numerosSelected.length === 0) {
        displayEl.textContent = 'Nenhum';
        inputEl.value = '';
        // Esconder info de premiação
        infoPremiacaoEl.classList.add('d-none');
        
        // Limpar select de valores
        valorSelect.innerHTML = '<option value="">Selecione o valor</option>';
    } else {
        // Ordenar os números para melhor visualização
        const numerosSorted = [...numerosSelected].sort((a, b) => a - b);
        
        // Formatar os números com padding de zero
        const numerosFormatados = numerosSorted.map(n => 
            n < 10 ? `0${n}` : `${n}`
        );
        
        displayEl.textContent = numerosFormatados.join(', ');
        inputEl.value = numerosSorted.join(',');
        
        // Atualizar valores disponíveis com base na quantidade de números selecionados
        atualizarValoresDisponiveis(numerosSelected.length);
    }
    
    // Atualizar o estado do botão de envio
    const submitBtn = document.getElementById('btnSubmit');
    submitBtn.disabled = numerosSelected.length < minNumeros;
}

function atualizarValoresDisponiveis(qtdNumeros) {
    const valorSelect = document.getElementById('valor_aposta');
    const infoPremiacaoEl = document.getElementById('infoPremiacao');
    const valorPremiacaoEl = document.getElementById('valorPremiacao');
    
    // Limpar select de valores
    valorSelect.innerHTML = '<option value="">Selecione o valor</option>';
    
    // Esconder info de premiação inicialmente
    infoPremiacaoEl.classList.add('d-none');
    
    if (!jogoAtual || !valoresJogos[jogoAtual]) return;
    
    // Filtrar valores para a quantidade de números selecionados
    const valoresDisponiveis = valoresJogos[jogoAtual].filter(v => v.dezenas == qtdNumeros);
    
    if (valoresDisponiveis.length === 0) {
        // Não há valores para a quantidade de números selecionados
        valorSelect.innerHTML = '<option value="">Não disponível para esta quantidade</option>';
        return;
    }
    
    // Adicionar opções ao select
    valoresDisponiveis.forEach(valor => {
        const option = document.createElement('option');
        option.value = valor.valor_aposta;
        option.textContent = `R$ ${parseFloat(valor.valor_aposta).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
        option.setAttribute('data-premio', valor.valor_premio);
        valorSelect.appendChild(option);
    });
    
    // Selecionar o primeiro valor por padrão
    if (valorSelect.options.length > 1) {
        valorSelect.selectedIndex = 1;
        atualizarPremiacao();
    }
    
    // Evento para atualizar premiação quando o valor mudar
    valorSelect.addEventListener('change', atualizarPremiacao);
}

function atualizarPremiacao() {
    const valorSelect = document.getElementById('valor_aposta');
    const infoPremiacaoEl = document.getElementById('infoPremiacao');
    const valorPremiacaoEl = document.getElementById('valorPremiacao');
    const premioInput = document.getElementById('premioInput');
    
    if (valorSelect.selectedIndex > 0) {
        const option = valorSelect.options[valorSelect.selectedIndex];
        const premio = option.getAttribute('data-premio');
        
        // Mostrar info de premiação
        infoPremiacaoEl.classList.remove('d-none');
        valorPremiacaoEl.textContent = `R$ ${parseFloat(premio).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
        
        // Atualizar o campo oculto com o valor do prêmio
        premioInput.value = premio;
    } else {
        // Esconder info de premiação
        infoPremiacaoEl.classList.add('d-none');
        premioInput.value = '';
    }
}

document.getElementById('formAposta').addEventListener('submit', function(e) {
    if (numerosSelected.length < minNumeros) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Números insuficientes',
            text: `Você precisa selecionar pelo menos ${minNumeros} números!`
        });
        return;
    }
    
    const valorSelect = document.getElementById('valor_aposta');
    if (valorSelect.value === '') {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Valor não selecionado',
            text: 'Por favor, selecione um valor para a aposta.'
        });
        return;
    }
});

// Inicializar ao carregar a página
window.addEventListener('DOMContentLoaded', function() {
    atualizarGradeNumeros();
    carregarConfigJogo();
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 