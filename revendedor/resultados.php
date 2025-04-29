<?php
// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar arquivos de inclusão
$config_path = '../config/database.php';
$layout_path = 'includes/layout.php';

// Verificar o modo de manutenção
require_once __DIR__ . '/verificar_manutencao.php';

// Verificar se não há sessão ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Função PHP para formatar valor básico sem R$
function formatarValorBasico($valor) {
    return number_format($valor, 2, ',', '.');
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
                <button type="button" class="btn btn-light" id="btnInserirResultado">
                    <i class="fas fa-plus me-2"></i>Inserir Resultado
                </button>
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

<!-- Modal para inserir resultados -->
<div class="modal fade" id="modalInserirResultado" tabindex="-1" aria-labelledby="modalInserirResultadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalInserirResultadoLabel">Inserir Resultado Manualmente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <form id="formInserirResultado">
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
              <input type="number" class="form-control" id="concurso" name="concurso" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="data_sorteio" class="form-label">Data do Sorteio</label>
              <input type="datetime-local" class="form-control" id="data_sorteio" name="data_sorteio" required>
            </div>
            <div class="col-md-6">
              <label for="valor_acumulado" class="form-label">Valor Acumulado (R$)</label>
              <input type="text" class="form-control" id="valor_acumulado" name="valor_acumulado" placeholder="0,00">
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="data_proximo" class="form-label">Data do Próximo Sorteio</label>
              <input type="datetime-local" class="form-control" id="data_proximo" name="data_proximo">
            </div>
            <div class="col-md-6">
              <label for="valor_estimado" class="form-label">Valor Estimado Próximo (R$)</label>
              <input type="text" class="form-control" id="valor_estimado" name="valor_estimado" placeholder="0,00">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Números Sorteados</label>
            <input type="hidden" name="numeros" value="">
            <div class="numeros-info mb-2">Números selecionados: 0/0</div>
            <div class="numeros-sorteaveis d-flex flex-wrap gap-2">
              <!-- Números serão inseridos via JavaScript -->
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSalvarResultado">Salvar Resultado</button>
      </div>
    </div>
  </div>
</div>

<!-- Botão para abrir o modal -->
<div class="d-flex justify-content-end mb-4">
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInserirResultado">
    <i class="fas fa-plus-circle me-2"></i>Inserir Novo Resultado
  </button>
</div>

<!-- Botões de ação -->
<div class="mb-4 text-center">
    <button id="btnProcessarGanhadores" class="btn btn-primary me-2">
        <i class="fas fa-sync me-1"></i> Processar Ganhadores
    </button>
    <button id="btnProcessarApostasImportadas" class="btn btn-success me-2">
        <i class="fas fa-file-import me-1"></i> Processar Apostas Importadas
    </button>
    <button id="btnAtualizarResultados" class="btn btn-info me-2">
        <i class="fas fa-cloud-download-alt me-1"></i> Atualizar da API
    </button>
    <button id="btnCorrigirBD" class="btn btn-warning">
        <i class="fas fa-tools me-1"></i> Corrigir Banco de Dados
    </button>
</div>

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

.numeros-grid {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 8px;
    margin-bottom: 1rem;
}

.numero-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #ddd;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s;
}

.numero-item:hover {
    background-color: #f0f0f0;
}

