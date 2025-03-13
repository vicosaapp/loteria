<?php
// Script para verificar as permissões dos arquivos no servidor

// Informações do sistema
echo "<h1>Verificação de Permissões</h1>";

echo "<h2>Informações do Sistema</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li><strong>Current Script:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "<li><strong>User:</strong> " . exec('whoami') . "</li>";
echo "</ul>";

// Verificar permissões do diretório atual
echo "<h2>Permissões do Diretório Atual</h2>";
echo "<p><strong>Diretório atual:</strong> " . getcwd() . "</p>";
echo "<p><strong>Permissões:</strong> " . substr(sprintf('%o', fileperms('.')), -4) . "</p>";

// Listar arquivos e permissões
echo "<h2>Arquivos e Permissões</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Arquivo</th><th>Tipo</th><th>Permissões</th><th>Proprietário</th><th>Grupo</th><th>Tamanho</th><th>Modificado</th></tr>";

$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $owner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($file))['name'] : fileowner($file);
        $group = function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($file))['name'] : filegroup($file);
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        $type = is_dir($file) ? 'Diretório' : 'Arquivo';
        
        echo "<tr>";
        echo "<td>$file</td>";
        echo "<td>$type</td>";
        echo "<td>$perms</td>";
        echo "<td>$owner</td>";
        echo "<td>$group</td>";
        echo "<td>$size</td>";
        echo "<td>$modified</td>";
        echo "</tr>";
    }
}
echo "</table>";

// Verificar se o arquivo de teste existe
echo "<h2>Verificação do Arquivo de Teste</h2>";
$test_file = 'teste_deploy.php';
if (file_exists($test_file)) {
    echo "<p style='color: green;'>✅ O arquivo de teste <strong>$test_file</strong> existe.</p>";
    echo "<p><strong>Permissões:</strong> " . substr(sprintf('%o', fileperms($test_file)), -4) . "</p>";
    echo "<p><strong>Conteúdo:</strong></p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($test_file)) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ O arquivo de teste <strong>$test_file</strong> não existe.</p>";
}

// Verificar se é possível criar arquivos
echo "<h2>Teste de Criação de Arquivo</h2>";
$test_write_file = 'teste_write_' . time() . '.txt';
$content = "Teste de escrita: " . date('Y-m-d H:i:s');

if (file_put_contents($test_write_file, $content)) {
    echo "<p style='color: green;'>✅ Arquivo criado com sucesso: <strong>$test_write_file</strong></p>";
    echo "<p><strong>Conteúdo:</strong> " . htmlspecialchars($content) . "</p>";
    echo "<p><strong>Permissões:</strong> " . substr(sprintf('%o', fileperms($test_write_file)), -4) . "</p>";
    
    // Excluir o arquivo de teste
    if (unlink($test_write_file)) {
        echo "<p style='color: green;'>✅ Arquivo excluído com sucesso.</p>";
    } else {
        echo "<p style='color: red;'>❌ Falha ao excluir o arquivo.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Falha ao criar o arquivo de teste.</p>";
}

echo "<p>Data e hora da verificação: " . date('Y-m-d H:i:s') . "</p>";
?> 