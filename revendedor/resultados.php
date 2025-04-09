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
$currentPage = 'resultados';
$pageTitle = 'Resultados das Loterias';

// Função PHP para formatar valor monetário
function formatarValor($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$mensagem = '';
$tipo_mensagem = '';
$resultados_banco = [];

try {
    // Verificar primeiro se a coluna 'concurso' existe nas tabelas
    $verificar_coluna_aposta = $pdo->prepare("SHOW COLUMNS FROM apostas LIKE 'concurso'");
    $verificar_coluna_aposta->execute();
    $coluna_concurso_existe_apostas = $verificar_coluna_aposta->rowCount() > 0;
    
    $verificar_coluna_importada = $pdo->prepare("SHOW COLUMNS FROM apostas_importadas LIKE 'concurso'");
    $verificar_coluna_importada->execute();
    $coluna_concurso_existe_importadas = $verificar_coluna_importada->rowCount() > 0;
    
    // Montar a consulta SQL com base nas colunas existentes
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
                SELECT COUNT(DISTINCT ai.id)
                FROM apostas_importadas ai
                WHERE ai.jogo_nome LIKE CONCAT('%', j.nome, '%')
                " . ($coluna_concurso_existe_importadas ? "AND ai.concurso = c.codigo" : "") . "
                AND ai.valor_premio > 0
            ) + " . ($coluna_concurso_existe_apostas ? "
            (
                SELECT COUNT(DISTINCT a.id)
                FROM apostas a
                WHERE a.tipo_jogo_id = j.id
                AND a.concurso = c.codigo
                AND a.valor_premio > 0
            )" : "0") . " as total_ganhadores,
            (
                SELECT GROUP_CONCAT(DISTINCT CONCAT(u.nome, ':', ai.valor_premio, ':importada', ':', ai.id) SEPARATOR '|')
                FROM apostas_importadas ai
                JOIN usuarios u ON ai.usuario_id = u.id
                WHERE ai.jogo_nome LIKE CONCAT('%', j.nome, '%')
                " . ($coluna_concurso_existe_importadas ? "AND ai.concurso = c.codigo" : "") . "
                AND ai.valor_premio > 0
            ) as ganhadores_importados" . ($coluna_concurso_existe_apostas ? ",
            (
                SELECT GROUP_CONCAT(DISTINCT CONCAT(u.nome, ':', a.valor_premio, ':regular', ':', a.id) SEPARATOR '|')
                FROM apostas a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.tipo_jogo_id = j.id
                AND a.concurso = c.codigo
                AND a.valor_premio > 0
            ) as ganhadores_regulares" : "")
        . ",
            (
                SELECT COUNT(*) 
                FROM apostas a
                WHERE a.tipo_jogo_id = j.id
                AND a.status = 'aprovada'
                AND EXISTS (
                    SELECT 1 
                    FROM numeros_sorteados ns2 
                    WHERE ns2.concurso_id = c.id 
                    AND FIND_IN_SET(ns2.numero, a.numeros)
                    HAVING COUNT(*) >= 5
                )
            ) as apostas_correspondentes,
            (
                SELECT GROUP_CONCAT(DISTINCT CONCAT(u.nome, ':', COALESCE(a.valor_premio, '0.00'), ':manual', ':', a.id) SEPARATOR '|')
                FROM apostas a
                JOIN usuarios u ON a.usuario_id = u.id
                WHERE a.tipo_jogo_id = j.id
                AND a.status = 'aprovada'
                AND EXISTS (
                    SELECT 1 
                    FROM numeros_sorteados ns2 
                    WHERE ns2.concurso_id = c.id 
                    AND FIND_IN_SET(ns2.numero, a.numeros)
                    HAVING COUNT(*) >= 5
                )
            ) as ganhadores_manuais
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
    } else {
        error_log("Erro na execução da consulta: " . $pdo->errorInfo()[2]);
        throw new Exception("Erro na execução da consulta: " . $pdo->errorInfo()[2]);
    }
    
    // Verificar estrutura da tabela jogos
    $debug_sql = "DESCRIBE jogos";
    $debug_stmt = $pdo->query($debug_sql);
    $debug_colunas_jogos = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    $colunas_encontradas = [];
    
    foreach ($debug_colunas_jogos as $coluna) {
        $colunas_encontradas[] = $coluna['Field'] . ' (' . $coluna['Type'] . ')';
    }
    
    $mensagem_debug = "Colunas na tabela jogos: " . implode(", ", $colunas_encontradas);
    error_log($mensagem_debug);
    
    // Verificar estrutura da tabela valores_jogos
    $debug_sql = "DESCRIBE valores_jogos";
    $debug_stmt = $pdo->query($debug_sql);
    if ($debug_stmt) {
        $debug_colunas_vj = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
        $colunas_vj_encontradas = [];
        
        foreach ($debug_colunas_vj as $coluna) {
            $colunas_vj_encontradas[] = $coluna['Field'] . ' (' . $coluna['Type'] . ')';
        }
        
        $mensagem_debug = "Colunas na tabela valores_jogos: " . implode(", ", $colunas_vj_encontradas);
        error_log($mensagem_debug);
    } else {
        error_log("A tabela valores_jogos não existe ou não está acessível.");
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar resultados: " . $e->getMessage();
    $tipo_mensagem = "danger";
    error_log("Erro ao verificar estrutura da tabela: " . $e->getMessage());
}

