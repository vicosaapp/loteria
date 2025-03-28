<?php
header('Content-Type: text/plain; charset=utf-8');
require_once '../includes/resultados_functions.php';

echo "=== Teste de Ambiente de Produção ===\n\n";

// Informações do ambiente
echo "Informações do Ambiente:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Sistema Operacional: " . php_uname() . "\n";
echo "Servidor Web: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "cURL Version: " . curl_version()['version'] . "\n";
echo "SSL Version: " . curl_version()['ssl_version'] . "\n\n";

// Verificar extensões necessárias
echo "Extensões PHP:\n";
$extensoes = ['curl', 'json', 'openssl', 'pdo', 'pdo_mysql'];
foreach ($extensoes as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "Instalada" : "Não instalada") . "\n";
}
echo "\n";

// Verificar diretório de cache
$cache_dir = __DIR__ . "/../../cache";
echo "Teste de Cache:\n";
echo "Diretório: $cache_dir\n";
echo "Existe: " . (file_exists($cache_dir) ? "Sim" : "Não") . "\n";
echo "Permissões: " . decoct(fileperms($cache_dir)) . "\n";
echo "Gravável: " . (is_writable($cache_dir) ? "Sim" : "Não") . "\n";
echo "Owner: " . fileowner($cache_dir) . "\n";
echo "Group: " . filegroup($cache_dir) . "\n\n";

// Testar conexão com APIs
echo "Teste de Conexão com APIs:\n\n";

// Testar Caixa
$url = "https://servicebus2.caixa.gov.br/portaldeloterias/api/megasena";
echo "Testando Caixa ($url):\n";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 30
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
echo "Status HTTP: $httpCode\n";
echo "Erro cURL: " . ($error ? $error : "Nenhum") . "\n";
echo "Resposta: " . substr($response, 0, 200) . "...\n\n";
curl_close($ch);

// Testar API Alt 1
$url = "https://loteriascaixa-api.herokuapp.com/api/mega-sena/latest";
echo "Testando API Alt 1 ($url):\n";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 30
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
echo "Status HTTP: $httpCode\n";
echo "Erro cURL: " . ($error ? $error : "Nenhum") . "\n";
echo "Resposta: " . substr($response, 0, 200) . "...\n\n";
curl_close($ch);

// Testar API Alt 2
$url = "https://apiloterias.com.br/app/resultado/mega-sena/latest";
echo "Testando API Alt 2 ($url):\n";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 30
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
echo "Status HTTP: $httpCode\n";
echo "Erro cURL: " . ($error ? $error : "Nenhum") . "\n";
echo "Resposta: " . substr($response, 0, 200) . "...\n\n";
curl_close($ch);

// Testar busca de resultados
echo "Teste de Busca de Resultados:\n\n";

$jogos = ['megasena', 'lotofacil', 'quina', 'lotomania', 'timemania', 'duplasena', 'maismilionaria', 'diadesorte'];

foreach ($jogos as $jogo) {
    echo "Testando $jogo:\n";
    $resultado = buscarResultadosAPI($jogo);
    
    if ($resultado) {
        echo "✓ Sucesso!\n";
        echo "Concurso: " . $resultado['numero'] . "\n";
        echo "Data: " . $resultado['dataApuracao'] . "\n";
        echo "Dezenas: " . implode(',', $resultado['dezenas']) . "\n\n";
    } else {
        echo "✗ Falha!\n\n";
    }
}

echo "Teste concluído!\n"; 