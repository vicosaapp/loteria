<?php
// Página inicial do diretório de testes
echo "<h1>Diretório de Testes</h1>";
echo "<p>Este diretório contém scripts de teste para o sistema LotoMinas.</p>";

echo "<h2>Scripts Disponíveis:</h2>";
echo "<ul>";
echo "<li><a href='teste_cursor.php'>Teste de Upload via Cursor IDE</a></li>";
echo "<li><a href='teste_ftp.php'>Teste de Conexão FTP</a></li>";
echo "<li><a href='teste_basico.php'>Teste Básico do Servidor</a></li>";
echo "</ul>";

echo "<p>Data e hora: " . date('Y-m-d H:i:s') . "</p>";
?> 