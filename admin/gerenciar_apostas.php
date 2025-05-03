<?php
session_start();
require_once '../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'apostas';

$mensagem = '';
$erro = '';

/* Função para obter o nome do revendedor */
function getRevendedorNome($pdo, $revendedor_id) {
    if (empty($revendedor_id)) {
        return 'N/A';
    }
    
    static $cache = [];
    
    // Verificar cache
    if (isset($cache[$revendedor_id])) {
        return $cache[$revendedor_id];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
        $stmt->execute([$revendedor_id]);
        $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($revendedor && !empty($revendedor['nome'])) {
            $cache[$revendedor_id] = htmlspecialchars($revendedor['nome']);
            return $cache[$revendedor_id];
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar revendedor: " . $e->getMessage());
    }
    
    $cache[$revendedor_id] = 'N/A';
    return 'N/A';
}

// Processar ações de aprovação/rejeição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aposta_id = $_POST['aposta_id'];
    $acao = $_POST['acao'];
    
    try {
        if ($acao === 'aprovar') {
            // Buscar dados da aposta
            $stmt = $pdo->prepare("
                SELECT a.*, u.nome, u.email, u.whatsapp 
                FROM apostas a 
                JOIN usuarios u ON a.usuario_id = u.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$aposta_id]);
            $dados_aposta = $stmt->fetch();

            // Gerar nome do comprovante
            $comprovante = 'comprovante_' . $aposta_id . '_' . time() . '.html';
            
            // Criar diretório se não existir
            if (!file_exists('../comprovantes')) {
                mkdir('../comprovantes', 0777, true);
            }
            
            // Criar comprovante em HTML
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Comprovante de Aposta #' . $aposta_id . '</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; margin: 40px; }
                    .comprovante { max-width: 800px; margin: 0 auto; border: 2px solid #007bff; padding: 20px; }
                    .header { text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 20px; margin-bottom: 20px; }
                    .header h1 { color: #007bff; margin: 0; }
                    .info-grupo { margin-bottom: 20px; }
                    .info-grupo h2 { color: #0056b3; }
                    .info-item { margin-bottom: 10px; }
                    .info-label { font-weight: bold; color: #555; }
                    .numeros { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
                    .numero { 
                        width: 40px; height: 40px; 
                        display: flex; align-items: center; justify-content: center;
                        background: #007bff; color: white; 
                        border-radius: 50%; font-weight: bold;
                    }
                    .footer { 
                        margin-top: 30px; text-align: center; 
                        padding-top: 20px; border-top: 1px solid #ddd;
                        color: #666; font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class="comprovante">
                    <div class="header">
                        <h1>Comprovante de Aposta</h1>
                        <p>Sistema de Loteria</p>
                    </div>
                    
                    <div class="info-grupo">
                        <h2>Informações da Aposta</h2>
                        <div class="info-item">
                            <span class="info-label">Número da Aposta:</span>
                            <span>#' . str_pad($aposta_id, 6, '0', STR_PAD_LEFT) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Data:</span>
                            <span>' . date('d/m/Y H:i:s', strtotime($dados_aposta['created_at'])) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span style="color: #2e7d32;">Aprovada</span>
                        </div>
                    </div>
                    
                    <div class="info-grupo">
                        <h2>Dados do Apostador</h2>
                        <div class="info-item">
                            <span class="info-label">Nome:</span>
                            <span>' . htmlspecialchars($dados_aposta['nome']) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span>' . htmlspecialchars($dados_aposta['email']) . '</span>
                        </div>
                        ' . ($dados_aposta['whatsapp'] ? '
                        <div class="info-item">
                            <span class="info-label">WhatsApp:</span>
                            <span>' . htmlspecialchars($dados_aposta['whatsapp']) . '</span>
                        </div>
                        ' : '') . '
                    </div>
                    
                    <div class="info-grupo">
                        <h2>Números Apostados</h2>
                        <div class="numeros">
                            ' . implode('', array_map(function($num) {
                                return '<div class="numero">' . trim($num) . '</div>';
                            }, explode(',', $dados_aposta['numeros']))) . '
                        </div>
                    </div>
                    
                    <div class="footer">
                        <p>Este comprovante é válido como confirmação da sua aposta.</p>
                        <p>Data de emissão: ' . date('d/m/Y H:i:s') . '</p>
                    </div>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                    }
                </script>
            </body>
            </html>';
            
            file_put_contents('../comprovantes/' . $comprovante, $html);
            
            $stmt = $pdo->prepare("UPDATE apostas SET status = 'aprovada', comprovante_path = ? WHERE id = ?");
            $stmt->execute([$comprovante, $aposta_id]);
            $mensagem = "Aposta aprovada com sucesso!";
        } else if ($acao === 'rejeitar') {
            $stmt = $pdo->prepare("UPDATE apostas SET status = 'rejeitada' WHERE id = ?");
            $stmt->execute([$aposta_id]);
            $mensagem = "Aposta rejeitada com sucesso!";
        }
    } catch(PDOException $e) {
        $erro = "Erro ao processar ação: " . $e->getMessage();
    }
}

// Construir condições de filtro
$whereConditions = [];
$params = [];

// Filtro por data
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $whereConditions[] = "DATE(a.data_aposta) >= ?";
    $params[] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $whereConditions[] = "DATE(a.data_aposta) <= ?";
    $params[] = $_GET['data_fim'];
}

