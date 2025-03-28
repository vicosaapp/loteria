<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_config.php';

// Configurações
$config = require __DIR__ . '/../config/api_config.php';
$log_file = __DIR__ . '/../logs/resultados_' . date('Y-m-d') . '.log';

// Função para registrar logs
function registrarLog($mensagem) {
    global $log_file;
    $data = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$data}] {$mensagem}\n", FILE_APPEND);
}

try {
    registrarLog("Iniciando atualização automática dos resultados");
    
    // Verificar se já foi atualizado recentemente
    $ultima_atualizacao = file_exists($log_file) ? filemtime($log_file) : 0;
    $tempo_desde_ultima = time() - $ultima_atualizacao;
    
    if ($tempo_desde_ultima < $config['atualizacao_automatica']['intervalo']) {
        registrarLog("Atualização ignorada - muito recente");
        exit;
    }
    
    // Buscar resultados da API
    foreach ($config['jogos'] as $api_jogo => $info) {
        try {
            registrarLog("Atualizando {$info['nome']}");
            
            // Buscar último concurso do jogo no banco
            $stmt = $pdo->prepare("
                SELECT MAX(numero_concurso) as ultimo_concurso 
                FROM concursos 
                WHERE jogo_id = (SELECT id FROM jogos WHERE identificador_api = ?)
            ");
            $stmt->execute([$info['identificador']]);
            $ultimo_concurso = $stmt->fetch(PDO::FETCH_ASSOC)['ultimo_concurso'] ?? 0;
            
            // Buscar resultado da API
            $response = file_get_contents($config['api_url'] . '/' . $api_jogo . '/latest');
            $resultado = json_decode($response, true);
            
            if (!$resultado) {
                throw new Exception("Erro ao decodificar resposta da API");
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
                    $info['identificador']
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
                    $info['identificador']
                ]);
                
                registrarLog("Concurso {$resultado['concurso']} do {$info['nome']} atualizado com sucesso");
            } else {
                registrarLog("Concurso {$resultado['concurso']} do {$info['nome']} já está atualizado");
            }
            
        } catch (Exception $e) {
            registrarLog("Erro ao atualizar {$info['nome']}: " . $e->getMessage());
        }
    }
    
    registrarLog("Atualização automática concluída");
    
} catch (Exception $e) {
    registrarLog("Erro fatal: " . $e->getMessage());
} 