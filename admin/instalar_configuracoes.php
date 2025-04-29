<?php
// Script para instalar a tabela de configurações

// Configurações de exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir conexão com o banco de dados
require_once '../config/database.php';

// Verificar se é admin
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso restrito a administradores']);
    exit;
}

// Carregar o SQL da tabela de configurações
$sqlFile = __DIR__ . '/sql/configuracoes.sql';

if (!file_exists($sqlFile)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo SQL não encontrado']);
    exit;
}

try {
    // Verificar se a tabela já existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'configuracoes'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Verificar se precisamos adicionar novos campos
        $stmt = $pdo->query("SHOW COLUMNS FROM configuracoes LIKE 'modo_manutencao'");
        $columnExists = $stmt->rowCount() > 0;
        
        if (!$columnExists) {
            // Adicionar os campos de manutenção
            $pdo->exec("
                ALTER TABLE configuracoes 
                ADD COLUMN `modo_manutencao` tinyint(1) NOT NULL DEFAULT 0 
                    COMMENT 'Modo de manutenção: 0 = desativado, 1 = ativado' AFTER `motivo_pausa`,
                ADD COLUMN `mensagem_manutencao` text DEFAULT 'Sistema em manutenção. Por favor, tente novamente mais tarde.' 
                    COMMENT 'Mensagem exibida durante a manutenção' AFTER `modo_manutencao`
            ");
            
            echo json_encode(['success' => true, 'message' => 'Campos de manutenção adicionados com sucesso!']);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => 'Tabela de configurações já existe e está atualizada']);
        exit;
    }
    
    // Ler o conteúdo do arquivo SQL
    $sql = file_get_contents($sqlFile);
    
    // Executar o SQL
    $pdo->exec($sql);
    
    echo json_encode(['success' => true, 'message' => 'Tabela de configurações instalada com sucesso!']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao instalar tabela: ' . $e->getMessage()]);
}
?> 