// Filtro por revendedor
if (isset($_GET['revendedor']) && !empty($_GET['revendedor'])) {
    $whereConditions[] = "r.id = ?";
    $params[] = $_GET['revendedor'];
}

// Filtro por jogo
if (isset($_GET['jogo']) && !empty($_GET['jogo'])) {
    $whereConditions[] = "(j.id = ? OR j.nome LIKE ?)";
    $params[] = $_GET['jogo'];
    $params[] = '%' . $_GET['jogo'] . '%';
}

// Filtro por status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $whereConditions[] = "a.status = ?";
    $params[] = $_GET['status'];
}

// Filtro por valor
if (isset($_GET['valor_min']) && !empty($_GET['valor_min'])) {
    $whereConditions[] = "a.valor >= ?";
    $params[] = $_GET['valor_min']; // Valores já estão em reais, não precisamos multiplicar por 100
}

if (isset($_GET['valor_max']) && !empty($_GET['valor_max'])) {
    $whereConditions[] = "a.valor <= ?";
    $params[] = $_GET['valor_max']; // Valores já estão em reais, não precisamos multiplicar por 100
}

// Filtro por apostador
if (isset($_GET['apostador']) && !empty($_GET['apostador'])) {
    $whereConditions[] = "u.nome LIKE ?";
    $params[] = '%' . $_GET['apostador'] . '%';
}

// Montar a cláusula WHERE
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(' AND ', $whereConditions);
}

// Buscar todas as apostas sem agrupamento
$sql = "
    WITH todas_apostas AS (
        -- Apostas normais
        (SELECT 
            a.id,
            'normal' as tipo_aposta,
            a.numeros,
            a.valor_aposta as valor,
            a.created_at as data_aposta,
            a.usuario_id,
            a.status,
            u.nome as apostador_nome,
            u.whatsapp as apostador_whatsapp,
            u.telefone as apostador_telefone,
            COALESCE(a.revendedor_id, u.revendedor_id) as revendedor_id,
            r_final.nome as revendedor_nome,
            j.id as jogo_id,
            j.nome as jogo_nome,
            j.nome as jogo_nome_original,
            a.valor_premio
        FROM apostas a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN usuarios r ON a.revendedor_id = r.id AND r.tipo = 'revendedor'
        LEFT JOIN usuarios r2 ON u.revendedor_id = r2.id AND r2.tipo = 'revendedor'
        -- Aqui pegamos o revendedor final (da aposta ou do usuário)
        LEFT JOIN usuarios r_final ON COALESCE(a.revendedor_id, u.revendedor_id) = r_final.id AND r_final.tipo = 'revendedor'
        LEFT JOIN jogos j ON a.tipo_jogo_id = j.id)
        
        UNION ALL
        
        -- Apostas importadas
        (SELECT 
            ai.id,
            'importada' as tipo_aposta,
            ai.numeros,
            ai.valor_aposta as valor,
            ai.created_at as data_aposta,
            ai.usuario_id,
            'aprovada' as status,
            u.nome as apostador_nome,
            COALESCE(ai.whatsapp, u.whatsapp) as apostador_whatsapp,
            u.telefone as apostador_telefone,
            COALESCE(ai.revendedor_id, u.revendedor_id) as revendedor_id,
            r_final.nome as revendedor_nome,
            j.id as jogo_id,
            COALESCE(j.nome, ai.jogo_nome) as jogo_nome,
            ai.jogo_nome as jogo_nome_original,
            ai.valor_premio
        FROM apostas_importadas ai
        LEFT JOIN usuarios u ON ai.usuario_id = u.id
        LEFT JOIN usuarios r ON ai.revendedor_id = r.id AND r.tipo = 'revendedor'
        LEFT JOIN usuarios r2 ON u.revendedor_id = r2.id AND r2.tipo = 'revendedor'
        -- Aqui pegamos o revendedor final (da aposta importada ou do usuário)
        LEFT JOIN usuarios r_final ON COALESCE(ai.revendedor_id, u.revendedor_id) = r_final.id AND r_final.tipo = 'revendedor'
        LEFT JOIN jogos j ON (
            CASE 
                WHEN ai.jogo_nome LIKE '%MS' THEN 'Mega Sena'
                WHEN ai.jogo_nome LIKE '%LF' THEN 'LotoFácil'
                ELSE ai.jogo_nome 
            END = j.nome
        )
        LEFT JOIN valores_jogos vj ON (
            j.id = vj.jogo_id 
            AND vj.dezenas = (LENGTH(ai.numeros) - LENGTH(REPLACE(ai.numeros, ' ', '')) + 1)
            AND vj.valor_aposta = ai.valor_aposta
        ))
    )
    SELECT 
        id,
        tipo_aposta,
        numeros,
        valor,
        data_aposta,
        usuario_id,
        status,
        apostador_nome,
        apostador_whatsapp,
        apostador_telefone,
        revendedor_id,
        revendedor_nome,
        jogo_id,
        jogo_nome,
        jogo_nome_original,
        valor_premio
    FROM todas_apostas a
    " . $whereClause . "
    ORDER BY apostador_nome ASC, data_aposta DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Não precisamos mais forçar a atualização dos nomes dos revendedores aqui,