// Início do buffer de saída
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Header com gradiente -->
    <div class="header-card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-white mb-0"><i class="fas fa-trophy"></i> Resultados Oficiais</h1>
                <p class="text-white-50 mb-0">Resultados das Loterias para Revendedores</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light" id="btnProcessarGanhadores">
                    <i class="fas fa-users me-2"></i>Processar Ganhadores
                </button>
            <button type="button" class="btn btn-light" id="btnAtualizarResultados">
                <i class="fas fa-sync-alt me-2"></i>Atualizar Resultados
            </button>
                <a href="ajax/corrigir_estrutura_tabelas.php" target="_blank" class="btn btn-secondary ms-2">
                    <i class="fas fa-database me-2"></i>Corrigir DB
                </a>
            </div>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="results-grid">
        <?php if (empty($resultados_banco)): ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Nenhum resultado disponível no momento.
            </div>
        <?php else: ?>
            <?php foreach ($resultados_banco as $resultado): ?>
                <div class="lottery-card <?php echo htmlspecialchars(strtolower($resultado['identificador_api'] ?? '')); ?>">
                    <div class="lottery-header">
                        <div class="lottery-info">
                            <h3><?php echo htmlspecialchars($resultado['nome'] ?? ''); ?></h3>
                            <p class="mb-0">
                                Concurso <?php echo htmlspecialchars($resultado['numero_concurso'] ?? ''); ?>
                                <?php if (!empty($resultado['data_sorteio'])): ?>
                                    <span class="mx-2">•</span> <?php echo date('d/m/Y', strtotime($resultado['data_sorteio'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if (!empty($resultado['identificador_api'])): ?>
                            <img src="../assets/images/logos/<?php echo htmlspecialchars($resultado['identificador_api']); ?>.png" 
                                 alt="<?php echo htmlspecialchars($resultado['nome'] ?? ''); ?>"
                                 class="lottery-logo"
                                 onerror="this.src='../assets/images/logos/default.png'">
                        <?php endif; ?>
                    </div>
                    
                    <div class="lottery-body">
                        <?php if (!empty($resultado['dezenas'])): ?>
                            <div class="numbers-grid">
                                <?php 
                                $dezenas = explode(',', $resultado['dezenas']);
                                foreach ($dezenas as $dezena): 
                                ?>
                                    <div class="number"><?php echo str_pad($dezena, 2, '0', STR_PAD_LEFT); ?></div>
                                <?php endforeach; ?>
                            </div>

                            <?php
                            // Debug temporário
                            echo "<!-- Debug: Total Ganhadores: " . ($resultado['total_ganhadores'] ?? 0) . " -->";
                            echo "<!-- Debug: Info Ganhadores: " . htmlspecialchars($resultado['ganhadores_importados'] ?? '') . " -->";
                            echo "<!-- Debug: Apostas Correspondentes: " . ($resultado['apostas_correspondentes'] ?? 0) . " -->";
                            
                            if ((!empty($resultado['total_ganhadores']) && (!empty($resultado['ganhadores_importados']) || (isset($resultado['ganhadores_regulares']) && !empty($resultado['ganhadores_regulares'])))) 
                                || !empty($resultado['apostas_correspondentes'])): ?>
                                <div class="winners-button-container">
                                    <button type="button" class="btn btn-success btn-lg w-100" data-bs-toggle="modal" data-bs-target="#winnersModal<?php echo $resultado['id']; ?>">
                                        <i class="fas fa-trophy me-2"></i>
                                        Ver <?php echo ($resultado['total_ganhadores'] ?? 0) + ($resultado['apostas_correspondentes'] ?? 0); ?> Ganhador<?php echo (($resultado['total_ganhadores'] ?? 0) + ($resultado['apostas_correspondentes'] ?? 0)) > 1 ? 'es' : ''; ?>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($resultado['data_proximo_concurso'])): ?>
                                <div class="next-draw">
                                    <div class="next-draw-header">Próximo Sorteio</div>
                                    <div class="next-draw-info">
                                        <div class="date">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo date('d/m/Y', strtotime($resultado['data_proximo_concurso'])); ?>
                                        </div>

                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhum resultado disponível para este jogo.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($resultados_banco as $resultado): ?>
    <!-- Modal de Ganhadores -->
    <div class="modal fade" id="winnersModal<?php echo $resultado['id']; ?>" tabindex="-1" aria-labelledby="winnersModalLabel<?php echo $resultado['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header lottery-modal-header <?php echo htmlspecialchars(strtolower($resultado['identificador_api'] ?? '')); ?>">
                    <h5 class="modal-title" id="winnersModalLabel<?php echo $resultado['id']; ?>">
                        <i class="fas fa-trophy me-2"></i>
                        Ganhadores - <?php echo htmlspecialchars($resultado['nome'] ?? ''); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="concurso-info mb-3">
                        <strong>Concurso:</strong> <?php echo htmlspecialchars($resultado['numero_concurso'] ?? ''); ?>
                        <?php if (!empty($resultado['data_sorteio'])): ?>
                            <br>
                            <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($resultado['data_sorteio'])); ?>
                        <?php endif; ?>
                    </div>

                    <div class="winners-list">
                        <?php
                        // Combinar ganhadores importados e regulares
                        $todos_ganhadores = [];
                        
                        // Processar ganhadores de apostas importadas
                        if (!empty($resultado['ganhadores_importados'])) {
                            $ganhadores = explode('|', $resultado['ganhadores_importados']);
                            foreach ($ganhadores as $ganhador) {
                                if (strpos($ganhador, ':') !== false) {
                                    list($nome, $premio, $tipo, $id) = explode(':', $ganhador);
                                    $todos_ganhadores[] = [
                                        'nome' => $nome,
                                        'premio' => $premio,
                                        'tipo' => 'Aposta Importada',
                                        'id' => $id
                                    ];
                                }
                            }
                        }
                        
                        // Processar ganhadores de apostas regulares
                        if (isset($resultado['ganhadores_regulares']) && !empty($resultado['ganhadores_regulares'])) {
                            $ganhadores = explode('|', $resultado['ganhadores_regulares']);
                            foreach ($ganhadores as $ganhador) {
                                if (strpos($ganhador, ':') !== false) {
                                    list($nome, $premio, $tipo, $id) = explode(':', $ganhador);
                                    $todos_ganhadores[] = [
                                        'nome' => $nome,
                                        'premio' => $premio,
                                        'tipo' => 'Aposta Manual',
                                        'id' => $id
                                    ];
                                }
                            }
                        }
                        
                        // Processar ganhadores manuais (apostas que correspondem aos números sorteados)
                        if (isset($resultado['ganhadores_manuais']) && !empty($resultado['ganhadores_manuais'])) {
                            $ganhadores = explode('|', $resultado['ganhadores_manuais']);
                            foreach ($ganhadores as $ganhador) {
                                if (strpos($ganhador, ':') !== false) {
                                    list($nome, $premio, $tipo, $id) = explode(':', $ganhador);
                                    $todos_ganhadores[] = [
                                        'nome' => $nome,
                                        'premio' => $premio > 0 ? $premio : 'Pendente',
                                        'tipo' => 'Acertou Todos os Números',
                                        'id' => $id
                                    ];
                                }
                            }
                        }
                        
                        // Ordenar ganhadores pelo valor do prêmio (decrescente)
                        usort($todos_ganhadores, function($a, $b) {
                            if ($a['premio'] === 'Pendente') return 1;
                            if ($b['premio'] === 'Pendente') return -1;
                            return $b['premio'] <=> $a['premio'];
                        });
                        
                        // Exibir todos os ganhadores
                        if (!empty($todos_ganhadores)) {
                            foreach ($todos_ganhadores as $ganhador) {
                        ?>
                            <div class="winner-item">
                                <div class="winner-info">
                                <div class="winner-name">
                                    <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($ganhador['nome'] ?? ''); ?>
                                        <span class="badge-acertos">
                                            <?php if (strpos($ganhador['tipo'], 'Acertou Todos') !== false): ?>
                                                Todos os números
                                            <?php elseif ($ganhador['premio'] === 'Pendente'): ?>
                                                Não processado
                                            <?php else: ?>
                                                <?php echo (isset($ganhador['acertos']) ? $ganhador['acertos'] . ' acertos' : 'Premiado'); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="winner-type">
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($ganhador['tipo'] ?? ''); ?></span>
                                    </div>
                                </div>
                                <div class="winner-prize">
                                    <?php if ($ganhador['premio'] === 'Pendente'): ?>
                                        <button class="btn btn-sm btn-primary processar-ganhador" data-id="<?php echo $ganhador['id']; ?>" data-concurso="<?php echo $resultado['numero_concurso']; ?>" data-jogo="<?php echo $resultado['id']; ?>">
                                            Processar
                                        </button>
                                    <?php else: ?>
                                        <?php echo formatarValor((float)$ganhador['premio']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php 
                                }
                        } else {
                        ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhum ganhador encontrado para este concurso.
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<style>
/* Estilos específicos para a página de resultados */
.header-card {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
    padding: 10px;
}

.lottery-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.lottery-header {
    padding: 20px;
    color: black;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.lottery-info h3 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
}

.lottery-info p {
    opacity: 0.9;
    font-size: 0.9rem;
    margin-top: 5px;
}

.lottery-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
}

/* Cores específicas para cada jogo */
.lottery-card.megasena .lottery-header { background: linear-gradient(135deg, #209869, #1a7d55); }
.lottery-card.lotofacil .lottery-header { background: linear-gradient(135deg,rgb(211, 5, 197),rgb(128, 14, 120)); }
.lottery-card.quina .lottery-header { background: linear-gradient(135deg,rgb(44, 125, 247),rgb(42, 86, 206)); }
.lottery-card.lotomania .lottery-header { background: linear-gradient(135deg, #F78100, #c66800); }
.lottery-card.timemania .lottery-header { background: linear-gradient(135deg,rgb(215, 230, 5),rgb(223, 238, 13)); }
.lottery-card.duplasena .lottery-header { background: linear-gradient(135deg, #A61324, #8a0f1e); }
.lottery-card.maismilionaria .lottery-header { background: linear-gradient(135deg, #742ce3, #7c37e7); }
.lottery-card.diadesorte .lottery-header { background: linear-gradient(135deg, #e9bb08, #bd9704); }

.lottery-body {
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
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
    color: white;
    transition: transform 0.2s;
}

/* Cores dos números para cada jogo */
.lottery-card.megasena .number { background: #209869; }
.lottery-card.lotofacil .number { background: #930089; }
.lottery-card.quina .number { background: #260085; }
.lottery-card.lotomania .number { background: #F78100; }
.lottery-card.timemania .number { background: #00ff48; color: #000; }
.lottery-card.duplasena .number { background: #A61324; }
.lottery-card.maismilionaria .number { background: #930089; }
.lottery-card.diadesorte .number { background: #CB8E37; }

.number:hover {
    transform: scale(1.1);
}

.info-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 20px;
}

.info-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.info-value {
    font-size: 1.3rem;
    font-weight: bold;
    color: #28a745;
}

.next-draw {
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.next-draw-header {
    font-size: 1rem;
    color: #495057;
    margin-bottom: 15px;
}

.next-draw-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.date {
    color: #6c757d;
}

.date i {
    margin-right: 8px;
}

.prize {
    text-align: right;
}

.prize-label {
    font-size: 0.85rem;
    color: #6c757d;
}

.prize-value {
    font-size: 1.1rem;
    font-weight: bold;
    color: #28a745;
}

@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: 1fr;
    }

    .lottery-header {
        padding: 15px;
    }

    .lottery-info h3 {
        font-size: 1.2rem;
    }

    .number {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .next-draw-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .prize {
        text-align: left;
        width: 100%;
    }
}

.winners-button-container {
    text-align: center;
    margin: 20px 0;
    padding: 15px;
    border-top: 1px solid #eee;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.winners-button-container .btn {
    padding: 15px 30px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 1.1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.winners-button-container .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.winners-button-container .btn i {
    font-size: 1.2rem;
}

.lottery-modal-header {
    color: white;
    background: #343a40;
    padding: 15px;
    border-bottom: none;
}

.modal-content {
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    border: none;
}

.modal-body {
    padding: 20px;
    background-color: #ffffff;
}

.concurso-info {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
}

.winners-list {
    max-height: 350px;
    overflow-y: auto;
    padding-right: 5px;
}

.winner-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: all 0.2s ease;
    border-left: 4px solid #28a745;
}

.winner-item:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 3px 5px rgba(0,0,0,0.1);
}

.winner-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.winner-name {
    font-weight: 600;
    color: #212529;
}

.winner-name i {
    color: #007bff;
    margin-right: 8px;
}

.winner-type .badge {
    background-color: #6c757d;
    font-weight: normal;
}

.winner-prize {
    font-weight: bold;
    color: #28a745;
    font-size: 1.1rem;
    text-align: right;
}

.processar-ganhador {
    background-color: #007bff;
    border: none;
    padding: 5px 10px;
    transition: all 0.2s ease;
}

.processar-ganhador:hover {
    background-color: #0069d9;
    transform: translateY(-1px);
}

.modal-footer {
    border-top: none;
    padding: 15px 20px;
}

/* Estilo do botão que abre o modal */
.winners-button-container {
    margin-top: 15px;
    margin-bottom: 15px;
}

.winners-button-container .btn {
    background: linear-gradient(to right, #28a745, #20c997);
    border: none;
    box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
    transition: all 0.3s ease;
}

.winners-button-container .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(40, 167, 69, 0.3);
}

/* Badge de acertos */
.badge-acertos {
    background-color: #007bff;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    margin-left: 10px;
    font-size: 0.8rem;
}

/* Modal para jogos específicos */
.lottery-modal-header.quina {
    background: linear-gradient(to right, #000080, #4169E1);
}

.lottery-modal-header.megasena {
    background: linear-gradient(to right, #006400, #2E8B57); 
}

.lottery-modal-header.lotofacil {
    background: linear-gradient(to right, #800080, #BA55D3);
}

.lottery-modal-header.timemania {
    background: linear-gradient(to right, #FFD700, #DAA520);
}

.lottery-modal-header.lotomania {
    background: linear-gradient(to right, #FF4500, #FF8C00);
}
</style>

<script>
// Carregar jQuery de forma síncrona para garantir que ele esteja disponível antes de qualquer uso
if (typeof jQuery === 'undefined') {
    document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"><\/script>');
    console.log('jQuery carregado via CDN');
} else {
    console.log('jQuery já está disponível');
}

// Usando JavaScript puro (Vanilla JS) para evitar dependências de jQuery
document.addEventListener('DOMContentLoaded', function() {
    // Atualização automática a cada 5 minutos
    setInterval(function() {
        location.reload();
    }, 300000);
    
    // Atualização de resultados via AJAX
    document.getElementById('btnAtualizarResultados').addEventListener('click', function() {
        atualizarResultados();
    });
    
    // Botão para processar ganhadores
    document.getElementById('btnProcessarGanhadores').addEventListener('click', function() {
        processarGanhadores();
    });
    
    // Botões para processar ganhador individual
    document.querySelectorAll('.processar-ganhador').forEach(button => {
        button.addEventListener('click', function() {
            const apostaiId = this.getAttribute('data-id');
            const concursoId = this.getAttribute('data-concurso');
            const jogoId = this.getAttribute('data-jogo');
            
            processarGanhadorManual(apostaiId, concursoId, jogoId, this);
        });
    });
});

// Função para atualizar resultados
function atualizarResultados() {
    // Mostrar modal de carregamento
    Swal.fire({
        title: 'Atualizando Resultados',
        html: 'Por favor, aguarde...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Fazer requisição AJAX
    fetch('ajax/atualizar_resultados.php')
        .then(response => response.json())
        .then(data => {
            Swal.close();
            
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Recarregar página
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.message,
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Ocorreu um erro ao atualizar os resultados. Por favor, tente novamente.',
                confirmButtonText: 'OK'
            });
            console.error('Erro:', error);
        });
}

// Função para processar ganhadores
function processarGanhadores() {
    // Mostrar modal de carregamento
    Swal.fire({
        title: 'Processando Ganhadores',
        html: 'Por favor, aguarde enquanto verificamos as apostas...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Fazer requisição AJAX
    fetch('ajax/processar_ganhadores_manual.php')
        .then(response => response.json())
        .then(data => {
            Swal.close();
            
            // Exibir logs no console
            console.log('Logs de processamento:', data.logs);
            
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message,
                    showDenyButton: true,
                    confirmButtonText: 'OK',
                    denyButtonText: 'Ver Logs',
                }).then((result) => {
                    if (result.isDenied) {
                        // Mostrar logs em um modal
                        let logsHtml = '<div style="max-height: 400px; overflow-y: auto; text-align: left;">';
                        data.logs.forEach(log => {
                            logsHtml += `<div>${log}</div>`;
                        });
                        logsHtml += '</div>';
                        
                        Swal.fire({
                            title: 'Logs de Processamento',
                            html: logsHtml,
                            width: '80%',
                            confirmButtonText: 'Fechar'
                        });
            } else {
                        // Recarregar página
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.message,
                    showDenyButton: true,
                    confirmButtonText: 'OK',
                    denyButtonText: 'Ver Logs',
                }).then((result) => {
                    if (result.isDenied) {
                        // Mostrar logs em um modal
                        let logsHtml = '<div style="max-height: 400px; overflow-y: auto; text-align: left;">';
                        data.logs.forEach(log => {
                            logsHtml += `<div>${log}</div>`;
                        });
                        logsHtml += '</div>';
                        
                        Swal.fire({
                            title: 'Logs de Processamento',
                            html: logsHtml,
                            width: '80%',
                            confirmButtonText: 'Fechar'
                        });
                    }
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Ocorreu um erro ao processar os ganhadores. Por favor, tente novamente.',
                confirmButtonText: 'OK'
            });
            console.error('Erro:', error);
        });
}

// Função para processar manualmente um ganhador
function processarGanhadorManual(apostaiId, concursoId, jogoId, buttonEl) {
    // Alterar o texto do botão para indicar processamento
    const textoOriginal = buttonEl.innerHTML;
    buttonEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processando...';
    buttonEl.disabled = true;
    
    // Fazer requisição para processar o ganhador
    fetch('ajax/processar_ganhador_manual.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `aposta_id=${apostaiId}&concurso=${concursoId}&jogo_id=${jogoId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Substituir o botão pelo valor do prêmio
            buttonEl.outerHTML = formatarValor(data.premio);
            
            // Mostrar mensagem de sucesso
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Aposta processada com sucesso!',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            // Restaurar o botão
            buttonEl.innerHTML = textoOriginal;
            buttonEl.disabled = false;
            
            // Mostrar mensagem de erro
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: data.message || 'Erro ao processar aposta'
            });
        }
    })
    .catch(error => {
        // Restaurar o botão
        buttonEl.innerHTML = textoOriginal;
        buttonEl.disabled = false;
        
        // Mostrar mensagem de erro
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Ocorreu um erro ao processar a aposta'
        });
        console.error('Erro:', error);
    });
}

// Função JavaScript para formatar valor em R$
function formatarValor(valor) {
    return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
}
</script>

<?php
// Capturar o conteúdo do buffer
$content = ob_get_clean();

// Incluir o layout
require_once $layout_path;