<?php
require_once '../includes/verificar_manutencao.php';
require_once '../config/database.php';
require_once 'includes/header.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'resultados';

/**
 * URLs das APIs da Caixa para buscar resultados
 */
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

/**
 * Função para buscar resultado da API da Caixa
 * @param string $url URL da API
 * @return array Dados do resultado
 */
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

/**
 * Função para salvar resultado no banco de dados
 * @param PDO $pdo Conexão com o banco de dados
 * @param int $jogo_id ID do jogo
 * @param int $numero_concurso Número do concurso
 * @param string $data_sorteio Data do sorteio
 * @param array $dezenas Dezenas sorteadas
 * @param array $resultado Dados adicionais do resultado
 * @return bool Sucesso ou falha
 */
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

/**
 * Formata valor monetário
 * @param float $valor Valor a ser formatado
 * @return string Valor formatado
 */
function formatarValor($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Inicializa variáveis
$mensagem = '';
$tipo_mensagem = '';
$resultados = [];
$resultados_banco = [];

try {
    // Se foi solicitada atualização manual
    if (isset($_POST['atualizar'])) {
        foreach ($api_urls as $jogo => $url) {
            try {
                // Busca resultado da API
                $resultado = buscarResultado($url);
                
                if ($resultado) {
                    $resultados[$jogo] = $resultado;
                    
                    // Salva no banco de dados
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
    
    // Desativa modo restrito do MySQL para evitar problemas com GROUP_CONCAT
    $pdo->exec("SET SESSION sql_mode=''");
    
    // Busca resultados do banco
    $sql = "
        SELECT 
            j.id,
            j.nome,
            j.identificador_api,
            j.numero_concurso,
            c.codigo as numero_concurso_atual,
            c.data_sorteio,
            GROUP_CONCAT(DISTINCT ns.numero ORDER BY ns.numero ASC SEPARATOR ',') as dezenas,
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
    <!-- Cabeçalho da página -->
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-trophy"></i> Resultados Oficiais</h1>
            <p>Resultados oficiais das Loterias Caixa</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" onclick="abrirModal()" class="btn-custom">
                <i class="fas fa-plus"></i>
                <span>Inserir Manualmente</span>
            </button>
            <form method="post" class="d-inline">
                <button type="submit" name="atualizar" class="btn-update">
                    <i class="fas fa-sync-alt"></i>
                    <span>Atualizar Resultados</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para inserção manual de resultados -->
    <div id="meuModal" class="modal-personalizado">
        <div class="modal-conteudo">
            <div class="modal-cabecalho">
                <h5>Inserir Resultado Manualmente</h5>
                <span class="fechar-modal">&times;</span>
            </div>
            <div class="modal-corpo">
                <form id="formInserirResultado" onsubmit="return false;">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="jogo_id" class="form-label">Jogo</label>
                            <select class="form-select" id="jogo_id" name="jogo_id" required>
                                <option value="">Selecione o jogo</option>
                                <?php
                                // Buscar jogos do banco de dados
                                $stmt = $pdo->query("SELECT id, nome FROM jogos ORDER BY nome");
                                while ($jogo = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $jogo['id'] . '">' . htmlspecialchars($jogo['nome']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="concurso" class="form-label">Número do Concurso</label>
                            <input type="number" class="form-control" id="concurso" name="concurso" required min="1">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="data_sorteio" class="form-label">Data do Sorteio</label>
                            <input type="datetime-local" class="form-control" id="data_sorteio" name="data_sorteio" required>
                        </div>
                        <div class="col-md-6">
                            <label for="data_proximo" class="form-label">Data do Próximo Sorteio</label>
                            <input type="datetime-local" class="form-control" id="data_proximo" name="data_proximo">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Números Sorteados</label>
                        <input type="hidden" name="numeros_selecionados" id="numeros_selecionados">
                        <div class="numeros-info mb-2">Números selecionados: <span id="contador_numeros">0/0</span></div>
                        <div class="numeros-container" id="numeros-container">
                            <!-- Os números serão inseridos aqui via JavaScript -->
                        </div>
                    </div>
                
                    <!-- Campos ocultos para manter compatibilidade -->
                    <input type="hidden" id="valor_acumulado" name="valor_acumulado" value="0">
                    <input type="hidden" id="valor_estimado" name="valor_estimado" value="0">
                </form>
            
                <!-- Seção para mostrar resultados de ganhadores -->
                <div id="resultadosGanhadores" class="mt-4 d-none">
                    <h4 class="mb-3"><i class="fas fa-trophy me-2"></i> Ganhadores</h4>
                    <div id="alert-ganhadores" class="alert alert-info">
                        <i class="fas fa-spinner fa-spin me-2"></i> Processando ganhadores...
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tabelaGanhadores">
                            <thead>
                                <tr>
                                    <th>Apostador</th>
                                    <th>Revendedor</th>
                                    <th>Acertos</th>
                                    <th>Valor Aposta</th>
                                    <th>Prêmio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dados dos ganhadores serão inseridos aqui -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Total:</th>
                                    <th id="total-premios">R$ 0,00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-rodape">
                <button type="button" class="btn-secondary" id="botaoCancelar">Cancelar</button>
                <button type="button" class="btn-primary" id="btnSalvarResultado">Salvar Resultado</button>
            </div>
        </div>
    </div>

    <!-- Mensagens de alerta -->
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

    <!-- Grid de resultados -->
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

                                <?php 
                                // Verificar se há ganhadores para este concurso em apostas importadas
                                $sql_check = "
                                    SELECT COUNT(*) as total
                                    FROM apostas_importadas ai
                                    WHERE ai.jogo_nome = ? AND ai.numeros = ?
                                ";
                                $stmt_check = $pdo->prepare($sql_check);
                                $stmt_check->execute([$resultado['nome'], $resultado['dezenas']]);
                                $check_result = $stmt_check->fetch(PDO::FETCH_ASSOC);
                                $has_ganhadores_importados = ($check_result && $check_result['total'] > 0);
                                
                                // Verificar se há ganhadores na tabela apostas
                                $sql_check_apostas = "
                                    SELECT COUNT(*) as total
                                    FROM apostas a
                                    JOIN jogos j ON a.tipo_jogo_id = j.id
                                    WHERE j.nome = ? AND (a.status = 'aprovada' OR a.status = 'ativa')
                                    AND a.numeros = ?
                                ";
                                $stmt_check_apostas = $pdo->prepare($sql_check_apostas);
                                $stmt_check_apostas->execute([$resultado['nome'], $resultado['dezenas']]);
                                $check_apostas_result = $stmt_check_apostas->fetch(PDO::FETCH_ASSOC);
                                $has_ganhadores_apostas = ($check_apostas_result && $check_apostas_result['total'] > 0);
                                
                                // Determinar se tem ganhadores em pelo menos uma das tabelas
                                $has_ganhadores = $has_ganhadores_importados || $has_ganhadores_apostas;
                                
                                if ($has_ganhadores): 
                                ?>
                                    <div class="ganhadores-info">
                                        <h4>
                                            <i class="fas fa-trophy me-2"></i>
                                            Ganhadores deste Concurso
                                        </h4>
                                        <div class="ganhadores-lista">
                                            <?php
                                            // Array para armazenar todos os ganhadores
                                            $todos_ganhadores = [];
                                            
                                            // Buscar ganhadores de apostas importadas
                                            if ($has_ganhadores_importados) {
                                                $sql_ganhadores = "
                                                    SELECT 
                                                        u.nome,
                                                        u.id,
                                                        ai.valor_premio,
                                                        'importada' as tipo
                                                    FROM apostas_importadas ai
                                                    JOIN usuarios u ON ai.revendedor_id = u.id
                                                    WHERE ai.jogo_nome = ? AND ai.numeros = ?
                                                ";
                                                $stmt_ganhadores = $pdo->prepare($sql_ganhadores);
                                                $stmt_ganhadores->execute([$resultado['nome'], $resultado['dezenas']]);
                                                $ganhadores_importados = $stmt_ganhadores->fetchAll(PDO::FETCH_ASSOC);
                                                $todos_ganhadores = array_merge($todos_ganhadores, $ganhadores_importados);
                                            }
                                            
                                            // Buscar ganhadores da tabela apostas
                                            if ($has_ganhadores_apostas) {
                                                $sql_apostas_ganhadores = "
                                                    SELECT 
                                                        u.nome,
                                                        u.id,
                                                        a.valor_premio,
                                                        'regular' as tipo
                                                    FROM apostas a
                                                    JOIN usuarios u ON a.usuario_id = u.id
                                                    JOIN jogos j ON a.tipo_jogo_id = j.id
                                                    WHERE j.nome = ? 
                                                    AND (a.status = 'aprovada' OR a.status = 'ativa')
                                                    AND a.numeros = ?
                                                ";
                                                $stmt_apostas = $pdo->prepare($sql_apostas_ganhadores);
                                                $stmt_apostas->execute([$resultado['nome'], $resultado['dezenas']]);
                                                $ganhadores_apostas = $stmt_apostas->fetchAll(PDO::FETCH_ASSOC);
                                                $todos_ganhadores = array_merge($todos_ganhadores, $ganhadores_apostas);
                                            }
                                            
                                            // Contador de ganhadores
                                            $total_ganhadores = count($todos_ganhadores);
                                            
                                            foreach ($todos_ganhadores as $ganhador) {
                                                $premio = isset($ganhador['valor_premio']) ? $ganhador['valor_premio'] : 1000.00;
                                            ?>
                                                <div class="ganhador-item" onclick="abrirDetalhesGanhador(<?php echo intval($ganhador['id']); ?>, '<?php echo htmlspecialchars(addslashes($ganhador['nome'])); ?>', '<?php echo isset($ganhador['tipo']) ? $ganhador['tipo'] : 'importada'; ?>', <?php echo floatval($premio); ?>)">
                                                    <div class="ganhador-nome">
                                                        <i class="fas fa-user me-2"></i>
                                                        <?php echo htmlspecialchars($ganhador['nome']); ?>
                                                        <?php if (isset($ganhador['tipo']) && $ganhador['tipo'] == 'regular'): ?>
                                                            <span class="badge bg-success ms-2">Apostador</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-primary ms-2">Revendedor</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ganhador-premio">
                                                        <?php echo formatarValor($premio); ?>
                                                        <i class="fas fa-chevron-right ms-2"></i>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="total-ganhadores">
                                            Total de Ganhadores: <?php echo $total_ganhadores; ?>
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

<!-- Modal de Detalhes do Ganhador -->
<div id="modalDetalhesGanhador" class="modal-personalizado">
    <div class="modal-conteudo modal-menor">
        <div class="modal-cabecalho">
            <h5><i class="fas fa-trophy me-2"></i> Detalhes do Ganhador</h5>
            <span class="fechar-modal" onclick="fecharModalDetalhes()">&times;</span>
        </div>
        <div class="modal-corpo">
            <div id="carregando-detalhes" class="text-center py-4">
                <div class="spinner"></div>
                <p class="mt-3">Carregando detalhes do ganhador...</p>
            </div>
            
            <div id="conteudo-detalhes" class="d-none">
                <div class="perfil-ganhador text-center mb-4">
                    <div class="avatar-container">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h4 id="nome-ganhador"></h4>
                    <span id="tipo-ganhador" class="badge"></span>
                    <div class="contato-info mt-2">
                        <span id="email-ganhador">-</span>
                        <span id="telefone-ganhador" class="ms-2">-</span>
                    </div>
                </div>
                
                <div class="detalhes-premio">
                    <h5><i class="fas fa-money-bill-wave me-2"></i> Prêmio</h5>
                    <div class="premio-valor-grande" id="premio-valor"></div>
                </div>
                
                <div class="detalhes-aposta mt-4">
                    <h5><i class="fas fa-ticket-alt me-2"></i> Detalhes da Aposta</h5>
                    <table class="table-detalhes">
                        <tr>
                            <td>ID do Usuário:</td>
                            <td id="usuario-id"></td>
                        </tr>
                        <tr>
                            <td>Data da Aposta:</td>
                            <td id="data-aposta">-</td>
                        </tr>
                        <tr>
                            <td>Jogo:</td>
                            <td id="jogo-nome">-</td>
                        </tr>
                        <tr>
                            <td>Números Apostados:</td>
                            <td id="numeros-apostados">-</td>
                        </tr>
                        <tr>
                            <td>Valor Apostado:</td>
                            <td id="valor-apostado">-</td>
                        </tr>
                        <tr>
                            <td>Status da Aposta:</td>
                            <td id="status-aposta">-</td>
                        </tr>
                    </table>
                </div>
                
                <div class="estatisticas-apostador mt-4">
                    <h5><i class="fas fa-chart-pie me-2"></i> Estatísticas</h5>
                    <div class="estatisticas-grid">
                        <div class="estatistica-item">
                            <span class="numero" id="total-apostas">0</span>
                            <span class="label">Apostas Realizadas</span>
                        </div>
                        <div class="estatistica-item" id="container-apostas-importadas">
                            <span class="numero" id="total-apostas-importadas">0</span>
                            <span class="label">Apostas Importadas</span>
                        </div>
                    </div>
                </div>
                
                <div class="acoes-ganhador mt-4">
                    <a href="#" id="link-perfil" class="btn-acao">
                        <i class="fas fa-user me-2"></i> Ver Perfil
                    </a>
                    <a href="#" id="link-apostas" class="btn-acao">
                        <i class="fas fa-history me-2"></i> Histórico de Apostas
                    </a>
                </div>
            </div>
        </div>
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

/* Garantir que os cards sejam visíveis por padrão */
.result-card {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    margin-bottom: 25px;
}

.result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Assegurar que o container de resultados esteja visível */
.results-grid {
    display: grid !important;
    visibility: visible !important;
    opacity: 1 !important;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
    padding: 10px;
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

/* Estilos para o modal */
.modal-personalizado {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
    overflow: auto;
}

.modal-conteudo {
    position: relative;
    background-color: #fff;
    margin: 50px auto;
    padding: 0;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    width: 90%;
    max-width: 800px;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-cabecalho {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.modal-cabecalho h5 {
    margin: 0;
    font-weight: 600;
    font-size: 1.2rem;
}

.fechar-modal {
    font-size: 24px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.fechar-modal:hover {
    color: #333;
}

.modal-corpo {
    padding: 20px;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-rodape {
    padding: 15px 20px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background-color: #f8f9fa;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-primary {
    background-color: #0d6efd;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-custom {
    background: #4e73df;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-custom:hover {
    background: #2e59d9;
    transform: translateY(-2px);
}

.btn-primary:hover, .btn-secondary:hover {
    opacity: 0.9;
}

/* Estilos para os números do modal */
.numeros-container {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 15px;
}

.numeros-grid {
    width: 100%;
    border-collapse: separate; 
    border-spacing: 5px;
}

.numeros-grid td {
    padding: 0;
    text-align: center;
}

.numero-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background-color: #e9ecef;
    border: 2px solid #dee2e6;
    color: #333;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s, color 0.2s, border-color 0.2s;
    padding: 0;
    margin: 8px;
}

.numero-btn:hover {
    background-color: #dee2e6;
    transform: scale(1.1);
}

.numero-btn.selected {
    background-color: #0d6efd;
    color: white;
    border-color: #0a58ca;
    transform: scale(1.1);
}

.numeros-info {
    font-weight: bold;
    color: #495057;
    padding: 5px 0;
}

/* Manter compatibilidade com Bootstrap */
.d-none {
    display: none !important;
}

.d-flex {
    display: flex !important;
}

.gap-2 {
    gap: 0.5rem !important;
}

.d-inline {
    display: inline !important;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -0.75rem;
    margin-left: -0.75rem;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding-right: 0.75rem;
    padding-left: 0.75rem;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.form-label {
    margin-bottom: 0.5rem;
    display: inline-block;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    appearance: none;
    border-radius: 0.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-select {
    display: block;
    width: 100%;
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    appearance: none;
}

.table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.05);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
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
    
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

/* Estilos para o modal de detalhes do ganhador */
.modal-menor {
    max-width: 500px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    border-top-color: var(--primary-color);
    margin: 0 auto;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.perfil-ganhador .avatar-container {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: var(--light-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
}

.perfil-ganhador .avatar-container i {
    font-size: 40px;
    color: var(--primary-color);
}

.perfil-ganhador h4 {
    margin: 0 0 8px;
    font-size: 1.5rem;
}

.premio-valor-grande {
    font-size: 2rem;
    font-weight: bold;
    color: var(--success-color);
    text-align: center;
    padding: 15px;
    background-color: rgba(46, 204, 113, 0.1);
    border-radius: 10px;
    margin-top: 10px;
}

.table-detalhes {
    width: 100%;
    border-collapse: collapse;
}

.table-detalhes tr:not(:last-child) {
    border-bottom: 1px solid var(--light-color);
}

.table-detalhes td {
    padding: 12px 8px;
}

.table-detalhes td:first-child {
    font-weight: bold;
    width: 40%;
}

.acoes-ganhador {
    display: flex;
    justify-content: space-around;
    gap: 10px;
}

.btn-acao {
    display: inline-block;
    padding: 10px 15px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    text-align: center;
    flex: 1;
    transition: all 0.3s ease;
}

.btn-acao:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
}

/* Estilo para item clicável */
.ganhador-item {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.ganhador-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    background: rgba(255,255,255,0.8);
}

.perfil-ganhador h4 {
    margin: 0 0 8px;
    font-size: 1.5rem;
}

.contato-info {
    font-size: 0.9rem;
    color: #666;
}

.contato-info span {
    display: inline-block;
}

.estatisticas-grid {
    display: flex;
    justify-content: space-around;
    text-align: center;
    margin-top: 15px;
    gap: 15px;
}

.estatistica-item {
    flex: 1;
    padding: 15px;
    background-color: var(--light-color);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.estatistica-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.estatistica-item .numero {
    display: block;
    font-size: 1.8rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 5px;
}

.estatistica-item .label {
    font-size: 0.85rem;
    color: #666;
}
</style>

<script>
// Garantir que os jogos apareçam ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Carregado. Verificando resultados...');
    console.log('Resultados encontrados:', document.querySelectorAll('.result-card').length);
    
    // Forçar exibição dos cards de resultados
    const resultCards = document.querySelectorAll('.result-card');
    if (resultCards.length > 0) {
        console.log('Forçando exibição de', resultCards.length, 'cards de resultados');
        resultCards.forEach(card => {
            card.style.display = 'block';
            card.style.visibility = 'visible';
            card.style.opacity = '1';
        });
    }
    
    // Se não houver resultados, tente atualizá-los automaticamente
    if (resultCards.length === 0) {
        console.log('Nenhum resultado encontrado. Tentando atualizar automaticamente...');
        document.querySelector('button[name="atualizar"]').click();
    }
    
    // Verificar se o elemento pai está visível
    const resultsGrid = document.querySelector('.results-grid');
    if (resultsGrid) {
        console.log('Status do container de resultados:', {
            display: getComputedStyle(resultsGrid).display,
            visibility: getComputedStyle(resultsGrid).visibility,
            opacity: getComputedStyle(resultsGrid).opacity,
            height: getComputedStyle(resultsGrid).height
        });
        
        // Garantir que o container também esteja visível
        resultsGrid.style.display = 'grid';
        resultsGrid.style.visibility = 'visible';
        resultsGrid.style.opacity = '1';
    }
    
    // Aplicar listener ao select para atualizar contador quando mudar o jogo
    const jogoSelect = document.getElementById('jogo_id');
    if (jogoSelect) {
        jogoSelect.addEventListener('change', atualizarSelecao);
    }
    
    // Configurar o botão de Salvar Resultado
    const btnSalvarResultado = document.getElementById('btnSalvarResultado');
    if (btnSalvarResultado) {
        btnSalvarResultado.addEventListener('click', salvarResultado);
    }
    
    // Configurar o botão de Cancelar
    const btnCancelar = document.getElementById('botaoCancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', fecharModal);
    }
    
    // Configurar o botão X para fechar o modal
    const fecharModalBtn = document.querySelector('.fechar-modal');
    if (fecharModalBtn) {
        fecharModalBtn.addEventListener('click', fecharModal);
    }
    
    // Gerar números via JavaScript para garantir eventos corretos
    const numerosContainer = document.querySelector('.numeros-container');
    if (numerosContainer) {
        // Criar tabela
        let html = '<table class="numeros-grid"><tbody>';
        
        // Gerar 10 linhas de números
        for (let i = 0; i < 10; i++) {
            html += '<tr>';
            
            // Cada linha tem 10 números
            for (let j = 1; j <= 10; j++) {
                const num = i * 10 + j;
                html += gerarBotaoNumero(num);
            }
            
            html += '</tr>';
        }
        
        html += '</tbody></table>';
        
        // Injetar HTML
        numerosContainer.innerHTML = html;
    }
    
    // Inicializar data atual para o modal
    const now = new Date();
    const dataFormatada = now.toISOString().slice(0, 16);
    const dataSorteio = document.getElementById('data_sorteio');
    if (dataSorteio) dataSorteio.value = dataFormatada;
    
    // Data próximo sorteio (7 dias depois)
    const nextWeek = new Date();
    nextWeek.setDate(nextWeek.getDate() + 7);
    const dataProximo = document.getElementById('data_proximo');
    if (dataProximo) dataProximo.value = nextWeek.toISOString().slice(0, 16);
});

// Variável para controlar o timer de atualização automática
let autoUpdateTimer = null;

// Atualização automática a cada 1 minuto - NÃO iniciar imediatamente para evitar problemas
function iniciarAtualizacaoAutomatica() {
    // Limpar timer existente, se houver
    if (autoUpdateTimer) {
        clearInterval(autoUpdateTimer);
        autoUpdateTimer = null;
    }
    
    // Criar novo timer
    autoUpdateTimer = setInterval(function() {
        // Não atualizar se o modal estiver aberto
        if (!modalAberto) {
            console.log('Executando atualização automática...');
            document.querySelector('button[name="atualizar"]').click();
        } else {
            console.log('Modal aberto, atualização automática pausada');
        }
    }, 60000); // 60 segundos
}

// Iniciar atualização automática após 10 segundos
setTimeout(iniciarAtualizacaoAutomatica, 10000);

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
document.querySelector('form').addEventListener('submit', function(e) {
    // Não atualizar se o modal estiver aberto
    if (modalAberto) {
        console.log('Modal aberto, impedindo atualização automática');
        e.preventDefault();
        return false;
    }
    
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
    // Não iniciar pull to refresh se o modal estiver aberto
    if (modalAberto) return;
    touchstartY = e.touches[0].clientY;
}, { passive: true });

document.addEventListener('touchmove', function(e) {
    // Não executar pull to refresh se o modal estiver aberto
    if (modalAberto) return;
    
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
    // Não concluir pull to refresh se o modal estiver aberto
    if (modalAberto) return;
    
    if (isPulling && (touchendY - touchstartY) > refreshThreshold) {
        // Trigger refresh
        document.querySelector('button[name="atualizar"]').click();
    }
    
    isPulling = false;
    refreshIndicator.classList.remove('visible');
    touchstartY = 0;
    touchendY = 0;
}, { passive: true });

// Funções para manipular o modal
let modalAberto = false;
let ignorarEventosFechar = false;

function abrirModal() {
    const modal = document.getElementById('meuModal');
    
    // Exibir o modal
    modal.style.display = 'block';
    modalAberto = true;
    
    // Bloquear scroll do fundo
    document.body.style.overflow = 'hidden';
    
    // Adicionar eventos para capturar cliques dentro do modal
    const modalConteudo = modal.querySelector('.modal-conteudo');
    
    // Prevenir qualquer evento de clique no fundo quando o modal estiver aberto
    modal.addEventListener('click', function(e) {
        if (e.target === modal && !ignorarEventosFechar) {
            fecharModal();
        }
    });
    
    // Prevenir que cliques dentro do modal propaguem para o fundo
    modalConteudo.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Resetar formulário
    const form = document.getElementById('formInserirResultado');
    if (form) form.reset();
    
    // Limpar seleções anteriores
    document.querySelectorAll('.numero-btn.selected').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Reset input hidden
    const numerosSelecionados = document.getElementById('numeros_selecionados');
    if (numerosSelecionados) numerosSelecionados.value = '';
    
    // Esconder seção de ganhadores
    const resultadosGanhadores = document.getElementById('resultadosGanhadores');
    if (resultadosGanhadores) resultadosGanhadores.classList.add('d-none');
    
    // Atualizar contador
    atualizarSelecao();
    
    // Configurar todos os botões numéricos para usar o evento correto
    document.querySelectorAll('.numero-btn').forEach(btn => {
        btn.removeEventListener('click', handleNumeroClick);
        btn.addEventListener('click', handleNumeroClick);
    });
}

function fecharModal() {
    // Verificar se há números selecionados ou se estamos no meio de uma operação
    if (document.querySelector('.numero-btn.selected') && !confirm('Você tem números selecionados. Deseja realmente fechar?')) {
        return;
    }
    
    document.getElementById('meuModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Desbloqueia o scroll
    modalAberto = false;
}

// Handler para clique nos números para prevenir propagação e comportamento padrão
function handleNumeroClick(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.toggle('selected');
    atualizarSelecao();
    return false;
}

// Função para gerar botão de número
function gerarBotaoNumero(numero) {
    const numFormatado = (numero < 10) ? `0${numero}` : numero;
    return `
        <td>
            <button type="button" 
                    class="numero-btn" 
                    data-numero="${numero}">
                ${numFormatado}
            </button>
        </td>
    `;
}

// Função para toggle dos números
function toggleNumero(btn, numero) {
    btn.classList.toggle('selected');
    atualizarSelecao();
    return false;
}

// Função para atualizar seleção e contador
function atualizarSelecao() {
    const selecionados = document.querySelectorAll('.numero-btn.selected');
    const numeros = Array.from(selecionados).map(el => parseInt(el.getAttribute('data-numero')));
    numeros.sort((a, b) => a - b);

    const input = document.getElementById('numeros_selecionados');
    if (input) {
        input.value = numeros.join(',');
    }

    // Obter jogo selecionado para validar quantidade mínima
    const jogoSelect = document.getElementById('jogo_id');
    let minNumeros = 15; // Padrão para lotofácil

    if (jogoSelect && jogoSelect.selectedIndex > 0) {
        const jogoNome = jogoSelect.options[jogoSelect.selectedIndex]?.text.toLowerCase() || '';
        
        if (jogoNome.includes('lotomania')) {
            minNumeros = 20;
        } else if (jogoNome.includes('lotofácil') || jogoNome.includes('lotofacil')) {
            minNumeros = 15;
        } else if (jogoNome.includes('quina')) {
            minNumeros = 5;
        } else if (jogoNome.includes('mega') || jogoNome.includes('sena')) {
            minNumeros = 6;
        } else if (jogoNome.includes('dia de sorte')) {
            minNumeros = 7;
        } else if (jogoNome.includes('timemania')) {
            minNumeros = 10;
        }
    }

    // Atualizar contador
    const contadorEl = document.getElementById('contador_numeros');
    if (contadorEl) {
        contadorEl.textContent = `${numeros.length}/${minNumeros}`;
    }
}

// Função para salvar resultado
function salvarResultado() {
    // Obter dados do formulário
    const form = document.getElementById('formInserirResultado');
    const formData = new FormData(form);
    
    // Validar dados obrigatórios
    const jogoId = formData.get('jogo_id');
    const concurso = formData.get('concurso');
    const numeros = formData.get('numeros_selecionados');
    
    if (!jogoId || !concurso || !numeros) {
        alert('Por favor, preencha todos os campos obrigatórios');
        return;
    }
    
    // Verificar quantidade mínima de números
    const jogoSelect = document.getElementById('jogo_id');
    const jogoNome = jogoSelect.options[jogoSelect.selectedIndex]?.text.toLowerCase() || '';
    const numerosArray = numeros.split(',');
    
    let minNumeros = 6;
    if (jogoNome.includes('lotofácil') || jogoNome.includes('lotofacil')) {
        minNumeros = 15;
    } else if (jogoNome.includes('lotomania')) {
        minNumeros = 20;
    } else if (jogoNome.includes('quina')) {
        minNumeros = 5;
    } else if (jogoNome.includes('dia de sorte')) {
        minNumeros = 7;
    } else if (jogoNome.includes('timemania')) {
        minNumeros = 10;
    }
    
    if (numerosArray.length < minNumeros) {
        alert(`É necessário selecionar pelo menos ${minNumeros} números para este jogo!`);
        return;
    }
    
    // Desabilitar botão durante envio
    const btnSalvarResultado = document.getElementById('btnSalvarResultado');
    btnSalvarResultado.disabled = true;
    btnSalvarResultado.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    
    // Mostrar seção de ganhadores com status de processamento
    const resultadosGanhadores = document.getElementById('resultadosGanhadores');
    resultadosGanhadores.classList.remove('d-none');
    
    const alertGanhadores = document.getElementById('alert-ganhadores');
    alertGanhadores.className = 'alert alert-info';
    alertGanhadores.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processando sorteio e verificando ganhadores...';
    
    console.log('Enviando dados para salvar resultado:', Object.fromEntries(formData));
    
    // Enviar dados via AJAX
    fetch('ajax/salvar_resultado_manual.php', {
        method: 'POST',
        body: formData,
        // Adicionar cabeçalhos para evitar problemas de cache
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache',
            'Expires': '0'
        }
    })
    .then(response => {
        // Verificar status HTTP primeiro
        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status} - ${response.statusText}`);
        }
        
        // Verificar tipo do conteúdo
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Mostrar o texto da resposta em caso de erro
            return response.text().then(text => {
                throw new Error(`Resposta não é JSON válido. Resposta: ${text.substring(0, 200)}...`);
            });
        }
        
        return response.json();
    })
    .then(data => {
        btnSalvarResultado.disabled = false;
        btnSalvarResultado.innerHTML = 'Salvar Resultado';
        
        if (data.success) {
            // Atualizar mensagem de sucesso
            alertGanhadores.className = 'alert alert-success';
            
            // Verificar se há ganhadores
            if (data.ganhadores && data.ganhadores.length > 0) {
                alertGanhadores.innerHTML = `<i class="fas fa-check-circle me-2"></i> Resultado salvo com sucesso! Encontrados ${data.ganhadores.length} ganhadores.`;
                
                // Preencher tabela de ganhadores
                const tbody = document.querySelector('#tabelaGanhadores tbody');
                tbody.innerHTML = '';
                
                let totalPremios = 0;
                
                // Log para depuração
                console.log('Ganhadores encontrados:', data.ganhadores);
                console.log('Dados de depuração:', data.debug);
                
                data.ganhadores.forEach(ganhador => {
                    const tr = document.createElement('tr');
                    
                    // Coluna Apostador
                    const tdApostador = document.createElement('td');
                    tdApostador.textContent = ganhador.nome_usuario || 'Desconhecido';
                    tr.appendChild(tdApostador);
                    
                    // Coluna Revendedor
                    const tdRevendedor = document.createElement('td');
                    if (ganhador.nome_revendedor) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-primary';
                        badge.textContent = ganhador.nome_revendedor;
                        tdRevendedor.appendChild(badge);
                    } else {
                        tdRevendedor.textContent = 'Direto';
                    }
                    tr.appendChild(tdRevendedor);
                    
                    // Coluna Acertos
                    const tdAcertos = document.createElement('td');
                    tdAcertos.textContent = ganhador.acertos || 0;
                    tr.appendChild(tdAcertos);
                    
                    // Coluna Valor Aposta
                    const tdValorAposta = document.createElement('td');
                    tdValorAposta.textContent = formatarMoeda(ganhador.valor_aposta || 0);
                    tr.appendChild(tdValorAposta);
                    
                    // Coluna Prêmio
                    const tdPremio = document.createElement('td');
                    tdPremio.textContent = formatarMoeda(ganhador.premio || 0);
                    tdPremio.className = 'fw-bold text-success';
                    tr.appendChild(tdPremio);
                    
                    tbody.appendChild(tr);
                    
                    totalPremios += parseFloat(ganhador.premio || 0);
                });
                
                // Exibir resultadosGanhadores
                document.getElementById('resultadosGanhadores').classList.remove('d-none');
                
                // Atualizar total de prêmios
                document.getElementById('total-premios').textContent = formatarMoeda(totalPremios);
                
                // Recarregar a página após 5 segundos para mostrar o novo resultado
                setTimeout(function() {
                    window.location.reload();
                }, 5000);
                
            } else {
                // Mostrar mensagem de nenhum ganhador
                alertGanhadores.innerHTML = '<i class="fas fa-check-circle me-2"></i> Resultado salvo com sucesso! Nenhum ganhador encontrado.';
                
                console.log('Sem ganhadores. Dados de depuração:', data.debug);
                
                // Exibir resultadosGanhadores mesmo sem ganhadores
                document.getElementById('resultadosGanhadores').classList.remove('d-none');
                
                document.querySelector('#tabelaGanhadores tbody').innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">Nenhum ganhador encontrado</td>
                    </tr>
                `;
                document.getElementById('total-premios').textContent = 'R$ 0,00';
                
                // Recarregar a página após 3 segundos para mostrar o novo resultado
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            }
            
        } else {
            // Mostrar erro
            alertGanhadores.className = 'alert alert-danger';
            alertGanhadores.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i> Erro ao salvar resultado: ${data.message || 'Erro desconhecido'}`;
            console.error('Erro retornado pelo servidor:', data);
        }
    })
    .catch(error => {
        console.error('Erro completo:', error);
        btnSalvarResultado.disabled = false;
        btnSalvarResultado.innerHTML = 'Salvar Resultado';
        
        alertGanhadores.className = 'alert alert-danger';
        alertGanhadores.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i> Erro ao processar a requisição: ${error.message}`;
        
        // Criar botão para tentar novamente
        const btnTentarNovamente = document.createElement('button');
        btnTentarNovamente.className = 'btn btn-warning mt-2';
        btnTentarNovamente.innerHTML = '<i class="fas fa-sync"></i> Tentar Novamente';
        btnTentarNovamente.addEventListener('click', salvarResultado);
        alertGanhadores.appendChild(document.createElement('br'));
        alertGanhadores.appendChild(btnTentarNovamente);
    });
}

// Função para formatar moeda
function formatarMoeda(valor) {
    return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

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

// Funções para o modal de detalhes do ganhador
function abrirDetalhesGanhador(usuarioId, nome, tipo, premio) {
    // Mostrar o modal
    const modal = document.getElementById('modalDetalhesGanhador');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Mostrar carregando e esconder conteúdo
    document.getElementById('carregando-detalhes').classList.remove('d-none');
    document.getElementById('conteudo-detalhes').classList.add('d-none');
    
    // Preencher informações básicas
    document.getElementById('nome-ganhador').textContent = nome;
    document.getElementById('usuario-id').textContent = usuarioId;
    document.getElementById('premio-valor').textContent = formatarMoeda(premio);
    
    // Definir o tipo de ganhador
    const tipoEl = document.getElementById('tipo-ganhador');
    if (tipo === 'regular') {
        tipoEl.textContent = 'Apostador';
        tipoEl.className = 'badge bg-success';
    } else {
        tipoEl.textContent = 'Revendedor';
        tipoEl.className = 'badge bg-primary';
    }
    
    // Configurar links de ação
    document.getElementById('link-perfil').href = '../perfil.php?id=' + usuarioId;
    document.getElementById('link-apostas').href = 'gerenciar_apostas.php?usuario_id=' + usuarioId;
    
    // Buscar detalhes adicionais do ganhador via AJAX
    buscarDetalhesGanhador(usuarioId);
}

function fecharModalDetalhes() {
    const modal = document.getElementById('modalDetalhesGanhador');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function buscarDetalhesGanhador(usuarioId) {
    fetch('ajax/buscar_detalhes_ganhador.php?id=' + usuarioId, {
        method: 'GET',
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro ao buscar detalhes do ganhador');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Preencher detalhes do usuário
            if (data.usuario) {
                document.getElementById('email-ganhador').textContent = data.usuario.email || '-';
                document.getElementById('telefone-ganhador').textContent = data.usuario.telefone || '-';
            }
            
            // Preencher detalhes da aposta
            if (data.aposta) {
                document.getElementById('data-aposta').textContent = data.aposta.data_criacao || '-';
                document.getElementById('numeros-apostados').textContent = data.aposta.numeros || '-';
                document.getElementById('valor-apostado').textContent = formatarMoeda(data.aposta.valor_aposta) || '-';
                document.getElementById('status-aposta').textContent = data.aposta.status || '-';
                document.getElementById('jogo-nome').textContent = data.aposta.jogo_nome || '-';
            }
            
            // Preencher estatísticas
            document.getElementById('total-apostas').textContent = data.total_apostas || '0';
            
            // Se for revendedor, mostrar apostas importadas
            if (tipo === 'regular') {
                document.getElementById('container-apostas-importadas').style.display = 'none';
            } else {
                document.getElementById('container-apostas-importadas').style.display = 'block';
                document.getElementById('total-apostas-importadas').textContent = data.total_apostas_importadas || '0';
            }
            
            // Quando terminar de carregar, mostrar conteúdo e esconder carregando
            document.getElementById('carregando-detalhes').classList.add('d-none');
            document.getElementById('conteudo-detalhes').classList.remove('d-none');
        } else {
            // Mostrar mensagem de erro se não conseguir obter os detalhes
            document.getElementById('carregando-detalhes').innerHTML = `
                <div class="alert-danger p-3 rounded">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Não foi possível carregar os detalhes do ganhador.
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        // Mostrar mensagem de erro amigável
        document.getElementById('carregando-detalhes').innerHTML = `
            <div class="alert-danger p-3 rounded">
                <i class="fas fa-exclamation-circle me-2"></i>
                Erro ao carregar detalhes: ${error.message}
            </div>
        `;
    });
}

// Fechar modal ao clicar fora dele
window.addEventListener('click', function(e) {
    const modalDetalhes = document.getElementById('modalDetalhesGanhador');
    if (e.target === modalDetalhes) {
        fecharModalDetalhes();
    }
});
</script>

</body>
</html> 