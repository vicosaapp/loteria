<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>Verificação de Caminhos</h1>';
echo '<pre>';

echo 'DOCUMENT_ROOT: ' . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo 'Caminho absoluto para esse arquivo: ' . __FILE__ . "\n";
echo 'Diretório desse arquivo: ' . __DIR__ . "\n\n";

$config_path = $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
echo 'Caminho para database.php: ' . $config_path . "\n";
echo 'O arquivo existe? ' . (file_exists($config_path) ? 'SIM' : 'NÃO') . "\n\n";

$alternativo1 = __DIR__ . '/../config/database.php';
echo 'Caminho alternativo 1: ' . $alternativo1 . "\n";
echo 'O arquivo existe? ' . (file_exists($alternativo1) ? 'SIM' : 'NÃO') . "\n\n";

$alternativo2 = '../../config/database.php';
echo 'Caminho alternativo 2: ' . $alternativo2 . "\n";
echo 'O arquivo existe? ' . (file_exists($alternativo2) ? 'SIM' : 'NÃO') . "\n\n";

echo '</pre>'; 