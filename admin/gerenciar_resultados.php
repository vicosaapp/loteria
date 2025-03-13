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
        echo "<!-- Iniciando atualização dos resultados -->";
        foreach ($api_urls as $jogo => $url) {
            try {
                echo "<!-- Buscando resultado para $jogo na URL: $url -->";
                $resultado = buscarResultado($url);
                
                if ($resultado) {
                    $resultados[$jogo] = $resultado;
                    
                    // Salvar no banco de dados
                    $stmt = $pdo->prepare("SELECT id FROM jogos WHERE LOWER(identificador_api) = LOWER(?)");
                    $stmt->execute([$jogo]);
                    $jogo_db = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($jogo_db) {
                        echo "<!-- Salvando resultado para jogo ID: " . $jogo_db['id'] . " -->";
                        
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
                    } else {
                        echo "<!-- Jogo não encontrado no banco: $jogo -->";
                    }
                } else {
                    echo "<!-- Sem dados no resultado para $jogo -->";
                }
            } catch (Exception $e) {
                echo "<!-- Erro ao buscar $jogo: " . $e->getMessage() . " -->";
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
            c.id as concurso_id
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
    
    echo "<!-- Executando query: " . str_replace(['-->', '<!--'], '', $sql) . " -->";
    
    $stmt = $pdo->query($sql);
    if ($stmt) {
        $resultados_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<!-- Número de resultados encontrados: " . count($resultados_banco) . " -->";
        foreach ($resultados_banco as $resultado) {
            echo "<!-- 
                Jogo: " . $resultado['nome'] . "
                API: " . $resultado['identificador_api'] . "
                Concurso: " . ($resultado['numero_concurso'] ?? 'N/A') . "
                Dezenas: " . ($resultado['dezenas'] ?? 'N/A') . "
            -->";
        }
    } else {
        echo "<!-- Erro ao executar a query -->";
        var_dump($pdo->errorInfo());
    }
    
} catch (Exception $e) {
    echo "<!-- Erro geral: " . $e->getMessage() . " -->";
    $mensagem = "Erro: " . $e->getMessage();
    $tipo_mensagem = "danger";
}
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-trophy"></i> Resultados dos Jogos</h1>
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
        <div class="alert alert-<?php echo $tipo_mensagem; ?>" role="alert">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <div class="results-grid">
        <?php if (empty($resultados_banco)): ?>
            <div class="alert alert-info" role="alert">
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
                                Nenhum resultado disponível para este jogo.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-update {
    background: #00ff7f;
    color: #000;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-update:hover {
    background: #00ff95;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,255,127,0.4);
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    padding: 20px;
}

.result-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Cores específicas para cada jogo */
.result-card.megasena .card-header { background: #209869; }
.result-card.lotofacil .card-header { background: #930089; }
.result-card.quina .card-header { background: #260085; }
.result-card.lotomania .card-header { background: #F78100; }
.result-card.timemania .card-header { background: #00FF48; }
.result-card.duplasena .card-header { background: #A61324; }
.result-card.maismilionaria .card-header { background: #930089; }
.result-card.diadesorte .card-header { background: #CB8E37; }

.card-header {
    padding: 15px 20px;
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
}

.concurso {
    font-size: 0.9rem;
    opacity: 0.9;
    display: block;
    margin-top: 5px;
}

.data {
    font-size: 0.85rem;
    opacity: 0.8;
}

.logo-container {
    width: 60px;
    height: 60px;
    margin-left: 15px;
}

.jogo-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.card-body {
    padding: 20px;
}

.numbers-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-bottom: 20px;
}

.number {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    color: white;
    background: #4e73df;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
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
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fc;
    border-radius: 8px;
}

.acumulado {
    text-align: center;
}

.acumulado .label {
    display: block;
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 5px;
}

.acumulado .value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #209869;
}

.proximo-sorteio {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e3e6f0;
}

.proximo-sorteio h4 {
    font-size: 1rem;
    color: #666;
    margin-bottom: 10px;
}

.proximo-sorteio .info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.proximo-sorteio .data {
    display: flex;
    align-items: center;
    gap: 5px;
}

.estimativa {
    text-align: right;
}

.estimativa .label {
    display: block;
    font-size: 0.8rem;
    color: #666;
}

.estimativa .value {
    font-size: 1.1rem;
    font-weight: bold;
    color: #209869;
}

@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        gap: 15px;
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
}
</style>

<script>
// Atualização automática a cada 30 minutos
setInterval(function() {
    document.querySelector('button[name="atualizar"]').click();
}, 1800000); // 30 minutos em milissegundos

// Animação dos números
document.querySelectorAll('.number').forEach(number => {
    number.addEventListener('mouseover', function() {
        this.style.transform = 'scale(1.1)';
    });
    
    number.addEventListener('mouseout', function() {
        this.style.transform = 'scale(1)';
    });
});
</script>

<?php require_once 'includes/layout.php'; ?> 