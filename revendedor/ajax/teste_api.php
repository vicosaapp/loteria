<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../includes/resultados_functions.php';

echo "<h1>Teste de Conexão com API da Caixa</h1>";
echo "<pre>";

$jogos = ['megasena', 'lotofacil', 'quina', 'lotomania', 'timemania', 'duplasena', 'maismilionaria', 'diadesorte'];

foreach ($jogos as $jogo) {
    echo "\n\n=== Testando $jogo ===\n";
    
    // Testar conexão direta via cURL
    $url = "https://servicebus2.caixa.gov.br/portaldeloterias/api/$jogo/";
    echo "URL: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_ENCODING, ''); // Aceitar encoding
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecionamentos
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Máximo de redirecionamentos
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout em segundos
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // Headers necessários para a API da Caixa
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Origin: https://loterias.caixa.gov.br',
        'Referer: https://loterias.caixa.gov.br/',
        'sec-ch-ua: "Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-site',
        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    echo "\nTestando conexão direta...\n";
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "HTTP Status: $httpCode\n";
    
    if (curl_errno($ch)) {
        echo "Erro cURL: " . curl_error($ch) . "\n";
    }
    
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    echo "Log detalhado:\n$verboseLog\n";
    
    if ($response) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Resposta válida recebida!\n";
            echo "Número do concurso: " . ($data['numero'] ?? 'N/A') . "\n";
            echo "Data: " . ($data['dataApuracao'] ?? 'N/A') . "\n";
            echo "Dezenas: " . implode(',', $data['dezenas'] ?? []) . "\n";
        } else {
            echo "Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
            echo "Resposta bruta: " . substr($response, 0, 500) . "...\n";
        }
    } else {
        echo "Nenhuma resposta recebida\n";
    }
    
    curl_close($ch);
    
    // Testar via função
    echo "\nTestando via função buscarResultadosAPI()...\n";
    $resultado = buscarResultadosAPI($jogo);
    
    if ($resultado) {
        echo "Função retornou dados com sucesso!\n";
        echo "Número do concurso: " . ($resultado['numero'] ?? 'N/A') . "\n";
        echo "Data: " . ($resultado['dataApuracao'] ?? 'N/A') . "\n";
        echo "Dezenas: " . implode(',', $resultado['dezenas'] ?? []) . "\n";
    } else {
        echo "Função não retornou dados\n";
    }
    
    echo "\n" . str_repeat('-', 80);
}

echo "</pre>"; 