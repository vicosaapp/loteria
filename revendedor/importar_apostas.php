<?php
require_once '../config/database.php';

// Verificar o modo de manutenção
require_once __DIR__ . '/verificar_manutencao.php';

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
        if (empty($_POST['cliente_id']) || empty($_POST['jogo_id']) || empty($_POST['apostas']) || empty($_POST['valor_aposta'])) {
            throw new Exception("Todos os campos são obrigatórios");
        }
        
        $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
        $jogo_id = filter_input(INPUT_POST, 'jogo_id', FILTER_VALIDATE_INT);
        
        // Substitui FILTER_SANITIZE_STRING que foi depreciado
        $apostas_texto = htmlspecialchars($_POST['apostas'] ?? '', ENT_QUOTES, 'UTF-8');
        
        // Verificar quebras de linha no texto recebido
        $quebras_originais = substr_count($apostas_texto, "\n") + 1;
        error_log("Texto recebido: " . strlen($apostas_texto) . " caracteres, com aproximadamente " . $quebras_originais . " linhas");
        
        // Normalizar quebras de linha para garantir consistência
        $apostas_texto = str_replace(["\r\n", "\r"], "\n", $apostas_texto);
        
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

        // Separar as apostas por linha e processar cada linha
        $linhas = array_filter(explode("\n", $apostas_texto), 'trim');
        error_log("Número de linhas detectadas: " . count($linhas));
        
        $apostas = [];
        
        // Processar cada linha para extrair os números
        foreach ($linhas as $linha) {
            // Remover espaços extras e separar números
            $numeros = preg_split('/\s+/', trim($linha));
            $numeros = array_filter($numeros, 'is_numeric');
            
            // Verificar se temos números suficientes para uma aposta
            if (!empty($numeros)) {
                $aposta = implode(',', $numeros);
                $apostas[] = $aposta;
                error_log("Aposta processada: " . $aposta . " - Números: " . count($numeros));
            }
        }
        
        if (empty($apostas)) {
            throw new Exception("Nenhuma aposta válida encontrada");
        }
        
        error_log("Total de apostas processadas: " . count($apostas));
        
        // Adicionar log para debug
        error_log("Apostas processadas: " . count($apostas) . " - Linhas originais: " . count($linhas));
        
        // Buscar informações do jogo para validar
        $stmt = $pdo->prepare("SELECT minimo_numeros, maximo_numeros FROM jogos WHERE id = ?");
        $stmt->execute([$jogo_id]);
        $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$jogo) {
            throw new Exception("Jogo não encontrado");
        }
        
        // Validar cada aposta
        foreach ($apostas as $idx => $aposta) {
            $numerosArray = explode(',', $aposta);
            
            // Validar número mínimo de dezenas
            if (count($numerosArray) < $jogo['minimo_numeros']) {
                throw new Exception("Aposta " . ($idx + 1) . " possui menos números que o mínimo permitido (" . $jogo['minimo_numeros'] . ")");
            }
            
            // Validar número máximo de dezenas (com limite absoluto de segurança)
            $maxPermitido = min($jogo['maximo_numeros'], 20);
            if (count($numerosArray) > $maxPermitido) {
                throw new Exception("Aposta " . ($idx + 1) . " excede o máximo permitido (" . $maxPermitido . " números)");
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
        }
        
        // Verificar se o cliente pertence ao revendedor
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND revendedor_id = ?");
        $stmt->execute([$cliente_id, $_SESSION['usuario_id']]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Cliente não encontrado ou não pertence a este revendedor");
        }
        
        // Verificar apostas repetidas antes de inserir
        $apostasRepetidas = [];
        foreach ($apostas as $index => $aposta) {
            // Verificar se já existe uma aposta idêntica para este jogo e números
            $stmt = $pdo->prepare("
                SELECT a.id, u.nome as apostador 
                FROM apostas a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.tipo_jogo_id = ? 
                AND a.numeros = ? 
                AND DATE(a.created_at) = CURDATE()
            ");
            $stmt->execute([$jogo_id, $aposta]);
            
            $aposta_existente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($aposta_existente) {
                $apostasRepetidas[] = [
                    'indice' => $index + 1,
                    'apostador' => $aposta_existente['apostador'],
                    'numeros' => $aposta
                ];
            }
        }
        
        // Se houver apostas repetidas, não permitir a importação
        if (!empty($apostasRepetidas)) {
            $mensagemErro = "As seguintes apostas não podem ser registradas pois já existem no sistema para hoje:<br><ul>";
            
            foreach ($apostasRepetidas as $ap) {
                $numerosFormatados = implode(' ', array_map(function($n) {
                    return str_pad($n, 2, '0', STR_PAD_LEFT);
                }, explode(',', $ap['numeros'])));
                
                $mensagemErro .= "<li>Aposta #{$ap['indice']}: {$numerosFormatados} - já feita por {$ap['apostador']}</li>";
            }
            
            $mensagemErro .= "</ul>Não são permitidas apostas com a mesma sequência de números.";
            
            throw new Exception($mensagemErro);
        }

        // Iniciar transação
        $pdo->beginTransaction();
        
        try {
            // Verificar número de apostas antes de inserir
            error_log("Iniciando inserção de " . count($apostas) . " apostas no banco de dados");
            
            // Inserir cada aposta como um registro separado
            foreach ($apostas as $index => $aposta) {
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
                    $aposta,
                    $valor_aposta,
                    $valor_premio
                ]);
                
                if (!$result) {
                    error_log("Erro ao inserir aposta #" . ($index + 1));
                    throw new Exception("Erro ao salvar a aposta #" . ($index + 1));
                }
            }

            // Commit da transação
            $pdo->commit();
            
            $mensagem = count($apostas) . " aposta(s) registrada(s) com sucesso!";
            $tipo_mensagem = "success";
            error_log("Transação concluída: " . count($apostas) . " apostas registradas");
            
            // Adicionar timestamp para visualização das apostas recém-criadas
            $_SESSION['ultima_importacao'] = time();
            $_SESSION['apostas_importadas'] = count($apostas);
            
        } catch (Exception $e) {
            // Rollback em caso de erro
            $pdo->rollBack();
            error_log("Erro na transação: " . $e->getMessage());
            throw $e;
        }
        
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
    
    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?>" role="alert">
            <div class="d-flex justify-content-between align-items-center">
                <div><?php echo $mensagem; ?></div>
                <?php if ($tipo_mensagem == 'success' && isset($_SESSION['apostas_importadas']) && $_SESSION['apostas_importadas'] > 0): ?>
                    <a href="apostas.php?ultimas=<?php echo $_SESSION['apostas_importadas']; ?>" class="btn btn-primary ms-3">
                        <i class="fas fa-eye"></i> Ver estas apostas
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Alerta informativo sobre apostas repetidas -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i> <strong>Atenção!</strong> Não é permitido registrar apostas com a mesma sequência de números no mesmo jogo no mesmo dia, independentemente do cliente.
    </div>
    
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
                        <label class="form-label">Cole as apostas (uma por linha)</label>
                        <textarea id="apostasTextarea" name="apostas" class="form-control" rows="8" placeholder="Exemplo:\n01 02 03 04 05 06 07 08 09 10 11 12 13 14 15\n02 03 04 05 06 07 08 09 10 11 12 13 14 15 16"></textarea>
                        <div class="alert alert-info mt-2">
                            Cole os números das apostas, separados por espaço, uma aposta por linha.<br>
                            Exemplo: <code>01 02 03 04 05 06 07 08 09 10 11 12 13 14 15</code>
                        </div>
                        <div id="previewApostas" class="apostas-preview mt-3"></div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label for="valor_aposta" class="form-label">Valor da Aposta</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <select name="valor_aposta" id="valor_aposta" class="form-control" required>
                                <option value="">Selecione o valor</option>
                            </select>
                        </div>
                        <input type="hidden" name="premio" id="premioInput" value="">
                        <div id="infoPremiacao" class="mt-2 d-none">
                            <small class="text-muted">Valor do Prêmio: <span id="valorPremiacao">R$ 0,00</span></small>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-lg w-100" id="btnSubmit">
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
    
    /* Garantir que o botão de confirmação seja visível completamente */
    #btnSubmit {
        margin-bottom: 70px; /* Espaço para não ser ocultado pela navegação móvel */
        width: 100%;
        font-size: 1rem;
        padding: 10px;
    }
}

