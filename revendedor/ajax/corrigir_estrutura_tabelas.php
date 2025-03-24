<?php
// Habilitar exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuração do banco de dados
require_once '../../config/database.php';

// Função para verificar se uma coluna existe em uma tabela
function coluna_existe($pdo, $tabela, $coluna) {
    try {
        $sql = "SHOW COLUMNS FROM {$tabela} LIKE '{$coluna}'";
        $stmt = $pdo->query($sql);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Função para adicionar coluna se não existir
function adicionar_coluna_se_necessario($pdo, $tabela, $coluna, $definicao) {
    if (!coluna_existe($pdo, $tabela, $coluna)) {
        try {
            $sql = "ALTER TABLE {$tabela} ADD COLUMN {$coluna} {$definicao}";
            $pdo->exec($sql);
            echo "Coluna '{$coluna}' adicionada à tabela '{$tabela}'.<br>";
        } catch (Exception $e) {
            echo "Erro ao adicionar coluna '{$coluna}' à tabela '{$tabela}': " . $e->getMessage() . "<br>";
        }
    } else {
        echo "Coluna '{$coluna}' já existe na tabela '{$tabela}'.<br>";
    }
}

// Verificar e adicionar colunas necessárias
try {
    // Verificar tabela apostas
    if ($pdo->query("SHOW TABLES LIKE 'apostas'")->rowCount() > 0) {
        adicionar_coluna_se_necessario($pdo, 'apostas', 'concurso', 'VARCHAR(50) NULL');
        adicionar_coluna_se_necessario($pdo, 'apostas', 'processado', 'TINYINT(1) DEFAULT 0');
        adicionar_coluna_se_necessario($pdo, 'apostas', 'valor_premio', 'DECIMAL(10,2) DEFAULT 0');
    } else {
        echo "Tabela 'apostas' não encontrada.<br>";
    }
    
    // Verificar tabela apostas_importadas
    if ($pdo->query("SHOW TABLES LIKE 'apostas_importadas'")->rowCount() > 0) {
        adicionar_coluna_se_necessario($pdo, 'apostas_importadas', 'concurso', 'VARCHAR(50) NULL');
        adicionar_coluna_se_necessario($pdo, 'apostas_importadas', 'processado', 'TINYINT(1) DEFAULT 0');
        adicionar_coluna_se_necessario($pdo, 'apostas_importadas', 'valor_premio', 'DECIMAL(10,2) DEFAULT 0');
    } else {
        echo "Tabela 'apostas_importadas' não encontrada.<br>";
    }
    
    echo "<br>Verificação e correção concluídas com sucesso!";
    
} catch (Exception $e) {
    echo "Erro durante a verificação e correção das tabelas: " . $e->getMessage();
}

session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Array para armazenar logs
    $logs = [];
    
    // 1. Verificar e adicionar coluna api_nome na tabela jogos
    $stmt = $pdo->query("SHOW COLUMNS FROM jogos LIKE 'api_nome'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE jogos ADD COLUMN api_nome VARCHAR(50) AFTER nome");
        $logs[] = "Coluna api_nome adicionada à tabela jogos";
        
        // Atualizar api_nome para jogos existentes
        $pdo->exec("
            UPDATE jogos 
            SET api_nome = CASE 
                WHEN nome LIKE '%Mega-Sena%' THEN 'mega-sena'
                WHEN nome LIKE '%Lotofácil%' THEN 'lotofacil'
                WHEN nome LIKE '%Quina%' THEN 'quina'
                WHEN nome LIKE '%Lotomania%' THEN 'lotomania'
                WHEN nome LIKE '%Timemania%' THEN 'timemania'
                WHEN nome LIKE '%Dia de Sorte%' THEN 'dia-de-sorte'
                WHEN nome LIKE '%+Milionária%' THEN 'mais-milionaria'
                ELSE LOWER(REPLACE(nome, ' ', '-'))
            END
            WHERE api_nome IS NULL
        ");
        $logs[] = "Valores padrão de api_nome atualizados";
    }
    
    // 2. Verificar e adicionar coluna identificador_api na tabela jogos
    $stmt = $pdo->query("SHOW COLUMNS FROM jogos LIKE 'identificador_api'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE jogos ADD COLUMN identificador_api VARCHAR(50) AFTER api_nome");
        $logs[] = "Coluna identificador_api adicionada à tabela jogos";
        
        // Atualizar identificador_api para jogos existentes
        $pdo->exec("
            UPDATE jogos 
            SET identificador_api = CASE 
                WHEN nome LIKE '%Mega-Sena%' THEN 'megasena'
                WHEN nome LIKE '%Lotofácil%' THEN 'lotofacil'
                WHEN nome LIKE '%Quina%' THEN 'quina'
                WHEN nome LIKE '%Lotomania%' THEN 'lotomania'
                WHEN nome LIKE '%Timemania%' THEN 'timemania'
                WHEN nome LIKE '%Dia de Sorte%' THEN 'diadesorte'
                WHEN nome LIKE '%+Milionária%' THEN 'maismilionaria'
                ELSE LOWER(REPLACE(REPLACE(nome, ' ', ''), '-', ''))
            END
            WHERE identificador_api IS NULL
        ");
        $logs[] = "Valores padrão de identificador_api atualizados";
    }
    
    // 3. Verificar e adicionar coluna processado na tabela apostas
    $stmt = $pdo->query("SHOW COLUMNS FROM apostas LIKE 'processado'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE apostas ADD COLUMN processado TINYINT(1) DEFAULT 0 AFTER status");
        $logs[] = "Coluna processado adicionada à tabela apostas";
    }
    
    // 4. Verificar e adicionar coluna acertos na tabela apostas
    $stmt = $pdo->query("SHOW COLUMNS FROM apostas LIKE 'acertos'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE apostas ADD COLUMN acertos INT DEFAULT 0 AFTER processado");
        $logs[] = "Coluna acertos adicionada à tabela apostas";
    }
    
    // 5. Verificar e adicionar coluna concurso na tabela apostas
    $stmt = $pdo->query("SHOW COLUMNS FROM apostas LIKE 'concurso'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE apostas ADD COLUMN concurso VARCHAR(20) AFTER tipo_jogo_id");
        $logs[] = "Coluna concurso adicionada à tabela apostas";
    }
    
    // 6. Verificar e adicionar coluna concurso na tabela apostas_importadas
    $stmt = $pdo->query("SHOW COLUMNS FROM apostas_importadas LIKE 'concurso'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE apostas_importadas ADD COLUMN concurso VARCHAR(20) AFTER jogo_nome");
        $logs[] = "Coluna concurso adicionada à tabela apostas_importadas";
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Estrutura das tabelas atualizada com sucesso',
        'logs' => $logs
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao corrigir estrutura das tabelas: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar estrutura das tabelas: ' . $e->getMessage(),
        'logs' => $logs
    ]);
}
?> 