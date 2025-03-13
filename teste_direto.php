<?php
// Arquivo de teste simples para verificar redirecionamentos

// Desativar qualquer buffer de saída
if (ob_get_level()) ob_end_clean();

// Definir cabeçalhos para evitar cache
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Saída simples
echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste Direto</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .info { background-color: #f0f0f0; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Teste Direto - Sem Redirecionamento</h1>
    
    <p class='success'>Se você está vendo esta página, o arquivo PHP está sendo executado corretamente sem redirecionamentos.</p>
    
    <div class='info'>
        <h2>Informações do Servidor</h2>
        <p>Data e Hora: " . date('Y-m-d H:i:s') . "</p>
        <p>PHP Version: " . phpversion() . "</p>
        <p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>
        <p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>
    </div>
    
    <h2>Próximos Passos</h2>
    <ol>
        <li>Verifique se o arquivo <code>testes/teste_cursor.php</code> está acessível</li>
        <li>Configure o GitHub Actions conforme as instruções no README.md</li>
        <li>Faça um commit de teste para verificar o deploy automático</li>
    </ol>
</body>
</html>";
?> 