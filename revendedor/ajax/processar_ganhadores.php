<?php
// Habilitar exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuração do banco de dados
require_once '../../config/database.php';

// Iniciar a sessão
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
    exit;
}

header('Content-Type: application/json');

// Array para logs detalhados
$logs = [];
$logs[] = "Iniciando processamento de ganhadores: " . date('Y-m-d H:i:s');

try {
    // Iniciar transação para garantir integridade dos dados
    $pdo->beginTransaction();
    
    // 1. Obter todos os concursos finalizados com números sorteados
    $sql = "
        SELECT 
            c.id as concurso_id,
            c.codigo as concurso_codigo,
            c.jogo_id,
            j.nome as jogo_nome,
            GROUP_CONCAT(ns.numero ORDER BY ns.numero ASC) as numeros_sorteados
        FROM concursos c
        JOIN jogos j ON j.id = c.jogo_id
        JOIN numeros_sorteados ns ON ns.concurso_id = c.id
        WHERE c.status = 'finalizado'
        GROUP BY c.id, c.codigo, c.jogo_id, j.nome
        ORDER BY c.jogo_id, c.data_sorteio DESC
    ";
    
    $logs[] = "Buscando concursos finalizados com números sorteados";
    $stmt = $pdo->query($sql);
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($concursos)) {
        $logs[] = "Nenhum concurso finalizado encontrado com números sorteados";
        echo json_encode([
            'status' => 'warning',
            'message' => 'Nenhum concurso finalizado encontrado',
            'logs' => $logs
        ]);
        exit;
    }
    
    $logs[] = "Encontrados " . count($concursos) . " concursos finalizados";
    
    // Processa cada concurso
    $total_apostas_processadas = 0;
    $total_ganhadores = 0;
    
    foreach ($concursos as $concurso) {
        $logs[] = "Processando concurso {$concurso['concurso_codigo']} - {$concurso['jogo_nome']}";
        
        // Obtém os números sorteados
        $numeros_sorteados = explode(',', $concurso['numeros_sorteados']);
        $numeros_sorteados = array_map('intval', $numeros_sorteados);
        
        $logs[] = "Números sorteados: " . implode(', ', $numeros_sorteados);
        
        // 2. Obter todas as apostas não processadas para este jogo
        $sql = "
            SELECT 
                a.id,
                a.usuario_id,
                a.numeros,
                a.valor_aposta,
                a.data_criacao,
                u.nome as nome_usuario
            FROM apostas a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.tipo_jogo_id = ? 
            AND a.status = 'aprovada' 
            AND (a.processado = 0 OR a.processado IS NULL)
            AND a.concurso IS NULL
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$concurso['jogo_id']]);
        $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $logs[] = "Encontradas " . count($apostas) . " apostas não processadas para este jogo";
        
        if (empty($apostas)) {
            $logs[] = "Sem apostas para processar neste concurso";
            continue;
        }
        
        // Verificar valores de prêmios disponíveis para este jogo
        $sql = "SELECT dezenas, valor_premio FROM valores_jogos WHERE jogo_id = ? ORDER BY dezenas DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$concurso['jogo_id']]);
        $premios = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (empty($premios)) {
            $logs[] = "ATENÇÃO: Não foram encontrados valores de prêmios para o jogo ID {$concurso['jogo_id']}";
            // Criar valores padrão para prêmios
            $premios = [
                15 => 1000.00,
                14 => 500.00,
                13 => 250.00,
                12 => 100.00,
                11 => 50.00,
                10 => 20.00,
                9 => 10.00,
                8 => 5.00,
                7 => 2.50,
                6 => 1.00
            ];
            $logs[] = "Utilizando valores padrão para prêmios";
        }
        
        // Processar cada aposta
        foreach ($apostas as $aposta) {
            $logs[] = "Processando aposta ID: {$aposta['id']} de {$aposta['nome_usuario']}";
            
            // Obter números da aposta
            $numeros_apostados = explode(',', $aposta['numeros']);
            $numeros_apostados = array_map('intval', array_map('trim', $numeros_apostados));
            
            $logs[] = "Números apostados: " . implode(', ', $numeros_apostados);
            
            // Verificar acertos
            $acertos = array_intersect($numeros_apostados, $numeros_sorteados);
            $total_acertos = count($acertos);
            
            $logs[] = "Total de acertos: $total_acertos - Números acertados: " . implode(', ', $acertos);
            
            // Determinar o prêmio
            $valor_premio = 0;
            foreach ($premios as $acertos_necessarios => $premio) {
                if ($total_acertos >= $acertos_necessarios) {
                    $valor_premio = $premio;
                    $logs[] = "Prêmio encontrado: R$ $valor_premio para $total_acertos acertos";
                    break;
                }
            }
            
            // Atualizar a aposta
            $sql = "
                UPDATE apostas 
                SET 
                    concurso = ?,
                    valor_premio = ?,
                    processado = 1
                WHERE id = ?
            ";
            
            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                $concurso['concurso_codigo'],
                $valor_premio,
                $aposta['id']
            ]);
            
            if ($resultado) {
                $logs[] = "Aposta ID {$aposta['id']} atualizada com sucesso";
                $total_apostas_processadas++;
                
                if ($valor_premio > 0) {
                    $total_ganhadores++;
                    $logs[] = "GANHADOR ENCONTRADO! Usuário: {$aposta['nome_usuario']}, Prêmio: R$ $valor_premio";
                }
            } else {
                $logs[] = "ERRO ao atualizar aposta ID {$aposta['id']}";
            }
        }
    }
    
    // Confirmar transação
    $pdo->commit();
    
    // Resumo final
    $logs[] = "Processamento concluído: " . date('Y-m-d H:i:s');
    $logs[] = "Total de apostas processadas: $total_apostas_processadas";
    $logs[] = "Total de ganhadores encontrados: $total_ganhadores";
    
    // Retornar resultado
    echo json_encode([
        'status' => 'success',
        'message' => "Processamento concluído com sucesso. Apostas processadas: $total_apostas_processadas, Ganhadores encontrados: $total_ganhadores",
        'apostas_processadas' => $total_apostas_processadas,
        'ganhadores' => $total_ganhadores,
        'logs' => $logs
    ]);

} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $logs[] = "ERRO: " . $e->getMessage();
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao processar ganhadores: ' . $e->getMessage(),
        'logs' => $logs
    ]);
} 