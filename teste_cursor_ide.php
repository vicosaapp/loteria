<?php
// Script para testar o upload via Cursor IDE

// Informações do arquivo
$file_info = [
    'nome' => 'teste_cursor_ide.php',
    'data_criacao' => date('Y-m-d H:i:s'),
    'versao' => '1.0',
    'descricao' => 'Este arquivo foi criado para testar o upload via Cursor IDE'
];

// Exibir informações
echo "<h1>Teste de Upload via Cursor IDE</h1>";

echo "<h2>Informações do Arquivo</h2>";
echo "<ul>";
foreach ($file_info as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>";
}
echo "</ul>";

// Criar um arquivo de teste
$test_file = "teste_cursor_" . time() . ".txt";
$content = "Este arquivo foi criado pelo script teste_cursor_ide.php em " . date('Y-m-d H:i:s');

if (file_put_contents($test_file, $content)) {
    echo "<h2>Arquivo de Teste Criado</h2>";
    echo "<p>Nome do arquivo: $test_file</p>";
    echo "<p>Conteúdo: " . htmlspecialchars($content) . "</p>";
    
    // Adicionar instruções
    echo "<h2>Próximos Passos</h2>";
    echo "<ol>";
    echo "<li>Edite este arquivo no Cursor IDE</li>";
    echo "<li>Adicione a linha: \$file_info['modificado'] = 'Sim - " . date('Y-m-d H:i:s') . "';</li>";
    echo "<li>Salve o arquivo</li>";
    echo "<li>Atualize esta página no navegador</li>";
    echo "</ol>";
    
    echo "<p>Se você vir a informação 'modificado: Sim' após atualizar a página, significa que o upload via Cursor IDE está funcionando corretamente.</p>";
} else {
    echo "<h2>Erro</h2>";
    echo "<p>Não foi possível criar o arquivo de teste.</p>";
}

// Verificar se o arquivo foi modificado
if (isset($file_info['modificado'])) {
    echo "<h2>Resultado do Teste</h2>";
    echo "<p style='color: green; font-weight: bold;'>✅ O arquivo foi modificado com sucesso! O upload via Cursor IDE está funcionando.</p>";
    echo "<p>Data da modificação: " . $file_info['modificado'] . "</p>";
}
?> 