.numero-item.selected {
    background-color: #007bff;
    color: white;
    border-color: #0056b3;
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
        // Redirecionar para a página de diagnóstico com a ação de processar
        window.location.href = 'debug_ganhadores.php?acao=processar';
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

    // Inicializar o modal
    document.getElementById('btnInserirResultado').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('modalInserirResultado'));
        modal.show();
        
        // Inicializar data atual
        const now = new Date();
        const dataFormatada = now.toISOString().slice(0, 16);
        document.getElementById('data_sorteio').value = dataFormatada;
        
        // Data próximo sorteio (7 dias depois)
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        document.getElementById('data_proximo').value = nextWeek.toISOString().slice(0, 16);
        
        // Gerar grid de números
        gerarGradeNumeros();
    });

    // Inicializar quando o modal for aberto
    const btnAbrirModal = document.getElementById('btnAbrirModalResultado');
    const modalResultado = document.getElementById('modalResultado');
    
    if (btnAbrirModal && modalResultado) {
        btnAbrirModal.addEventListener('click', function() {
            limparFormulario();
            gerarGradeNumeros();
        });
    }
    
    // Botão para salvar resultado
    const btnSalvarResultado = document.getElementById('btnSalvarResultado');
    if (btnSalvarResultado) {
        btnSalvarResultado.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validar seleção de jogo
            const jogoSelect = document.getElementById('jogo_id');
            if (!jogoSelect.value) {
                alert('Selecione um jogo');
                jogoSelect.focus();
                return;
            }
            
            // Validar número do concurso
            const concursoInput = document.querySelector('input[name="concurso"]');
            if (!concursoInput.value) {
                alert('Informe o número do concurso');
                concursoInput.focus();
                return;
            }
            
            // Obter números selecionados
            const numerosInput = document.querySelector('input[name="numeros"]');
            if (!numerosInput.value) {
                alert('Selecione os números sorteados');
                return;
            }
            
            // Validar data do sorteio
            const dataSorteioInput = document.querySelector('input[name="data_sorteio"]');
            if (!dataSorteioInput.value) {
                alert('Informe a data do sorteio');
                dataSorteioInput.focus();
                return;
            }
            
            // Preparar dados do formulário
            const formData = new FormData();
            formData.append('jogo_id', jogoSelect.value);
            formData.append('concurso', concursoInput.value);
            formData.append('numeros', numerosInput.value);
            formData.append('data_sorteio', dataSorteioInput.value);
            
            // Adicionar valor acumulado
            const valorAcumuladoInput = document.querySelector('input[name="valor_acumulado"]');
            if (valorAcumuladoInput) {
                formData.append('valor_acumulado', valorAcumuladoInput.value);
            }
            
            // Adicionar data do próximo concurso
            const dataProximoInput = document.querySelector('input[name="data_proximo"]');
            if (dataProximoInput) {
                formData.append('data_proximo', dataProximoInput.value);
            }
            
            // Adicionar valor estimado
            const valorEstimadoInput = document.querySelector('input[name="valor_estimado"]');
            if (valorEstimadoInput) {
                formData.append('valor_estimado', valorEstimadoInput.value);
            }
            
            // Mostrar carregamento
            const btnTexto = btnSalvarResultado.textContent;
            btnSalvarResultado.disabled = true;
            btnSalvarResultado.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            
            // Enviar requisição
            fetch('ajax/salvar_resultado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Resultado salvo com sucesso!');
                    // Fechar modal se estiver em uma
                    const modal = document.getElementById('modalInserirResultado');
                    if (modal && typeof bootstrap !== 'undefined') {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    
                    // Recarregar a página para mostrar os novos resultados
                    window.location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar resultado. Verifique o console para mais detalhes.');
            })
            .finally(() => {
                // Restaurar botão
                btnSalvarResultado.disabled = false;
                btnSalvarResultado.textContent = btnTexto;
            });
        });
    }
    
    // Atualizar grid de números quando o jogo mudar
    const jogoSelect = document.getElementById('jogo_id');
    if (jogoSelect) {
        jogoSelect.addEventListener('change', function() {
            atualizarGridNumeros();
        });
        
        // Inicializar grid na carga da página
        atualizarGridNumeros();
    }
    
    function atualizarGridNumeros() {
        if (!jogoSelect.value) return;
        
        // Obter o jogo selecionado
        const option = jogoSelect.options[jogoSelect.selectedIndex];
        const jogoNome = option.textContent.trim();
        
        // Determinar o número máximo baseado no jogo
        let numeroMaximo = 60;
        let maximoSelecao = 6;
        
        switch (jogoNome.toLowerCase()) {
            case 'lotofácil':
                numeroMaximo = 25;
                maximoSelecao = 15;
                break;
            case 'quina':
                numeroMaximo = 80;
                maximoSelecao = 5;
                break;
            case 'lotomania':
                numeroMaximo = 100;
                maximoSelecao = 20;
                break;
            case 'timemania':
                numeroMaximo = 80;
                maximoSelecao = 7;
                break;
            case '+milionária':
                numeroMaximo = 50;
                maximoSelecao = 6;
                break;
            case 'dia de sorte':
                numeroMaximo = 31;
                maximoSelecao = 7;
                break;
            default: // Mega-Sena
                numeroMaximo = 60;
                maximoSelecao = 6;
        }
        
        // Atualizar o container de números
        const containerNumeros = document.querySelector('.numeros-sorteaveis');
        if (containerNumeros) {
            // Limpar container existente
            containerNumeros.innerHTML = '';
            
            // Adicionar números
            for (let i = 1; i <= numeroMaximo; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-dark numero-btn';
                btn.dataset.numero = i;
                btn.textContent = i.toString().padStart(2, '0');
                
                // Adicionar evento de clique
                btn.addEventListener('click', function() {
                    toggleNumero(this, maximoSelecao);
                });
                
                containerNumeros.appendChild(btn);
            }
        }
        
        // Limpar seleção atual
        document.querySelector('input[name="numeros"]').value = '';
        atualizarContador();
    }
    
    function toggleNumero(btn, maximoSelecao) {
        // Toggle classe ativa
        btn.classList.toggle('active');
        
        // Atualizar input com números selecionados
        const numerosAtivos = document.querySelectorAll('.numero-btn.active');
        if (numerosAtivos.length > maximoSelecao) {
            alert(`Você só pode selecionar no máximo ${maximoSelecao} números.`);
            btn.classList.remove('active');
            return;
        }
        
        // Atualizar o campo oculto com os números selecionados
        const numeros = Array.from(numerosAtivos).map(el => el.dataset.numero);
        document.querySelector('input[name="numeros"]').value = numeros.join(',');
        
        // Atualizar contador
        atualizarContador();
    }
    
    function atualizarContador() {
        const contadorEl = document.querySelector('.numeros-info');
        if (!contadorEl) return;
        
        const numerosInput = document.querySelector('input[name="numeros"]');
        const selecionados = numerosInput.value ? numerosInput.value.split(',').length : 0;
        
        // Determinar o máximo baseado no jogo selecionado
        const option = jogoSelect.options[jogoSelect.selectedIndex];
        const jogoNome = option ? option.textContent.trim() : '';
        
        let maximoSelecao = 6;
        switch (jogoNome.toLowerCase()) {
            case 'lotofácil': maximoSelecao = 15; break;
            case 'quina': maximoSelecao = 5; break;
            case 'lotomania': maximoSelecao = 20; break;
            case 'timemania': maximoSelecao = 7; break;
            case '+milionária': maximoSelecao = 6; break;
            case 'dia de sorte': maximoSelecao = 7; break;
            default: maximoSelecao = 6; // Mega-Sena
        }
        
        contadorEl.textContent = `Números selecionados: ${selecionados}/${maximoSelecao}`;
    }
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