.apostas-preview {
    max-height: 300px;
    overflow-y: auto;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
}
.aposta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 2px;
}
.numero-bolinha {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    margin: 1px;
    font-weight: 600;
    font-size: 13px;
    padding: 0;
    line-height: 1;
    text-align: center;
}
</style>

<script>
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
        // Limpar select de valores
        const valorSelect = document.getElementById('valor_aposta');
        valorSelect.innerHTML = '<option value="">Selecione o valor</option>';
        // Ao trocar o jogo, processar apostas coladas para atualizar valores
        processarApostasColadas();
    } else {
        jogoAtual = null;
        document.getElementById('valor_aposta').innerHTML = '<option value="">Selecione o valor</option>';
    }
}

function atualizarValoresDisponiveis(qtdNumeros) {
    const valorSelect = document.getElementById('valor_aposta');
    const infoPremiacaoEl = document.getElementById('infoPremiacao');
    const valorPremiacaoEl = document.getElementById('valorPremiacao');
    
    // Limpar select de valores
    valorSelect.innerHTML = '<option value="">Selecione o valor</option>';
    
    // Esconder info de premiação inicialmente
    if (infoPremiacaoEl) infoPremiacaoEl.classList.add('d-none');
    if (valorPremiacaoEl) valorPremiacaoEl.textContent = '';
    
    if (!jogoAtual || !valoresJogos[jogoAtual]) return;
    
    // Verificar se há valores configurados para o jogo atual
    if (valoresJogos[jogoAtual].length === 0) {
        valorSelect.innerHTML = '<option value="">Não há valores configurados para este jogo</option>';
        return;
    }
    
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
        option.setAttribute('data-valor-base', valor.valor_aposta);
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
        const valorAposta = parseFloat(option.value);
        const valorBasePremio = parseFloat(option.getAttribute('data-premio'));
        const valorBaseAposta = parseFloat(option.getAttribute('data-valor-base'));
        
        // Calcular o multiplicador baseado no valor da aposta
        const multiplicador = valorAposta / valorBaseAposta;
        
        // Calcular valor final do prêmio
        const valorPremioFinal = valorBasePremio * multiplicador;
        
        // Mostrar info de premiação
        if (infoPremiacaoEl) infoPremiacaoEl.classList.remove('d-none');
        if (valorPremiacaoEl) valorPremiacaoEl.textContent = `R$ ${valorPremioFinal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
        
        // Atualizar o campo oculto com o valor do prêmio
        if (premioInput) premioInput.value = valorPremioFinal;
    } else {
        // Esconder info de premiação
        if (infoPremiacaoEl) infoPremiacaoEl.classList.add('d-none');
        if (valorPremiacaoEl) valorPremiacaoEl.textContent = '';
        if (premioInput) premioInput.value = '';
    }
}

document.getElementById('formAposta').addEventListener('submit', function(e) {
    e.preventDefault();

    // Validação baseada nas apostas coladas
    const textarea = document.getElementById('apostasTextarea');
    const apostas = textarea.value.trim().split('\n').map(l => l.trim()).filter(l => l);
    if (apostas.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Nenhuma aposta colada',
            text: 'Cole pelo menos uma aposta para continuar.'
        });
        return;
    }
    if (!jogoAtual) {
        Swal.fire({
            icon: 'error',
            title: 'Jogo não selecionado',
            text: 'Selecione um jogo antes de confirmar.'
        });
        return;
    }
    let erro = null;
    apostas.forEach((linha, idx) => {
        const numeros = linha.split(/\s+/).filter(n => n);
        if (numeros.length < minNumeros) {
            erro = `Aposta ${idx+1} possui menos números que o mínimo permitido (${minNumeros}).`;
        }
        if (numeros.length > maxNumeros) {
            erro = `Aposta ${idx+1} possui mais números que o máximo permitido (${maxNumeros}).`;
        }
        if (numeros.length > 20) {
            erro = `Aposta ${idx+1} excede o limite absoluto de 20 números.`;
        }
    });
    if (erro) {
        Swal.fire({
            icon: 'error',
            title: 'Aposta inválida',
            text: erro
        });
        return;
    }
    // Verificação de valor da aposta
    const valorSelect = document.getElementById('valor_aposta');
    if (valorSelect.value === '') {
        Swal.fire({
            icon: 'error',
            title: 'Valor não selecionado',
            text: 'Por favor, selecione um valor para a aposta.'
        });
        return;
    }

    // Verificar apostas repetidas
    const cliente_id = document.getElementById('cliente_id').value;
    const jogo_id = document.getElementById('jogo_id').value;
    
    // Criar array de apostas formatadas para verificação
    const apostasFormatadas = apostas.map(linha => {
        const numeros = linha.split(/\s+/).filter(n => n).map(n => parseInt(n, 10));
        return numeros.sort((a, b) => a - b).join(',');
    });
    
    // Mostrar loading enquanto verifica
    Swal.fire({
        title: 'Verificando apostas...',
        text: 'Aguarde enquanto verificamos se existe alguma aposta repetida',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Fazer múltiplas verificações de apostas repetidas
    const verificacoes = apostasFormatadas.map((numeros, index) => {
        return fetch('ajax/verificar_aposta_repetida.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cliente_id=${cliente_id}&jogo_id=${jogo_id}&numeros=${numeros}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                return {
                    index: index + 1, 
                    existe: true,
                    apostador: data.apostador || 'Outro apostador',
                    numeros: apostasFormatadas[index]
                };
            }
            return {index: index + 1, existe: false};
        });
    });
    
    // Aguardar todas as verificações
    Promise.all(verificacoes)
        .then(resultados => {
            // Filtrar apenas as apostas repetidas
            const repetidas = resultados.filter(r => r.existe);
            
            if (repetidas.length > 0) {
                // Existem apostas repetidas
                let mensagem = 'As seguintes apostas não podem ser registradas pois já existem no sistema:<br><ul>';
                
                repetidas.forEach(ap => {
                    // Formatar os números para exibição
                    const numerosExibicao = ap.numeros.split(',').map(n => {
                        return n.toString().padStart(2, '0');
                    }).join(' ');
                    
                    mensagem += `<li>Aposta #${ap.index}: ${numerosExibicao} - já apostada por ${ap.apostador}</li>`;
                });
                
                mensagem += '</ul>Não são permitidas apostas com a mesma sequência de números.';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Apostas repetidas',
                    html: mensagem
                });
            } else {
                // Se não houver apostas repetidas, enviar o formulário
                this.submit();
            }
        })
        .catch(error => {
            console.error('Erro ao verificar apostas:', error);
            // Em caso de erro na verificação, permite o envio do formulário
            // O backend fará a validação final
            this.submit();
        });
});

