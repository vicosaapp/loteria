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
        
        // Buscar apostas deste jogo
        $sql = "SELECT ai.id, ai.numeros, ai.usuario_id, u.nome as nome_usuario, ai.jogo_nome
                FROM apostas_importadas ai
                INNER JOIN usuarios u ON u.id = ai.usuario_id
                WHERE ai.jogo_nome LIKE CONCAT('%', ?) 
                AND ai.concurso = ?
                AND (ai.processado = 0 OR ai.processado IS NULL)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$concurso['jogo_nome'], $concurso['concurso_codigo']]);
        $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $logs[] = "Encontradas " . count($apostas) . " apostas para verificar";
        
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
            
            // Verificar se a aposta possui numeros
            if (empty($aposta['numeros'])) {
                $logs[] = "Aposta ID " . $aposta['id'] . " não possui números apostados. Pulando...";
                continue;
            }
            
            // Processar o texto da aposta para encontrar os números
            $linhas = explode("\n", $aposta['numeros']);
            $logs[] = "Aposta possui " . count($linhas) . " linhas";
            
            // Primeira linha contém o nome do jogo, vamos ignorá-la
            if (count($linhas) > 1) {
                array_shift($linhas);
                
                // A primeira linha (agora) contém os números apostados
                $primeira_aposta = trim($linhas[0]);
                $logs[] = "Primeira linha de números: " . $primeira_aposta;
                
                // Extrai todos os números da primeira linha
                preg_match_all('/\d+/', $primeira_aposta, $matches);
                $numeros_apostados = $matches[0];
                
                if (empty($numeros_apostados)) {
                    $logs[] = "Não foi possível extrair números válidos da aposta ID " . $aposta['id'] . ". Pulando...";
                    continue;
                }
                
                $logs[] = "Números apostados extraídos: " . implode(', ', $numeros_apostados);
            } else {
                $logs[] = "Formato de aposta inválido para ID " . $aposta['id'] . ". Pulando...";
                continue;
            }
            
            // Contar acertos
            $acertos = count(array_intersect($numeros_apostados, $numeros_sorteados));
            $logs[] = "Aposta ID " . $aposta['id'] . " teve " . $acertos . " acertos";
            
            // Determinar o prêmio
            $valor_premio = 0;
            foreach ($premios as $acertos_necessarios => $premio) {
                if ($acertos >= $acertos_necessarios) {
                    $valor_premio = $premio;
                    $logs[] = "Prêmio encontrado: R$ $valor_premio para $acertos acertos";
                    break;
                }
            }
            
            // Atualizar a aposta
            $sql = "
                UPDATE apostas_importadas 
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