// pois já estamos fazendo isso adequadamente no SQL com a junção r_final
// Vamos apenas tratar os casos em que o nome não foi encontrado

foreach ($apostas as $key => $aposta) {
    if (empty($aposta['revendedor_nome']) && !empty($aposta['revendedor_id'])) {
        // Se temos um ID de revendedor mas o nome não foi preenchido pelo JOIN, tentamos buscar novamente
        $stmt_rev = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
        $stmt_rev->execute([$aposta['revendedor_id']]);
        $revendedor = $stmt_rev->fetch(PDO::FETCH_ASSOC);
        
        if ($revendedor && !empty($revendedor['nome'])) {
            $apostas[$key]['revendedor_nome'] = $revendedor['nome'];
        } else {
            $apostas[$key]['revendedor_nome'] = "N/A";
        }
    } elseif (empty($aposta['revendedor_id'])) {
        // Se não temos um revendedor_id, configuramos explicitamente como "Direto"
        $apostas[$key]['revendedor_nome'] = "N/A";
    }
}

// Debug
error_log("Consulta de apostas: " . $sql);
error_log("Parâmetros da consulta: " . json_encode($params));
error_log("Total de apostas encontradas: " . count($apostas));

// Debug dos revendedores após a correção
error_log("------ APÓS CORREÇÃO DOS REVENDEDORES ------");
foreach ($apostas as $aposta) {
    error_log("Aposta #" . $aposta['id'] . 
              " - Revendedor ID: " . ($aposta['revendedor_id'] ?? 'NULL') . 
              " - Nome: " . ($aposta['revendedor_nome'] ?? 'NULL') . 
              " - Apostador: " . ($aposta['apostador_nome'] ?? 'NULL'));
}

