<?php
// Exibir erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detectar ambiente
$is_production = (strpos($_SERVER['HTTP_HOST'] ?? '', 'lotominas.site') !== false);

// URLs e Paths
if ($is_production) {
    define('BASE_URL', 'https://lotominas.site');
} else {
    define('BASE_URL', 'https://loteria.test');
}
define('ROOT_PATH', dirname(__DIR__));

// Configurações do Banco de Dados
if ($is_production) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'lotominas_db');
    define('DB_USER', 'lotominas_user');
    define('DB_PASS', 'sua_senha_aqui');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'loteria');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

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