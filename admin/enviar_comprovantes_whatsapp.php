<?php
/**
 * Enviar Comprovantes por WhatsApp
 * 
 * Este script permite enviar comprovantes de apostas por WhatsApp para os apostadores.
 * Se um apostador tiver várias apostas, todos os comprovantes serão enviados de uma vez.
 */

session_start();
require_once '../config/database.php';

// Verifica se é admin ou revendedor
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'revendedor')) {
    header("Location: ../login.php");
    exit();
}

$mensagem = '';
$erro = '';

// Define a página atual para o menu
$currentPage = 'enviar_comprovantes';

// Incluir o cabeçalho
include 'includes/header.php';

// Função para gerar comprovantes em PDF
function gerarComprovantePDF($usuario_id, $jogo_nome, $aposta_id = null) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    // Construir a URL para o comprovante
    $url = "{$base_url}/admin/gerar_comprovante.php";
    $params = [
        'usuario_id' => $usuario_id,
        'jogo' => $jogo_nome,
        'formato' => 'pdf'
    ];
    
    if ($aposta_id) {
        $params['aposta_id'] = $aposta_id;
    }
    
    return $url . '?' . http_build_query($params);
}

// Processar envio de comprovantes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar se foram selecionados apostadores/apostas
        if (isset($_POST['apostas']) && !empty($_POST['apostas'])) {
            $apostas_ids = $_POST['apostas'];
            
            // Obter informações das apostas selecionadas
            $placeholders = str_repeat('?,', count($apostas_ids) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT 
                    a.id AS aposta_id, 
                    a.usuario_id, 
                    a.numeros,
                    a.valor_aposta,
                    a.valor_premio,
                    u.nome AS apostador_nome,
                    u.whatsapp AS apostador_whatsapp,
                    j.nome AS jogo_nome
                FROM 
                    apostas a
                JOIN 
                    usuarios u ON a.usuario_id = u.id
                JOIN 
                    jogos j ON a.tipo_jogo_id = j.id
                WHERE 
                    a.id IN ({$placeholders})
                ORDER BY
                    u.id, a.id
            ");
            $stmt->execute($apostas_ids);
            $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar apostas por apostador
            $apostas_por_apostador = [];
            foreach ($apostas as $aposta) {
                if (empty($aposta['apostador_whatsapp'])) {
                    continue; // Pular apostadores sem WhatsApp
                }
                
                $usuario_id = $aposta['usuario_id'];
                if (!isset($apostas_por_apostador[$usuario_id])) {
                    $apostas_por_apostador[$usuario_id] = [
                        'nome' => $aposta['apostador_nome'],
                        'whatsapp' => $aposta['apostador_whatsapp'],
                        'apostas' => []
                    ];
                }
                
                $apostas_por_apostador[$usuario_id]['apostas'][] = $aposta;
            }
            
            // Preparar e enfileirar mensagens para cada apostador
            $total_apostadores = 0;
            $total_apostas = 0;
            
            foreach ($apostas_por_apostador as $usuario_id => $dados) {
                if (count($dados['apostas']) === 0) {
                    continue;
                }
                
                $total_apostadores++;
                $apostas_count = count($dados['apostas']);
                $total_apostas += $apostas_count;
                
                // Preparar mensagem inicial para o WhatsApp
                $mensagem_whatsapp = "✅ *COMPROVANTES DE APOSTAS* ✅\n\n";
                $mensagem_whatsapp .= "*Apostador:* {$dados['nome']}\n";
                $mensagem_whatsapp .= "*Data:* " . date('d/m/Y H:i') . "\n\n";
                
                if ($apostas_count === 1) {
                    $mensagem_whatsapp .= "Estamos enviando seu comprovante de aposta logo abaixo.\n";
                } else {
                    $mensagem_whatsapp .= "Estamos enviando seus {$apostas_count} comprovantes de apostas logo abaixo.\n";
                }
                
                $mensagem_whatsapp .= "Boa sorte! 🍀\n\n";
                
                // Incluir resumo das apostas
                $mensagem_whatsapp .= "*RESUMO DAS APOSTAS:*\n";
                
                foreach ($dados['apostas'] as $index => $aposta) {
                    $numeros = explode(',', $aposta['numeros']);
                    $numeros_formatados = implode('-', array_map(function($n) { 
                        return str_pad(trim($n), 2, '0', STR_PAD_LEFT); 
                    }, $numeros));
                    
                    $mensagem_whatsapp .= "\n*Aposta " . ($index + 1) . ":*\n";
                    $mensagem_whatsapp .= "- Jogo: {$aposta['jogo_nome']}\n";
                    $mensagem_whatsapp .= "- Números: {$numeros_formatados}\n";
                    $mensagem_whatsapp .= "- Valor: R$ " . number_format($aposta['valor_aposta'], 2, ',', '.') . "\n";
                    $mensagem_whatsapp .= "- Prêmio: R$ " . number_format($aposta['valor_premio'], 2, ',', '.') . "\n";
                }
                
                // Gerar e incluir URLs dos comprovantes na mensagem
                $mensagem_whatsapp .= "\n*LINKS DOS COMPROVANTES:*\n";
                
                foreach ($dados['apostas'] as $index => $aposta) {
                    $comprovante_url = gerarComprovantePDF($aposta['usuario_id'], $aposta['jogo_nome'], $aposta['aposta_id']);
                    $mensagem_whatsapp .= "\nComprovante " . ($index + 1) . ":\n";
                    $mensagem_whatsapp .= $comprovante_url . "\n";
                    
                    // Adicionar à fila de envio se a tabela existir
                    $table_exists = $pdo->query("SHOW TABLES LIKE 'fila_envio_comprovantes'")->rowCount() > 0;
                    
                    if ($table_exists) {
                        $stmt = $pdo->prepare("
                            INSERT INTO fila_envio_comprovantes 
                            (aposta_id, status, data_enfileiramento, tentativas) 
                            VALUES (?, 'pendente', NOW(), 0)
                        ");
                        $stmt->execute([$aposta['aposta_id']]);
                    }
                }
                
                // Preparar URL para WhatsApp
                $telefone = preg_replace('/\D/', '', $dados['whatsapp']);
                $whatsapp_url = "https://wa.me/{$telefone}?text=" . urlencode($mensagem_whatsapp);
                
                // Enfileirar mensagem para processamento em segundo plano
                // (Na versão real, isso seria feito por uma tarefa em segundo plano)
                
                // Por enquanto, abrir a URL do WhatsApp em uma nova guia para o primeiro apostador
                if ($total_apostadores === 1) {
                    echo "<script>window.open('" . htmlspecialchars($whatsapp_url) . "', '_blank');</script>";
                }
            }
            
            if ($total_apostadores > 0) {
                $mensagem = "Foram preparados comprovantes para {$total_apostadores} apostador(es), totalizando {$total_apostas} apostas.";
                
                if ($total_apostadores > 1) {
                    $mensagem .= " O WhatsApp foi aberto para o primeiro apostador. Os demais comprovantes foram enfileirados para envio.";
                }
            } else {
                $erro = "Nenhum apostador válido encontrado com as apostas selecionadas.";
            }
            
        } else {
            $erro = "Nenhuma aposta selecionada.";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao processar apostas: " . $e->getMessage();
    }
}

// Buscar apostas disponíveis
$filtros = [];
$parametros = [];

// Filtro por data
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filtros[] = "DATE(a.created_at) >= ?";
    $parametros[] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filtros[] = "DATE(a.created_at) <= ?";
    $parametros[] = $_GET['data_fim'];
}

