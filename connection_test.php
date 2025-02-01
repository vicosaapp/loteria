<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste de Conexão MySQL</h1>";

// Testar conexão mysqli primeiro
echo "<h2>Teste com MySQLi:</h2>";
try {
    $mysqli = new mysqli('localhost', 'root', '');
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
    echo "<p style='color:green'>Conexão MySQLi OK!</p>";
    $mysqli->close();
} catch (Exception $e) {
    echo "<p style='color:red'>Erro MySQLi: " . $e->getMessage() . "</p>";
}

// Testar conexão PDO
echo "<h2>Teste com PDO:</h2>";
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "<p style='color:green'>Conexão PDO OK!</p>";
    
    // Tentar criar o banco de dados
    $pdo->exec("CREATE DATABASE IF NOT EXISTS loteria");
    echo "<p>Banco de dados 'loteria' criado/verificado</p>";
    
    // Conectar ao banco loteria
    $pdo = new PDO("mysql:host=localhost;dbname=loteria", "root", "");
    echo "<p style='color:green'>Conexão com banco 'loteria' OK!</p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>Erro PDO: " . $e->getMessage() . "</p>";
} 