// Substituir seleção manual por processamento do textarea
function processarApostasColadas() {
    const textarea = document.getElementById('apostasTextarea');
    const valorSelect = document.getElementById('valor_aposta');
    if (!jogoAtual) {
        console.log('[DEBUG] Nenhum jogo selecionado');
        valorSelect.innerHTML = '<option value="">Selecione o valor</option>';
        return;
    }
    const apostas = textarea.value.trim().split('\n').map(l => l.trim()).filter(l => l);
    if (apostas.length === 0) {
        console.log('[DEBUG] Nenhuma aposta colada');
        valorSelect.innerHTML = '<option value="">Selecione o valor</option>';
        return;
    }
    // Detectar quantidade de dezenas da primeira linha válida
    let dezenas = 0;
    for (let i = 0; i < apostas.length; i++) {
        const numeros = apostas[i].split(/\s+/).filter(n => n);
        if (numeros.length > 0) {
            dezenas = numeros.length;
            break;
        }
    }
    console.log('[DEBUG] Jogo selecionado:', jogoAtual);
    console.log('[DEBUG] Dezenas detectadas:', dezenas);
    console.log('[DEBUG] valoresJogos[jogoAtual]:', valoresJogos[jogoAtual]);
    if (dezenas > 0) {
        atualizarValoresDisponiveis(dezenas);
    } else {
        valorSelect.innerHTML = '<option value="">Selecione o valor</option>';
    }
}

