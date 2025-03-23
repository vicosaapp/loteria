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
?> 