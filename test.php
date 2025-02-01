<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste de Conexão</h1>";

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=loteria;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green'>Conexão com banco de dados estabelecida com sucesso!</p>";
    
    // Testar se as tabelas existem
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tabelas encontradas:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Erro de conexão: " . $e->getMessage() . "</p>";
}
?> 