<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

require_once 'config/database.php';
require_once 'config/config.php';

// Se for uma requisição OPTIONS, retorna apenas os headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Teste de conexão com o banco
    $stmt = $pdo->query("SELECT 1");
    
    // Teste de configurações
    $config = [
        'base_url' => BASE_URL,
        'root_path' => ROOT_PATH,
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'database_connected' => true
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'API funcionando corretamente',
        'data' => $config
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro na conexão com o banco de dados: ' . $e->getMessage(),
        'data' => null
    ]);
} 