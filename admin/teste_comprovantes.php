<?php
header('Content-Type: text/plain; charset=utf-8');
require_once '../config/database.php';

echo "=== Teste de Geração de Comprovantes ===\n\n";

// Verificar diretório de assets
$assets_dir = __DIR__ . "/../assets/images";
echo "Verificando diretório de assets:\n";
echo "Diretório: $assets_dir\n";
echo "Existe: " . (file_exists($assets_dir) ? "Sim" : "Não") . "\n";
if (file_exists($assets_dir)) {
    echo "Permissões: " . decoct(fileperms($assets_dir)) . "\n";
    echo "Arquivos encontrados:\n";
    $files = scandir($assets_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file\n";
        }
    }
}
echo "\n";

// Verificar tabelas necessárias
echo "Verificando estrutura do banco:\n";
$tables = ['apostas_importadas', 'usuarios', 'jogos', 'valores_jogos'];
foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    echo "$table: " . ($stmt->rowCount() > 0 ? "Existe" : "Não existe") . "\n";
}
echo "\n";

// Testar busca de apostas
echo "Testando busca de apostas:\n";
$sql = "SELECT COUNT(*) as total FROM apostas_importadas";
$stmt = $pdo->query($sql);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Total de apostas: $total\n\n";

if ($total > 0) {
    // Pegar uma aposta aleatória para teste
    $sql = "SELECT ai.*, u.nome as apostador, r.nome as revendedor, j.nome as jogo_nome
            FROM apostas_importadas ai
            LEFT JOIN usuarios u ON ai.usuario_id = u.id
            LEFT JOIN usuarios r ON ai.revendedor_id = r.id
            LEFT JOIN jogos j ON j.titulo_importacao = ai.jogo_nome
            LIMIT 1";
    $stmt = $pdo->query($sql);
    $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($aposta) {
        echo "Aposta de teste encontrada:\n";
        echo "ID: " . $aposta['id'] . "\n";
        echo "Usuário: " . $aposta['apostador'] . "\n";
        echo "Jogo: " . $aposta['jogo_nome'] . "\n";
        echo "Números: " . $aposta['numeros'] . "\n\n";
        
        // Testar geração de comprovante
        echo "Testando geração de comprovante:\n";
        $url = "gerar_comprovante.php?usuario_id=" . $aposta['usuario_id'] . "&jogo=" . urlencode($aposta['jogo_nome']);
        echo "URL: $url\n";
        
        // Verificar se o arquivo existe
        $comprovante_path = __DIR__ . "/comprovantes/comprovante_" . $aposta['id'] . ".html";
        echo "Caminho do comprovante: $comprovante_path\n";
        echo "Existe: " . (file_exists($comprovante_path) ? "Sim" : "Não") . "\n";
        
        // Tentar gerar o comprovante
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        echo "Status HTTP: $httpCode\n";
        echo "Erro cURL: " . ($error ? $error : "Nenhum") . "\n";
        echo "Tamanho da resposta: " . strlen($response) . " bytes\n";
        
        curl_close($ch);
    } else {
        echo "Nenhuma aposta encontrada para teste\n";
    }
} else {
    echo "Nenhuma aposta cadastrada no sistema\n";
}

echo "\nTeste concluído!\n"; 