// Variáveis globais
let numerosSelecionados = [];
let maximoNumeros = 15; // Padrão para Lotofácil

// Função para atualizar configuração com base no jogo selecionado
function atualizarConfiguracaoPorJogo() {
    const jogoSelect = document.getElementById('jogo_id');
    if (!jogoSelect) return;
    
    const jogoId = jogoSelect.value;
    numerosSelecionados = []; // Limpar seleções existentes
    
    // Resetar seleções visuais
    const grid = document.getElementById('numeros-grid');
    if (grid) {
        const elementos = grid.getElementsByClassName('numero-item');
        for (let i = 0; i < elementos.length; i++) {
            elementos[i].classList.remove('selected');
        }
    }
    
    // Atualizar o display
    atualizarNumerosDisplay();
    
    // Definir o máximo de números com base no jogo
    switch (jogoId) {
        case '3': // Lotofácil
            maximoNumeros = 15;
            break;
        case '6': // Lotomania
            maximoNumeros = 20;
            break;
        case '8': // Quina
            maximoNumeros = 5;
            break;
        case '9': // Mega-Sena
            maximoNumeros = 6;
            break;
        case '11': // Timemania
            maximoNumeros = 7;
            break;
        case '14': // +Milionária
            maximoNumeros = 6;
            break;
        case '15': // Dia de Sorte
            maximoNumeros = 7;
            break;
        default:
            maximoNumeros = 15;
    }
    
    // Não chamar gerarGradeNumeros aqui para evitar referência cíclica
    // Apenas atualizar a interface de números
    criarGradeNumeros();
}

// Função para gerar a grade de números
function gerarGradeNumeros() {
    const jogoSelect = document.getElementById('jogo_id');
    if (jogoSelect) {
        jogoSelect.addEventListener('change', atualizarConfiguracaoPorJogo);
        // Configurar na inicialização
        atualizarConfiguracaoPorJogo();
    } else {
        // Se não houver select de jogo, criar a grade padrão
        criarGradeNumeros();
    }
}

