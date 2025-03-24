<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/resultados_functions.php';

echo "<h1>Teste de Web Scraping - Loterias Caixa</h1>";
echo "<pre>";

$jogos = ['megasena', 'lotofacil', 'quina', 'lotomania', 'timemania', 'duplasena', 'maismilionaria', 'diadesorte'];

foreach ($jogos as $jogo) {
    echo "\n\n=== Testando $jogo ===\n";
    
    try {
        // Limpar cache para forçar nova busca
        $cache_file = __DIR__ . "/../../cache/resultados_{$jogo}.json";
        if (file_exists($cache_file)) {
            unlink($cache_file);
            echo "Cache limpo para $jogo\n";
        }
        
        $resultado = buscarResultadosAPI($jogo);
        
        if ($resultado && is_array($resultado)) {
            // Validar campos obrigatórios
            $campos_obrigatorios = ['numero', 'dataApuracao', 'dezenas'];
            $campos_faltando = [];
            
            foreach ($campos_obrigatorios as $campo) {
                if (!isset($resultado[$campo]) || empty($resultado[$campo])) {
                    $campos_faltando[] = $campo;
                }
            }
            
            if (!empty($campos_faltando)) {
                echo "✗ Erro: Campos obrigatórios faltando: " . implode(', ', $campos_faltando) . "\n";
                continue;
            }
            
            // Validar formato das dezenas
            $dezenas_invalidas = false;
            foreach ($resultado['dezenas'] as $dezena) {
                if (!is_numeric($dezena) || strlen($dezena) > 2) {
                    $dezenas_invalidas = true;
                    break;
                }
            }
            
            if ($dezenas_invalidas) {
                echo "✗ Erro: Formato inválido nas dezenas\n";
                continue;
            }
            
            echo "✓ Dados obtidos com sucesso!\n";
            echo "Concurso: " . $resultado['numero'] . "\n";
            echo "Data: " . $resultado['dataApuracao'] . "\n";
            echo "Dezenas: " . implode(',', $resultado['dezenas']) . "\n";
            echo "Valor Acumulado: R$ " . number_format($resultado['valorAcumulado'], 2, ',', '.') . "\n";
            echo "Próximo Concurso: " . $resultado['dataProximoConcurso'] . "\n";
            echo "Valor Estimado: R$ " . number_format($resultado['valorEstimadoProximoConcurso'], 2, ',', '.') . "\n";
            
            // Salvar resultado em arquivo para debug
            $debug_file = __DIR__ . "/../../cache/debug_{$jogo}.txt";
            file_put_contents($debug_file, print_r($resultado, true));
            echo "\nLog detalhado salvo em: $debug_file";
        } else {
            echo "✗ Erro ao obter dados\n";
            echo "Verifique o arquivo de log para mais detalhes\n";
        }
    } catch (Exception $e) {
        echo "✗ Exceção: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    echo "\n" . str_repeat('-', 80);
}

echo "</pre>"; 