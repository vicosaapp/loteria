<?php
require_once '../config/database.php';

// Verificar o modo de manutenção
require_once __DIR__ . '/../includes/verificar_manutencao.php';

// Verificar se não há sessão ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Script para limpar o cache local no caso de problemas de visualização
$cacheVersion = '1.0.1'; // Atualizar sempre que houver mudanças importantes

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
        
        // Validar valor da aposta
        if ($valor_aposta === false || $valor_aposta <= 0) {
            error_log("Valor da aposta inválido: " . print_r($_POST['valor_aposta'], true));
            throw new Exception("Valor da aposta inválido");
        }
        
        // Debug do valor do prêmio
        if ($valor_premio === false) {
            error_log("Valor do prêmio inválido: " . print_r($_POST['premio'], true));
            $valor_premio = 0; // Fallback seguro
        }
        
        // Validar a quantidade de números selecionados
        $numerosArray = explode(',', $numeros);
        if (count($numerosArray) > 50) {
            throw new Exception("Quantidade de números selecionados excede o limite permitido (máximo 50)");
        }
        
        // Buscar informações do jogo para validar
        $stmt = $pdo->prepare("SELECT minimo_numeros, maximo_numeros FROM jogos WHERE id = ?");
        $stmt->execute([$jogo_id]);
        $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$jogo) {
            throw new Exception("Jogo não encontrado");
        }
        
        // Validar número mínimo de dezenas
        if (count($numerosArray) < $jogo['minimo_numeros']) {
            throw new Exception("Quantidade de números insuficiente. Mínimo: " . $jogo['minimo_numeros']);
        }
        
        // Validar número máximo de dezenas (com limite absoluto de segurança)
        $maxPermitido = min($jogo['maximo_numeros'], 50);
        if (count($numerosArray) > $maxPermitido) {
            throw new Exception("Quantidade de números excede o máximo permitido. Máximo: " . $maxPermitido);
        }
        
        // Verificar se existem valores configurados para a quantidade de números selecionados
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM valores_jogos 
            WHERE jogo_id = ? AND dezenas = ?
        ");
        $stmt->execute([$jogo_id, count($numerosArray)]);
        
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Não existem valores configurados para " . count($numerosArray) . " números neste jogo");
        }
        
        // Verificar se o cliente pertence ao revendedor
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND revendedor_id = ?");
        $stmt->execute([$cliente_id, $_SESSION['usuario_id']]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Cliente não encontrado ou não pertence a este revendedor");
        }
        
        // Verificar se já existe uma aposta idêntica para este jogo e números
        $stmt = $pdo->prepare("
            SELECT a.id, u.nome as apostador 
            FROM apostas a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.tipo_jogo_id = ? 
            AND a.numeros = ? 
            AND DATE(a.created_at) = CURDATE()
        ");
        $stmt->execute([$jogo_id, $numeros]);
        
        $aposta_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aposta_existente) {
            throw new Exception("Essa sequência de números já foi apostada hoje por {$aposta_existente['apostador']}. Não são permitidas apostas com a mesma sequência de números.");
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
        <div>
            <button onclick="limparCacheLocal()" class="btn btn-warning btn-sm me-2" type="button">
                <i class="fas fa-sync-alt"></i> Limpar Cache
            </button>
            <a href="apostas.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <!-- Alerta informativo sobre apostas repetidas -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i> <strong>Atenção!</strong> Não é permitido registrar apostas com a mesma sequência de números no mesmo jogo no mesmo dia, independentemente do cliente.
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

<script>
// Criar uma função de fallback para Swal caso a biblioteca não seja carregada
if (typeof Swal === 'undefined') {
    window.Swal = {
        fire: function(options) {
            if (options.title && options.text) {
                alert(options.title + '\n\n' + options.text);
            } else if (options.title) {
                alert(options.title);
            } else if (options.text) {
                alert(options.text);
            }
            
            // Retorna uma promise para simular o comportamento do SweetAlert2
            return Promise.resolve({isConfirmed: false});
        }
    };
    
    console.warn('SweetAlert2 não está disponível. Usando fallback de alerta nativo.');
}

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
        // Define um limite máximo absoluto para evitar problemas
        const limiteAbsoluto = 50;
        const limiteEfetivo = Math.min(maxNumeros, limiteAbsoluto);
        
        // Se o número não está selecionado e não atingimos o máximo, adicione-o
        if (numerosSelected.length < limiteEfetivo) {
            // Verificar se já temos um número excessivo que pode causar problemas
            if (numerosSelected.length >= 15) { // valor arbitrário para aviso
                // Verificar se existem valores para a quantidade atual + 1
                const qtdFutura = numerosSelected.length + 1;
                const existemValores = jogoAtual && valoresJogos[jogoAtual] && 
                                      valoresJogos[jogoAtual].some(v => parseInt(v.dezenas) === qtdFutura);
                
                if (!existemValores) {
                    // Adicionar o número mesmo sem valores configurados
                    // Em vez de mostrar o modal, incluiremos esta verificação na função atualizarDisplayNumeros
                    numerosSelected.push(numero);
                    element.classList.add('selected');
                    atualizarDisplayNumeros();
                    return;
                }
            }
            
            // Adicionar normalmente
            numerosSelected.push(numero);
            element.classList.add('selected');
        } else {
            // Limite atingido - mostrar uma mensagem visual menos intrusiva
            // Adicionar uma animação de "shake" ao elemento
            element.classList.add('shake-animation');
            
            // Mostrar uma pequena mensagem temporária acima da grade de números
            const selecaoInfo = document.getElementById('selecaoInfo');
            selecaoInfo.textContent = `Limite de ${limiteEfetivo} números atingido!`;
            selecaoInfo.classList.add('bg-warning');
            
            // Restaurar o texto original após 2 segundos
            setTimeout(() => {
                selecaoInfo.textContent = `Selecione de ${minNumeros} a ${maxNumeros} números`;
                selecaoInfo.classList.remove('bg-warning');
                element.classList.remove('shake-animation');
            }, 2000);
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
    
    // Verificar se há valores configurados para o jogo atual
    if (valoresJogos[jogoAtual].length === 0) {
        valorSelect.innerHTML = '<option value="">Não há valores configurados para este jogo</option>';
        
        // Mostrar alerta inline em vez de modal
        const alertEl = document.createElement('div');
        alertEl.className = 'alert alert-warning mt-2';
        alertEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Não existem valores configurados para este jogo. Por favor, entre em contato com o administrador.';
        
        const parentEl = valorSelect.parentElement.parentElement;
        if (!parentEl.querySelector('.alert-warning')) {
            parentEl.appendChild(alertEl);
        }
        
        return;
    }
    
    // Remover alertas anteriores, se existirem
    const parentEl = valorSelect.parentElement.parentElement;
    const alertaExistente = parentEl.querySelector('.alert-warning');
    if (alertaExistente) {
        parentEl.removeChild(alertaExistente);
    }
    
    // Filtrar valores para a quantidade de números selecionados
    const valoresDisponiveis = valoresJogos[jogoAtual].filter(v => v.dezenas == qtdNumeros);
    
    if (valoresDisponiveis.length === 0) {
        // Não há valores para a quantidade de números selecionados
        valorSelect.innerHTML = '<option value="">Não disponível para esta quantidade</option>';
        
        // Exibir mensagem de alerta inline em vez de modal
        const alertEl = document.createElement('div');
        alertEl.className = 'alert alert-warning mt-2';
        alertEl.innerHTML = `<i class="fas fa-exclamation-circle"></i> Você selecionou ${qtdNumeros} números, mas não há valores configurados para essa quantidade. Selecione menos números.`;
        
        // Adicionar apenas se não existe já um alerta
        if (!parentEl.querySelector('.alert-warning')) {
            parentEl.appendChild(alertEl);
        }
        
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
    // Sempre prevenir o envio inicialmente para fazer validações
    e.preventDefault();
    
    // Verificação de número mínimo de números selecionados
    if (numerosSelected.length < minNumeros) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Números insuficientes',
                text: `Você precisa selecionar pelo menos ${minNumeros} números!`
            });
        } else {
            alert(`Números insuficientes! Você precisa selecionar pelo menos ${minNumeros} números!`);
        }
        return;
    }
    
    // Verificação de limite máximo de números (20 é um limite razoável)
    const limiteAbsoluto = 50;
    if (numerosSelected.length > limiteAbsoluto) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Muitos números selecionados',
                text: `Você selecionou ${numerosSelected.length} números, o máximo permitido é ${limiteAbsoluto}.`
            });
        } else {
            alert(`Você selecionou ${numerosSelected.length} números, o máximo permitido é ${limiteAbsoluto}.`);
        }
        return;
    }
    
    // Verificação de valor da aposta
    const valorSelect = document.getElementById('valor_aposta');
    if (valorSelect.value === '') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Valor não selecionado',
                text: 'Por favor, selecione um valor para a aposta.'
            });
        } else {
            alert('Valor não selecionado! Por favor, selecione um valor para a aposta.');
        }
        return;
    }
    
    // Verificar se existem valores configurados para a quantidade de números selecionados
    if (jogoAtual && valoresJogos[jogoAtual]) {
        const existemValores = valoresJogos[jogoAtual].some(v => parseInt(v.dezenas) === numerosSelected.length);
        
        if (!existemValores) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Configuração inválida',
                    text: `Não existem valores configurados para ${numerosSelected.length} números. Por favor, selecione uma quantidade diferente.`
                });
            } else {
                alert(`Não existem valores configurados para ${numerosSelected.length} números. Por favor, selecione uma quantidade diferente.`);
            }
            return;
        }
    }
    
    // Verificar se já existe uma aposta idêntica (mesmo cliente, jogo e números)
    const cliente_id = document.getElementById('cliente_id').value;
    const jogo_id = document.getElementById('jogo_id').value;
    const numeros = document.getElementById('numerosInput').value;
    
    // Mostrar loading enquanto verifica
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Verificando aposta...',
            text: 'Aguarde enquanto verificamos se esta aposta já existe',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    // Verificar se a aposta é repetida via AJAX
    fetch('ajax/verificar_aposta_repetida.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cliente_id=${cliente_id}&jogo_id=${jogo_id}&numeros=${numeros}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            // Aposta já existe
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Aposta repetida',
                    text: data.apostador 
                        ? `Esta sequência de números já foi apostada hoje por ${data.apostador}. Não são permitidas apostas duplicadas.`
                        : 'Esta sequência de números já foi apostada hoje. Não são permitidas apostas duplicadas.'
                });
            } else {
                alert('Esta sequência de números já foi apostada hoje. Não são permitidas apostas duplicadas.');
            }
        } else {
            // Se passar por todas as validações, enviar o formulário
            document.getElementById('formAposta').submit();
        }
    })
    .catch(error => {
        console.error('Erro ao verificar aposta:', error);
        // Em caso de erro na verificação, permite o envio do formulário
        // O backend fará a validação final
        document.getElementById('formAposta').submit();
    });
});

