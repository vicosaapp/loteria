<?php
// Forçar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

try {
    // Debug da conexão
    if (!$pdo) {
        throw new Exception("Erro na conexão com o banco de dados");
    }

    // Buscar jogos disponíveis
    $stmt = $pdo->query("SELECT id, nome FROM jogos WHERE status = 1 ORDER BY nome");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar apostadores vinculados ao revendedor atual
    $stmt = $pdo->prepare("
        SELECT id, nome, whatsapp, telefone 
        FROM usuarios 
        WHERE tipo = 'usuario' 
        AND revendedor_id = ?
        ORDER BY nome ASC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar dados do revendedor
    $stmtRevendedor = $pdo->prepare("
        SELECT id, nome 
        FROM usuarios 
        WHERE id = ? 
        LIMIT 1
    ");
    $stmtRevendedor->execute([$_SESSION['usuario_id']]);
    $revendedor = $stmtRevendedor->fetch(PDO::FETCH_ASSOC);

    // Criar array para dados dos apostadores em JavaScript
    $apostadoresData = [];
    foreach ($apostadores as $apostador) {
        $apostadoresData[$apostador['id']] = [
            'whatsapp' => $apostador['whatsapp'] ?: $apostador['telefone'] ?: ''
        ];
    }

} catch (Exception $e) {
    error_log("Erro na importação de apostas: " . $e->getMessage());
    $error = "Erro ao carregar os dados. Por favor, tente novamente.";
}

// Array para armazenar mensagens de debug
$debug_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos obrigatórios
        if (empty($_POST['apostador'])) {
            throw new Exception("Selecione um apostador");
        }
        
        if (empty($_POST['valor_aposta']) || empty($_POST['apostas'])) {
            throw new Exception("Valor da aposta e apostas são obrigatórios");
        }

        // Processar valores
        $usuario_id = intval($_POST['apostador']);
        $revendedor_id = $_SESSION['usuario_id']; // Usa o ID do revendedor logado
        $whatsapp = !empty($_POST['whatsapp']) ? $_POST['whatsapp'] : null;
        
        // Tratar valor da aposta
        $valor_aposta = str_replace(['R$', ' '], '', $_POST['valor_aposta']);
        $valor_aposta = str_replace(',', '.', $valor_aposta);
        $valor_aposta = number_format(floatval($valor_aposta), 2, '.', '');
        
        // Tratar valor do prêmio
        $valor_premio = str_replace(['R$', ' '], '', $_POST['valor_premiacao']);
        $valor_premio = str_replace('.', '', $valor_premio); // Remove pontos de milhar
        $valor_premio = str_replace(',', '.', $valor_premio); // Converte vírgula em ponto
        $valor_premio = number_format(floatval($valor_premio), 2, '.', '');
        
        // Verificar se o apostador pertence ao revendedor atual
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND revendedor_id = ?");
        $stmt->execute([$usuario_id, $revendedor_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Este apostador não está vinculado à sua conta");
        }
        
        // Preparar os dados para inserção
        $dados = [
            'revendedor_id' => $_SESSION['usuario_id'],
            'usuario_id' => $usuario_id,
            'jogo_nome' => trim(explode("\n", $_POST['apostas'])[0]),
            'numeros' => $_POST['apostas'],
            'valor_aposta' => $valor_aposta,
            'valor_premio' => $valor_premio,
            'whatsapp' => $whatsapp
        ];

        // Iniciar transação
        $pdo->beginTransaction();

        try {
            // Verificar estrutura da tabela apostas
            error_log("Verificando estrutura da tabela apostas...");
            $stmt = $pdo->query("DESCRIBE apostas");
            $colunas_apostas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Colunas na tabela apostas: " . implode(", ", $colunas_apostas));
            
            // Verificar se a coluna revendedor_id existe na tabela apostas
            if (!in_array('revendedor_id', $colunas_apostas)) {
                error_log("Coluna revendedor_id não existe na tabela apostas. Adicionando...");
                $pdo->exec("ALTER TABLE apostas ADD COLUMN revendedor_id INT NULL AFTER usuario_id");
                error_log("Coluna revendedor_id adicionada à tabela apostas");
            }
            
            // Inserir na tabela apostas_importadas
            $stmt = $pdo->prepare("
                INSERT INTO apostas_importadas (
                    usuario_id,
                    revendedor_id,
                    jogo_nome,
                    numeros,
                    valor_aposta,
                    valor_premio,
                    whatsapp,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $usuario_id,
                $_SESSION['usuario_id'],
                trim(explode("\n", $_POST['apostas'])[0]),
                $_POST['apostas'],
                $valor_aposta,
                $valor_premio,
                $whatsapp
            ]);
            $apostas_importadas_id = $pdo->lastInsertId();
            
            // Buscar o ID do jogo correspondente ao nome
            $jogo_nome = trim(explode("\n", $_POST['apostas'])[0]);
            
            // Adicionar log para debug
            error_log("Buscando jogo com nome: " . $jogo_nome);
            
            // Melhorar a busca do jogo: primeiro tenta correspondência exata, depois tenta LIKE
            $stmt = $pdo->prepare("SELECT id, nome FROM jogos WHERE titulo_importacao = ? OR nome = ? LIMIT 1");
            $stmt->execute([$jogo_nome, $jogo_nome]);
            $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Se não encontrou com correspondência exata, tenta com LIKE
            if (!$jogo) {
                $stmt = $pdo->prepare("SELECT id, nome FROM jogos WHERE nome LIKE ? OR titulo_importacao LIKE ? LIMIT 1");
                $stmt->execute(['%' . $jogo_nome . '%', '%' . $jogo_nome . '%']);
                $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Adicionar log
                error_log("Tentativa com LIKE: " . ($jogo ? "Jogo encontrado: {$jogo['nome']} (ID: {$jogo['id']})" : "Jogo não encontrado"));
            } else {
                error_log("Jogo encontrado com correspondência exata: {$jogo['nome']} (ID: {$jogo['id']})");
            }
            
            // Se não encontrou o jogo, verifica se existe a coluna titulo_importacao, caso não exista, cria-a
            if (!$jogo) {
                try {
                    // Verificar se a coluna titulo_importacao existe
                    $checkColumn = $pdo->query("SHOW COLUMNS FROM jogos LIKE 'titulo_importacao'");
                    if ($checkColumn->rowCount() == 0) {
                        // A coluna não existe, vamos criá-la
                        error_log("Coluna 'titulo_importacao' não existe na tabela jogos. Criando...");
                        $pdo->exec("ALTER TABLE jogos ADD COLUMN titulo_importacao VARCHAR(100) NULL AFTER nome");
                    }
                    
                    // Listar todos os jogos disponíveis para diagnóstico
                    error_log("Jogos disponíveis na tabela jogos:");
                    $jogosDisponiveis = $pdo->query("SELECT id, nome, titulo_importacao FROM jogos")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($jogosDisponiveis as $jogoDisp) {
                        error_log("ID: {$jogoDisp['id']}, Nome: {$jogoDisp['nome']}, Título Importação: " . ($jogoDisp['titulo_importacao'] ?? 'NULL'));
                    }
                    
                    // Tenta novamente com o nome exato, sem filtros
                    $stmt = $pdo->prepare("SELECT id FROM jogos WHERE nome = ? LIMIT 1");
                    $stmt->execute([$jogo_nome]);
                    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$jogo) {
                        // Tenta extrair código do jogo (QN, DI, MM, MS, LF, LM, TM)
                        preg_match('/(QN|DI|MM|MS|LF|LM|TM)/', $jogo_nome, $matches);
                        $codigoJogo = $matches[0] ?? '';
                        
                        if ($codigoJogo) {
                            error_log("Código do jogo extraído: {$codigoJogo}");
                            
                            // Mapear códigos para nomes completos
                            $mapaJogos = [
                                'QN' => 'Quina',
                                'DI' => 'Dia de Sorte',
                                'MM' => 'Mais Milionária',
                                'MS' => 'Mega-Sena',
                                'LF' => 'Lotofácil',
                                'LM' => 'Lotomania',
                                'TM' => 'Timemania'
                            ];
                            
                            $nomeCompleto = $mapaJogos[$codigoJogo] ?? '';
                            
                            if ($nomeCompleto) {
                                error_log("Tentando buscar pelo nome completo: {$nomeCompleto}");
                                $stmt = $pdo->prepare("SELECT id FROM jogos WHERE nome LIKE ? LIMIT 1");
                                $stmt->execute(['%' . $nomeCompleto . '%']);
                                $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($jogo) {
                                    error_log("Jogo encontrado pelo nome completo mapeado: {$nomeCompleto} (ID: {$jogo['id']})");
                                    
                                    // Atualiza o título_importacao para futuras referências
                                    $stmt = $pdo->prepare("UPDATE jogos SET titulo_importacao = ? WHERE id = ?");
                                    $stmt->execute([$jogo_nome, $jogo['id']]);
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Erro ao verificar/criar coluna titulo_importacao: " . $e->getMessage());
                }
            }
            
            // Se encontrou o jogo, insere também na tabela apostas
            if ($jogo) {
                $jogo_id = $jogo['id'];
                error_log("Inserindo na tabela apostas com jogo_id: {$jogo_id}");
                
                // Inserir na tabela apostas
                try {
                    // Extrair os números da primeira linha de apostas (ignorando o título)
                    $linhas_apostas = explode("\n", $_POST['apostas']);
                    if (count($linhas_apostas) >= 2) {
                        // Remove a primeira linha (título do jogo)
                        array_shift($linhas_apostas);
                        
                        // Pega a primeira aposta
                        $primeira_aposta = trim($linhas_apostas[0]);
                        
                        // Extrai somente os números da primeira aposta
                        preg_match_all('/\d+/', $primeira_aposta, $matches);
                        $numeros_formatados = implode(',', $matches[0]);
                        
                        error_log("Números formatados para tabela apostas: {$numeros_formatados}");
                    } else {
                        $numeros_formatados = "";
                        error_log("Não foi possível extrair números da aposta. Usando texto completo.");
                    }
                    
                    // Usar os números formatados se disponíveis, caso contrário usa o texto completo
                    $numeros_para_apostas = !empty($numeros_formatados) ? $numeros_formatados : $_POST['apostas'];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO apostas 
                        (usuario_id, tipo_jogo_id, numeros, valor_aposta, status, created_at, revendedor_id) 
                        VALUES 
                        (?, ?, ?, ?, 'aprovada', NOW(), ?)
                    ");
                    $stmt->execute([
                        $usuario_id,
                        $jogo_id,
                        $numeros_para_apostas,
                        $valor_aposta,
                        $_SESSION['usuario_id']
                    ]);
                    error_log("Aposta inserida com sucesso na tabela apostas");
                } catch (Exception $e) {
                    error_log("Erro ao inserir na tabela apostas: " . $e->getMessage());
                    // Não fazemos throw aqui para não interromper o fluxo, já que a inserção em apostas_importadas funcionou
                }
            } else {
                // Log para debug se não encontrar o jogo
                error_log("Jogo não encontrado para: '{$jogo_nome}'. A aposta não foi salva na tabela apostas.");
            }
            
            // Commit da transação
            $pdo->commit();
            
            header('Location: importar_apostas.php?success=1');
            exit;
        } catch (Exception $e) {
            // Rollback em caso de erro
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Define a página atual para o menu
$currentPage = 'importar_apostas';

// Carrega a view
ob_start();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-import"></i> Importar Apostas
        </h1>
    </div>
        
    <div class="debug-info" style="background:#f4f6f9; padding: 0px; margin: 0px 0; border-radius: 0px; max-height: 0px; overflow-y: auto;">
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Aposta importada com sucesso!</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" class="mt-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="apostador" class="form-label">Apostador</label>
                        <select class="form-control form-select" id="apostador" name="apostador" required>
                            <option value="">Selecione um apostador</option>
                            <?php foreach ($apostadores as $apostador): ?>
                                <option value="<?php echo $apostador['id']; ?>" 
                                        data-whatsapp="<?php echo htmlspecialchars($apostador['whatsapp'] ?: $apostador['telefone'] ?: ''); ?>">
                                    <?php echo htmlspecialchars($apostador['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="whatsapp" class="form-label">WhatsApp</label>
                        <input type="text" class="form-control" id="whatsapp" name="whatsapp" readonly>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Valor da premiação</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">R$</span>
                        </div>
                        <input type="text" id="valor_premiacao" name="valor_premiacao" class="form-control" value="0,00" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor por Aposta</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <select id="valor_aposta" name="valor_aposta" class="form-control" required>
                                <option value="">Selecione o valor</option>
                            </select>
                        </div>
                        <small class="form-text text-muted">Os valores disponíveis dependem do número de dezenas da aposta.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantidade de Dezenas</label>
                        <input type="text" id="qtd_dezenas" class="form-control" readonly>
                        <small class="form-text text-muted">Quantidade de dezenas detectada automaticamente.</small>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Cole as apostas aqui</label>
                    <textarea id="apostas" name="apostas" class="form-control" rows="10"></textarea>
                    <small class="form-text text-muted">Na primeira linha, coloque o nome do jogo. Nas linhas seguintes, coloque os números.</small>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-secondary" id="btnVisualizar">
                        <i class="fas fa-eye"></i> Visualizar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Dados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para visualização -->
<div class="modal fade" id="visualizarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visualizar Apostas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="resumoApostas"></div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/importar-apostas.js"></script>

<style>
.numero-bolinha {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    margin: 2px;
    font-weight: 600;
    font-size: 13px;
    padding: 0;
    line-height: 1;
    text-align: center;
}

.aposta-item {
    padding: 15px;
    border-bottom: 1px solid #e3e6f0;
}

.aposta-item:last-child {
    border-bottom: none;
}

.apostas-preview {
    max-height: 500px;
    overflow-y: auto;
}

.alert-info {
    background-color: #e3f2fd;
    border-color: #bee5eb;
    color: #0c5460;
    margin-bottom: 20px;
}

.gap-1 {
    gap: 0.25rem !important;
}

.modal-lg {
    max-width: 800px;
}

.text-primary {
    color: #4e73df !important;
}

#valor_aposta {
    text-align: right;
}

.text-danger {
    color: #dc3545 !important;
}
</style>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 