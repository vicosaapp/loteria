<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../includes/resultados_functions.php';

echo "<h1>Debug de Resultados</h1>";
echo "<pre>";

try {
    // 1. Verificar configuração do banco de dados
    echo "\n=== Configuração do Banco de Dados ===\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "User: " . DB_USERNAME . "\n";
    
    // 2. Testar conexão com o banco
    echo "\n=== Teste de Conexão ===\n";
    $pdo->query("SELECT 1");
    echo "Conexão com o banco OK\n";
    
    // 3. Verificar estrutura das tabelas
    echo "\n=== Estrutura da Tabela jogos ===\n";
    $colunas = $pdo->query("SHOW COLUMNS FROM jogos")->fetchAll(PDO::FETCH_ASSOC);
    print_r($colunas);
    
    echo "\n=== Estrutura da Tabela concursos ===\n";
    $colunas = $pdo->query("SHOW COLUMNS FROM concursos")->fetchAll(PDO::FETCH_ASSOC);
    print_r($colunas);
    
    echo "\n=== Estrutura da Tabela numeros_sorteados ===\n";
    $colunas = $pdo->query("SHOW COLUMNS FROM numeros_sorteados")->fetchAll(PDO::FETCH_ASSOC);
    print_r($colunas);
    
    // 4. Listar jogos cadastrados
    echo "\n=== Jogos Cadastrados ===\n";
    $jogos = $pdo->query("SELECT * FROM jogos WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
    print_r($jogos);
    
    // 5. Testar API para cada jogo
    echo "\n=== Teste de APIs ===\n";
    foreach ($jogos as $jogo) {
        echo "\nTestando API para {$jogo['nome']} ({$jogo['api_nome']}):\n";
        $resultado = buscarResultadosAPI($jogo['api_nome']);
        if ($resultado) {
            echo "✓ API respondeu com sucesso\n";
            echo "Concurso: " . ($resultado['numero'] ?? 'N/A') . "\n";
            echo "Data: " . ($resultado['dataApuracao'] ?? 'N/A') . "\n";
            echo "Dezenas: " . implode(',', $resultado['dezenas'] ?? []) . "\n";
        } else {
            echo "✗ Erro ao buscar resultado da API\n";
        }
    }
    
    // 6. Verificar últimos resultados salvos
    echo "\n=== Últimos Resultados Salvos ===\n";
    $sql = "
        SELECT 
            j.nome,
            j.numero_concurso,
            c.codigo as concurso_codigo,
            c.data_sorteio,
            GROUP_CONCAT(ns.numero ORDER BY ns.numero ASC) as dezenas
        FROM jogos j
        LEFT JOIN concursos c ON j.id = c.jogo_id
        LEFT JOIN numeros_sorteados ns ON c.id = ns.concurso_id
        WHERE j.status = 1
        GROUP BY j.id, c.id
        ORDER BY j.nome ASC, c.data_sorteio DESC
    ";
    $resultados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    print_r($resultados);
    
} catch (Exception $e) {
    echo "\n=== ERRO ===\n";
    echo $e->getMessage() . "\n";
    echo "Trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>"; 