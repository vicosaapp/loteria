<?php
session_start();

// Define as rotas permitidas e suas respectivas páginas
$routes = [
    '/' => 'index.php',
    '/login' => 'login.php',
    '/register' => 'register.php',
    '/fazer-aposta' => 'fazer_aposta.php',
    '/minhas-apostas' => 'minhas_apostas.php',
    '/admin/dashboard' => 'admin/dashboard.php',
    '/admin/usuarios' => 'admin/usuarios.php',
    '/admin/apostas' => 'admin/gerenciar_apostas.php'
];

// Pega a URI atual
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = rtrim($request_uri, '/');

// Se a rota existir, inclui o arquivo correspondente
if (array_key_exists($request_uri, $routes)) {
    require_once $routes[$request_uri];
} else {
    // Rota não encontrada
    header("HTTP/1.0 404 Not Found");
    require_once '404.php';
} 