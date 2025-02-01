<?php
// Exibir erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// URLs e Paths
define('BASE_URL', 'http://loteria.test');
define('ROOT_PATH', dirname(__DIR__));

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'loteria');
define('DB_USER', 'root');
define('DB_PASS', '');

// Funções úteis
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
} 