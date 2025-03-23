<?php
// Habilitar exibição de erros sem saída direta
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Limpar qualquer variável de logs anterior
$debug_logs = [];

function processar_resultados() {
    global $debug_logs, $pdo;
    
    // URLs da API para cada jogo
    $api_urls = [
        'megasena' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/megasena',
        'lotofacil' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil',
        'quina' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/quina',
        'lotomania' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/lotomania',
        'timemania' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/timemania',
        'duplasena' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/duplasena',
        'maismilionaria' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/maismilionaria',
        'diadesorte' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/diadesorte'
    ];
    
    try {
        $debug_logs[] = "Iniciando processamento de resultados";
        $resultados_atualizados = 0;
        
        foreach ($api_urls as $jogo => $url) {
            try {
                $debug_logs[] = "Processando jogo: " . $jogo;
                $resultado = buscarResultado($url);
                
                if ($resultado) {
                    // Buscar ID do jogo no banco de dados
                    $stmt = $pdo->prepare("SELECT id FROM jogos WHERE LOWER(identificador_api) = LOWER(?)");
                    $stmt->execute([$jogo]);
                    $jogo_db = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($jogo_db) {
                        // Prepara as dezenas na ordem correta
                        $dezenas = isset($resultado['listaDezenas']) ? $resultado['listaDezenas'] : [];
                        
                        // Prepara os dados do resultado
                        $dados_resultado = [
                            'valorAcumulado' => $resultado['valorAcumuladoProximoConcurso'] ?? 0,
                            'dataProximoConcurso' => $resultado['dataProximoConcurso'] ?? null,
                            'valorEstimadoProximoConcurso' => $resultado['valorEstimadoProximoConcurso'] ?? 0
                        ];
                        
                        // Se for Dupla Sena, inclui as dezenas do segundo sorteio
                        if ($jogo === 'duplasena' && isset($resultado['listaDezenasSegundoSorteio'])) {
                            $dezenas = array_merge($dezenas, $resultado['listaDezenasSegundoSorteio']);
                        }
                        
                        salvarResultado(
                            $pdo,
                            $jogo_db['id'],
                            $resultado['numero'],
                            $resultado['dataApuracao'],
                            $dezenas,
                            $dados_resultado
                        );
                        
                        $resultados_atualizados++;
                        $debug_logs[] = "Resultado do jogo {$jogo} atualizado com sucesso";
                    } else {
                        $debug_logs[] = "Jogo {$jogo} não encontrado no banco de dados";
                    }
                }
            } catch (Exception $e) {
                $debug_logs[] = "Erro ao processar jogo {$jogo}: " . $e->getMessage();
                // Continua para o próximo jogo mesmo se houver erro
                continue;
            }
        }
        
        $debug_logs[] = "Total de resultados atualizados: " . $resultados_atualizados;
        return $debug_logs;
        
    } catch (Exception $e) {
        $debug_logs[] = "Erro ao processar resultados: " . $e->getMessage();
        throw $e;
    }
}

// Funções auxiliares
function buscarResultado($url) {
    global $debug_logs;
    $debug_logs[] = "Buscando resultado da URL: " . $url;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        $debug_logs[] = "Erro ao buscar resultados: " . $error;
        throw new Exception("Erro ao buscar resultados: " . $error);
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $debug_logs[] = "Erro ao decodificar resposta da API: " . json_last_error_msg();
        throw new Exception("Erro ao decodificar resposta da API: " . json_last_error_msg());
    }
    
    $debug_logs[] = "Resultado obtido com sucesso";
    return $data;
}

function salvarResultado($pdo, $jogo_id, $numero_concurso, $data_sorteio, $dezenas, $resultado) {
    global $debug_logs;
    $debug_logs[] = "Salvando resultado para o jogo ID: " . $jogo_id;
    
    try {
        $pdo->beginTransaction();
        
        // Formatar a data do sorteio
        $data_sorteio = date('Y-m-d', strtotime(str_replace('/', '-', $data_sorteio)));
        
        // Verificar se o concurso já existe
        $stmt = $pdo->prepare("SELECT id FROM concursos WHERE jogo_id = ? AND codigo = ?");
        $stmt->execute([$jogo_id, $numero_concurso]);
        $concurso = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($concurso) {
            $debug_logs[] = "Concurso já existe, atualizando...";
            $concurso_id = $concurso['id'];
            
            // Atualizar concurso
            $stmt = $pdo->prepare("UPDATE concursos SET data_sorteio = ?, status = 'finalizado' WHERE id = ?");
            $stmt->execute([$data_sorteio, $concurso_id]);
            
            // Limpar números antigos
            $stmt = $pdo->prepare("DELETE FROM numeros_sorteados WHERE concurso_id = ?");
            $stmt->execute([$concurso_id]);
        } else {
            $debug_logs[] = "Criando novo concurso...";
            // Inserir novo concurso
            $stmt = $pdo->prepare("INSERT INTO concursos (jogo_id, codigo, data_sorteio, status) VALUES (?, ?, ?, 'finalizado')");
            $stmt->execute([$jogo_id, $numero_concurso, $data_sorteio]);
            $concurso_id = $pdo->lastInsertId();
        }
        
        // Inserir números sorteados
        $stmt = $pdo->prepare("INSERT INTO numeros_sorteados (concurso_id, numero) VALUES (?, ?)");
        foreach ($dezenas as $numero) {
            $stmt->execute([$concurso_id, $numero]);
        }
        
        // Atualizar informações do jogo
        $stmt = $pdo->prepare("UPDATE jogos SET 
            valor_acumulado = ?,
            data_proximo_concurso = ?,
            valor_estimado_proximo = ?,
            numero_concurso = ?
            WHERE id = ?");
        
        $data_proximo = null;
        if (!empty($resultado['dataProximoConcurso'])) {
            $data_proximo = date('Y-m-d', strtotime(str_replace('/', '-', $resultado['dataProximoConcurso'])));
        }
        
        $stmt->execute([
            floatval($resultado['valorAcumulado'] ?? 0),
            $data_proximo,
            floatval($resultado['valorEstimadoProximoConcurso'] ?? 0),
            $numero_concurso,
            $jogo_id
        ]);
        
        $pdo->commit();
        $debug_logs[] = "Resultado salvo com sucesso";
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $debug_logs[] = "Erro ao salvar resultado: " . $e->getMessage();
        throw $e;
    }
}

// Apenas definir a função, não executar nada diretamente
// Se o arquivo for chamado diretamente, não fazer nada
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // Silenciosamente sair, não enviar nenhuma saída
} 