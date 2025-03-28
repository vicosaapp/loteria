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

        // Configurar diretório de cache
        $cache_dir = __DIR__ . "/../../cache";
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0777, true);
            chmod($cache_dir, 0777); // Garantir permissões após criar
        }
        
        // Verificar permissões do cache
        if (!is_writable($cache_dir)) {
            chmod($cache_dir, 0777);
            if (!is_writable($cache_dir)) {
                error_log("ERRO: Diretório de cache sem permissão de escrita: $cache_dir");
            }
        }

        // Nome do arquivo de cache
        $cache_file = $cache_dir . "/resultados_{$jogo}.json";
        $cache_tempo = 300; // 5 minutos
        
        // Tentar ler do cache
        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_tempo)) {
            $cache_data = @file_get_contents($cache_file);
            if ($cache_data) {
                $data = json_decode($cache_data, true);
                if ($data && !empty($data['numero']) && !empty($data['dezenas'])) {
                    return $data;
                }
            }
        }

        // Primeiro tentar scraping do site da Caixa
        $resultado_scraping = buscarResultadosSiteCaixa($jogo);
        if ($resultado_scraping) {
            // Salvar no cache
            @file_put_contents($cache_file, json_encode($resultado_scraping));
            return $resultado_scraping;
        }

        // Configurações do certificado SSL
        $ssl_cert = __DIR__ . '/cacert.pem';
        if (!file_exists($ssl_cert)) {
            // Baixar certificado atualizado se não existir
            $cert_url = 'https://curl.se/ca/cacert.pem';
            $cert_content = @file_get_contents($cert_url);
            if ($cert_content) {
                @file_put_contents($ssl_cert, $cert_content);
            }
        }

        // APIs alternativas com melhor compatibilidade SSL
        $apis = [
            [
                'megasena' => 'https://loteriascaixa-api.herokuapp.com/api/mega-sena/latest',
                'lotofacil' => 'https://loteriascaixa-api.herokuapp.com/api/lotofacil/latest',
                'quina' => 'https://loteriascaixa-api.herokuapp.com/api/quina/latest',
                'lotomania' => 'https://loteriascaixa-api.herokuapp.com/api/lotomania/latest',
                'timemania' => 'https://loteriascaixa-api.herokuapp.com/api/timemania/latest',
                'duplasena' => 'https://loteriascaixa-api.herokuapp.com/api/dupla-sena/latest',
                'maismilionaria' => 'https://loteriascaixa-api.herokuapp.com/api/mais-milionaria/latest',
                'diadesorte' => 'https://loteriascaixa-api.herokuapp.com/api/dia-de-sorte/latest'
            ]
        ];

        foreach ($apis as $api) {
            if (!isset($api[$jogo])) continue;
            
            $url = $api[$jogo];
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true, // Habilitar verificação SSL
                CURLOPT_SSL_VERIFYHOST => 2, // Verificar hostname
                CURLOPT_CAINFO => $ssl_cert, // Usar certificado local
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0',
                CURLOPT_ENCODING => 'gzip, deflate',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Cache-Control: no-cache'
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if ($data) {
                    // Mapear dados para o formato esperado
                    if (isset($data['concurso'])) {
                        $resultado['numero'] = $data['concurso'];
                    }
                    if (isset($data['data'])) {
                        $data_obj = DateTime::createFromFormat('d/m/Y', $data['data']);
                        if ($data_obj) {
                            $resultado['dataApuracao'] = $data_obj->format('Y-m-d');
                        }
                    }
                    if (isset($data['dezenas'])) {
                        $resultado['dezenas'] = array_map(function($n) {
                            return str_pad($n, 2, '0', STR_PAD_LEFT);
                        }, $data['dezenas']);
                    }
                    
                    // Salvar no cache se tiver dados válidos
                    if (!empty($resultado['numero']) && !empty($resultado['dezenas'])) {
                        @file_put_contents($cache_file, json_encode($resultado));
                        curl_close($ch);
                        return $resultado;
                    }
                }
            }
            curl_close($ch);
        }

        // Se chegou aqui, todas as tentativas falharam
        error_log("ERRO: Não foi possível obter resultados para $jogo");
        return null;
        
    } catch (Exception $e) {
        error_log("ERRO: " . $e->getMessage());
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

function buscarResultadosSiteCaixa($jogo) {
    error_log("Tentando scraping do site da Caixa para $jogo");
    
    $mapeamento_urls = [
        'megasena' => [
            'https://www.caixa.gov.br/loterias/mega-sena/Paginas/default.aspx',
            'https://loterias.caixa.gov.br/Paginas/Mega-Sena.aspx'
        ],
        'lotofacil' => [
            'https://www.caixa.gov.br/loterias/lotofacil/Paginas/default.aspx',
            'https://loterias.caixa.gov.br/Paginas/Lotofacil.aspx'
        ],
        'quina' => [
            'https://www.caixa.gov.br/loterias/quina/Paginas/default.aspx',
            'https://loterias.caixa.gov.br/Paginas/Quina.aspx'
        ],
        'lotomania' => [
            'https://www.caixa.gov.br/loterias/lotomania/Paginas/default.aspx',
            'https://loterias.caixa.gov.br/Paginas/Lotomania.aspx'
        ],
        'timemania' => [
            'https://www.caixa.gov.br/loterias/timemania/Paginas/default.aspx',
            'https://loterias.caixa.gov.br/Paginas/Timemania.aspx'
        ],
        'duplasena' => [
            'https://www.caixa.gov.br/loterias/dupla-sena/Paginas/default.aspx',
            'https://loterias.caixa.gov.br/Paginas/Dupla-Sena.aspx'
        ],
        'maismilionaria' => [
            'https://www.caixa.gov.br/loterias/mais-milionaria/Paginas/default.aspx',
            'https://loterias.caixa.gov.br/Paginas/Mais-Milionaria.aspx'
        ],
        'diadesorte' => [
            'https://www.caixa.gov.br/loterias/dia-de-sorte/Paginas/default.aspx',
            'https://loterias.caixa.gov.br/Paginas/Dia-de-Sorte.aspx'
        ]
    ];
    
    if (!isset($mapeamento_urls[$jogo])) {
        error_log("Jogo não encontrado no mapeamento de URLs: $jogo");
        return null;
    }
    
    $resultado = null;
    
    // Tentar cada URL alternativa
    foreach ($mapeamento_urls[$jogo] as $url) {
        error_log("Tentando URL: $url");
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding: gzip, deflate',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        error_log("HTTP Status Code (Scraping): " . $httpCode);
        error_log("cURL Error (Scraping): " . $error);
        
        if ($httpCode === 200 && $response) {
            // Inicializar resultado
            $resultado = [
                'numero' => '',
                'dataApuracao' => date('Y-m-d'),
                'dezenas' => [],
                'valorAcumulado' => 0,
                'dataProximoConcurso' => date('Y-m-d'),
                'valorEstimadoProximoConcurso' => 0
            ];
            
            // Usar DOMDocument para parse do HTML
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($response, LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            
            // Array de seletores para cada informação
            $seletores = [
                'numero' => [
                    "//div[contains(@class, 'title-bar')]//h2[contains(text(), 'Concurso')]",
                    "//div[contains(@class, 'resultado-loteria')]//span[contains(text(), 'Concurso')]",
                    "//h2[contains(text(), 'Concurso')]",
                    "//span[contains(text(), 'Concurso')]"
                ],
                'data' => [
                    "//div[contains(@class, 'lottery-info')]//span[contains(@class, 'date')]",
                    "//div[contains(@class, 'resultado-loteria')]//span[contains(@class, 'data')]",
                    "//span[contains(@class, 'data-sorteio')]"
                ],
                'dezenas' => [
                    "//ul[contains(@class, 'numbers')]/li",
                    "//ul[contains(@class, 'dezenas-premiadas')]/li",
                    "//div[contains(@class, 'resultado-loteria')]//li[contains(@class, 'ng-binding')]"
                ]
            ];
            
            // Tentar cada seletor para número do concurso
            foreach ($seletores['numero'] as $seletor) {
                $nodes = $xpath->query($seletor);
                if ($nodes->length > 0) {
                    if (preg_match('/Concurso\s*(?:n[º°]?)?\s*(\d+)/i', $nodes->item(0)->textContent, $matches)) {
                        $resultado['numero'] = $matches[1];
                        break;
                    }
                }
            }
            
            // Tentar cada seletor para data
            foreach ($seletores['data'] as $seletor) {
                $nodes = $xpath->query($seletor);
                if ($nodes->length > 0) {
                    if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', trim($nodes->item(0)->textContent), $matches)) {
                        $data = DateTime::createFromFormat('d/m/Y', $matches[1]);
                        if ($data) {
                            $resultado['dataApuracao'] = $data->format('Y-m-d');
                            break;
                        }
                    }
                }
            }
            
            // Tentar cada seletor para dezenas
            foreach ($seletores['dezenas'] as $seletor) {
                $nodes = $xpath->query($seletor);
                if ($nodes->length > 0) {
                    foreach ($nodes as $node) {
                        $dezena = trim($node->textContent);
                        if (is_numeric($dezena)) {
                            $resultado['dezenas'][] = str_pad($dezena, 2, '0', STR_PAD_LEFT);
                        }
                    }
                    if (!empty($resultado['dezenas'])) {
                        break;
                    }
                }
            }
            
            // Se encontrou número do concurso e dezenas, considerar sucesso
            if (!empty($resultado['numero']) && !empty($resultado['dezenas'])) {
                error_log("Dados obtidos com sucesso da URL: $url");
                curl_close($ch);
                break;
            }
        }
        
        curl_close($ch);
    }
    
    // Validar resultado final
    if (!$resultado || empty($resultado['numero']) || empty($resultado['dezenas'])) {
        error_log("Dados incompletos obtidos via scraping");
        error_log("Resultado: " . print_r($resultado, true));
        return null;
    }
    
    error_log("Dados obtidos com sucesso via scraping: " . print_r($resultado, true));
    return $resultado;
} 