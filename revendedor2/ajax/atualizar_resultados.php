<?php
header('Content-Type: application/json');

require_once '../../config/database.php';

// URLs das APIs da Caixa
$api_urls = [
    'quina' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/quina/',
    'megasena' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/megasena/',
    'lotofacil' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/lotofacil/',
    'lotomania' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/lotomania/',
    'timemania' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/timemania/',
    'duplasena' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/duplasena/',
    'maismilionaria' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/maismilionaria/',
    'diadesorte' => 'https://servicebus2.caixa.gov.br/portaldeloterias/api/diadesorte/'
];

function buscarResultado($url) {
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
        throw new Exception("Erro ao buscar resultados: " . $error);
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao decodificar resposta da API: " . json_last_error_msg());
    }
    
    return $data;
}

function salvarResultado($pdo, $jogo_id, $numero_concurso, $data_sorteio, $dezenas, $resultado = []) {
    try {
        $pdo->beginTransaction();
        
        // Formata a data corretamente
        $data_sorteio = date('Y-m-d', strtotime(str_replace('/', '-', $data_sorteio)));
        
        // Verifica se o concurso já existe
        $stmt = $pdo->prepare("SELECT id FROM concursos WHERE jogo_id = ? AND codigo = ?");
        $stmt->execute([$jogo_id, $numero_concurso]);
        $concurso_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($concurso_existente) {
            // Atualiza concurso existente
            $stmt = $pdo->prepare("UPDATE concursos SET data_sorteio = ?, status = 'finalizado' WHERE id = ?");
            $stmt->execute([$data_sorteio, $concurso_existente['id']]);
            $concurso_id = $concurso_existente['id'];
            
            // Remove números antigos
            $stmt = $pdo->prepare("DELETE FROM numeros_sorteados WHERE concurso_id = ?");
            $stmt->execute([$concurso_id]);
        } else {
            // Insere novo concurso
            $stmt = $pdo->prepare("INSERT INTO concursos (jogo_id, codigo, data_sorteio, status) VALUES (?, ?, ?, 'finalizado')");
            $stmt->execute([$jogo_id, $numero_concurso, $data_sorteio]);
            $concurso_id = $pdo->lastInsertId();
        }
        
        // Insere números sorteados
        if (!empty($dezenas)) {
            $stmt = $pdo->prepare("INSERT INTO numeros_sorteados (concurso_id, numero) VALUES (?, ?)");
            foreach ($dezenas as $dezena) {
                $stmt->execute([$concurso_id, intval($dezena)]);
            }
        }
        
        // Atualiza informações adicionais do jogo
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
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao salvar resultado: " . $e->getMessage());
        throw $e;
    }
}

try {
    $resultados_atualizados = 0;
    
    foreach ($api_urls as $jogo => $url) {
        try {
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
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar resultado do $jogo: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => "Resultados atualizados: $resultados_atualizados jogos",
        'resultados_atualizados' => $resultados_atualizados
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 