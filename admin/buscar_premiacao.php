<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// DEBUG: Mostrar todos os registros relevantes da tabela
$stmt = $pdo->query("SELECT * FROM valores_jogos WHERE jogo_id = 3 AND dezenas = 18 AND valor_aposta = 1");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("\n=== REGISTROS NA TABELA ===");
error_log(print_r($registros, true));

try {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    // Log dos dados recebidos
    error_log("Dados recebidos: " . print_r($dados, true));
    
    if (!isset($dados['apostas'])) {
        throw new Exception('Dados das apostas não encontrados');
    }
    
    // Processar as apostas
    $linhas = explode("\n", trim($dados['apostas']));
    $jogo_nome = trim($linhas[0]); // Usar o nome completo: "Loterias Mobile: MS"
    
    // Debug
    error_log("Buscando jogo com nome: " . $jogo_nome);
    
    // Buscar o jogo pelo nome completo
    $stmt = $pdo->prepare("
        SELECT id, nome, identificador_importacao 
        FROM jogos 
        WHERE identificador_importacao = ?
        LIMIT 1
    ");
    
    $stmt->execute([$jogo_nome]);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$jogo) {
        throw new Exception("Jogo não encontrado: $jogo_nome");
    }
    
    // Pegar a primeira linha de apostas e contar dezenas
    $primeira_aposta = trim($linhas[1] ?? '');
    // Novo método para contar dezenas
    $dezenas = array_filter(explode(' ', $primeira_aposta), function($num) {
        return is_numeric(trim($num));
    });
    $quantidade_dezenas = count($dezenas);
    
    error_log("Quantidade de dezenas encontradas: " . $quantidade_dezenas);
    error_log("Primeira aposta: " . $primeira_aposta);
    
    // Buscar valor do prêmio
    $stmt = $pdo->prepare("
        SELECT valor_premio 
        FROM valores_jogos 
        WHERE jogo_id = ? 
        AND dezenas = ?
        AND valor_aposta = ?
        LIMIT 1
    ");
    
    $stmt->execute([
        $jogo['id'],
        $quantidade_dezenas,
        $dados['valorAposta']
    ]);
    
    $premio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug da busca do prêmio
    error_log("Busca do prêmio - Jogo ID: {$jogo['id']}, Dezenas: $quantidade_dezenas, Valor: {$dados['valorAposta']}");
    error_log("Resultado da busca do prêmio: " . print_r($premio, true));
    
    echo json_encode([
        'success' => true,
        'valor_premio' => $premio ? floatval($premio['valor_premio']) : 0,
        'debug' => [
            'jogo_id' => $jogo['id'],
            'nome_jogo' => $jogo_nome,
            'dezenas' => $quantidade_dezenas,
            'valor_aposta' => $dados['valorAposta'],
            'primeira_aposta' => $primeira_aposta
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'dados_recebidos' => $dados ?? null,
            'nome_jogo' => $jogo_nome ?? null
        ]
    ]);
} 