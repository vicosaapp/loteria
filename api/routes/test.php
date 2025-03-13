<?php
// Arquivo de teste para a API
// Este arquivo retorna uma resposta simples para verificar se a API está funcionando

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
    exit;
}

// Retornar resposta de sucesso
echo json_encode([
    'success' => true,
    'message' => 'API funcionando corretamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
        'host' => $_SERVER['HTTP_HOST'] ?? 'Desconhecido'
    ]
]); 