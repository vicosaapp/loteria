<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste de Configuração</h1>";

echo "<h2>Informações do PHP</h2>";
echo "<p>Versão do PHP: " . phpversion() . "</p>";
echo "<p>Display Errors: " . ini_get('display_errors') . "</p>";
echo "<p>Error Reporting: " . ini_get('error_reporting') . "</p>";

echo "<h2>Teste de Banco de Dados</h2>";
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>Conexão com banco de dados estabelecida!</p>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Tabelas encontradas:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>Teste de Diretórios</h2>";
$dirs = ['admin', 'config', 'comprovantes', 'css'];
foreach ($dirs as $dir) {
    echo "<p>Diretório '$dir': " . 
         (is_dir($dir) ? 
         "<span style='color: green;'>existe</span>" : 
         "<span style='color: red;'>não existe</span>") . 
         "</p>";
} 