<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>Teste de Conexão com Banco de Dados</h1>';
echo '<pre>';

try {
    // Tentativa com caminho absoluto
    $config_path = $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
    if (file_exists($config_path)) {
        echo "Usando caminho absoluto: $config_path\n";
        require_once $config_path;
    } else {
        // Caminho relativo
        $caminho_relativo = __DIR__ . '/../config/database.php';
        echo "Usando caminho relativo: $caminho_relativo\n";
        require_once $caminho_relativo;
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Conexão estabelecida com sucesso!\n\n";
    
    // Verificar configurações
    $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Configurações encontradas:\n";
    print_r($config);
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

echo '</pre>'; 