<?php
require_once '../config/database.php';

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'usuario') {
    header('Location: ../login.php');
    exit;
}

// Verificar se o ID do jogo foi passado
if (!isset($_GET['jogo_id'])) {
    header('Location: dashboard.php');
    exit;
}

$jogo_id = $_GET['jogo_id'];

try {
    // Buscar informações do jogo
    $stmt = $pdo->prepare("
        SELECT j.*, 
               (SELECT MIN(valor_aposta) FROM configuracoes_apostas WHERE tipo_jogo_id = j.id) as valor_min,
               (SELECT MAX(valor_premio) FROM configuracoes_apostas WHERE tipo_jogo_id = j.id) as premio_max
        FROM jogos j 
        WHERE j.id = ? AND j.status = 1
    ");
    $stmt->execute([$jogo_id]);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$jogo) {
        header('Location: dashboard.php');
        exit;
    }

    // Buscar configurações de apostas para este jogo
    $stmt = $pdo->prepare("
        SELECT * FROM configuracoes_apostas 
        WHERE tipo_jogo_id = ? AND status = 'ativo'
        ORDER BY quantidade_numeros ASC, valor_aposta ASC
    ");
    $stmt->execute([$jogo_id]);
    $configuracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erro ao buscar informações: " . $e->getMessage());
}

$currentPage = 'apostas';
$pageTitle = 'Fazer Aposta - ' . $jogo['nome'];

ob_start();
?>

<style>
.aposta-container {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.jogo-header {
    background: linear-gradient(135deg, #4e73df, #224abe);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.jogo-title {
    font-size: 1.4rem;
    margin-bottom: 10px;
}

.jogo-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.info-card {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
}

.info-label {
    color: #6e7687;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.info-value {
    color: #2d3748;
    font-size: 1.1rem;
    font-weight: 600;
}

.numeros-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
    gap: 10px;
    margin: 20px 0;
}

.numero-btn {
    width: 45px;
    height: 45px;
    border: none;
    border-radius: 50%;
    background: #f0f2f5;
    color: #2d3748;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.numero-btn:hover {
    background: #e2e8f0;
}

.numero-btn.selected {
    background: #4e73df;
    color: white;
}

.opcoes-aposta {
    margin: 20px 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.opcao-card {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s;
}

.opcao-card:hover {
    border-color: #4e73df;
}

.opcao-card.selected {
    border-color: #4e73df;
    background: #f8f9fc;
}

.opcao-valor {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 5px;
}

.opcao-premio {
    color: #4e73df;
    font-size: 0.9rem;
}

.btn-apostar {
    background: #4e73df;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    margin-top: 20px;
    transition: all 0.3s;
}

.btn-apostar:hover {
    background: #224abe;
}

.btn-apostar:disabled {
    background: #cbd5e0;
    cursor: not-allowed;
}
</style>

<div class="aposta-container">
    <div class="jogo-header">
        <h1 class="jogo-title"><?php echo htmlspecialchars($jogo['nome']); ?></h1>
        <p>Escolha <?php echo $jogo['dezenas_premiar']; ?> números para apostar</p>
    </div>

    <div class="jogo-info">
        <div class="info-card">
            <div class="info-label">Números Necessários</div>
            <div class="info-value"><?php echo $jogo['dezenas_premiar']; ?></div>
        </div>
        <div class="info-card">
            <div class="info-label">Valor Mínimo</div>
            <div class="info-value">R$ <?php echo number_format($jogo['valor_min'], 2, ',', '.'); ?></div>
        </div>
        <div class="info-card">
            <div class="info-label">Prêmio Máximo</div>
            <div class="info-value">R$ <?php echo number_format($jogo['premio_max'], 2, ',', '.'); ?></div>
        </div>
    </div>

    <div class="numeros-grid">
        <?php for($i = 1; $i <= $jogo['total_numeros']; $i++): ?>
            <button class="numero-btn" data-numero="<?php echo $i; ?>"><?php echo $i; ?></button>
        <?php endfor; ?>
    </div>

    <div class="opcoes-aposta">
        <?php foreach($configuracoes as $config): ?>
            <div class="opcao-card" data-valor="<?php echo $config['valor_aposta']; ?>">
                <div class="opcao-valor">R$ <?php echo number_format($config['valor_aposta'], 2, ',', '.'); ?></div>
                <div class="opcao-premio">Prêmio: R$ <?php echo number_format($config['valor_premio'], 2, ',', '.'); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <button class="btn-apostar" id="btnApostar" disabled>Confirmar Aposta</button>
</div>

<script>
let numerosEscolhidos = [];
const numerosBtns = document.querySelectorAll('.numero-btn');
const opcoesBtns = document.querySelectorAll('.opcao-card');
const btnApostar = document.getElementById('btnApostar');
const numeroMaximo = <?php echo $jogo['dezenas_premiar']; ?>;
let valorEscolhido = null;

numerosBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const numero = parseInt(btn.dataset.numero);
        
        if (btn.classList.contains('selected')) {
            btn.classList.remove('selected');
            numerosEscolhidos = numerosEscolhidos.filter(n => n !== numero);
        } else if (numerosEscolhidos.length < numeroMaximo) {
            btn.classList.add('selected');
            numerosEscolhidos.push(numero);
        }
        
        verificarBotaoApostar();
    });
});

opcoesBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        opcoesBtns.forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        valorEscolhido = parseFloat(btn.dataset.valor);
        verificarBotaoApostar();
    });
});

function verificarBotaoApostar() {
    btnApostar.disabled = numerosEscolhidos.length !== numeroMaximo || !valorEscolhido;
}

btnApostar.addEventListener('click', () => {
    const dados = {
        jogo_id: <?php echo $jogo_id; ?>,
        numeros: numerosEscolhidos.sort((a, b) => a - b),
        valor: valorEscolhido
    };
    
    fetch('ajax/salvar_aposta.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Aposta realizada com sucesso!');
            window.location.href = 'minhas_apostas.php';
        } else {
            alert(data.message || 'Erro ao realizar aposta');
        }
    })
    .catch(error => {
        alert('Erro ao salvar aposta: ' + error.message);
    });
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 