// Inicializar ao carregar a página
window.addEventListener('DOMContentLoaded', function() {
    // Verificar se há algo estranho salvo no localStorage
    try {
        const limiteAbsoluto = 50;
        const possiveisChaves = ['numerosLotomania', 'numerosSelecionados', 'loteriaNumerosSelected'];
        
        possiveisChaves.forEach(chave => {
            const valoresSalvos = localStorage.getItem(chave);
            if (valoresSalvos) {
                try {
                    const valores = JSON.parse(valoresSalvos);
                    if (Array.isArray(valores) && valores.length > limiteAbsoluto) {
                        console.warn(`Detectada seleção excessiva armazenada em ${chave}. Limpando...`);
                        localStorage.removeItem(chave);
                    }
                } catch (e) {
                    // Se não conseguir analisar o JSON, remover para evitar problemas
                    localStorage.removeItem(chave);
                }
            }
        });
    } catch (e) {
        console.error("Erro ao verificar localStorage:", e);
    }
    
    // Inicializações padrão
    atualizarGradeNumeros();
    carregarConfigJogo();
});

// Função para limpar o cache local
function limparCacheLocal() {
    if (window.caches) {
        caches.keys().then(function(names) {
            for (let name of names) {
                caches.delete(name);
            }
        });
    }
    
    // Limpar localStorage
    localStorage.clear();
    
    // Limpar sessionStorage
    sessionStorage.clear();
    
    // Recarregar página com limpeza de cache
    location.reload(true);
    
    // Verifica se SweetAlert2 está disponível e usa alert como fallback
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Cache Limpo',
            text: 'O cache local foi limpo com sucesso. A página será recarregada.',
            showConfirmButton: false,
            timer: 2000
        });
    } else {
        alert('Cache Limpo! O cache local foi limpo com sucesso. A página será recarregada.');
    }
}
</script>

<!-- Carregamento explícito do SweetAlert2 via CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 