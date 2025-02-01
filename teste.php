<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Teste de Configuração</h1>";

echo "<h2>Configurações Básicas</h2>";
echo "<pre>";
echo "BASE_URL: " . BASE_URL . "\n";
echo "ROOT_PATH: " . ROOT_PATH . "\n";
echo "</pre>";

echo "<h2>Teste de Banco de Dados</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green'>Conexão com banco de dados OK!</p>";
} catch(PDOException $e) {
    echo "<p style='color:red'>Erro de conexão: " . $e->getMessage() . "</p>";
} 