// Filtro por apostador
if (isset($_GET['apostador_id']) && !empty($_GET['apostador_id'])) {
    $filtros[] = "a.usuario_id = ?";
    $parametros[] = $_GET['apostador_id'];
}

// Filtro por jogo
if (isset($_GET['jogo_id']) && !empty($_GET['jogo_id'])) {
    $filtros[] = "a.tipo_jogo_id = ?";
    $parametros[] = $_GET['jogo_id'];
}

// Montar cláusula WHERE
$where = "";
if (!empty($filtros)) {
    $where = "WHERE " . implode(" AND ", $filtros);
}

// Buscar apostas
$sql = "
    SELECT 
        a.id, 
        a.usuario_id, 
        a.numeros, 
        a.valor_aposta,
        a.created_at, 
        u.nome AS apostador_nome, 
        u.whatsapp AS apostador_whatsapp,
        j.nome AS jogo_nome,
        j.id AS jogo_id
    FROM 
        apostas a
    JOIN 
        usuarios u ON a.usuario_id = u.id
    JOIN 
        jogos j ON a.tipo_jogo_id = j.id
    {$where}
    ORDER BY 
        a.created_at DESC
    LIMIT 50
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar apostas: " . $e->getMessage();
    $apostas = [];
}

// Buscar apostadores para filtro
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            u.id, 
            u.nome 
        FROM 
            usuarios u 
        JOIN 
            apostas a ON u.id = a.usuario_id 
        WHERE 
            u.whatsapp IS NOT NULL 
            AND u.whatsapp != '' 
        ORDER BY 
            u.nome
    ");
    $stmt->execute();
    $apostadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar apostadores: " . $e->getMessage();
    $apostadores = [];
}

