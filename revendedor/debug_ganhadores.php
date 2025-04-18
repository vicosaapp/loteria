<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo "Acesso não autorizado";
    exit;
}

// Inicializar variáveis
$mensagem = '';
$tipo_mensagem = '';
$logs = [];
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// Executar diagnóstico
if ($acao == 'diagnostico') {
    try {
        // Verificar conexão com o banco
        if (!$pdo) {
            throw new Exception("Falha na conexão com o banco de dados");
        }
        
        // Verificar tabelas necessárias
        $tabelas = [
            'apostas' => [
                'colunas' => ['id', 'usuario_id', 'tipo_jogo_id', 'numeros', 'valor_aposta', 'processado', 'valor_premio', 'concurso', 'status'],
                'existe' => false,
                'colunas_faltantes' => []
            ],
            'concursos' => [
                'colunas' => ['id', 'jogo_id', 'codigo', 'data_sorteio', 'status'],
                'existe' => false, 
                'colunas_faltantes' => []
            ],
            'numeros_sorteados' => [
                'colunas' => ['id', 'concurso_id', 'numero'],
                'existe' => false,
                'colunas_faltantes' => []
            ],
            'valores_jogos' => [
                'colunas' => ['id', 'jogo_id', 'dezenas', 'valor_premio'],
                'existe' => false,
                'colunas_faltantes' => []
            ]
        ];
        
        // Verificar cada tabela
        foreach ($tabelas as $tabela => &$info) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
            $info['existe'] = $stmt->rowCount() > 0;
            
            if ($info['existe']) {
                // Verificar colunas
                $stmt = $pdo->query("DESCRIBE $tabela");
                $colunas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                
                foreach ($info['colunas'] as $coluna) {
                    if (!in_array($coluna, $colunas_existentes)) {
                        $info['colunas_faltantes'][] = $coluna;
                    }
                }
            }
        }
        
        // Verificar concursos finalizados
        $total_concursos = 0;
        $concursos_finalizados = 0;
        
        if ($tabelas['concursos']['existe']) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM concursos");
            $total_concursos = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM concursos WHERE status = 'finalizado'");
            $concursos_finalizados = $stmt->fetchColumn();
        }
        
        // Verificar apostas não processadas
        $total_apostas = 0;
        $apostas_nao_processadas = 0;
        
        if ($tabelas['apostas']['existe']) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM apostas");
            $total_apostas = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM apostas WHERE processado = 0 OR processado IS NULL");
            $apostas_nao_processadas = $stmt->fetchColumn();
        }
        
        // Verificar números sorteados
        $total_numeros_sorteados = 0;
        if ($tabelas['numeros_sorteados']['existe']) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM numeros_sorteados");
            $total_numeros_sorteados = $stmt->fetchColumn();
        }
        
        // Verificar valores de prêmios
        $valores_premios = [];
        if ($tabelas['valores_jogos']['existe']) {
            $stmt = $pdo->query("SELECT jogo_id, COUNT(*) as quantidade FROM valores_jogos GROUP BY jogo_id");
            $valores_premios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        
        // Preparar resultado
        $resultado = [
            'tabelas' => $tabelas,
            'concursos' => [
                'total' => $total_concursos,
                'finalizados' => $concursos_finalizados
            ],
            'apostas' => [
                'total' => $total_apostas,
                'nao_processadas' => $apostas_nao_processadas
            ],
            'numeros_sorteados' => $total_numeros_sorteados,
            'valores_premios' => $valores_premios
        ];
        
        $mensagem = "Diagnóstico concluído com sucesso";
        $tipo_mensagem = "success";
    } catch (Exception $e) {
        $mensagem = "Erro ao realizar diagnóstico: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Processar ganhadores
if ($acao == 'processar') {
    try {
        // URL do endpoint de processamento
        $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/ajax/processar_ganhadores.php';
        
        // Inicializar cURL
        $ch = curl_init();
        
        // Configurar opções do cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id()); // Enviar cookie da sessão
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para ambiente de desenvolvimento
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Para ambiente de desenvolvimento
        
        // Executar a requisição
        $response = curl_exec($ch);
        
        // Verificar erros
        if (curl_errno($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch));
        }
        
        // Obter o código de status HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            throw new Exception("Erro HTTP $httpCode ao acessar o endpoint");
        }
        
        // Fechar a sessão cURL
        curl_close($ch);
        
        // Decodificar resposta
        $resultado = json_decode($response, true);
        
        if (!$resultado) {
            throw new Exception("Erro ao decodificar resposta do servidor: " . $response);
        }
        
        $mensagem = $resultado['message'];
        $tipo_mensagem = ($resultado['status'] == 'success') ? "success" : "danger";
        $logs = $resultado['logs'] ?? [];
    } catch (Exception $e) {
        $mensagem = "Erro ao processar ganhadores: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Teste específico
if ($acao == 'testar_aposta') {
    try {
        // Validar parâmetros
        $aposta_id = isset($_POST['aposta_id']) ? intval($_POST['aposta_id']) : 0;
        $concurso_id = isset($_POST['concurso_id']) ? intval($_POST['concurso_id']) : 0;
        
        if ($aposta_id <= 0 || $concurso_id <= 0) {
            throw new Exception("Parâmetros inválidos");
        }
        
        // Buscar informações da aposta
        $stmt = $pdo->prepare("
            SELECT a.id, a.numeros, a.tipo_jogo_id, j.nome as jogo_nome, u.nome as usuario_nome 
            FROM apostas a 
            JOIN jogos j ON a.tipo_jogo_id = j.id 
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$aposta_id]);
        $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aposta) {
            throw new Exception("Aposta não encontrada");
        }
        
        // Buscar informações do concurso
        $stmt = $pdo->prepare("
            SELECT c.id, c.codigo, c.jogo_id, j.nome as jogo_nome, GROUP_CONCAT(ns.numero ORDER BY ns.numero ASC) as numeros
            FROM concursos c 
            JOIN jogos j ON c.jogo_id = j.id 
            JOIN numeros_sorteados ns ON ns.concurso_id = c.id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$concurso_id]);
        $concurso = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$concurso) {
            throw new Exception("Concurso não encontrado");
        }
        
        // Verificar se o jogo é o mesmo
        if ($aposta['tipo_jogo_id'] != $concurso['jogo_id']) {
            throw new Exception("A aposta e o concurso são de jogos diferentes");
        }
        
        // Verificar acertos
        $numeros_aposta = explode(',', $aposta['numeros']);
        $numeros_concurso = explode(',', $concurso['numeros']);
        
        $numeros_aposta = array_map('intval', array_map('trim', $numeros_aposta));
        $numeros_concurso = array_map('intval', array_map('trim', $numeros_concurso));
        
        $acertos = array_intersect($numeros_aposta, $numeros_concurso);
        $total_acertos = count($acertos);
        
        // Buscar valor do prêmio
        $stmt = $pdo->prepare("SELECT dezenas, valor_premio FROM valores_jogos WHERE jogo_id = ? ORDER BY dezenas DESC");
        $stmt->execute([$concurso['jogo_id']]);
        $premios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $valor_premio = 0;
        foreach ($premios as $dezenas => $premio) {
            if ($total_acertos >= $dezenas) {
                $valor_premio = $premio;
                break;
            }
        }
        
        // Preparar resultado do teste
        $resultado_teste = [
            'aposta' => $aposta,
            'concurso' => $concurso,
            'acertos' => array_values($acertos),
            'total_acertos' => $total_acertos,
            'valor_premio' => $valor_premio
        ];
        
        $mensagem = "Teste concluído. Acertos: $total_acertos, Prêmio: R$ " . number_format($valor_premio, 2, ',', '.');
        $tipo_mensagem = "success";
    } catch (Exception $e) {
        $mensagem = "Erro ao testar aposta: " . $e->getMessage();
        $tipo_mensagem = "danger";
        $resultado_teste = null;
    }
}