// Função para criar a grade de números com base nas configurações atuais
function criarGradeNumeros() {
    const grid = document.getElementById('numeros-grid');
    if (!grid) {
        console.error('Elemento numeros-grid não encontrado');
        return;
    }
    
    // Limpar grid atual
    grid.innerHTML = '';
    
    // Quantidade total de números disponíveis (pode variar por jogo)
    let totalNumeros = 25;
    
    // Verificar o jogo selecionado para determinar o total de números
    const jogoSelect = document.getElementById('jogo_id');
    if (jogoSelect) {
        const jogoId = jogoSelect.value;
        
        // Definir o total de números com base no jogo
        switch (jogoId) {
            case '3': // Lotofácil
                totalNumeros = 25;
                break;
            case '6': // Lotomania
                totalNumeros = 100;
                break;
            case '8': // Quina
                totalNumeros = 80;
                break;
            case '9': // Mega-Sena
                totalNumeros = 60;
                break;
            case '11': // Timemania
                totalNumeros = 80;
                break;
            case '14': // +Milionária
                totalNumeros = 50;
                break;
            case '15': // Dia de Sorte
                totalNumeros = 31;
                break;
            default:
                totalNumeros = 60;
        }
    }
    
    // Gerar números
    for (let i = 1; i <= totalNumeros; i++) {
        const numero = document.createElement('div');
        numero.className = 'numero-item';
        numero.textContent = i < 10 ? `0${i}` : i;
        numero.setAttribute('data-numero', i);
        numero.onclick = function() { 
            toggleNumero(this, i); 
        };
        grid.appendChild(numero);
    }
}

// Função para alternar seleção de número
function toggleNumero(elemento, numero) {
    const index = numerosSelecionados.indexOf(numero);
    
    if (index === -1) {
        // Verificar se já atingiu o máximo de números
        if (numerosSelecionados.length >= maximoNumeros) {
            Swal.fire({
                icon: 'warning',
                title: 'Limite atingido',
                text: `Você só pode selecionar ${maximoNumeros} números para este jogo.`
            });
            return;
        }
        
        numerosSelecionados.push(numero);
        elemento.classList.add('selected');
    } else {
        numerosSelecionados.splice(index, 1);
        elemento.classList.remove('selected');
    }
    
    atualizarNumerosDisplay();
}

// Função para atualizar display de números selecionados
function atualizarNumerosDisplay() {
    const display = document.getElementById('numeros-selecionados');
    const input = document.getElementById('numeros-input');
    
    if (!display || !input) {
        console.error('Elementos de exibição não encontrados');
        return;
    }
    
    if (numerosSelecionados.length === 0) {
        display.textContent = 'Nenhum';
        input.value = '';
    } else {
        const numerosOrdenados = [...numerosSelecionados].sort((a, b) => a - b);
        display.textContent = numerosOrdenados.map(n => n < 10 ? `0${n}` : n).join(', ');
        input.value = numerosOrdenados.join(',');
    }
    
    // Adicionar informação sobre o limite
    if (display) {
        display.textContent += ` (${numerosSelecionados.length}/${maximoNumeros})`;
    }
}

// Função para limpar o formulário
function limparFormulario() {
    const form = document.getElementById('formSalvarResultado');
    if (form) {
        form.reset();
        numerosSelecionados = [];
        const display = document.getElementById('numeros-selecionados');
        if (display) {
            display.textContent = 'Nenhum';
        }
        
        // Limpar seleção visual
        const numerosGrid = document.getElementById('numeros-grid');
        if (numerosGrid) {
            const elementos = numerosGrid.getElementsByClassName('numero-item');
            for (let i = 0; i < elementos.length; i++) {
                elementos[i].classList.remove('selected');
            }
        }
        
        // Atualizar configuração do jogo
        atualizarConfiguracaoPorJogo();
    }
}