function atualizarPreviewApostas() {
    const textarea = document.getElementById('apostasTextarea');
    const preview = document.getElementById('previewApostas');
    
    // Limpa a preview
    preview.innerHTML = '';
    
    if (!textarea.value.trim()) {
        return;
    }
    
    // Separa por linhas, ignorando linhas vazias
    const apostas = textarea.value.trim().split('\n')
        .map(linha => linha.trim())
        .filter(linha => linha);
    
    console.log(`Número de linhas detectadas na preview: ${apostas.length}`);
    
    let html = '';
    apostas.forEach((linha, idx) => {
        const numeros = linha.split(/\s+/).filter(n => n.trim() !== '');
        console.log(`Linha ${idx+1}: ${numeros.length} números`);
        
        html += `<div class='aposta-item mb-1'><span class='me-2 text-muted'>${(idx+1).toString().padStart(2,'0')}</span>`;
        numeros.forEach(num => {
            html += `<span class='numero-bolinha'>${num.padStart(2,'0')}</span>`;
        });
        html += '</div>';
    });
    preview.innerHTML = html;
    
    // Atualiza o contador
    if (apostas.length > 0) {
        const counter = document.createElement('div');
        counter.className = 'text-end mt-2 text-muted';
        counter.innerHTML = `<small>Total: ${apostas.length} aposta(s)</small>`;
        preview.appendChild(counter);
    }
}

document.getElementById('jogo_id').addEventListener('change', carregarConfigJogo);
document.getElementById('apostasTextarea').addEventListener('input', processarApostasColadas);
document.getElementById('apostasTextarea').addEventListener('input', atualizarPreviewApostas);
window.addEventListener('DOMContentLoaded', atualizarPreviewApostas);

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
    
    Swal.fire({
        icon: 'success',
        title: 'Cache Limpo',
        text: 'O cache local foi limpo com sucesso. A página será recarregada.',
        showConfirmButton: false,
        timer: 2000
    });
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 