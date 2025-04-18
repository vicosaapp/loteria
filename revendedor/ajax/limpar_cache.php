<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Cria um arquivo de versão para forçar o navegador a recarregar arquivos JS e CSS
$version_file = '../version.txt';
file_put_contents($version_file, time());

// Limpar cache OPcache se estiver habilitado
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Resposta
echo json_encode([
    'success' => true,
    'message' => 'Cache limpo com sucesso. A página será recarregada.',
    'timestamp' => time()
]); 