// Buscar jogos para filtro
try {
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            nome 
        FROM 
            jogos 
        ORDER BY 
            nome
    ");
    $stmt->execute();
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar jogos: " . $e->getMessage();
    $jogos = [];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Enviar Comprovantes por WhatsApp</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Enviar Comprovantes</li>
    </ol>
    
    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-success"><?php echo $mensagem; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtros
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3 mb-3">
                    <label for="data_inicio">Data Início:</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?php echo $_GET['data_inicio'] ?? ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="data_fim">Data Fim:</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?php echo $_GET['data_fim'] ?? ''; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="apostador_id">Apostador:</label>
                    <select id="apostador_id" name="apostador_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($apostadores as $apostador): ?>
                            <option value="<?php echo $apostador['id']; ?>" <?php echo (isset($_GET['apostador_id']) && $_GET['apostador_id'] == $apostador['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($apostador['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="jogo_id">Jogo:</label>
                    <select id="jogo_id" name="jogo_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($jogos as $jogo): ?>
                            <option value="<?php echo $jogo['id']; ?>" <?php echo (isset($_GET['jogo_id']) && $_GET['jogo_id'] == $jogo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($jogo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="enviar_comprovantes_whatsapp.php" class="btn btn-secondary">Limpar Filtros</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-paper-plane me-1"></i>
            Enviar Comprovantes
        </div>
        <div class="card-body">
            <form method="POST" id="form-enviar-comprovantes">
                <div class="mb-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fab fa-whatsapp"></i> Enviar Comprovantes Selecionados
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tabela-apostas">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selecionar-todos">
                                </th>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Apostador</th>
                                <th>WhatsApp</th>
                                <th>Jogo</th>
                                <th>Números</th>
                                <th>Valor</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($apostas)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Nenhuma aposta encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($apostas as $aposta): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($aposta['apostador_whatsapp'])): ?>
                                                <input type="checkbox" name="apostas[]" value="<?php echo $aposta['id']; ?>" class="aposta-checkbox">
                                            <?php else: ?>
                                                <i class="fas fa-ban text-danger" title="Sem WhatsApp"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $aposta['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($aposta['apostador_nome']); ?></td>
                                        <td>
                                            <?php if (!empty($aposta['apostador_whatsapp'])): ?>
                                                <?php echo htmlspecialchars($aposta['apostador_whatsapp']); ?>
                                            <?php else: ?>
                                                <span class="text-danger">Não cadastrado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($aposta['jogo_nome']); ?></td>
                                        <td>
                                            <?php 
                                            $numeros = explode(',', $aposta['numeros']);
                                            $numeros_formatados = implode(' - ', array_map(function($n) { 
                                                return str_pad(trim($n), 2, '0', STR_PAD_LEFT); 
                                            }, $numeros));
                                            echo $numeros_formatados;
                                            ?>
                                        </td>
                                        <td>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                                        <td>
                                            <a href="gerar_comprovante.php?usuario_id=<?php echo $aposta['usuario_id']; ?>&jogo=<?php echo urlencode($aposta['jogo_nome']); ?>&aposta_id=<?php echo $aposta['id']; ?>&formato=pdf" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <?php if (!empty($aposta['apostador_whatsapp'])): ?>
                                                <a href="javascript:void(0)" onclick="enviarComprovanteIndividual(<?php echo $aposta['id']; ?>)" class="btn btn-sm btn-success">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manipular seleção de todas as apostas
        const selecionarTodos = document.getElementById('selecionar-todos');
        const checkboxes = document.querySelectorAll('.aposta-checkbox');
        
        if (selecionarTodos) {
            selecionarTodos.addEventListener('change', function() {
                const isChecked = this.checked;
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = isChecked;
                });
            });
        }
        
        // Validar formulário antes de enviar
        const form = document.getElementById('form-enviar-comprovantes');
        if (form) {
            form.addEventListener('submit', function(e) {
                const apostasChecked = document.querySelectorAll('input[name="apostas[]"]:checked');
                if (apostasChecked.length === 0) {
                    e.preventDefault();
                    alert('Por favor, selecione pelo menos uma aposta para enviar.');
                    return false;
                }
                
                if (apostasChecked.length > 10) {
                    if (!confirm('Você selecionou ' + apostasChecked.length + ' apostas. Isso pode sobrecarregar o sistema. Deseja continuar?')) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                return true;
            });
        }
    });
    
    // Função para enviar comprovante individual
    function enviarComprovanteIndividual(apostaId) {
        // Limpar todas as seleções
        document.querySelectorAll('.aposta-checkbox').forEach(function(checkbox) {
            checkbox.checked = false;
        });
        
        // Selecionar apenas a aposta desejada
        const checkbox = document.querySelector('input[name="apostas[]"][value="' + apostaId + '"]');
        if (checkbox) {
            checkbox.checked = true;
            
            // Enviar o formulário
            document.getElementById('form-enviar-comprovantes').submit();
        }
    }
</script>

<?php
// Incluir o rodapé
include 'includes/footer.php';
?> 