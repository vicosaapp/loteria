<?php
// Arquivo de diagnóstico para o GitHub Actions
echo "<h1>Diagnóstico do GitHub Actions</h1>";

// Informações básicas
echo "<h2>Informações Básicas</h2>";
echo "<ul>";
echo "<li><strong>Data e hora:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "</ul>";

// Criar arquivo de marcação de tempo
$timestamp_file = "github_timestamp_" . date('Ymd_His') . ".txt";
$timestamp_content = "Arquivo criado pelo GitHub Actions em: " . date('Y-m-d H:i:s') . "\n";
$timestamp_content .= "Este arquivo serve como marcador para verificar se o GitHub Actions está funcionando.\n";
$timestamp_content .= "ID único: " . uniqid() . "\n";

if (file_put_contents($timestamp_file, $timestamp_content)) {
    echo "<div style='background-color: #dff0d8; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #3c763d;'>✅ Arquivo de timestamp criado com sucesso!</h3>";
    echo "<p><strong>Nome do arquivo:</strong> $timestamp_file</p>";
    echo "<p><strong>Conteúdo:</strong></p>";
    echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 3px;'>" . htmlspecialchars($timestamp_content) . "</pre>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f2dede; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #a94442;'>❌ Falha ao criar arquivo de timestamp!</h3>";
    echo "<p>Verifique as permissões do diretório.</p>";
    echo "</div>";
}

// Listar arquivos recentes
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
    if ($count >= 20) break; // Limitar a 20 arquivos
    
    $row_class = $count % 2 == 0 ? 'background-color: #f9f9f9;' : '';
    echo "<tr style='$row_class'>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($file) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . date('Y-m-d H:i:s', $mtime) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . number_format(filesize($file) / 1024, 2) . " KB</td>";
    echo "</tr>";
    
    $count++;
}

echo "</table>";

// Instruções
echo "<h2>Próximos Passos</h2>";
echo "<ol>";
echo "<li>Faça commit e push deste arquivo para o GitHub</li>";
echo "<li>Verifique se o workflow do GitHub Actions foi acionado</li>";
echo "<li>Acesse esta página novamente após o workflow ser concluído</li>";
echo "<li>Verifique se o arquivo de timestamp foi criado</li>";
echo "</ol>";

echo "<p><strong>Nota:</strong> Se o arquivo de timestamp não for criado, o GitHub Actions não está funcionando corretamente.</p>";
echo "<p><strong>Timestamp da execução:</strong> " . time() . "</p>";
?> 