// Função para salvar o resultado
function salvarResultado() {
    // Validar o formulário primeiro
    if (!validarFormulario()) {
        return;
    }

    // Mostrar carregamento
    Swal.fire({
        title: 'Salvando...',
        html: 'Processando os dados. Por favor, aguarde.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Preparar dados do formulário
    const form = document.getElementById('formSalvarResultado');
    if (!form) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Formulário não encontrado'
        });
        return;
    }
    
    const formData = new FormData(form);
    
    // Adicionar números selecionados ao formData
    formData.set('numeros', numerosSelecionados.join(','));
    
    // Verificar se todos os campos necessários estão presentes
    if (!formData.get('jogo_id') || !formData.get('concurso') || !formData.get('data_sorteio')) {
        Swal.fire({
            icon: 'error',
            title: 'Dados incompletos',
            text: 'Por favor, preencha todos os campos obrigatórios'
        });
        return;
    }
    
    // Log para debugging
    console.log('Enviando dados:', Object.fromEntries(formData));
    
    // Enviar requisição AJAX
    console.log('Enviando requisição para: ajax/salvar_resultado.php');
    
    fetch('ajax/salvar_resultado.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Resposta recebida:', response);
        if (!response.ok) {
            throw new Error('Erro na resposta do servidor: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos:', data);
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Resultado cadastrado com sucesso',
                confirmButtonText: 'OK'
            }).then(() => {
                // Fechar o modal
                const modal = document.getElementById('modalInserirResultado');
                if (modal && typeof bootstrap !== 'undefined') {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                
                // Recarregar a página para mostrar o novo resultado
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: data.message || 'Ocorreu um erro ao salvar o resultado'
            });
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        
        // Tentar novamente com caminho absoluto
        console.log('Tentando novamente com caminho absoluto...');
        
        fetch('/revendedor/ajax/salvar_resultado.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Resposta da segunda tentativa:', response);
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor (2ª tentativa): ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos (2ª tentativa):', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Resultado cadastrado com sucesso',
                    confirmButtonText: 'OK'
                }).then(() => {
                    const modal = document.getElementById('modalInserirResultado');
                    if (modal && typeof bootstrap !== 'undefined') {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.message || 'Ocorreu um erro ao salvar o resultado'
                });
            }
        })
        .catch(secondError => {
            console.error('Erro na segunda tentativa:', secondError);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Ocorreu um erro ao processar a requisição. Detalhes no console.'
            });
        });
    });
}

// Função para validar o formulário
function validarFormulario() {
    const form = document.getElementById('formSalvarResultado');
    if (!form) return false;
    
    // Validar concurso
    const concurso = form.elements['concurso'].value.trim();
    if (!concurso) {
        Swal.fire({
            icon: 'error',
            title: 'Erro de validação',
            text: 'Por favor, informe o número do concurso'
        });
        return false;
    }
    
    // Validar data
    const data = form.elements['data_sorteio'].value.trim();
    if (!data) {
        Swal.fire({
            icon: 'error',
            title: 'Erro de validação',
            text: 'Por favor, informe a data do sorteio'
        });
        return false;
    }
    
    // Validar jogo selecionado
    const jogoId = form.elements['jogo_id'].value;
    if (!jogoId) {
        Swal.fire({
            icon: 'error',
            title: 'Erro de validação',
            text: 'Por favor, selecione um jogo'
        });
        return false;
    }
    
    // Validar números selecionados
    if (numerosSelecionados.length !== maximoNumeros) {
        Swal.fire({
            icon: 'error',
            title: 'Erro de validação',
            text: `Você precisa selecionar exatamente ${maximoNumeros} números para este jogo.`
        });
        return false;
    }
    
    return true;
}

// Processar apostas importadas
$('#btnProcessarApostasImportadas').on('click', function() {
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processando...');
    
    // Exibir modal de progresso
    $('#processingModal').modal('show');
    $('#processingLog').html('<p>Iniciando processamento de apostas importadas...</p>');
    
    $.ajax({
        url: 'ajax/processar_ganhadores.php',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#processingLog').append('<p class="text-success">Processamento concluído com sucesso!</p>');
                
                // Adicionar logs ao modal
                if (response.logs && response.logs.length > 0) {
                    $('#processingLog').append('<hr><h6>Detalhes do processamento:</h6>');
                    response.logs.forEach(function(log) {
                        $('#processingLog').append('<p>' + log + '</p>');
                    });
                }
                
                // Reativar botão e recarregar a página após alguns segundos
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            } else {
                $('#processingLog').append('<p class="text-danger">Erro: ' + response.message + '</p>');
                $('#btnProcessarApostasImportadas').prop('disabled', false).html('<i class="fas fa-file-import me-1"></i> Processar Apostas Importadas');
            }
        },
        error: function(xhr, status, error) {
            $('#processingLog').append('<p class="text-danger">Erro na requisição: ' + error + '</p>');
            $('#btnProcessarApostasImportadas').prop('disabled', false).html('<i class="fas fa-file-import me-1"></i> Processar Apostas Importadas');
        }
    });
});
</script>

<?php
// Capturar o conteúdo do buffer
$content = ob_get_clean();

// Incluir o layout
require_once $layout_path;