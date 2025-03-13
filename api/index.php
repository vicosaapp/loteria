<?php
require_once __DIR__ . '/../config/config.php';

// Configurar headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisição OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Obter caminho da requisição
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = parse_url(BASE_URL, PHP_URL_PATH);
$api_path = str_replace($base_path . '/api', '', $request_uri);
$path = trim($api_path, '/');

// Log para debug
error_log("API Request: " . $path);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers: " . json_encode(getallheaders()));

// Rotear requisição
switch ($path) {
    case 'login':
        require __DIR__ . '/routes/login.php';
        break;
        
    case 'revendedor/dashboard':
        require __DIR__ . '/routes/revendedor/dashboard.php';
        break;
        
    case 'v1/login':
        require __DIR__ . '/routes/login.php';
        break;
        
    case 'v1/dashboard':
        require __DIR__ . '/routes/revendedor/dashboard.php';
        break;
        
    case 'v1/test':
    case 'test':
        require __DIR__ . '/routes/test.php';
        break;
        
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Rota não encontrada: ' . $path
        ]);
        break;
} 