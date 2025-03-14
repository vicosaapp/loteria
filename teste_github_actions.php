<?php
// Arquivo de teste para forçar um novo commit e acionar o GitHub Actions
echo "<h1>Teste do GitHub Actions</h1>";
echo "<p>Este arquivo foi atualizado para forçar um novo commit e acionar o GitHub Actions.</p>";
echo "<p>Data e hora: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Versão: 1.1</p>";
echo "<p>ID único para evitar cache: " . uniqid() . "</p>";
echo "<p>Teste de atualização: " . time() . "</p>";
?> 