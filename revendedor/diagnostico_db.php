<?php
/**
 * Diagnóstico do Banco de Dados
 * 
 * Esta página realiza diagnósticos no banco de dados para verificar problemas comuns
 * que podem afetar o funcionamento do sistema.
 */

session_start();
require_once '../config/database.php';

// Verifica se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header("Location: ../login.php");
    exit();
}

// Definir variáveis para o layout
$pageTitle = 'Diagnóstico do Banco de Dados';
$currentPage = 'diagnostico_db';

// Inicializar arrays para armazenar resultados dos testes
$testes_ok = [];
$testes_erro = [];
$informacoes = [];

// Teste 1: Verificar conexão com o banco de dados
try {
    $pdo->query("SELECT 1");
    $testes_ok[] = "Conexão com o banco de dados estabelecida com sucesso";
    
    // Obter informações do servidor
    $stmt = $pdo->query("SELECT VERSION() as versao");
    $versao_mysql = $stmt->fetch(PDO::FETCH_ASSOC)['versao'];
    $informacoes[] = "Versão do MySQL: " . $versao_mysql;
} catch (PDOException $e) {
    $testes_erro[] = "Falha na conexão com o banco de dados: " . $e->getMessage();
}

// Teste 2: Verificar existência das tabelas necessárias
$tabelas_necessarias = [
    'apostas' => ['id', 'usuario_id', 'revendedor_id', 'tipo_jogo_id', 'numeros', 'valor_aposta', 'created_at'],
    'usuarios' => ['id', 'nome', 'email', 'whatsapp', 'tipo'],
    'jogos' => ['id', 'nome', 'descricao', 'preco_minimo']
];

foreach ($tabelas_necessarias as $tabela => $colunas) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$tabela}'");
        
        if ($stmt->rowCount() > 0) {
            $testes_ok[] = "Tabela '{$tabela}' existe no banco de dados";
            
            // Verificar as colunas da tabela
            $stmt = $pdo->query("SHOW COLUMNS FROM {$tabela}");
            $colunas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $colunas_faltando = array_diff($colunas, $colunas_existentes);
            
            if (empty($colunas_faltando)) {
                $testes_ok[] = "A tabela '{$tabela}' contém todas as colunas necessárias";
            } else {
                $testes_erro[] = "A tabela '{$tabela}' está faltando as seguintes colunas: " . implode(", ", $colunas_faltando);
            }
            
            // Verificar a quantidade de registros
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$tabela}");
            $count = $stmt->fetchColumn();
            $informacoes[] = "Total de registros na tabela '{$tabela}': {$count}";
        } else {
            $testes_erro[] = "Tabela '{$tabela}' não encontrada no banco de dados";
        }
    } catch (PDOException $e) {
        $testes_erro[] = "Erro ao verificar a tabela '{$tabela}': " . $e->getMessage();
    }
}

// Teste 3: Verificar apostas do revendedor atual
$revendedor_id = $_SESSION['usuario_id'];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apostas WHERE revendedor_id = ?");
    $stmt->execute([$revendedor_id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $testes_ok[] = "Você tem {$count} apostas registradas no sistema";
    } else {
        $informacoes[] = "Você ainda não tem apostas registradas no sistema";
    }
} catch (PDOException $e) {
    $testes_erro[] = "Erro ao verificar apostas do revendedor: " . $e->getMessage();
}

// Teste 4: Verificar clientes do revendedor
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT u.id) 
        FROM usuarios u 
        JOIN apostas a ON u.id = a.usuario_id 
        WHERE a.revendedor_id = ?
    ");
    $stmt->execute([$revendedor_id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $testes_ok[] = "Você tem {$count} clientes com apostas registradas";
    } else {
        $informacoes[] = "Você ainda não tem clientes com apostas registradas";
    }
} catch (PDOException $e) {
    $testes_erro[] = "Erro ao verificar clientes do revendedor: " . $e->getMessage();
}

// Teste 5: Verificar jogos disponíveis
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM jogos");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $testes_ok[] = "Existem {$count} jogos disponíveis no sistema";
    } else {
        $testes_erro[] = "Não há jogos cadastrados no sistema";
    }
} catch (PDOException $e) {
    $testes_erro[] = "Erro ao verificar jogos disponíveis: " . $e->getMessage();
}

// Verificar integridade de relacionamentos
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM apostas a 
        LEFT JOIN usuarios u ON a.usuario_id = u.id 
        WHERE u.id IS NULL
    ");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $testes_erro[] = "Existem {$count} apostas com referência a usuários que não existem";
    } else {
        $testes_ok[] = "Integridade referencial entre apostas e usuários está correta";
    }
} catch (PDOException $e) {
    $testes_erro[] = "Erro ao verificar integridade referencial: " . $e->getMessage();
}

// Incluir o layout
ob_start();
?>

<div class="container-fluid">
    <h1 class="mt-4 mb-4">Diagnóstico do Banco de Dados</h1>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Esta ferramenta verifica o estado do banco de dados e apresenta informações que podem ajudar a identificar problemas.
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-check-circle"></i> Testes bem-sucedidos (<?php echo count($testes_ok); ?>)
                </div>
                <div class="card-body">
                    <?php if (empty($testes_ok)): ?>
                        <p class="text-muted">Nenhum teste foi bem-sucedido.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($testes_ok as $teste): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-check text-success me-2"></i> <?php echo $teste; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header <?php echo empty($testes_erro) ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <i class="fas <?php echo empty($testes_erro) ? 'fa-smile' : 'fa-exclamation-triangle'; ?>"></i> 
                    <?php echo empty($testes_erro) ? 'Nenhum problema encontrado' : 'Problemas encontrados (' . count($testes_erro) . ')'; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($testes_erro)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-thumbs-up"></i> Seu banco de dados parece estar configurado corretamente.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Foram encontrados problemas que precisam ser resolvidos.
                        </div>
                        <ul class="list-group">
                            <?php foreach ($testes_erro as $erro): ?>
                                <li class="list-group-item list-group-item-danger">
                                    <i class="fas fa-times text-danger me-2"></i> <?php echo $erro; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-info-circle"></i> Informações adicionais
        </div>
        <div class="card-body">
            <ul class="list-group">
                <?php foreach ($informacoes as $info): ?>
                    <li class="list-group-item">
                        <i class="fas fa-info-circle text-info me-2"></i> <?php echo $info; ?>
                    </li>
                <?php endforeach; ?>
                
                <li class="list-group-item">
                    <i class="fas fa-user text-primary me-2"></i> ID do revendedor atual: <?php echo $revendedor_id; ?>
                </li>
                <li class="list-group-item">
                    <i class="fas fa-database text-primary me-2"></i> Nome do banco de dados: <?php echo getenv('DB_DATABASE') ?: 'Informação não disponível'; ?>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-tools"></i> Ações disponíveis
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <a href="enviar_comprovantes_whatsapp.php" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-arrow-left"></i> Voltar para Envio de Comprovantes
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="clientes.php" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-users"></i> Gerenciar Clientes
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="diagnostico_db.php" class="btn btn-info btn-lg w-100">
                        <i class="fas fa-sync"></i> Executar Novamente
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?> 