// Verificar revendedores diretamente
$debug_stmt = $pdo->query("
    SELECT a.id, a.revendedor_id, u.nome as revendedor_nome
    FROM apostas a
    LEFT JOIN usuarios u ON a.revendedor_id = u.id
    WHERE a.revendedor_id IS NOT NULL
    LIMIT 5
");
$debug_revendedores = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("------ VERIFICAÇÃO DIRETA DE REVENDEDORES ------");
foreach ($debug_revendedores as $rev) {
    error_log("Aposta #" . $rev['id'] . " - Revendedor ID: " . ($rev['revendedor_id'] ?? 'NULL') . " - Nome: " . ($rev['revendedor_nome'] ?? 'NULL'));
}

// Verificar revendedores nas apostas importadas
$debug_stmt = $pdo->query("
    SELECT ai.id, ai.revendedor_id, u.nome as revendedor_nome
    FROM apostas_importadas ai
    LEFT JOIN usuarios u ON ai.revendedor_id = u.id
    WHERE ai.revendedor_id IS NOT NULL
    LIMIT 5
");
$debug_revendedores_imp = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("------ VERIFICAÇÃO DIRETA DE REVENDEDORES (IMPORTADAS) ------");
foreach ($debug_revendedores_imp as $rev) {
    error_log("Aposta Importada #" . $rev['id'] . " - Revendedor ID: " . ($rev['revendedor_id'] ?? 'NULL') . " - Nome: " . ($rev['revendedor_nome'] ?? 'NULL'));
}

// Verificar todos os revendedores disponíveis no sistema
$debug_stmt = $pdo->query("
    SELECT id, nome, tipo, status
    FROM usuarios 
    WHERE tipo = 'revendedor'
    ORDER BY nome
    LIMIT 10
");
$debug_todos_revendedores = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("------ TODOS OS REVENDEDORES DISPONÍVEIS ------");
foreach ($debug_todos_revendedores as $rev) {
    error_log("Revendedor #" . $rev['id'] . " - Nome: " . ($rev['nome'] ?? 'NULL') . " - Status: " . ($rev['status'] ?? 'NULL'));
}

// Verificar quais apostas têm revendedor_id definido
$debug_stmt = $pdo->query("
    SELECT COUNT(*) as total_com_revendedor
    FROM apostas
    WHERE revendedor_id IS NOT NULL
");
$debug_contagem = $debug_stmt->fetch(PDO::FETCH_ASSOC);
error_log("------ CONTAGEM DE APOSTAS COM REVENDEDOR ------");
error_log("Total de apostas com revendedor_id: " . $debug_contagem['total_com_revendedor']);

// Inicia o buffer de saída
ob_start();
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">
        <i class="fas fa-ticket-alt"></i> Gerenciar Apostas
    </h1>

    <?php if ($mensagem): ?>
        <div class="alert alert-success">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger">
            <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <!-- Painel de Filtros Avançados -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Filtros Avançados</h6>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilters" aria-expanded="false" aria-controls="collapseFilters">
                <i class="fas fa-filter"></i> Mostrar/Ocultar Filtros
            </button>
        </div>
        <div class="collapse" id="collapseFilters">
            <div class="card-body">
                <form id="filterForm" method="GET" class="mb-0">
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label for="data_inicio">Data Inicial</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo isset($_GET['data_inicio']) ? $_GET['data_inicio'] : ''; ?>">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label for="data_fim">Data Final</label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo isset($_GET['data_fim']) ? $_GET['data_fim'] : ''; ?>">
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label for="valor_min">Valor Mínimo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">R$</span>
                                </div>
                                <input type="number" step="0.01" min="0" class="form-control" id="valor_min" name="valor_min" value="<?php echo isset($_GET['valor_min']) ? $_GET['valor_min'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label for="valor_max">Valor Máximo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">R$</span>
                                </div>
                                <input type="number" step="0.01" min="0" class="form-control" id="valor_max" name="valor_max" value="<?php echo isset($_GET['valor_max']) ? $_GET['valor_max'] : ''; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label for="revendedor">Revendedor</label>
                            <select class="form-control" id="revendedor" name="revendedor">
                                <option value="">Todos os Revendedores</option>
                                <?php 
                                // Buscar revendedores
                                $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'revendedor' ORDER BY nome");
                                $revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($revendedores as $revendedor): 
                                    $selected = (isset($_GET['revendedor']) && $_GET['revendedor'] == $revendedor['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $revendedor['id']; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($revendedor['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label for="jogo">Jogo</label>
                            <select class="form-control" id="jogo" name="jogo">
                                <option value="">Todos os Jogos</option>
                                <?php 
                                // Buscar jogos
                                $stmt = $pdo->query("SELECT id, nome FROM jogos ORDER BY nome");
                                $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($jogos as $jogo): 
                                    $selected = (isset($_GET['jogo']) && $_GET['jogo'] == $jogo['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $jogo['id']; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($jogo['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="pendente" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                <option value="aprovada" <?php echo (isset($_GET['status']) && $_GET['status'] == 'aprovada') ? 'selected' : ''; ?>>Aprovada</option>
                                <option value="rejeitada" <?php echo (isset($_GET['status']) && $_GET['status'] == 'rejeitada') ? 'selected' : ''; ?>>Rejeitada</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <label for="apostador">Apostador</label>
                            <input type="text" class="form-control" id="apostador" name="apostador" placeholder="Nome do apostador" value="<?php echo isset($_GET['apostador']) ? htmlspecialchars($_GET['apostador']) : ''; ?>">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="gerenciar_apostas.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-undo"></i> Limpar Filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="numerosModal" tabindex="-1" role="dialog" aria-labelledby="numerosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="numerosModalLabel">
                        <i class="fas fa-ticket-alt"></i> BILHETE DE APOSTAS
                    </h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="apostasContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel de resumo -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Apostas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($apostas); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Valor Total das Apostas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ <?php 
                                    $valor_total = 0;
                                    foreach ($apostas as $aposta) {
                                        $valor_total += floatval($aposta['valor']);
                                    }
                                    echo number_format($valor_total, 2, ',', '.'); 
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Potencial de Premiação</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ <?php 
                                    $premio_total = 0;
                                    foreach ($apostas as $aposta) {
                                        $premio_total += floatval($aposta['valor_premio']);
                                    }
                                    echo number_format($premio_total, 2, ',', '.'); 
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-trophy fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Apostadores Únicos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                    $apostadores_unicos = [];
                                    foreach ($apostas as $aposta) {
                                        if (!empty($aposta['usuario_id']) && !in_array($aposta['usuario_id'], $apostadores_unicos)) {
                                            $apostadores_unicos[] = $aposta['usuario_id'];
                                        }
                                    }
                                    echo count($apostadores_unicos);
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Apostas Registradas</h6>
            <a href="importar_apostas.php" class="btn btn-primary btn-sm">
                <i class="fas fa-file-import"></i> Importar Apostas
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered nowrap" id="apostasTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>ID</th>
                            <th>Apostador</th>
                            <th>Jogo</th>
                            <th>Números</th>
                            <th>Contato</th>
                            <th>Revendedor</th>
                            <th>Valor</th>
                            <th>Premiação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $last_apostador_id = null;
                        $row_class = '';
                        foreach ($apostas as $aposta): 
                            // Alternar classe para destacar apostadores diferentes
                            if ($last_apostador_id !== $aposta['usuario_id']) {
                                $last_apostador_id = $aposta['usuario_id'];
                                $row_class = ($row_class == 'apostador-par') ? 'apostador-impar' : 'apostador-par';
                            }
                        ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td data-order="<?php echo strtotime($aposta['data_aposta']); ?>"><?php echo date('d/m/Y H:i', strtotime($aposta['data_aposta'])); ?></td>
                                <td><?php echo $aposta['id']; ?></td>
                                <td><?php echo htmlspecialchars($aposta['apostador_nome'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($aposta['jogo_nome'] ?? 'Normal'); ?></td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-info btn-sm" 
                                            onclick="verNumeros('<?php echo htmlspecialchars($aposta['numeros']); ?>', '<?php echo htmlspecialchars($aposta['apostador_nome']); ?>', '<?php echo htmlspecialchars($aposta['jogo_nome']); ?>', '<?php echo htmlspecialchars($aposta['revendedor_nome'] ?? ''); ?>')">
                                        <i class="fas fa-eye"></i> Ver números
                                    </button>
                                </td>
                                <td>
                                    <?php if (!empty($aposta['apostador_whatsapp'])): ?>
                                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($aposta['apostador_whatsapp']); ?></span>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $aposta['apostador_whatsapp']); ?>" 
                                           class="whatsapp-link" 
                                           target="_blank"
                                           data-bs-toggle="tooltip"
                                           title="<?php echo htmlspecialchars($aposta['apostador_whatsapp']); ?>">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($aposta['apostador_telefone'] ?? 'N/A'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($aposta['revendedor_nome'])) {
                                        echo '<span class="badge bg-primary revendedor-badge">' . htmlspecialchars($aposta['revendedor_nome']) . '</span>';
                                    } else if (!empty($aposta['revendedor_id'])) {
                                        // Tenta buscar o nome do revendedor diretamente
                                        $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
                                        $stmt->execute([$aposta['revendedor_id']]);
                                        $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        if ($revendedor && !empty($revendedor['nome'])) {
                                            echo '<span class="badge bg-primary revendedor-badge">' . htmlspecialchars($revendedor['nome']) . '</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Sem Rev.</span>';
                                        }
                                    } else {
                                        echo '<span class="badge bg-secondary">Direto</span>';
                                    }
                                    ?>
                                </td>
                                <td data-order="<?php echo floatval($aposta['valor']); ?>">
                                    R$ <?php echo number_format($aposta['valor'], 2, ',', '.'); ?>
                                </td>
                                <td data-order="<?php echo floatval($aposta['valor_premio']); ?>">
                                    R$ <?php echo number_format($aposta['valor_premio'], 2, ',', '.'); ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-success btn-sm" 
                                                onclick="gerarComprovante(<?php echo $aposta['id']; ?>, '<?php echo htmlspecialchars($aposta['jogo_nome'] ?? ''); ?>')">
                                            <i class="fas fa-file-pdf d-block d-md-none"></i>
                                            <span class="d-none d-md-inline"><i class="fas fa-file-pdf"></i> Comprovante</span>
                                        </button>
                                        <a href="#" onclick="return excluirAposta(<?php echo $aposta['id']; ?>, '<?php echo htmlspecialchars($aposta['apostador_nome']); ?>')" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash d-block d-md-none"></i>
                                            <span class="d-none d-md-inline"><i class="fas fa-trash"></i></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.apostas-container {
    background: white;
    padding: 140px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
}

.btn-whatsapp {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #25D366;
    color: white;
    border-radius: 50%;
    margin-left: 5px;
    text-decoration: none;
}

.btn-whatsapp:hover {
    background: #128C7E;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.pendente {
    background: #ffc107;
    color: #000;
}

.status-badge.aprovada {
    background: #28a745;
    color: white;
}

.status-badge.rejeitada {
    background: #dc3545;
    color: white;
}

.numeros-apostados {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.numero {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #007bff;
    color: white;
    border-radius: 50%;
    font-size: 12px;
}

.btn {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
}

.btn-sm {
    padding: 2px 6px;
    font-size: 12px;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.no-data-message {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.no-data-message i {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
}

.no-data-message p {
    font-size: 16px;
    margin: 0;
}

.numero-bola {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: #007bff;
    color: white;
    border-radius: 50%;
    margin: 0 2px;
    font-size: 0.85rem;
}

.whatsapp-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #25d366;
    color: white !important;
    border-radius: 50%;
    margin-left: 8px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.4);
}

.whatsapp-link:hover {
    transform: scale(1.15);
    background: #128c7e;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.6);
}

.whatsapp-link i {
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));
}

/* Animação de pulso */
@keyframes whatsappPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}

.whatsapp-link {
    animation: whatsappPulse 2s infinite;
}

.whatsapp-link:hover {
    animation: none;
}

.badge {
    padding: 5px 10px;
    border-radius: 4px;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.table td {
    vertical-align: middle;
}

.badge.bg-success {
    background: #00ff66 !important; /* Verde fluorescente */
    color: #000 !important; /* Texto preto para melhor contraste */
    font-weight: 600;
    text-shadow: 0 0 10px rgba(0,255,102,0.5);
    box-shadow: 0 0 10px rgba(0,255,102,0.3);
}

.badge.bg-danger {
    background: #ff0000 !important; /* Vermelho vivo */
    color: #fff !important;
    font-weight: 600;
    text-shadow: 0 0 10px rgba(255,0,0,0.5);
    box-shadow: 0 0 10px rgba(255,0,0,0.3);
}

.badge.bg-warning {
    background: #ffd700 !important; /* Amarelo para pendente */
    color: #000 !important;
    font-weight: 600;
}

/* Efeito hover suave */
.badge {
    transition: all 0.3s ease;
}

.badge:hover {
    transform: scale(1.05);
}

.apostas-list {
    max-height: 400px;
    overflow-y: auto;
}

.aposta-item {
    padding: 10px;
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.aposta-item:last-child {
    border-bottom: none;
}

.numeros {
    font-family: monospace;
    font-size: 1.1em;
    margin-top: 5px;
}

.table th {
    background-color: #4e73df;
    color: white;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.btn-success {
    background-color: #1cc88a;
    border-color: #1cc88a;
}

.btn-success:hover {
    background-color: #169b6b;
    border-color: #169b6b;
}

.whatsapp-btn {
    background-color: #25d366;
    border-color: #25d366;
    color: white;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.whatsapp-btn:hover {
    background-color: #128c7e;
    border-color: #128c7e;
    color: white;
}

.whatsapp-btn i {
    font-size: 1.2em;
}

.modal-header .close {
    padding: 1rem;
    margin: -1rem -1rem -1rem auto;
    cursor: pointer;
}

.modal-header .close:hover {
    opacity: 0.7;
}

.modal-footer {
    border-top: none;
    padding: 1rem;
    display: flex;
    justify-content: center;
}

.modal-footer .btn-secondary {
    background-color: #6c757d;
    color: white;
    padding: 8px 20px;
    border-radius: 5px;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.modal-footer .btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

.modal-header .close {
    color: white;
    opacity: 1;
    text-shadow: none;
    background: transparent;
    border: none;
    padding: 1rem;
    margin: -1rem -1rem -1rem auto;
}

.modal-header .close:hover {
    opacity: 0.8;
}

.numeros-grid {
    display: inline-flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 8px;
    padding: 10px;
    overflow-x: auto;
    width: 100%;
    background: #f8f9fa;
    border-radius: 5px;
    padding: 15px;
}

.numero-bola {
    width: 40px;
    height: 40px;
    min-width: 40px;
    background: #1a237e;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
    margin-right: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.aposta-section {
    background: white;
    border-radius: 10px;
    padding: 15px;
    border: 1px solid #e0e0e0;
    margin-bottom: 15px;
}

.modal-dialog {
    max-width: 800px;
    width: 95%;
    margin: 1.75rem auto;
}

@media (max-width: 768px) {
    .numeros-grid {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .numero-bola {
        width: 35px;
        height: 35px;
        min-width: 35px;
        font-size: 1rem;
    }
}

/* Estilos do Modal */
.ticket-style {
    background: #fff;
    border-radius: 15px;
    border: 2px solid #1a237e;
}

.modal-header {
    border-bottom: none;
    padding: 1rem;
}

.ticket-header {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo-area i {
    font-size: 2rem;
    color: #1a237e;
}

.logo-area h5 {
    font-size: 1.5rem;
    font-weight: bold;
    color: #1a237e;
    margin: 0;
}

/* Conteúdo do Ticket */
.ticket-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
}

.ticket-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    margin-bottom: 10px;
    padding: 5px 0;
    border-bottom: 1px dashed #ccc;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: bold;
    color: #1a237e;
    width: 120px;
}

.info-value {
    flex: 1;
    color: #333;
}

.ticket-separator {
    text-align: center;
    margin: 20px 0;
    position: relative;
}

.ticket-separator:before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    border-top: 2px dashed #1a237e;
    z-index: 1;
}

.separator-text {
    background: white;
    padding: 0 15px;
    color: #1a237e;
    font-weight: bold;
    position: relative;
    z-index: 2;
    display: inline-block;
}

.ticket-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px dashed #1a237e;
}

.footer-text {
    font-size: 1.5rem;
    font-weight: bold;
    color: #1a237e;
    margin-bottom: 10px;
}

.ticket-id {
    color: #666;
    font-size: 0.9rem;
}

/* Ajustes para telas menores */
@media (max-width: 768px) {
    .numero-bola {
        width: 35px;
        height: 35px;
        min-width: 35px;
        font-size: 1rem;
    }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-label {
        width: 100%;
        margin-bottom: 5px;
    }
}

.numeros-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.numero-bola {
    width: 40px;
    height: 40px;
    background: #1a237e;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.aposta-section {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border: 1px solid #e3e6f0;
    transition: all 0.3s ease;
}

.aposta-section:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.aposta-header {
    border-bottom: 1px solid #e3e6f0;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.aposta-title {
    font-size: 1.1rem;
    font-weight: 600;
}

.numeros-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
}

.numero-bola {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #4e73df, #3961cd);
    color: white;
    font-weight: bold;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.numero-bola:hover {
    transform: scale(1.1);
    box-shadow: 0 5px 10px rgba(0,0,0,0.3);
}

.ticket-separator {
    position: relative;
    text-align: center;
    margin: 25px 0;
}

.ticket-separator:before {
    content: "";
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e3e6f0;
    z-index: 1;
}

.separator-text {
    background: white;
    padding: 0 15px;
    position: relative;
    z-index: 2;
    color: #4e73df;
    font-weight: 600;
    display: inline-block;
}

.ticket-footer {
    margin-top: 25px;
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e3e6f0;
}

.footer-text {
    font-size: 1.2rem;
    font-weight: bold;
    color: #4e73df;
    margin-bottom: 5px;
}

.modal-dialog {
    max-width: 800px;
}

@media (max-width: 576px) {
    .numero-bola {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    .numeros-container {
        gap: 6px;
    }
    
    .aposta-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .aposta-details {
        margin-top: 5px;
    }
}

.apostador-par {
    background-color: rgba(238, 242, 255, 0.3);
}

.apostador-impar {
    background-color: rgba(220, 230, 255, 0.5);
}

tr.apostador-par:hover, tr.apostador-impar:hover {
    background-color: rgba(200, 215, 255, 0.7);
    transition: background-color 0.3s ease;
}

.numeros-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
    max-height: 300px;
    overflow-y: auto;
}

/* Melhoria da visualização do modal */
.ticket-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    border-left: 4px solid #4e73df;
}

/* Estilos para o agrupamento de linhas do DataTable */
.dtrg-group {
    background-color: #4e73df !important;
    color: white !important;
    font-weight: bold !important;
    padding: 8px 15px !important;
}

.dtrg-group td {
    text-shadow: 0 1px 1px rgba(0,0,0,0.3);
}

.dtrg-level-0 {
    font-size: 1.1em !important;
    text-transform: uppercase;
}

tr.dtrg-group:hover {
    background-color: #3a5ccc !important;
    cursor: pointer;
}

.revendedor-badge {
    background-color: #4e73df !important;
    color: white !important;
    font-weight: bold !important;
    padding: 6px 10px !important;
    border-radius: 5px !important;
    font-size: 0.85rem !important;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important;
    transition: all 0.3s ease;
}

.revendedor-badge:hover {
    background-color: #3a5ccc !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function aprovarAposta(id) {
    Swal.fire({
        title: 'Confirmar aprovação',
        text: 'Deseja realmente aprovar esta aposta?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#00ff66',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, aprovar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Processando...',
                text: 'Aguarde enquanto a aposta é aprovada',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fazer requisição AJAX
            fetch('ajax/atualizar_status_aposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    status: 'aprovada'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Aposta aprovada com sucesso!',
                        icon: 'success',
                        confirmButtonColor: '#00ff66'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao aprovar aposta');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Erro!',
                    text: error.message || 'Ocorreu um erro ao aprovar a aposta',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}

function rejeitarAposta(id) {
    Swal.fire({
        title: 'Confirmar rejeição',
        text: 'Deseja realmente rejeitar esta aposta?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, rejeitar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Processando...',
                text: 'Aguarde enquanto a aposta é rejeitada',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fazer requisição AJAX
            fetch('ajax/atualizar_status_aposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    status: 'rejeitada'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Concluído!',
                        text: 'Aposta rejeitada com sucesso!',
                        icon: 'success',
                        confirmButtonColor: '#d33'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao rejeitar aposta');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Erro!',
                    text: error.message || 'Ocorreu um erro ao rejeitar a aposta',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}

function excluirAposta(id, apostadorNome) {
    if (!id) {
        alert('Dados inválidos para exclusão');
        return false;
    }

    if (confirm(`Deseja excluir a aposta de ${apostadorNome}?`)) {
        fetch('ajax/excluir_aposta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                aposta_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Aposta excluída com sucesso!');
                window.location.reload();
            } else {
                throw new Error(data.error || 'Erro ao excluir aposta');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir aposta: ' + error.message);
        });
    }
    
    return false;
}

$(document).ready(function() {
    var table = $('#apostasTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
        },
        "order": [[2, "asc"], [0, "desc"]],
        "responsive": true,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        "pageLength": 25,
        "columnDefs": [
            { "responsivePriority": 1, "targets": [0, 2, 7, 9] },
            { "responsivePriority": 2, "targets": [3, 4, 8] },
            { "responsivePriority": 3, "targets": [1, 5, 6] },
            { 
                "targets": 0,
                "render": function (data, type, row) {
                    return type === 'display' ? 
                        '<span title="' + data + '">' + data.split(' ')[0] + '</span>' : 
                        data;
                }
            }
        ],
        "dom": '<"row mb-3"<"col-md-6"l><"col-md-6"f>>' +
               '<"row"<"col-md-12"t>>' +
               '<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
        "stateSave": true,
        "stateDuration": 60 * 60 * 24,
        "rowGroup": {
            "dataSrc": 2,
            "startRender": function(rows, group) {
                var totalApostas = rows.count();
                var valorTotal = 0;
                var premioTotal = 0;
                var revendedor = '';
                
                rows.data().each(function(data) {
                    var valor = parseFloat(data[7].replace(/[^\d,]/g, '').replace(',', '.'));
                    var premio = parseFloat(data[8].replace(/[^\d,]/g, '').replace(',', '.'));
                    
                    if (!revendedor && data[6]) {
                        revendedor = data[6];
                    }
                    
                    valorTotal += valor;
                    premioTotal += premio;
                });
                
                var revendedorInfo = revendedor ? ' | Revendedor: <strong>' + revendedor + '</strong>' : '';
                
                return $('<tr class="group"/>')
                    .append('<td colspan="10">Apostador: <strong>' + group + '</strong>' + revendedorInfo + ' | ' +
                           totalApostas + ' apostas | ' +
                           'Valor: R$ ' + valorTotal.toFixed(2).replace('.', ',') + ' | ' +
                           'Potencial Prêmio: R$ ' + premioTotal.toFixed(2).replace('.', ',') + '</td>');
            }
        }
    });
    
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    var collapseElementList = [].slice.call(document.querySelectorAll('.collapse'));
    var collapseList = collapseElementList.map(function (collapseEl) {
        return new bootstrap.Collapse(collapseEl, {
            toggle: false
        });
    });
    
    if (
        <?php echo 
        (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) || 
        (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) || 
        (isset($_GET['revendedor']) && !empty($_GET['revendedor'])) ||
        (isset($_GET['jogo']) && !empty($_GET['jogo'])) ||
        (isset($_GET['status']) && !empty($_GET['status'])) ||
        (isset($_GET['valor_min']) && !empty($_GET['valor_min'])) ||
        (isset($_GET['valor_max']) && !empty($_GET['valor_max'])) ||
        (isset($_GET['apostador']) && !empty($_GET['apostador'])) 
        ? 'true' : 'false'; 
        ?>
    ) {
        var myCollapse = document.getElementById('collapseFilters');
        var bsCollapse = new bootstrap.Collapse(myCollapse, {
            toggle: true
        });
    }
});

function verNumeros(numerosStr, apostador, jogoNome, revendedorNome) {
    console.log('Números recebidos:', numerosStr);
    
    const numeros = numerosStr
        .trim()
        .split(/[\s,]+/)
        .map(num => parseInt(num.trim()))
        .filter(num => !isNaN(num))
        .sort((a, b) => a - b);
    
    console.log('Números processados:', numeros);
    
    let html = `
        <div class="ticket-content">
            <div class="ticket-info">
                <div class="info-row">
                    <span class="info-label">APOSTADOR:</span>
                    <span class="info-value">${apostador || 'Não Informado'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">JOGO:</span>
                    <span class="info-value">${jogoNome || 'Normal'}</span>
                </div>
                ${revendedorNome ? `
                <div class="info-row">
                    <span class="info-label">REVENDEDOR:</span>
                    <span class="info-value revendedor-valor">${revendedorNome}</span>
                </div>
                ` : ''}
                <div class="info-row">
                    <span class="info-label">DATA:</span>
                    <span class="info-value">${new Date().toLocaleDateString('pt-BR')}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">TOTAL DE NÚMEROS:</span>
                    <span class="info-value">${numeros.length}</span>
                </div>
            </div>

            <div class="ticket-separator">
                <div class="separator-text">NÚMEROS APOSTADOS</div>
            </div>

            <div class="numeros-container">
                ${numeros.map(numero => `
                    <div class="numero-bola">${String(numero).padStart(2, '0')}</div>
                `).join('')}
            </div>
            
            <div class="ticket-footer">
                <div class="footer-text">BOA SORTE!</div>
            </div>
        </div>
    `;
    
    document.getElementById('apostasContainer').innerHTML = html;
    
    var numerosModal = new bootstrap.Modal(document.getElementById('numerosModal'));
    numerosModal.show();
}

function fecharModal() {
    var numerosModal = bootstrap.Modal.getInstance(document.getElementById('numerosModal'));
    if (numerosModal) {
        numerosModal.hide();
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        fecharModal();
    }
});

function gerarComprovante(usuarioId, jogoNome) {
    window.open(`gerar_comprovante.php?usuario_id=${usuarioId}&jogo=${encodeURIComponent(jogoNome)}`, '_blank');
}
</script>

<div class="position-fixed bottom-0 right-0 p-3" style="z-index: 5; right: 0; bottom: 0;">
    <div id="notificationToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="toast-header bg-primary text-white">
            <i class="fas fa-bell me-2"></i>
            <strong class="me-auto" id="notificationTitle">Notificação</strong>
            <small id="notificationTime">Agora</small>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="notificationBody">
            Você tem novas apostas para revisar.
        </div>
    </div>
</div>

<script>
let lastAposta = <?php echo !empty($apostas) ? json_encode([
    'id' => $apostas[0]['usuario_id'], 
    'time' => strtotime($apostas[0]['data_aposta'])
]) : 'null'; ?>;

function verificarNovasApostas() {
    $.ajax({
        url: 'ajax/verificar_novas_apostas.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.hasNewApostas) {
                if (lastAposta === null || response.lastAposta.time > lastAposta.time) {
                    lastAposta = response.lastAposta;
                    
                    $('#notificationTitle').text('Nova Aposta');
                    $('#notificationBody').html(
                        `Nova aposta de <strong>${response.lastAposta.jogo}</strong> registrada por <strong>${response.lastAposta.revendedor}</strong> para <strong>${response.lastAposta.apostador}</strong><br>` +
                        `Valor: R$ ${response.lastAposta.valor} | Prêmio: R$ ${response.lastAposta.valor_premio}`
                    );
                    
                    var toastEl = document.getElementById('notificationToast');
                    var notificationToast = new bootstrap.Toast(toastEl);
                    notificationToast.show();
                    
                    if (parseFloat(response.lastAposta.valor_premio.replace(',', '.')) > 0) {
                        $('#notificationSound')[0].play();
                    }
                }
            }
        }
    });
}

$(document).ready(function() {
    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
    
    $('body').append('<audio id="notificationSound" src="../assets/sounds/notification.mp3" preload="auto"></audio>');
    
    verificarNovasApostas();
    setInterval(verificarNovasApostas, 60000);
    
    document.getElementById('notificationToast').addEventListener('click', function() {
        window.location.reload();
    });
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 