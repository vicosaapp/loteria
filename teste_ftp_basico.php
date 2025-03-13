<?php
// Script de teste básico para verificar a funcionalidade do servidor

// Informações do sistema
echo "<h1>Teste de Funcionalidade do Servidor</h1>";

echo "<h2>Informações do PHP</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "<br>";

// Teste de escrita de arquivo
echo "<h2>Teste de Escrita de Arquivo</h2>";
$test_file = "teste_escrita_" . time() . ".txt";
$content = "Teste de escrita: " . date('Y-m-d H:i:s');

if (file_put_contents($test_file, $content)) {
    echo "✅ Arquivo criado com sucesso: $test_file<br>";
    echo "Conteúdo: " . htmlspecialchars($content) . "<br>";
    
    // Ler o arquivo
    $read_content = file_get_contents($test_file);
    if ($read_content === $content) {
        echo "✅ Leitura do arquivo bem-sucedida<br>";
    } else {
        echo "❌ Falha na leitura do arquivo<br>";
    }
    
    // Excluir o arquivo
    if (unlink($test_file)) {
        echo "✅ Arquivo excluído com sucesso<br>";
    } else {
        echo "❌ Falha ao excluir o arquivo<br>";
    }
} else {
    echo "❌ Falha ao criar o arquivo<br>";
}

// Teste de diretório
echo "<h2>Teste de Diretório</h2>";
echo "Diretório atual: " . getcwd() . "<br>";
echo "Permissões do diretório: " . substr(sprintf('%o', fileperms('.')), -4) . "<br>";

// Listar alguns arquivos
echo "<h2>Arquivos no Diretório</h2>";
$files = scandir('.');
echo "<ul>";
$count = 0;
foreach ($files as $file) {
    if ($file != '.' && $file != '..' && $count < 10) {
        echo "<li>" . htmlspecialchars($file) . " - " . (is_dir($file) ? "Diretório" : "Arquivo") . "</li>";
        $count++;
    }
}
echo "</ul>";
if (count($files) > 10) {
    echo "... e mais " . (count($files) - 10) . " arquivos/diretórios.";
}

// Teste de comandos
echo "<h2>Teste de Comandos</h2>";
echo "Função exec() disponível: " . (function_exists('exec') ? "Sim" : "Não") . "<br>";
echo "Função system() disponível: " . (function_exists('system') ? "Sim" : "Não") . "<br>";
echo "Função shell_exec() disponível: " . (function_exists('shell_exec') ? "Sim" : "Não") . "<br>";

// Teste de extensões
echo "<h2>Extensões PHP</h2>";
$extensions = get_loaded_extensions();
$important_extensions = ['curl', 'ftp', 'json', 'openssl', 'pdo', 'pdo_mysql', 'mysqli', 'zip', 'gd'];
foreach ($important_extensions as $ext) {
    echo "$ext: " . (in_array($ext, $extensions) ? "✅ Disponível" : "❌ Não disponível") . "<br>";
}

echo "<h2>Conclusão</h2>";
echo "Se você consegue ver esta página e os testes de escrita de arquivo foram bem-sucedidos, o servidor está funcionando corretamente.<br>";
echo "Data e hora do teste: " . date('Y-m-d H:i:s');
?> 