<?php
// Script para verificar o caminho exato do servidor

// Informações do sistema
echo "<h1>Verificação do Caminho do Servidor</h1>";

echo "<h2>Informações do Sistema</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li><strong>Current Script:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "<li><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "<li><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</li>";
echo "<li><strong>Server Name:</strong> " . $_SERVER['SERVER_NAME'] . "</li>";
echo "</ul>";

// Verificar diretórios
echo "<h2>Verificação de Diretórios</h2>";

$directories = [
    '/www/wwwroot/lotominas.site/',
    '/www/wwwroot/',
    '/www/',
    '/home/wwwroot/lotominas.site/',
    '/home/wwwroot/',
    '/home/',
    '/var/www/html/',
    '/var/www/',
    '/var/',
    $_SERVER['DOCUMENT_ROOT'],
    dirname($_SERVER['DOCUMENT_ROOT']),
    dirname(dirname($_SERVER['DOCUMENT_ROOT'])),
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Diretório</th><th>Existe</th><th>Permissões</th><th>É Gravável</th></tr>";

foreach ($directories as $dir) {
    echo "<tr>";
    echo "<td>$dir</td>";
    
    if (file_exists($dir)) {
        echo "<td style='color: green;'>Sim</td>";
        echo "<td>" . substr(sprintf('%o', fileperms($dir)), -4) . "</td>";
        echo "<td>" . (is_writable($dir) ? "<span style='color: green;'>Sim</span>" : "<span style='color: red;'>Não</span>") . "</td>";
    } else {
        echo "<td style='color: red;'>Não</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Verificar arquivo atual
echo "<h2>Verificação do Arquivo Atual</h2>";
echo "<p><strong>Caminho completo:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Diretório:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Permissões do arquivo:</strong> " . substr(sprintf('%o', fileperms(__FILE__)), -4) . "</p>";
echo "<p><strong>Permissões do diretório:</strong> " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "</p>";
echo "<p><strong>Arquivo gravável:</strong> " . (is_writable(__FILE__) ? "Sim" : "Não") . "</p>";
echo "<p><strong>Diretório gravável:</strong> " . (is_writable(__DIR__) ? "Sim" : "Não") . "</p>";

// Criar arquivo de teste
echo "<h2>Teste de Criação de Arquivo</h2>";
$test_file = "teste_caminho_" . time() . ".txt";
$content = "Teste de escrita: " . date('Y-m-d H:i:s') . "\n";
$content .= "Caminho: " . __DIR__ . "\n";
$content .= "ID único: " . uniqid() . "\n";

if (file_put_contents($test_file, $content)) {
    echo "<p style='color: green;'>✅ Arquivo criado com sucesso: <strong>$test_file</strong></p>";
    echo "<p><strong>Conteúdo:</strong></p>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
    echo "<p><strong>Caminho completo:</strong> " . realpath($test_file) . "</p>";
    echo "<p><strong>Permissões:</strong> " . substr(sprintf('%o', fileperms($test_file)), -4) . "</p>";
    
    // Excluir o arquivo de teste
    if (unlink($test_file)) {
        echo "<p style='color: green;'>✅ Arquivo excluído com sucesso.</p>";
    } else {
        echo "<p style='color: red;'>❌ Falha ao excluir o arquivo.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Falha ao criar o arquivo de teste.</p>";
}

echo "<p>Data e hora da verificação: " . date('Y-m-d H:i:s') . "</p>";
?> 