<?php

function buscarResultadosAPI($jogo) {
    error_log("\n=== Iniciando busca de resultados para $jogo ===");
    try {
        // Inicializar resultado com valores padrão
        $resultado = [
            'numero' => '',
            'dataApuracao' => date('Y-m-d'),
            'dezenas' => [],
            'valorAcumulado' => 0,
            'dataProximoConcurso' => date('Y-m-d'),
            'valorEstimadoProximoConcurso' => 0
        ];

        // Tentar ler do cache primeiro
        $cache_file = __DIR__ . "/../../cache/resultados_{$jogo}.json";
        $cache_tempo = 300; // 5 minutos
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_tempo)) {
            $cache = file_get_contents($cache_file);
            if ($cache) {
                $data = json_decode($cache, true);
                if ($data && !empty($data['numero']) && !empty($data['dezenas'])) {
                    error_log("Retornando dados do cache para $jogo");
                    return $data;
                }
            }
        }

        // Mapeamento de jogos para a API
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

        // Verificar se o jogo existe
        if (!isset($api_urls[$jogo])) {
            error_log("Jogo não encontrado: " . $jogo);
            error_log("Jogos disponíveis: " . implode(', ', array_keys($api_urls)));
            return null;
        }

        $url = $api_urls[$jogo];
        error_log("URL da API: $url");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Origin: https://loterias.caixa.gov.br',
            'Referer: https://loterias.caixa.gov.br/'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        error_log("HTTP Status Code: " . $httpCode);
        
        if (curl_errno($ch)) {
            error_log("Erro cURL: " . curl_error($ch));
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Erro HTTP: " . $httpCode);
            return null;
        }
        
        $data = json_decode($response, true);
        if (!$data) {
            error_log("Erro ao decodificar JSON");
            return null;
        }
        
        // Mapear campos da API para o formato esperado
        if (isset($data['numero'])) {
            $resultado['numero'] = $data['numero'];
        } elseif (isset($data['concurso'])) {
            $resultado['numero'] = $data['concurso'];
        }
        
        // Tratar data de apuração
        $data_apuracao = null;
        if (isset($data['dataApuracao'])) {
            $data_apuracao = DateTime::createFromFormat('d/m/Y', $data['dataApuracao']);
        } elseif (isset($data['data'])) {
            $data_apuracao = DateTime::createFromFormat('d/m/Y', $data['data']);
        }
        
        if ($data_apuracao) {
            $resultado['dataApuracao'] = $data_apuracao->format('Y-m-d');
        } else {
            $resultado['dataApuracao'] = date('Y-m-d');
        }
        
        // Tratar dezenas
        if (isset($data['listaDezenas'])) {
            $resultado['dezenas'] = array_map(function($n) {
                return str_pad($n, 2, '0', STR_PAD_LEFT);
            }, $data['listaDezenas']);
        } elseif (isset($data['dezenas'])) {
            $resultado['dezenas'] = array_map(function($n) {
                return str_pad($n, 2, '0', STR_PAD_LEFT);
            }, $data['dezenas']);
        }
        
        // Tratar valor acumulado
        if (isset($data['valorAcumulado'])) {
            $valor = preg_replace('/[^\d,.]/', '', $data['valorAcumulado']);
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
            $resultado['valorAcumulado'] = floatval($valor);
        } elseif (isset($data['acumulado'])) {
            $valor = preg_replace('/[^\d,.]/', '', $data['acumulado']);
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
            $resultado['valorAcumulado'] = floatval($valor);
        }
        
        // Tratar data do próximo concurso
        $data_proximo = null;
        if (isset($data['dataProximoConcurso'])) {
            $data_proximo = DateTime::createFromFormat('d/m/Y', $data['dataProximoConcurso']);
        } elseif (isset($data['dataProxConcurso'])) {
            $data_proximo = DateTime::createFromFormat('d/m/Y', $data['dataProxConcurso']);
        }
        
        if ($data_proximo) {
            $resultado['dataProximoConcurso'] = $data_proximo->format('Y-m-d');
        } else {
            // Calcular próxima data baseado no dia atual
            $hoje = new DateTime();
            $proxima = clone $hoje;
            while ($proxima->format('N') != 3 && $proxima->format('N') != 6) {
                $proxima->modify('+1 day');
            }
            $resultado['dataProximoConcurso'] = $proxima->format('Y-m-d');
        }
        
        // Tratar valor estimado do próximo concurso
        if (isset($data['valorEstimadoProximoConcurso'])) {
            $valor = preg_replace('/[^\d,.]/', '', $data['valorEstimadoProximoConcurso']);
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
            $resultado['valorEstimadoProximoConcurso'] = floatval($valor);
        } elseif (isset($data['valorEstimadoProxConcurso'])) {
            $valor = preg_replace('/[^\d,.]/', '', $data['valorEstimadoProxConcurso']);
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
            $resultado['valorEstimadoProximoConcurso'] = floatval($valor);
        }
        
        // Validar resultado
        if (empty($resultado['numero']) || empty($resultado['dezenas'])) {
            error_log("Dados incompletos recebidos da API");
            return null;
        }
        
        // Salvar no cache
        if (!file_exists(__DIR__ . "/../../cache")) {
            mkdir(__DIR__ . "/../../cache", 0777, true);
        }
        file_put_contents($cache_file, json_encode($resultado));
        
        error_log("Dados obtidos com sucesso: " . print_r($resultado, true));
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Exceção ao buscar resultados: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return null;
    }
}

