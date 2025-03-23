<?php
// Habilitar exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuração do banco de dados
require_once '../../config/database.php';

// Definir o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Verificar se foram enviados os parâmetros necessários
if (empty($_POST['aposta_id']) || empty($_POST['concurso']) || empty($_POST['jogo_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros inválidos'
    ]);
    exit;
}

// Obter os parâmetros da requisição
$aposta_id = intval($_POST['aposta_id']);
$concurso = $_POST['concurso'];
$jogo_id = intval($_POST['jogo_id']);

// Array para logs detalhados
$logs = [];
$logs[] = "Iniciando processamento da aposta ID: $aposta_id, Concurso: $concurso, Jogo ID: $jogo_id";

try {
    // Buscar os números sorteados para este concurso
    $sql = "SELECT 
                c.codigo, 
                c.data_sorteio, 
                j.nome as nome_jogo,
                GROUP_CONCAT(ns.numero ORDER BY ns.numero ASC) as numeros_sorteados
            FROM concursos c
            JOIN jogos j ON j.id = c.jogo_id
            JOIN numeros_sorteados ns ON ns.concurso_id = c.id
            WHERE c.codigo = ? AND j.id = ?
            GROUP BY c.id";
    
    $logs[] = "Executando consulta: $sql com parâmetros: [$concurso, $jogo_id]";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$concurso, $jogo_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resultado) {
        $logs[] = "Erro: Concurso não encontrado.";
        
        // Verificar se existem concursos para esse jogo
        $sql = "SELECT c.codigo, c.data_sorteio FROM concursos c WHERE c.jogo_id = ? ORDER BY c.data_sorteio DESC LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jogo_id]);
        $concursos_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($concursos_disponiveis) {
            $logs[] = "Concursos disponíveis para este jogo:";
            foreach ($concursos_disponiveis as $c) {
                $logs[] = "- Concurso: {$c['codigo']}, Data: {$c['data_sorteio']}";
            }
        } else {
            $logs[] = "Não há concursos cadastrados para este jogo.";
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Concurso não encontrado',
            'logs' => $logs
        ]);
        exit;
    }
    
    $logs[] = "Concurso encontrado: {$resultado['codigo']} - {$resultado['nome_jogo']} - Data: {$resultado['data_sorteio']}";
    $logs[] = "Números sorteados: {$resultado['numeros_sorteados']}";
    
    // Buscar a aposta
    $sql = "SELECT 
                a.*, 
                u.nome as nome_usuario, 
                j.nome as nome_jogo
            FROM apostas a
            JOIN usuarios u ON a.usuario_id = u.id
            JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE a.id = ?";
    
    $logs[] = "Executando consulta: $sql com parâmetro: [$aposta_id]";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$aposta_id]);
    $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aposta) {
        $logs[] = "Erro: Aposta não encontrada.";
        echo json_encode([
            'success' => false,
            'message' => 'Aposta não encontrada',
            'logs' => $logs
        ]);
        exit;
    }
    
    $logs[] = "Aposta encontrada:";
    $logs[] = "- ID: {$aposta['id']}";
    $logs[] = "- Usuário: {$aposta['nome_usuario']}";
    $logs[] = "- Jogo: {$aposta['nome_jogo']}";
    $logs[] = "- Números apostados (raw): {$aposta['numeros']}";
    $logs[] = "- Status: {$aposta['status']}";
    
    // Verificar os acertos
    $numeros_sorteados = explode(',', $resultado['numeros_sorteados']);
    $numeros_apostados = explode(',', $aposta['numeros']);
    
    // Limpar e converter para inteiros
    $numeros_sorteados = array_map('intval', array_map('trim', $numeros_sorteados));
    $numeros_apostados = array_map('intval', array_map('trim', $numeros_apostados));
    
    $logs[] = "Números sorteados processados: " . implode(', ', $numeros_sorteados);
    $logs[] = "Números apostados processados: " . implode(', ', $numeros_apostados);
    
    // Contar acertos
    $acertos_array = array_intersect($numeros_apostados, $numeros_sorteados);
    $acertos = count($acertos_array);
    
    $logs[] = "Números acertados: " . implode(', ', $acertos_array);
    $logs[] = "Total de acertos: $acertos";
    
    // Verificar se a aposta tem o número mínimo de acertos para ganhar um prêmio
    if ($acertos < 1) {
        $logs[] = "A aposta não tem acertos suficientes para ganhar um prêmio.";
        echo json_encode([
            'success' => false,
            'message' => 'A aposta não tem acertos suficientes para ganhar um prêmio',
            'logs' => $logs
        ]);
        exit;
    }
    
    // Buscar valor do prêmio baseado nos acertos
    $sql = "SELECT valor_premio FROM valores_jogos 
            WHERE jogo_id = ? AND dezenas = ?";
    
    $logs[] = "Executando consulta: $sql com parâmetros: [$jogo_id, $acertos]";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$jogo_id, $acertos]);
    $premio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$premio) {
        $logs[] = "Não foi encontrado um prêmio configurado para $acertos acertos.";
        
        // Buscar configurações de prêmios disponíveis
        $sql = "SELECT dezenas, valor_premio FROM valores_jogos WHERE jogo_id = ? ORDER BY dezenas ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jogo_id]);
        $premios_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($premios_disponiveis) {
            $logs[] = "Prêmios disponíveis para este jogo:";
            foreach ($premios_disponiveis as $p) {
                $logs[] = "- {$p['dezenas']} acertos: R$ {$p['valor_premio']}";
            }
        } else {
            $logs[] = "Não há prêmios configurados para este jogo.";
        }
        
        // Prêmio padrão baseado no número de acertos
        $valor_premio = $acertos * 50.00;
        $logs[] = "Utilizando valor padrão de prêmio: R$ $valor_premio";
    } else {
        $valor_premio = $premio['valor_premio'];
        $logs[] = "Prêmio encontrado para $acertos acertos: R$ $valor_premio";
    }
    
    // Atualizar a aposta
    $sql = "UPDATE apostas 
            SET processado = 1, 
                concurso = ?, 
                valor_premio = ? 
            WHERE id = ?";
    
    $logs[] = "Executando atualização: $sql com parâmetros: [$concurso, $valor_premio, $aposta_id]";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$concurso, $valor_premio, $aposta_id]);
    
    if ($result) {
        $logs[] = "Aposta atualizada com sucesso!";
        
        // Retornar sucesso
        echo json_encode([
            'success' => true,
            'message' => 'Aposta processada com sucesso',
            'premio' => $valor_premio,
            'acertos' => $acertos,
            'logs' => $logs
        ]);
    } else {
        $logs[] = "Erro ao atualizar a aposta: " . implode(', ', $stmt->errorInfo());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar a aposta',
            'logs' => $logs
        ]);
    }
    
} catch (Exception $e) {
    $logs[] = "Erro: " . $e->getMessage();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar aposta: ' . $e->getMessage(),
        'logs' => $logs
    ]);
} 