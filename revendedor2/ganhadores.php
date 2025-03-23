<?php
// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar arquivos de inclusão
$config_path = '../config/database.php';
$layout_path = 'includes/layout.php';

// Função para exibir erros de forma amigável
function displayError($message) {
    echo "<!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <title>Erro</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='alert alert-danger' role='alert'>
                <h2>Ocorreu um erro</h2>
                <p>" . htmlspecialchars($message) . "</p>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

// Verificar existência dos arquivos
if (!file_exists($config_path)) {
    displayError("Arquivo de configuração do banco de dados não encontrado em $config_path");
}

if (!file_exists($layout_path)) {
    displayError("Arquivo de layout não encontrado em $layout_path");
}

// Incluir arquivos necessários
require_once $config_path;

// Definir a página atual para o menu
$currentPage = 'ganhadores';
$pageTitle = 'Ganhadores das Loterias';

function formatarValor($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Inicializar variáveis de filtro
$filtro_jogo = isset($_GET['jogo']) ? $_GET['jogo'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Buscar lista de jogos para o filtro
try {
    $sql_jogos = "SELECT DISTINCT nome FROM jogos WHERE status = 1 ORDER BY nome";
    $stmt_jogos = $pdo->query($sql_jogos);
    $jogos_lista = $stmt_jogos->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $jogos_lista = [];
}

// Construir a consulta SQL base
$sql = "
    SELECT 
        ai.id,
        ai.jogo_nome,
        ai.numeros,
        ai.valor_premio,
        ai.quantidade_dezenas,
        ai.created_at,
        u.nome as nome_usuario,
        u.whatsapp
    FROM apostas_importadas ai
    JOIN usuarios u ON ai.usuario_id = u.id
    WHERE ai.valor_premio > 0
";

// Adicionar condições de filtro
$params = [];
if (!empty($filtro_jogo)) {
    $sql .= " AND ai.jogo_nome = :jogo";
    $params[':jogo'] = $filtro_jogo;
}
if (!empty($data_inicio)) {
    $sql .= " AND DATE(ai.created_at) >= :data_inicio";
    $params[':data_inicio'] = $data_inicio;
}
if (!empty($data_fim)) {
    $sql .= " AND DATE(ai.created_at) <= :data_fim";
    $params[':data_fim'] = $data_fim;
}

$sql .= " ORDER BY ai.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ganhadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensagem = "Erro ao carregar ganhadores: " . $e->getMessage();
    $tipo_mensagem = "danger";
    $ganhadores = [];
}

// Calcular totais
$total_premios = 0;
$total_ganhadores = count($ganhadores);
foreach ($ganhadores as $ganhador) {
    $total_premios += $ganhador['valor_premio'];
}

// Início do buffer de saída
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Header com gradiente -->
    <div class="header-card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-white mb-0"><i class="fas fa-trophy"></i> Ganhadores</h1>
                <p class="text-white-50 mb-0">Lista de Ganhadores das Loterias</p>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-card mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="jogo" class="form-label">Jogo</label>
                <select name="jogo" id="jogo" class="form-select">
                    <option value="">Todos os jogos</option>
                    <?php foreach ($jogos_lista as $jogo): ?>
                        <option value="<?php echo htmlspecialchars($jogo); ?>" 
                                <?php echo $filtro_jogo === $jogo ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($jogo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="data_inicio" class="form-label">Data Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                       value="<?php echo htmlspecialchars($data_inicio); ?>">
            </div>
            <div class="col-md-3">
                <label for="data_fim" class="form-label">Data Fim</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" 
                       value="<?php echo htmlspecialchars($data_fim); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-2"></i>Filtrar
                </button>
                <a href="ganhadores.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="summary-card total-winners">
                <div class="summary-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="summary-info">
                    <h3><?php echo $total_ganhadores; ?></h3>
                    <p>Total de Ganhadores</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="summary-card total-prizes">
                <div class="summary-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="summary-info">
                    <h3><?php echo formatarValor($total_premios); ?></h3>
                    <p>Total em Prêmios</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Ganhadores -->
    <div class="winners-list">
        <?php if (empty($ganhadores)): ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Nenhum ganhador encontrado com os filtros selecionados.
            </div>
        <?php else: ?>
            <?php foreach ($ganhadores as $ganhador): ?>
                <div class="winner-card">
                    <div class="winner-header">
                        <div class="game-info">
                            <h4><?php echo htmlspecialchars($ganhador['jogo_nome']); ?></h4>
                            <span class="date">
                                <?php echo date('d/m/Y H:i', strtotime($ganhador['created_at'])); ?>
                            </span>
                        </div>
                        <div class="prize-amount">
                            <?php echo formatarValor($ganhador['valor_premio']); ?>
                        </div>
                    </div>
                    <div class="winner-body">
                        <div class="winner-info">
                            <div class="info-item">
                                <i class="fas fa-user me-2"></i>
                                <span><?php echo htmlspecialchars($ganhador['nome_usuario']); ?></span>
                            </div>
                            <?php if (!empty($ganhador['whatsapp'])): ?>
                                <div class="info-item">
                                    <i class="fab fa-whatsapp me-2"></i>
                                    <span><?php echo htmlspecialchars($ganhador['whatsapp']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="numbers-info">
                            <div class="info-item">
                                <i class="fas fa-dice me-2"></i>
                                <span><?php echo $ganhador['quantidade_dezenas']; ?> dezenas</span>
                            </div>
                            <div class="numbers">
                                <?php 
                                $numeros = explode(',', $ganhador['numeros']);
                                foreach ($numeros as $numero): 
                                ?>
                                    <span class="number"><?php echo str_pad($numero, 2, '0', STR_PAD_LEFT); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Estilos para a página de ganhadores */
.header-card {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.filter-card {
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.summary-card {
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
}

.summary-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.total-winners .summary-icon {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
}

.total-prizes .summary-icon {
    background: linear-gradient(135deg, #2196F3, #1976D2);
    color: white;
}

.summary-info h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.summary-info p {
    margin: 0;
    color: #6c757d;
}

.winners-list {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.winner-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.winner-header {
    padding: 20px;
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.game-info h4 {
    margin: 0;
    font-size: 1.2rem;
}

.date {
    font-size: 0.9rem;
    opacity: 0.9;
}

.prize-amount {
    font-size: 1.3rem;
    font-weight: 600;
}

.winner-body {
    padding: 20px;
}

.winner-info {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.info-item {
    display: flex;
    align-items: center;
    color: #495057;
}

.numbers-info {
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.numbers {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.number {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    color: #495057;
}

@media (max-width: 768px) {
    .winner-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .winner-info {
        flex-direction: column;
        gap: 10px;
    }

    .numbers {
        gap: 6px;
    }

    .number {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
}
</style>

<?php
// Capturar o conteúdo do buffer
$content = ob_get_clean();

// Incluir o layout
require_once $layout_path;
?> 