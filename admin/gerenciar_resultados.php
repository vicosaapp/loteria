<?php
require_once 'includes/header.php';
require_once '../config/database.php';

// Define a página atual para o menu
$currentPage = 'resultados';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    // Buscar totais para os cards informativos
    $stmt = $pdo->query("SELECT COUNT(*) FROM resultados");
    $totalResultados = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM ganhadores");
    $totalGanhadores = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(premio), 0) FROM ganhadores");
    $totalPremios = $stmt->fetchColumn();
    
    // Buscar jogos ativos
    $stmt = $pdo->query("
        SELECT j.*, 
               (SELECT COUNT(*) FROM resultados WHERE tipo_jogo_id = j.id) as total_resultados
        FROM jogos j 
        WHERE j.status = 1 
        ORDER BY j.created_at DESC
    ");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar todos os últimos resultados com contagem de ganhadores
    $resultados = [];
    $stmt = $pdo->query("
        SELECT r.*, 
               COUNT(DISTINCT g.id) as total_ganhadores,
               COALESCE(SUM(g.premio), 0) as total_premios
        FROM resultados r 
        INNER JOIN (
            SELECT tipo_jogo_id, MAX(data_sorteio) as ultima_data
            FROM resultados
            GROUP BY tipo_jogo_id
        ) ultimos ON r.tipo_jogo_id = ultimos.tipo_jogo_id 
                  AND r.data_sorteio = ultimos.ultima_data
        LEFT JOIN ganhadores g ON g.resultado_id = r.id
        GROUP BY r.id
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultados[$row['tipo_jogo_id']] = $row;
    }
} catch(PDOException $e) {
    die("Erro ao buscar informações: " . $e->getMessage());
}

ob_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Resultados</title>
    <!-- Seus estilos e scripts aqui -->
</head>
<body>
    <?php include_once 'includes/header.php'; ?>
    
    <div class="container">
        <!-- Cards informativos -->
        <div class="info-cards">
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="info-content">
                    <h3>Total de Resultados</h3>
                    <p class="info-value"><?php echo $totalResultados; ?></p>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="info-content">
                    <h3>Total de Ganhadores</h3>
                    <p class="info-value"><?php echo $totalGanhadores; ?></p>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="info-content">
                    <h3>Total em Prêmios</h3>
                    <p class="info-value">R$ <?php echo number_format($totalPremios, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Grid de jogos -->
        <div class="games-grid">
            <?php if(empty($jogos)): ?>
                <div class="no-data-message">
                    <i class="fas fa-trophy"></i>
                    <h3>Nenhum Jogo Disponível</h3>
                    <p>Não há jogos ativos para adicionar resultados.</p>
                </div>
            <?php else: ?>
                <?php foreach($jogos as $jogo): 
                    $temResultado = isset($resultados[$jogo['id']]);
                    $cardClass = $temResultado ? 'game-card has-result' : 'game-card';
                ?>
                    <div class="<?php echo $cardClass; ?>">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($jogo['nome']); ?></h3>
                            <span class="badge"><?php echo $jogo['dezenas_premiar']; ?> números</span>
                        </div>
                        
                        <div class="card-stats">
                            <div class="stat-item">
                                <div class="label">Total de Números</div>
                                <div class="value"><?php echo $jogo['total_numeros']; ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="label">Prêmio</div>
                                <div class="value">R$ <?php echo number_format($jogo['premio'], 2, ',', '.'); ?></div>
                            </div>
                        </div>

                        <?php if($temResultado): 
                            $resultado = $resultados[$jogo['id']];
                        ?>
                            <div class="resultado-info">
                                <div class="resultado-data">
                                    <i class="fas fa-calendar"></i>
                                    <span>Último Sorteio: <?php echo date('d/m/Y', strtotime($resultado['data_sorteio'])); ?></span>
                                </div>
                                <div class="numeros-sorteados">
                                    <label>Números Sorteados:</label>
                                    <div class="numbers-grid">
                                        <?php foreach(explode(',', $resultado['numeros']) as $numero): ?>
                                            <div class="number"><?php echo str_pad($numero, 2, '0', STR_PAD_LEFT); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Adicionar informações de ganhadores -->
                                <div class="ganhadores-info">
                                    <div class="ganhadores-item">
                                        <i class="fas fa-trophy"></i>
                                        <span><?php echo $resultado['total_ganhadores']; ?> ganhador<?php echo $resultado['total_ganhadores'] != 1 ? 'es' : ''; ?></span>
                                    </div>
                                    <?php if($resultado['total_ganhadores'] > 0): ?>
                                        <div class="ganhadores-item">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Total Prêmios: R$ <?php echo number_format($resultado['total_premios'], 2, ',', '.'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <button onclick="excluirResultado(<?php echo $resultado['id']; ?>, <?php echo $jogo['id']; ?>)" 
                                        class="btn-delete-result">
                                    <i class="fas fa-trash"></i>
                                    Excluir Resultado
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <button onclick="abrirModalResultado(<?php echo $jogo['id']; ?>, <?php echo $jogo['dezenas_premiar']; ?>)" 
                                class="btn-add-result">
                            <i class="fas fa-plus"></i>
                            <span><?php echo $temResultado ? 'Novo Resultado' : 'Adicionar Resultado'; ?></span>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Resultado -->
    <?php include_once 'includes/modal_resultado.php'; ?>
    
    <!-- Seus scripts JS aqui -->
</body>
</html>

<style>
/* Seus estilos existentes */

/* Ajuste o container principal */
.page-container {
    padding: 20px 20px 20px 40px;
    margin-left: 240px;
    width: calc(100% - 240px);
    min-height: 100vh;
    background: #f8f9fa;
}

/* Ajuste o grid para exatamente 3 cards por linha */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* Força 3 colunas */
    gap: 25px;
    margin-right: 20px;
}

/* Ajuste os cards */
.game-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%; /* Garante mesma altura */
}

