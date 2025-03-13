<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'loteria');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');

// String de conexão PDO
define('DB_DSN', "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET);

try {
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?> 