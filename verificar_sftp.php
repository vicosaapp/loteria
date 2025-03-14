<?php
// Script para verificar a conexão SFTP diretamente

echo "<h1>Verificação de Conexão SFTP</h1>";

// Informações do sistema
echo "<h2>Informações do Sistema</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li><strong>Current Script:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "</ul>";

// Verificar se o SFTP está instalado
echo "<h2>Verificação do SFTP</h2>";

if (function_exists('ssh2_connect')) {
    echo "<p style='color: green;'>✅ Função SSH2 está disponível no PHP.</p>";
} else {
    echo "<p style='color: red;'>❌ Função SSH2 não está disponível no PHP. A extensão SSH2 é necessária para SFTP.</p>";
}

// Criar arquivo de teste
echo "<h2>Teste de Criação de Arquivo</h2>";
$test_file = "teste_sftp_" . time() . ".txt";
$content = "Teste de SFTP: " . date('Y-m-d H:i:s') . "\n";
$content .= "Este arquivo foi criado para testar a conexão SFTP.\n";
$content .= "ID único: " . uniqid() . "\n";

if (file_put_contents($test_file, $content)) {
    echo "<p style='color: green;'>✅ Arquivo criado com sucesso: <strong>$test_file</strong></p>";
    echo "<p><strong>Caminho completo:</strong> " . realpath($test_file) . "</p>";
    echo "<p><strong>Conteúdo:</strong></p>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
    
    // Excluir o arquivo de teste
    if (unlink($test_file)) {
        echo "<p style='color: green;'>✅ Arquivo excluído com sucesso.</p>";
    } else {
        echo "<p style='color: red;'>❌ Falha ao excluir o arquivo.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Falha ao criar o arquivo de teste.</p>";
}

// Verificar arquivos recentes
echo "<h2>Arquivos Recentes</h2>";
$files = scandir('.');
$recent_files = [];

foreach ($files as $file) {
    if ($file == '.' || $file == '..') continue;
    
    if (is_file($file)) {
        $recent_files[$file] = filemtime($file);
    }
}

// Ordenar por data de modificação (mais recente primeiro)
arsort($recent_files);

echo "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>";
echo "<tr style='background-color: #f5f5f5;'>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Arquivo</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Última Modificação</th>";
echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Tamanho</th>";
echo "</tr>";

$count = 0;
foreach ($recent_files as $file => $mtime) {
    if ($count >= 10) break; // Limitar a 10 arquivos
    
    $row_class = $count % 2 == 0 ? 'background-color: #f9f9f9;' : '';
    echo "<tr style='$row_class'>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($file) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . date('Y-m-d H:i:s', $mtime) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . number_format(filesize($file) / 1024, 2) . " KB</td>";
    echo "</tr>";
    
    $count++;
}

echo "</table>";

// Instruções para o Cursor IDE
echo "<h2>Instruções para o Cursor IDE</h2>";
echo "<p>Para configurar o Cursor IDE para fazer upload automático via SFTP:</p>";
echo "<ol>";
echo "<li>Verifique se o arquivo <code>.vscode/sftp.json</code> está configurado corretamente:</li>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 3px;'>";
echo htmlspecialchars('{
    "name": "Servidor Lotominas",
    "host": "217.196.61.30",
    "protocol": "ftp",
    "port": 21,
    "username": "patto200",
    "password": "patto200",
    "remotePath": "/www/wwwroot/lotominas.site/",
    "uploadOnSave": true,
    "ignore": [
        ".vscode",
        ".git",
        ".DS_Store",
        "node_modules",
        "*.log"
    ],
    "passive": true,
    "debug": true
}');
echo "</pre>";
echo "<li>Salve um arquivo no Cursor IDE (Ctrl+S)</li>";
echo "<li>Verifique se o arquivo foi enviado para o servidor</li>";
echo "<li>Se o arquivo não foi enviado, verifique os logs do Cursor IDE</li>";
echo "</ol>";

echo "<p>Data e hora da verificação: " . date('Y-m-d H:i:s') . "</p>";
?> 