/* Ajuste o dashboard stats também para 3 colunas */
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    margin-bottom: 30px;
    margin-right: 20px;
}

/* Ajuste responsivo */
@media (max-width: 1400px) {
    .cards-grid {
        grid-template-columns: repeat(2, 1fr); /* 2 cards por linha */
    }
    
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 1000px) {
    .cards-grid {
        grid-template-columns: 1fr; /* 1 card por linha */
    }
    
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-container {
        margin-left: 0;
        width: 100%;
        padding: 15px;
    }
    
    .cards-grid, 
    .dashboard-stats {
        margin-right: 0;
    }
}

/* Mantenha os outros estilos iguais... */

/* Estilos para o Modal de Ganhadores */
.winners-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-height: 70vh;
    overflow-y: auto;
    padding: 10px;
}

.winner-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.winner-header {
    background: linear-gradient(135deg, #4e73df, #224abe);
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.winner-header h3 {
    color: white;
    margin: 0;
    font-size: 1.1rem;
}

.winner-info {
    padding: 15px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.prize {
    color: #27ae60;
    font-size: 1.2rem;
}

.numbers-info {
    margin-top: 15px;
}

.numbers-row {
    margin-bottom: 15px;
}

.numbers-row span {
    display: block;
    margin-bottom: 5px;
    color: #666;
}

.numbers-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.number {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #4e73df;
    border-radius: 50%;
    font-size: 0.9rem;
    color: #4e73df;
}

.number.winner {
    background: #4e73df;
    color: white;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge.pendente {
    background: #f39c12;
    color: white;
}

.status-badge.pago {
    background: #27ae60;
    color: white;
}

.no-winners {
    text-align: center;
    padding: 40px;
}

.no-winners i {
    font-size: 48px;
    color: #95a5a6;
    margin-bottom: 20px;
}

.no-winners p {
    color: #666;
    font-size: 1.1rem;
}

/* Cards Informativos */
.info-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.info-card:hover {
    transform: translateY(-5px);
}

.info-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgb(103 185 90 / 37%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.info-icon i {
    font-size: 24px;
    color: #2c3e50;
}

.info-content h3 {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.info-value {
    margin: 5px 0 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
}

/* Grid de Jogos */
.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.game-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    transition: all 0.3s ease;
    border: 1px solid #e3e6f0;
}

.game-card.has-result {
    background: #efd9d9;
    border-color: #fd0000;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #edf2f7;
}

.card-header h3 {
    margin: 0;
    color: #2d3748;
    font-size: 1.25rem;
}

.badge {
    background: #4e73df;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.875rem;
}

.card-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.stat-item {
    padding: 10px;
    background: #f8f9fc;
    border-radius: 8px;
    text-align: center;
}

.stat-item .label {
    font-size: 0.875rem;
    color: #858796;
    margin-bottom: 5px;
}

.stat-item .value {
    font-size: 1.25rem;
    font-weight: 600;
    color: #4e73df;
}

.resultado-info {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.resultado-data {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #5a5c69;
    margin-bottom: 10px;
}

.numeros-sorteados {
    margin: 15px 0;
}

.numeros-sorteados label {
    display: block;
    margin-bottom: 8px;
    color: #4e73df;
    font-weight: 500;
}

.numbers-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.number {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #4e73df;
    color: white;
    border-radius: 50%;
    font-weight: 600;
    font-size: 0.875rem;
}

.btn-add-result {
    width: 100%;
    padding: 12px;
    background: #4e73df;
    color: white;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-add-result:hover {
    background: #2e59d9;
}

.btn-delete-result {
    width: 100%;
    padding: 10px;
    background: #e74a3b;
    color: white;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.btn-delete-result:hover {
    background: #be3c2f;
}

/* Responsividade */
@media (max-width: 1200px) {
    .info-cards {
        grid-template-columns: repeat(3, 1fr);
    }
    .games-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .info-cards {
        grid-template-columns: repeat(1, 1fr);
    }
    .games-grid {
        grid-template-columns: 1fr;
    }
}

/* Ajuste dos cards existentes */
.game-card {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.card-body {
    flex: 1;
}

/* Espaçamento geral */
.page-content {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Ajustes no Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    overflow-y: auto;
    padding: 20px;
}

.modal-content {
    background: white;
    border-radius: 10px;
    margin: 20px auto;
    width: 95%;
    max-width: 900px; /* Aumentado para melhor visualização */
    position: relative;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.modal-header {
    padding-bottom: 15px;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #2c3e50;
}

.modal-body {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    padding: 0 10px;
}

/* Grid de Números */
.numbers-grid-select {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 8px;
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fc;
    border-radius: 8px;
}

.number-select {
    width: 40px; /* Tamanho fixo para os números */
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #4e73df;
    border-radius: 50%;
    font-size: 1rem;
    color: #4e73df;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 0 auto;
}

.number-select:hover {
    transform: scale(1.1);
    background: #f8f9fc;
}

.number-select.selected {
    background: #4e73df;
    color: white;
}

/* Formulário */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

/* Botões do Modal */
.modal-footer {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    position: sticky;
    bottom: 0;
    background: white;
}

.btn-secondary,
.btn-primary {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-primary {
    background: #4e73df;
    color: white;
}

.btn-primary:hover {
    background: #2e59d9;
}

/* Botão Fechar */
.close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #666;
    cursor: pointer;
    padding: 5px;
}

.close:hover {
    color: #333;
}

/* Responsividade */
@media (max-width: 768px) {
    .numbers-grid-select {
        grid-template-columns: repeat(5, 1fr);
        gap: 5px;
    }

    .number-select {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }

    .modal-content {
        margin: 10px;
        padding: 15px;
    }
}

/* Adicione ao bloco de estilos */
.ganhadores-info {
    margin: 15px 0;
    padding: 15px;
    background: #f9ff00;
    border-radius: 8px;
}

.ganhadores-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    color: #1f2125;
    font-weight: 500;
}

.ganhadores-item:last-child {
    margin-bottom: 0;
}

.ganhadores-item i {
    width: 20px;
    text-align: center;
}

.ganhadores-item span {
    font-size: 0.95rem;
}
</style>

<script>
/* Seus scripts existentes */

function verGanhadores(jogoId) {
    fetch(`ajax/buscar_ganhadores.php?jogo_id=${jogoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarModalGanhadores(data.data);
            } else {
                alert(data.message || 'Erro ao buscar ganhadores');
            }
        })
        .catch(error => {
            alert('Erro ao buscar ganhadores: ' + error.message);
        });
}

function mostrarModalGanhadores(ganhadores) {
    // Cria o conteúdo do modal
    let html = `
        <div class="modal-header">
            <h2>Ganhadores</h2>
            <button type="button" class="close" onclick="fecharModalGanhadores()">&times;</button>
        </div>
        <div class="modal-body">
    `;
    
    if (ganhadores.length === 0) {
        html += `
            <div class="no-winners">
                <i class="fas fa-trophy"></i>
                <p>Nenhum ganhador encontrado</p>
            </div>
        `;
    } else {
        html += `
            <div class="winners-list">
                ${ganhadores.map(ganhador => `
                    <div class="winner-card">
                        <div class="winner-header">
                            <h3>${ganhador.usuario_nome}</h3>
                            <span class="status-badge ${ganhador.status}">
                                ${ganhador.status === 'pago' ? 'Prêmio Pago' : 'Pendente'}
                            </span>
                        </div>
                        <div class="winner-info">
                            <div class="info-row">
                                <span>Data do Sorteio:</span>
                                <strong>${ganhador.data_sorteio_formatada}</strong>
                            </div>
                            <div class="info-row">
                                <span>Prêmio:</span>
                                <strong class="prize">R$ ${ganhador.premio_formatado}</strong>
                            </div>
                            <div class="numbers-info">
                                <div class="numbers-row">
                                    <span>Números Apostados:</span>
                                    <div class="numbers-grid">
                                        ${ganhador.numeros_apostados.map(num => 
                                            `<div class="number">${num.padStart(2, '0')}</div>`
                                        ).join('')}
                                    </div>
                                </div>
                                <div class="numbers-row">
                                    <span>Números Sorteados:</span>
                                    <div class="numbers-grid">
                                        ${ganhador.numeros_sorteados.map(num => 
                                            `<div class="number winner">${num.padStart(2, '0')}</div>`
                                        ).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    html += `</div>`;
    
    // Cria o modal
    const modal = document.createElement('div');
    modal.id = 'ganhadoresModal';
    modal.className = 'modal';
    modal.innerHTML = html;
    
    // Adiciona o modal ao documento
    document.body.appendChild(modal);
    
    // Mostra o modal
    modal.style.display = 'block';
}

function fecharModalGanhadores() {
    const modal = document.getElementById('ganhadoresModal');
    if (modal) {
        modal.remove();
    }
}

// Adicione estes estilos ao seu CSS
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>

<!-- Scripts movidos para depois do layout -->
<script>
let numerosSelecionados = [];
let dezenasPremiar = 0;

function abrirModalResultado(jogoId, qtdDezenas) {
    numerosSelecionados = [];
    dezenasPremiar = qtdDezenas;
    
    document.getElementById('jogoId').value = jogoId;
    document.getElementById('resultadoModal').style.display = 'block';
    
    // Busca informações do jogo para gerar o grid
    fetch(`ajax/buscar_jogo.php?id=${jogoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const jogo = data.data;
                gerarGridNumeros(jogo.total_numeros);
                
                // Atualiza o texto do modal para incluir a informação
                document.querySelector('.modal-header h2').innerHTML = 
                    `Adicionar Resultado <small>(Selecione ${dezenasPremiar} números)</small>`;
                
                // Inicializa o contador
                atualizarContador();
            } else {
                alert('Erro ao carregar informações do jogo');
            }
        });
}

function gerarGridNumeros(totalNumeros) {
    const grid = document.getElementById('numerosGrid');
    grid.innerHTML = '';
    
    for (let i = 1; i <= totalNumeros; i++) {
        const numero = document.createElement('div');
        numero.className = 'number-select';
        numero.textContent = String(i).padStart(2, '0');
        numero.onclick = () => toggleNumero(numero);
        grid.appendChild(numero);
    }
}

function toggleNumero(elemento) {
    const numero = parseInt(elemento.textContent);
    const index = numerosSelecionados.indexOf(numero);
    
    if (index === -1) {
        if (numerosSelecionados.length >= dezenasPremiar) {
            alert(`Você só pode selecionar ${dezenasPremiar} números!`);
            return;
        }
        elemento.classList.add('selected');
        numerosSelecionados.push(numero);
    } else {
        elemento.classList.remove('selected');
        numerosSelecionados.splice(index, 1);
    }
    
    atualizarContador();
}

function atualizarContador() {
    const contador = document.getElementById('numerosContador');
    if (contador) {
        contador.textContent = `${numerosSelecionados.length}/${dezenasPremiar} números selecionados`;
    }
}

function fecharModalResultado() {
    document.getElementById('resultadoModal').style.display = 'none';
    document.getElementById('resultadoForm').reset();
    numerosSelecionados = [];
    dezenasPremiar = 0;
    document.getElementById('numerosGrid').innerHTML = '';
    document.querySelector('.modal-header h2').innerHTML = 'Adicionar Resultado';
    atualizarContador();
}

function salvarResultado(event) {
    event.preventDefault();
    
    if (numerosSelecionados.length !== dezenasPremiar) {
        alert(`Você precisa selecionar exatamente ${dezenasPremiar} números!`);
        return;
    }
    
    const dados = {
        jogo_id: document.getElementById('jogoId').value,
        numeros: numerosSelecionados,
        data_sorteio: document.getElementById('dataSorteio').value
    };
    
    fetch('ajax/salvar_resultado.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Resultado salvo com sucesso!\nGanhadores: ${data.ganhadores}\nPrêmio Total: R$ ${data.premio_total}`);
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao salvar resultado');
        }
    })
    .catch(error => {
        alert('Erro ao salvar: ' + error.message);
    });
}

// Fechar modal quando clicar fora
window.onclick = function(event) {
    if (event.target == document.getElementById('resultadoModal')) {
        fecharModalResultado();
    }
}
</script> 