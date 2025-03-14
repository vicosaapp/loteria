<?php
// Arquivo de teste final para verificar o upload automático via SFTP no Cursor IDE
echo "<h1>Teste Final de Upload Automático via SFTP</h1>";

// Informações do sistema
echo "<h2>Informações do Sistema</h2>";
echo "<ul>";
echo "<li><strong>Data e hora:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "</ul>";

// Informações do arquivo
echo "<h2>Informações do Arquivo</h2>";
echo "<ul>";
echo "<li><strong>Nome do arquivo:</strong> " . basename(__FILE__) . "</li>";
echo "<li><strong>Caminho completo:</strong> " . __FILE__ . "</li>";
echo "<li><strong>Última modificação:</strong> " . date('Y-m-d H:i:s', filemtime(__FILE__)) . "</li>";
echo "<li><strong>Tamanho:</strong> " . number_format(filesize(__FILE__) / 1024, 2) . " KB</li>";
echo "</ul>";

// Criar arquivo de teste
echo "<h2>Teste de Criação de Arquivo</h2>";
$test_file = "teste_final_" . time() . ".txt";
$content = "Teste final de SFTP: " . date('Y-m-d H:i:s') . "\n";
$content .= "Este arquivo foi criado para testar o upload automático via SFTP no Cursor IDE.\n";
$content .= "ID único: " . uniqid() . "\n";

if (file_put_contents($test_file, $content)) {
    echo "<div style='background-color: #dff0d8; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #3c763d;'>✅ Arquivo criado com sucesso!</h3>";
    echo "<p><strong>Nome do arquivo:</strong> $test_file</p>";
    echo "<p><strong>Conteúdo:</strong></p>";
    echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 3px;'>" . htmlspecialchars($content) . "</pre>";
    echo "</div>";
    
    // Excluir o arquivo de teste
    if (unlink($test_file)) {
        echo "<p style='color: green;'>✅ Arquivo excluído com sucesso.</p>";
    } else {
        echo "<p style='color: red;'>❌ Falha ao excluir o arquivo.</p>";
    }
} else {
    echo "<div style='background-color: #f2dede; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #a94442;'>❌ Falha ao criar arquivo de teste!</h3>";
    echo "<p>Verifique as permissões do diretório.</p>";
    echo "</div>";
}

// Verificar configuração do SFTP
echo "<h2>Verificação da Configuração do SFTP</h2>";
echo "<p>Para verificar se o upload automático via SFTP está funcionando:</p>";
echo "<ol>";
echo "<li>Edite este arquivo no Cursor IDE</li>";
echo "<li>Altere a versão abaixo para 1.1</li>";
echo "<li>Salve o arquivo (Ctrl+S)</li>";
echo "<li>Atualize esta página no navegador</li>";
echo "<li>Verifique se a versão foi atualizada</li>";
echo "</ol>";

echo "<div style='background-color: #d9edf7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3 style='color: #31708f;'>Versão atual: 1.0</h3>";
echo "<p>Se você ver '1.0', o arquivo ainda não foi atualizado.</p>";
echo "<p>Se você ver '1.1', o upload automático está funcionando!</p>";
echo "</div>";

// Instruções finais
echo "<h2>Próximos Passos</h2>";
echo "<p>Se o upload automático estiver funcionando, você pode:</p>";
echo "<ol>";
echo "<li>Desenvolver seu projeto normalmente no Cursor IDE</li>";
echo "<li>Salvar os arquivos para enviá-los automaticamente para o servidor</li>";
echo "<li>Acessar o site para verificar as alterações</li>";
echo "</ol>";

echo "<p>Se o upload automático não estiver funcionando, consulte o guia <a href='GUIA_SFTP_CURSOR.md'>GUIA_SFTP_CURSOR.md</a> para solução de problemas.</p>";

echo "<p><strong>Timestamp da execução:</strong> " . time() . "</p>";
?> 