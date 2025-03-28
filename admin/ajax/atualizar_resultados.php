<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Configurações da API da Caixa
    $api_url = 'https://loteriascaixa-api.herokuapp.com/api';
    
    // Lista de jogos para atualizar
    $jogos = [
        'mega-sena' => 'megasena',
        'lotofacil' => 'lotofacil',
        'quina' => 'quina',
        'lotomania' => 'lotomania',
        'timemania' => 'timemania',
        'dupla-sena' => 'duplasena',
        'dia-de-sorte' => 'diadesorte',
        'super-sete' => 'supersete',
        'mais-milionaria' => 'maismilionaria'
    ];

    $logs = [];
    $sucesso = true;

    foreach ($jogos as $api_jogo => $db_jogo) {
        try {
            // Buscar último concurso do jogo no banco
            $stmt = $pdo->prepare("
                SELECT MAX(numero_concurso) as ultimo_concurso 
                FROM concursos 
                WHERE jogo_id = (SELECT id FROM jogos WHERE identificador_api = ?)
            ");
            $stmt->execute([$db_jogo]);
            $ultimo_concurso = $stmt->fetch(PDO::FETCH_ASSOC)['ultimo_concurso'] ?? 0;

            // Buscar resultado da API
            $response = file_get_contents($api_url . '/' . $api_jogo . '/latest');
            $resultado = json_decode($response, true);

            if (!$resultado) {
                throw new Exception("Erro ao decodificar resposta da API para {$db_jogo}");
            }

            // Verificar se é um concurso novo
            if ($resultado['concurso'] > $ultimo_concurso) {
                // Inserir novo concurso
                $stmt = $pdo->prepare("
                    INSERT INTO concursos (jogo_id, codigo, data_sorteio, status)
                    SELECT id, ?, ?, 'finalizado'
                    FROM jogos WHERE identificador_api = ?
                ");
                $stmt->execute([
                    $resultado['concurso'],
                    date('Y-m-d H:i:s', strtotime($resultado['data'])),
                    $db_jogo
                ]);
                $concurso_id = $pdo->lastInsertId();

                // Inserir números sorteados
                $stmt = $pdo->prepare("
                    INSERT INTO numeros_sorteados (concurso_id, numero)
                    VALUES (?, ?)
                ");
                foreach ($resultado['dezenas'] as $numero) {
                    $stmt->execute([$concurso_id, $numero]);
                }

                // Atualizar informações do jogo
                $stmt = $pdo->prepare("
                    UPDATE jogos 
                    SET numero_concurso = ?,
                        valor_acumulado = ?,
                        data_proximo_concurso = ?,
                        valor_estimado_proximo = ?
                    WHERE identificador_api = ?
                ");
                $stmt->execute([
                    $resultado['concurso'],
                    $resultado['acumulado'] ?? 0,
                    $resultado['proximo_concurso']['data'] ?? null,
                    $resultado['proximo_concurso']['valor'] ?? 0,
                    $db_jogo
                ]);

                $logs[] = "Concurso {$resultado['concurso']} do {$db_jogo} atualizado com sucesso";
            } else {
                $logs[] = "Concurso {$resultado['concurso']} do {$db_jogo} já está atualizado";
            }

        } catch (Exception $e) {
            $logs[] = "Erro ao atualizar {$db_jogo}: " . $e->getMessage();
            $sucesso = false;
        }
    }

    echo json_encode([
        'status' => $sucesso ? 'success' : 'error',
        'message' => $sucesso ? 'Resultados atualizados com sucesso' : 'Alguns resultados não foram atualizados',
        'logs' => $logs
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao atualizar resultados: ' . $e->getMessage(),
        'logs' => [$e->getMessage()]
    ]);
} 