// Buscar apostas para testar
$apostas = [];
try {
    $stmt = $pdo->query("
        SELECT a.id, a.numeros, a.data_criacao, u.nome as usuario, j.nome as jogo
        FROM apostas a 
        JOIN usuarios u ON a.usuario_id = u.id
        JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE a.processado = 0 OR a.processado IS NULL
        ORDER BY a.data_criacao DESC
        LIMIT 20
    ");
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silenciar erro
}

// Buscar concursos para testar
$concursos = [];
try {
    $stmt = $pdo->query("
        SELECT c.id, c.codigo, c.data_sorteio, j.nome as jogo 
        FROM concursos c
        JOIN jogos j ON c.jogo_id = j.id
        WHERE c.status = 'finalizado'
        ORDER BY c.data_sorteio DESC
        LIMIT 20
    ");
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silenciar erro
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Ganhadores - Loteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .logs-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .log-item {
            padding: 5px 0;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .log-item:last-child {
            border-bottom: none;
        }
        
        .log-success {
            color: #28a745;
        }
        
        .log-error {
            color: #dc3545;
        }
        
        .log-warning {
            color: #ffc107;
        }
        
        .log-info {
            color: #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Ferramenta de Diagnóstico - Processamento de Ganhadores</h1>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Ferramentas de Diagnóstico</h5>
            </div>
            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <a href="?acao=diagnostico" class="btn btn-outline-primary">
                        <i class="fas fa-search me-2"></i>Verificar Estrutura
                    </a>
                    <a href="?acao=processar" class="btn btn-outline-success">
                        <i class="fas fa-cogs me-2"></i>Processar Ganhadores
                    </a>
                    <a href="ajax/processar_ganhadores.php" target="_blank" class="btn btn-outline-info">
                        <i class="fas fa-code me-2"></i>Ver JSON Direto
                    </a>
                </div>
                
                <?php if ($acao == 'diagnostico' && isset($resultado)): ?>
                    <div class="alert alert-info">
                        <h5>Resultado do Diagnóstico:</h5>
                        
                        <h6 class="mt-3">1. Verificação de Tabelas</h6>
                        <ul>
                            <?php foreach ($resultado['tabelas'] as $tabela => $info): ?>
                                <li>
                                    <strong><?php echo $tabela; ?>:</strong> 
                                    <?php if ($info['existe']): ?>
                                        <span class="text-success">Existe</span>
                                        <?php if (empty($info['colunas_faltantes'])): ?>
                                            <span class="text-success">(Todas as colunas necessárias existem)</span>
                                        <?php else: ?>
                                            <span class="text-warning">(Colunas faltantes: <?php echo implode(', ', $info['colunas_faltantes']); ?>)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-danger">Não existe</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <h6 class="mt-3">2. Contagem de Registros</h6>
                        <ul>
                            <li><strong>Concursos:</strong> <?php echo $resultado['concursos']['total']; ?> (<?php echo $resultado['concursos']['finalizados']; ?> finalizados)</li>
                            <li><strong>Apostas:</strong> <?php echo $resultado['apostas']['total']; ?> (<?php echo $resultado['apostas']['nao_processadas']; ?> não processadas)</li>
                            <li><strong>Números Sorteados:</strong> <?php echo $resultado['numeros_sorteados']; ?></li>
                        </ul>
                        
                        <h6 class="mt-3">3. Valores de Prêmios</h6>
                        <ul>
                            <?php if (empty($resultado['valores_premios'])): ?>
                                <li><span class="text-warning">Nenhum valor de prêmio configurado</span></li>
                            <?php else: ?>
                                <?php foreach ($resultado['valores_premios'] as $jogo_id => $quantidade): ?>
                                    <li><strong>Jogo ID <?php echo $jogo_id; ?>:</strong> <?php echo $quantidade; ?> valores de prêmio configurados</li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        
                        <?php if (!$resultado['tabelas']['apostas']['existe'] || !$resultado['tabelas']['concursos']['existe'] || !$resultado['tabelas']['numeros_sorteados']['existe']): ?>
                            <div class="alert alert-danger mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Uma ou mais tabelas necessárias não existem. O processamento de ganhadores não funcionará.
                            </div>
                        <?php elseif ($resultado['concursos']['finalizados'] == 0): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Não existem concursos finalizados. O processamento de ganhadores não encontrará resultados.
                            </div>
                        <?php elseif ($resultado['apostas']['nao_processadas'] == 0): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Não existem apostas não processadas. O processamento de ganhadores não terá efeito.
                            </div>
                        <?php elseif (empty($resultado['valores_premios'])): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Não existem valores de prêmios configurados. Serão utilizados valores padrão.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-check-circle me-2"></i>
                                Todas as tabelas e dados necessários estão presentes. O processamento de ganhadores deve funcionar corretamente.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($acao == 'processar' && !empty($logs)): ?>
                    <h5 class="mb-3">Logs de Processamento:</h5>
                    <div class="logs-container">
                        <?php foreach ($logs as $log): ?>
                            <div class="log-item <?php 
                                if (strpos($log, 'ERRO') !== false) echo 'log-error';
                                elseif (strpos($log, 'GANHADOR ENCONTRADO') !== false) echo 'log-success';
                                elseif (strpos($log, 'ATENÇÃO') !== false) echo 'log-warning';
                                elseif (strpos($log, 'Processando') !== false) echo 'log-info';
                            ?>">
                                <?php echo htmlspecialchars($log); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-vial me-2"></i>Testar Aposta Específica</h5>
            </div>
            <div class="card-body">
                <form action="?acao=testar_aposta" method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="aposta_id" class="form-label">Aposta</label>
                            <select name="aposta_id" id="aposta_id" class="form-select" required>
                                <option value="">Selecione uma aposta</option>
                                <?php foreach ($apostas as $aposta): ?>
                                    <option value="<?php echo $aposta['id']; ?>">
                                        ID: <?php echo $aposta['id']; ?> - 
                                        <?php echo htmlspecialchars($aposta['usuario']); ?> - 
                                        <?php echo htmlspecialchars($aposta['jogo']); ?> - 
                                        Números: <?php echo htmlspecialchars($aposta['numeros']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="concurso_id" class="form-label">Concurso</label>
                            <select name="concurso_id" id="concurso_id" class="form-select" required>
                                <option value="">Selecione um concurso</option>
                                <?php foreach ($concursos as $concurso): ?>
                                    <option value="<?php echo $concurso['id']; ?>">
                                        ID: <?php echo $concurso['id']; ?> - 
                                        Código: <?php echo $concurso['codigo']; ?> - 
                                        <?php echo htmlspecialchars($concurso['jogo']); ?> - 
                                        Data: <?php echo date('d/m/Y', strtotime($concurso['data_sorteio'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Testar</button>
                </form>
                
                <?php if ($acao == 'testar_aposta' && isset($resultado_teste)): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Resultado do Teste</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Aposta</h6>
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item"><strong>ID:</strong> <?php echo $resultado_teste['aposta']['id']; ?></li>
                                        <li class="list-group-item"><strong>Usuário:</strong> <?php echo htmlspecialchars($resultado_teste['aposta']['usuario_nome']); ?></li>
                                        <li class="list-group-item"><strong>Jogo:</strong> <?php echo htmlspecialchars($resultado_teste['aposta']['jogo_nome']); ?></li>
                                        <li class="list-group-item"><strong>Números apostados:</strong> <?php echo htmlspecialchars($resultado_teste['aposta']['numeros']); ?></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Concurso</h6>
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item"><strong>ID:</strong> <?php echo $resultado_teste['concurso']['id']; ?></li>
                                        <li class="list-group-item"><strong>Código:</strong> <?php echo $resultado_teste['concurso']['codigo']; ?></li>
                                        <li class="list-group-item"><strong>Jogo:</strong> <?php echo htmlspecialchars($resultado_teste['concurso']['jogo_nome']); ?></li>
                                        <li class="list-group-item"><strong>Números sorteados:</strong> <?php echo htmlspecialchars($resultado_teste['concurso']['numeros']); ?></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6>Resultado</h6>
                                <p><strong>Total de acertos:</strong> <?php echo $resultado_teste['total_acertos']; ?></p>
                                <p><strong>Números acertados:</strong> <?php echo implode(', ', $resultado_teste['acertos']); ?></p>
                                <p><strong>Valor do prêmio:</strong> R$ <?php echo number_format($resultado_teste['valor_premio'], 2, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 