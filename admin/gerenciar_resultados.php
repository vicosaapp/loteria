<?php
require_once '../config/database.php';
require_once 'includes/header.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'resultados';

// URLs das APIs da Caixa
$api_urls = [
    'quina' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/quina/',
    'megasena' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/megasena/',
    'lotofacil' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil/',
    'lotomania' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/lotomania/',
    'timemania' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/timemania/',
    'duplasena' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/duplasena/',
    'maismilionaria' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/maismilionaria/',
    'diadesorte' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/diadesorte/'
];

function buscarResultado($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Erro ao buscar resultados: " . $error);
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao decodificar resposta da API: " . json_last_error_msg());
    }
    
    return $data;
}

function salvarResultado($pdo, $jogo_id, $numero_concurso, $data_sorteio, $dezenas, $resultado = []) {
    try {
        $pdo->beginTransaction();
        
        // Formata a data corretamente
        $data_sorteio = date('Y-m-d', strtotime(str_replace('/', '-', $data_sorteio)));
        
        // Verifica se o concurso já existe
        $stmt = $pdo->prepare("SELECT id FROM concursos WHERE jogo_id = ? AND codigo = ?");
        $stmt->execute([$jogo_id, $numero_concurso]);
        $concurso_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($concurso_existente) {
            // Atualiza concurso existente
            $stmt = $pdo->prepare("UPDATE concursos SET data_sorteio = ?, status = 'finalizado' WHERE id = ?");
            $stmt->execute([$data_sorteio, $concurso_existente['id']]);
            $concurso_id = $concurso_existente['id'];
            
            // Remove números antigos
            $stmt = $pdo->prepare("DELETE FROM numeros_sorteados WHERE concurso_id = ?");
            $stmt->execute([$concurso_id]);
        } else {
            // Insere novo concurso
            $stmt = $pdo->prepare("INSERT INTO concursos (jogo_id, codigo, data_sorteio, status) VALUES (?, ?, ?, 'finalizado')");
            $stmt->execute([$jogo_id, $numero_concurso, $data_sorteio]);
            $concurso_id = $pdo->lastInsertId();
        }
        
        // Insere números sorteados
        if (!empty($dezenas)) {
            $stmt = $pdo->prepare("INSERT INTO numeros_sorteados (concurso_id, numero) VALUES (?, ?)");
            foreach ($dezenas as $dezena) {
                $stmt->execute([$concurso_id, intval($dezena)]);
            }
        }
        
        // Atualiza informações adicionais do jogo
        $stmt = $pdo->prepare("UPDATE jogos SET 
            valor_acumulado = ?,
            data_proximo_concurso = ?,
            valor_estimado_proximo = ?,
            numero_concurso = ?
            WHERE id = ?");
        
        $data_proximo = null;
        if (!empty($resultado['dataProximoConcurso'])) {
            $data_proximo = date('Y-m-d', strtotime(str_replace('/', '-', $resultado['dataProximoConcurso'])));
        }
        
        $stmt->execute([
            floatval($resultado['valorAcumulado'] ?? 0),
            $data_proximo,
            floatval($resultado['valorEstimadoProximoConcurso'] ?? 0),
            $numero_concurso,
            $jogo_id
        ]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao salvar resultado: " . $e->getMessage());
        throw $e;
    }
}

function formatarValor($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$mensagem = '';
$tipo_mensagem = '';
$resultados = [];
$resultados_banco = [];

try {
    // Se foi solicitada atualização manual
    if (isset($_POST['atualizar'])) {
        foreach ($api_urls as $jogo => $url) {
            try {
                $resultado = buscarResultado($url);
                
                if ($resultado) {
                    $resultados[$jogo] = $resultado;
                    
                    // Salvar no banco de dados
                    $stmt = $pdo->prepare("SELECT id FROM jogos WHERE LOWER(identificador_api) = LOWER(?)");
                    $stmt->execute([$jogo]);
                    $jogo_db = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($jogo_db) {
                        // Prepara as dezenas na ordem correta
                        $dezenas = isset($resultado['listaDezenas']) ? $resultado['listaDezenas'] : [];
                        
                        // Prepara os dados do resultado
                        $dados_resultado = [
                            'valorAcumulado' => $resultado['valorAcumuladoProximoConcurso'] ?? 0,
                            'dataProximoConcurso' => $resultado['dataProximoConcurso'] ?? null,
                            'valorEstimadoProximoConcurso' => $resultado['valorEstimadoProximoConcurso'] ?? 0
                        ];
                        
                        // Se for Dupla Sena, inclui as dezenas do segundo sorteio
                        if ($jogo === 'duplasena' && isset($resultado['listaDezenasSegundoSorteio'])) {
                            $dezenas = array_merge($dezenas, $resultado['listaDezenasSegundoSorteio']);
                        }
                        
                        salvarResultado(
                            $pdo,
                            $jogo_db['id'],
                            $resultado['numero'],
                            $resultado['dataApuracao'],
                            $dezenas,
                            $dados_resultado
                        );
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao buscar resultado do $jogo: " . $e->getMessage());
            }
        }
        $mensagem = "Resultados atualizados com sucesso!";
        $tipo_mensagem = "success";
    }
    
    // Buscar resultados do banco
    $sql = "
        SELECT 
            j.id,
            j.nome,
            j.identificador_api,
            j.numero_concurso,
            c.codigo as numero_concurso_atual,
            c.data_sorteio,
            GROUP_CONCAT(DISTINCT ns.numero ORDER BY ns.numero ASC) as dezenas,
            j.valor_acumulado,
            j.data_proximo_concurso,
            j.valor_estimado_proximo,
            c.id as concurso_id,
            (
                SELECT COUNT(*)
                FROM apostas_importadas ai
                WHERE ai.jogo_nome = j.nome
                AND ai.numeros = GROUP_CONCAT(DISTINCT ns.numero ORDER BY ns.numero ASC)
            ) as total_ganhadores,
            (
                SELECT GROUP_CONCAT(DISTINCT CONCAT(u.nome, ':', ai.valor_premio) SEPARATOR '|')
                FROM apostas_importadas ai
                JOIN usuarios u ON ai.revendedor_id = u.id
                WHERE ai.jogo_nome = j.nome
                AND ai.numeros = GROUP_CONCAT(DISTINCT ns.numero ORDER BY ns.numero ASC)
            ) as ganhadores_info
        FROM jogos j
        LEFT JOIN (
            SELECT c1.*
            FROM concursos c1
            INNER JOIN (
                SELECT jogo_id, MAX(data_sorteio) as ultima_data
                FROM concursos
                WHERE status = 'finalizado'
                GROUP BY jogo_id
            ) c2 ON c1.jogo_id = c2.jogo_id AND c1.data_sorteio = c2.ultima_data
        ) c ON j.id = c.jogo_id
        LEFT JOIN numeros_sorteados ns ON ns.concurso_id = c.id
        WHERE j.status = 1 AND j.identificador_api IS NOT NULL
        GROUP BY 
            j.id, j.nome, j.identificador_api, j.numero_concurso,
            c.codigo, c.data_sorteio, j.valor_acumulado,
            j.data_proximo_concurso, j.valor_estimado_proximo,
            c.id
        ORDER BY j.nome ASC
    ";
    
    $stmt = $pdo->query($sql);
    if ($stmt) {
        $resultados_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $mensagem = "Erro: " . $e->getMessage();
    $tipo_mensagem = "danger";
}
?>

<div class="container-fluid py-4">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-trophy"></i> Resultados Oficiais</h1>
            <p>Resultados oficiais das Loterias Caixa</p>
        </div>
        <form method="post" class="d-inline">
            <button type="submit" name="atualizar" class="btn-update">
                <i class="fas fa-sync-alt"></i>
                <span>Atualizar Resultados</span>
            </button>
        </form>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Indicador de Pull to Refresh -->
    <div class="refresh-indicator">
        <div class="refresh-spinner"></div>
        <span>Atualizando...</span>
    </div>

    <div class="results-grid">
        <?php if (empty($resultados_banco)): ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Nenhum resultado encontrado. Clique em "Atualizar Resultados" para buscar os últimos resultados.
            </div>
        <?php else: ?>
            <?php foreach ($resultados_banco as $resultado): ?>
                <div class="result-card <?php echo htmlspecialchars($resultado['identificador_api'] ?? ''); ?>">
                    <div class="card-header">
                        <div class="header-content">
                            <h3><?php echo htmlspecialchars($resultado['nome'] ?? ''); ?></h3>
                            <?php if (!empty($resultado['numero_concurso'])): ?>
                                <span class="concurso">Concurso <?php echo htmlspecialchars($resultado['numero_concurso']); ?></span>
                                <span class="data"><?php echo !empty($resultado['data_sorteio']) ? date('d/m/Y', strtotime($resultado['data_sorteio'])) : ''; ?></span>
                            <?php else: ?>
                                <span class="concurso">Aguardando resultados</span>
                            <?php endif; ?>
                        </div>
                        <div class="logo-container">
                            <?php if (!empty($resultado['identificador_api'])): ?>
                                <img src="../assets/images/logos/<?php echo htmlspecialchars($resultado['identificador_api']); ?>.png" 
                                     alt="<?php echo htmlspecialchars($resultado['nome']); ?>"
                                     class="jogo-logo"
                                     onerror="this.src='../assets/images/logos/default.png'">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($resultado['dezenas'])): ?>
                            <div class="numbers-grid">
                                <?php 
                                $dezenas = explode(',', $resultado['dezenas']);
                                foreach ($dezenas as $dezena): 
                                ?>
                                    <div class="number"><?php echo str_pad($dezena, 2, '0', STR_PAD_LEFT); ?></div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($resultado['valor_acumulado'])): ?>
                                <div class="premio-info">
                                    <div class="acumulado">
                                        <span class="label">Acumulado para próximo concurso</span>
                                        <span class="value"><?php echo formatarValor($resultado['valor_acumulado']); ?></span>
                                    </div>
                                </div>

                                <?php if (isset($resultado['total_ganhadores']) && $resultado['total_ganhadores'] > 0): ?>
                                    <div class="ganhadores-info">
                                        <h4>
                                            <i class="fas fa-trophy me-2"></i>
                                            Ganhadores deste Concurso
                                        </h4>
                                        <div class="ganhadores-lista">
                                            <?php
                                            $ganhadores = explode('|', $resultado['ganhadores_info']);
                                            foreach ($ganhadores as $ganhador) {
                                                list($nome, $premio) = explode(':', $ganhador);
                                            ?>
                                                <div class="ganhador-item">
                                                    <div class="ganhador-nome">
                                                        <i class="fas fa-user me-2"></i>
                                                        <?php echo htmlspecialchars($nome); ?>
                                                    </div>
                                                    <div class="ganhador-premio">
                                                        <?php echo formatarValor($premio); ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="total-ganhadores">
                                            Total de Ganhadores: <?php echo $resultado['total_ganhadores']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($resultado['data_proximo_concurso'])): ?>
                                <div class="proximo-sorteio">
                                    <h4>Próximo Sorteio</h4>
                                    <div class="info">
                                        <div class="data">
                                            <i class="far fa-calendar-alt"></i>
                                            <span><?php echo date('d/m/Y', strtotime($resultado['data_proximo_concurso'])); ?></span>
                                        </div>
                                        <?php if (!empty($resultado['valor_estimado_proximo'])): ?>
                                            <div class="estimativa">
                                                <span class="label">Prêmio Estimado:</span>
                                                <span class="value"><?php echo formatarValor($resultado['valor_estimado_proximo']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhum resultado disponível para este jogo.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Navegação móvel -->
    <div class="mobile-nav">
        <a href="../revendedor/index.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Início</span>
        </a>
        <a href="../revendedor/apostas.php" class="mobile-nav-item">
            <i class="fas fa-ticket-alt"></i>
            <span>Apostas</span>
        </a>
        <a href="../admin/gerenciar_resultados.php" class="mobile-nav-item active">
            <i class="fas fa-trophy"></i>
            <span>Resultados</span>
        </a>
        <a href="../admin/relatorios.php" class="mobile-nav-item">
            <i class="fas fa-chart-bar"></i>
            <span>Relatórios</span>
        </a>
        <a href="../perfil.php" class="mobile-nav-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
    </div>
</div>

<style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #2ecc71;
    --warning-color: #f1c40f;
    --danger-color: #e74c3c;
    --light-color: #ecf0f1;
    --dark-color: #2c3e50;
    --white-color: #ffffff;
}

/* Base styles */
* {
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f5f7fa;
    margin: 0;
    padding-bottom: 60px; /* Espaço para menu móvel */
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 25px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 15px;
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.page-header p {
    margin: 5px 0 0;
    opacity: 0.9;
}

.btn-update {
    background: var(--success-color);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-update:hover {
    background: #27ae60;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(46,204,113,0.3);
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
    padding: 10px;
}

.result-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Cores específicas para cada jogo */
.result-card.megasena .card-header { background: linear-gradient(135deg, #209869, #1a7d55); }
.result-card.lotofacil .card-header { background: linear-gradient(135deg, #930089, #6d0066); }
.result-card.quina .card-header { background: linear-gradient(135deg, #260085, #1c0061); }
.result-card.lotomania .card-header { background: linear-gradient(135deg, #F78100, #c66800); }
.result-card.timemania .card-header { background: linear-gradient(135deg, #00ff48, #00cc3a); }
.result-card.duplasena .card-header { background: linear-gradient(135deg, #A61324, #8a0f1e); }
.result-card.maismilionaria .card-header { background: linear-gradient(135deg, #930089, #6d0066); }
.result-card.diadesorte .card-header { background: linear-gradient(135deg, #CB8E37, #a87429); }

.card-header {
    padding: 20px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-content {
    flex-grow: 1;
}

.card-header h3 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 700;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.concurso {
    font-size: 0.95rem;
    opacity: 0.9;
    display: block;
    margin-top: 5px;
}

.data {
    font-size: 0.9rem;
    opacity: 0.8;
}

.logo-container {
    width: 60px;
    height: 60px;
    margin-left: 15px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    padding: 5px;
}

.jogo-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.card-body {
    padding: 25px;
}

.numbers-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin-bottom: 25px;
}

.number {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    color: white;
    background: var(--primary-color);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.number:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

/* Cores dos números para cada jogo */
.megasena .number { background: #209869; }
.lotofacil .number { background: #930089; }
.quina .number { background: #260085; }
.lotomania .number { background: #F78100; }
.timemania .number { background: #00FF48; color: #000; }
.duplasena .number { background: #A61324; }
.maismilionaria .number { background: #930089; }
.diadesorte .number { background: #CB8E37; }

.premio-info {
    margin-top: 25px;
    padding: 20px;
    background: var(--light-color);
    border-radius: 12px;
}

.acumulado {
    text-align: center;
}

.acumulado .label {
    display: block;
    font-size: 0.95rem;
    color: var(--dark-color);
    margin-bottom: 8px;
}

.acumulado .value {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--success-color);
}

.proximo-sorteio {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid var(--light-color);
}

.proximo-sorteio h4 {
    font-size: 1.1rem;
    color: var(--dark-color);
    margin-bottom: 15px;
}

.proximo-sorteio .info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.proximo-sorteio .data {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    color: var(--dark-color);
}

.estimativa {
    text-align: right;
}

.estimativa .label {
    display: block;
    font-size: 0.9rem;
    color: var(--dark-color);
}

.estimativa .value {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--success-color);
}

.alert {
    border: none;
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.alert-success {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
}

.alert-danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.alert-info {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.ganhadores-info {
    margin-top: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.ganhadores-info h4 {
    color: var(--dark-color);
    font-size: 1.1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.ganhadores-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ganhador-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.2s ease;
}

.ganhador-item:hover {
    transform: translateX(5px);
}

.ganhador-nome {
    font-size: 0.95rem;
    color: var(--dark-color);
    display: flex;
    align-items: center;
}

.ganhador-premio {
    font-weight: bold;
    color: var(--success-color);
}

.total-ganhadores {
    margin-top: 15px;
    text-align: center;
    font-weight: bold;
    color: var(--primary-color);
    padding: 10px;
    background: rgba(255,255,255,0.5);
    border-radius: 8px;
}

/* Menu de navegação móvel */
.mobile-nav {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--white-color);
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
    height: 60px;
    padding: 5px;
}

.mobile-nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    text-decoration: none;
    font-size: 0.8rem;
    padding: 5px;
    transition: all 0.3s ease;
}

.mobile-nav-item i {
    font-size: 1.3rem;
    margin-bottom: 4px;
}

.mobile-nav-item.active {
    color: var(--primary-color);
    font-weight: bold;
}

.mobile-nav-item:active {
    transform: scale(0.9);
}

/* Pull to refresh indicator */
.refresh-indicator {
    display: none;
    position: fixed;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255,255,255,0.9);
    padding: 8px 15px;
    border-radius: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-size: 0.8rem;
    color: var(--dark-color);
    z-index: 1100;
}

.refresh-indicator.visible {
    display: flex;
    align-items: center;
    gap: 8px;
}

.refresh-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid var(--primary-color);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Media Queries para responsividade */
@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
        padding: 15px;
    }
    
    .btn-update {
        width: 100%;
        justify-content: center;
    }
    
    .proximo-sorteio .info {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .estimativa {
        text-align: left;
        width: 100%;
    }
    
    .number {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .numbers-grid {
        gap: 8px;
    }
    
    .card-header {
        padding: 15px;
    }
    
    .card-body {
        padding: 15px;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .ganhador-nome {
        font-size: 0.85rem;
        max-width: 170px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .ganhador-premio {
        font-size: 0.85rem;
    }
}
</style>

<script>
// Atualização automática a cada 1 minuto
setInterval(function() {
    document.querySelector('button[name="atualizar"]').click();
}, 60000);

// Animação dos números
document.querySelectorAll('.number').forEach(number => {
    number.addEventListener('mouseover', function() {
        this.style.transform = 'scale(1.1)';
    });
    
    number.addEventListener('mouseout', function() {
        this.style.transform = 'scale(1)';
    });
});

// Efeito de loading ao atualizar
document.querySelector('form').addEventListener('submit', function() {
    const button = this.querySelector('button[name="atualizar"]');
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';
    button.disabled = true;
});

// Pull to refresh (arraste para baixo para atualizar)
let touchstartY = 0;
let touchendY = 0;
const refreshThreshold = 150; // pixels
let isPulling = false;
const refreshIndicator = document.querySelector('.refresh-indicator');

document.addEventListener('touchstart', function(e) {
    touchstartY = e.touches[0].clientY;
}, { passive: true });

document.addEventListener('touchmove', function(e) {
    if (window.scrollY === 0) {
        touchendY = e.touches[0].clientY;
        const distance = touchendY - touchstartY;
        
        if (distance > 30 && !isPulling) {
            refreshIndicator.classList.add('visible');
            isPulling = true;
        }
        
        if (distance > refreshThreshold && isPulling) {
            refreshIndicator.innerHTML = '<div class="refresh-spinner"></div><span>Solte para atualizar</span>';
        }
    }
}, { passive: true });

document.addEventListener('touchend', function() {
    if (isPulling && (touchendY - touchstartY) > refreshThreshold) {
        // Trigger refresh
        document.querySelector('button[name="atualizar"]').click();
    }
    
    isPulling = false;
    refreshIndicator.classList.remove('visible');
    touchstartY = 0;
    touchendY = 0;
}, { passive: true });

// Adicionar Font Awesome se não estiver incluído
if (!document.querySelector('link[href*="font-awesome"]')) {
    const fontAwesome = document.createElement('link');
    fontAwesome.rel = 'stylesheet';
    fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
    document.head.appendChild(fontAwesome);
}

// Adicionar meta viewport se não estiver incluído
if (!document.querySelector('meta[name="viewport"]')) {
    const viewport = document.createElement('meta');
    viewport.name = 'viewport';
    viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
    document.head.appendChild(viewport);
}

// Adicionar meta theme-color
if (!document.querySelector('meta[name="theme-color"]')) {
    const themeColor = document.createElement('meta');
    themeColor.name = 'theme-color';
    themeColor.content = '#2c3e50';
    document.head.appendChild(themeColor);
}
</script>

<?php require_once 'includes/layout.php'; ?> 