function processarGanhadores($pdo) {
    try {
        // Verificar se há apostas para processar
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM apostas a
            WHERE a.status = 'aprovada'
            AND a.processado = 0
        ");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($total == 0) {
            return ['success' => true, 'message' => 'Não há apostas para processar.'];
        }
        
        // Buscar apostas não processadas
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.numeros,
                a.tipo_jogo_id,
                a.valor_premio,
                j.nome as jogo_nome,
                j.api_nome
            FROM apostas a
            INNER JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE a.status = 'aprovada'
            AND a.processado = 0
            LIMIT 100
        ");
        $stmt->execute();
        $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processadas = 0;
        $erros = 0;
        
        foreach ($apostas as $aposta) {
            $resultado = buscarResultadosAPI($aposta['api_nome']);
            
            if ($resultado && isset($resultado['dezenas'])) {
                $dezenasSorteadas = $resultado['dezenas'];
                $numerosAposta = explode(',', $aposta['numeros']);
                
                // Contar acertos
                $acertos = count(array_intersect($dezenasSorteadas, $numerosAposta));
                
                // Atualizar status da aposta
                $stmt = $pdo->prepare("
                    UPDATE apostas 
                    SET 
                        acertos = ?,
                        processado = 1,
                        status = CASE 
                            WHEN ? > 0 THEN 'premiada'
                            ELSE 'nao_premiada'
                        END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$acertos, $acertos, $aposta['id']])) {
                    $processadas++;
                } else {
                    $erros++;
                    error_log("Erro ao atualizar aposta ID: " . $aposta['id']);
                }
            } else {
                $erros++;
                error_log("Erro ao buscar resultado para aposta ID: " . $aposta['id']);
            }
        }
        
        return [
            'success' => true,
            'message' => "Processadas: $processadas, Erros: $erros",
            'processadas' => $processadas,
            'erros' => $erros
        ];
    } catch (Exception $e) {
        error_log("Erro ao processar ganhadores: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function verificarEstruturaBanco($pdo) {
    try {
        // Verificar coluna processado na tabela apostas
        $stmt = $pdo->query("SHOW COLUMNS FROM apostas LIKE 'processado'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE apostas ADD COLUMN processado TINYINT(1) DEFAULT 0 AFTER status");
        }
        
        // Verificar coluna acertos na tabela apostas
        $stmt = $pdo->query("SHOW COLUMNS FROM apostas LIKE 'acertos'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE apostas ADD COLUMN acertos INT DEFAULT 0 AFTER processado");
        }
        
        // Verificar coluna api_nome na tabela jogos
        $stmt = $pdo->query("SHOW COLUMNS FROM jogos LIKE 'api_nome'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE jogos ADD COLUMN api_nome VARCHAR(50) AFTER nome");
            
            // Preencher api_nome com valores padrão
            $pdo->exec("
                UPDATE jogos 
                SET api_nome = CASE 
                    WHEN nome LIKE '%Mega-Sena%' THEN 'mega-sena'
                    WHEN nome LIKE '%Lotofácil%' THEN 'lotofacil'
                    WHEN nome LIKE '%Quina%' THEN 'quina'
                    WHEN nome LIKE '%Lotomania%' THEN 'lotomania'
                    WHEN nome LIKE '%Timemania%' THEN 'timemania'
                    WHEN nome LIKE '%Dia de Sorte%' THEN 'dia-de-sorte'
                    WHEN nome LIKE '%+Milionária%' THEN 'mais-milionaria'
                    ELSE LOWER(REPLACE(nome, ' ', '-'))
                END
            ");
        }
        
        return ['success' => true, 'message' => 'Estrutura do banco verificada e atualizada.'];
    } catch (Exception $e) {
        error_log("Erro ao verificar estrutura do banco: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
} 