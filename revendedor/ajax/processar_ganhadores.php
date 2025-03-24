<?php
require_once '../../config/database.php';
session_start();

// Verificar se é uma requisição AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acesso negado');
}

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Buscar apostas não processadas
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.numeros,
            a.tipo_jogo_id,
            j.nome as jogo_nome,
            j.api_nome,
            j.identificador_api
        FROM apostas a
        INNER JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE a.status = 'aprovada'
        AND (a.processado = 0 OR a.processado IS NULL)
    ");
    
    $stmt->execute();
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($apostas)) {
        echo json_encode([
            'success' => true,
            'message' => 'Não há apostas para processar'
        ]);
        exit;
    }
    
    $processadas = 0;
    $erros = 0;
    $logs = [];
    
    foreach ($apostas as $aposta) {
        try {
            // Buscar resultado mais recente da API
            $url = "https://loteriascaixa-api.herokuapp.com/api/{$aposta['api_nome']}/latest";
            
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Accept: application/json',
                        'User-Agent: Mozilla/5.0'
                    ],
                    'timeout' => 30
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];
            
            $context = stream_context_create($opts);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception("Erro ao buscar resultados da API para {$aposta['jogo_nome']}");
            }
            
            $resultado = json_decode($response, true);
            
            if (!$resultado || !isset($resultado['dezenas'])) {
                throw new Exception("Dados inválidos da API para {$aposta['jogo_nome']}");
            }
            
            // Processar resultado
            $dezenas_sorteadas = $resultado['dezenas'];
            $numeros_aposta = explode(',', $aposta['numeros']);
            
            // Contar acertos
            $acertos = count(array_intersect($dezenas_sorteadas, $numeros_aposta));
            
            // Buscar valor do prêmio baseado nos acertos
            $stmt = $pdo->prepare("
                SELECT valor_premio 
                FROM valores_jogos 
                WHERE jogo_id = ? 
                AND quantidade_acertos = ?
            ");
            
            $stmt->execute([$aposta['tipo_jogo_id'], $acertos]);
            $valor_premio = $stmt->fetch(PDO::FETCH_COLUMN);
            
            // Atualizar aposta
            $stmt = $pdo->prepare("
                UPDATE apostas 
                SET 
                    processado = 1,
                    acertos = ?,
                    valor_premio = ?,
                    status = CASE 
                        WHEN ? > 0 THEN 'premiada'
                        ELSE 'nao_premiada'
                    END,
                    concurso = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $acertos,
                $valor_premio ?: 0,
                $valor_premio ?: 0,
                $resultado['concurso'],
                $aposta['id']
            ]);
            
            $logs[] = "Aposta {$aposta['id']} processada: {$acertos} acertos, prêmio: R$ " . number_format($valor_premio ?: 0, 2, ',', '.');
            $processadas++;
            
        } catch (Exception $e) {
            $erros++;
            $logs[] = "Erro ao processar aposta {$aposta['id']}: " . $e->getMessage();
            error_log("Erro ao processar aposta {$aposta['id']}: " . $e->getMessage());
            continue;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Processamento concluído. Processadas: $processadas, Erros: $erros",
        'processadas' => $processadas,
        'erros' => $erros,
        'logs' => $logs
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro ao processar ganhadores: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Erro ao processar ganhadores: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} 