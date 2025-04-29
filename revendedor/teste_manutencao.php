<?php
// Arquivo de teste para o verificador de manutenção

// Primeira parte: configurar um ambiente de teste
define('AMBIENTE_TESTE', true);

// Incluir o banco de dados primeiro
require_once __DIR__ . '/../config/database.php';

// Definindo índices do $_SERVER que podem estar ausentes em CLI
$_SERVER['REQUEST_URI'] = '/revendedor/teste_manutencao.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['PHP_SELF'] = 'teste_manutencao.php';

// Incluir o verificador de manutenção
require_once __DIR__ . '/verificar_manutencao.php';

// Verificar se o sistema está em modo de manutenção
$manutencao = sistema_em_manutencao();

// Obter a mensagem de manutenção
$mensagem = obter_mensagem_manutencao();

// Exibir resultados
echo "\n=== TESTE DO VERIFICADOR DE MANUTENÇÃO ===\n\n";
echo "Status do Modo de Manutenção: " . ($manutencao ? "ATIVADO" : "DESATIVADO") . "\n";
echo "Mensagem de Manutenção: " . $mensagem . "\n";

// Testar consulta direta
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nDados da tabela configuracoes:\n";
    echo "modo_manutencao: " . $config['modo_manutencao'] . "\n";
    echo "mensagem_manutencao: " . $config['mensagem_manutencao'] . "\n";
} catch (Exception $e) {
    echo "Erro na consulta: " . $e->getMessage() . "\n";
}
?> 