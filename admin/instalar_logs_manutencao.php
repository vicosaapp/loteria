<?php
// Habilitar exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar se a requisição é AJAX
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Incluir configuração do banco de dados
require_once '../config/database.php';

// Iniciar sessão
session_start();

// Verificar se o usuário é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Caminho para o arquivo SQL
$sqlFile = __DIR__ . '/sql/logs_manutencao.sql';

try {
    // Verificar se o arquivo SQL existe
    if (!file_exists($sqlFile)) {
        echo json_encode(['success' => false, 'message' => 'Arquivo SQL não encontrado: ' . $sqlFile]);
        exit;
    }
    
    // Verificar se a tabela já existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'logs_manutencao'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo json_encode(['success' => true, 'message' => 'Tabela de logs de manutenção já existe']);
        exit;
    }
    
    // Ler o conteúdo do arquivo SQL
    $sql = file_get_contents($sqlFile);
    
    // Executar o SQL
    $pdo->exec($sql);
    
    echo json_encode(['success' => true, 'message' => 'Tabela de logs de manutenção instalada com sucesso!']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao instalar tabela: ' . $e->getMessage()]);
}
?> 