<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!$dados) {
        throw new Exception('Dados inválidos');
    }

    // Debug
    error_log('Dados recebidos: ' . print_r($dados, true));

    // Inicia a transação
    $pdo->beginTransaction();

    // Pega o primeiro valor para usar como padrão
    $primeiroValor = $dados['valores'][0] ?? ['valor_aposta' => '0,00', 'qtd_dezenas' => '0', 'valor_premio' => '0,00'];
    
    // Processa os valores monetários do primeiro valor
    $valorAposta = str_replace(['R$', ' ', '.'], '', $primeiroValor['valor_aposta']);
    $valorAposta = str_replace(',', '.', $valorAposta);
    
    $valorPremio = str_replace(['R$', ' ', '.'], '', $primeiroValor['valor_premio']);
    $valorPremio = str_replace(',', '.', $valorPremio);

    // Query principal
    $stmt = $pdo->prepare("
        INSERT INTO jogos 
            (nome, identificador_importacao, minimo_numeros, maximo_numeros, 
             acertos_premio, status, valor, premio, dezenas) 
        VALUES 
            (:nome, :identificador_importacao, :minimo_numeros, :maximo_numeros, 
             :acertos_premio, :status, :valor, :premio, :dezenas)
    ");
    
    $stmt->execute([
        'nome' => $dados['nome'],
        'identificador_importacao' => $dados['identificador_importacao'],
        'minimo_numeros' => intval($dados['minimo_numeros']),
        'maximo_numeros' => intval($dados['maximo_numeros']),
        'acertos_premio' => intval($dados['acertos_premio']),
        'status' => intval($dados['status']),
        'valor' => floatval($valorAposta),
        'premio' => floatval($valorPremio),
        'dezenas' => intval($primeiroValor['qtd_dezenas'])
    ]);
    
    $jogoId = $pdo->lastInsertId();

    // Insere os valores
    $stmt = $pdo->prepare("
        INSERT INTO valores_jogos 
            (jogo_id, valor_aposta, dezenas, valor_premio) 
        VALUES 
            (:jogo_id, :valor_aposta, :dezenas, :valor_premio)
    ");

    foreach ($dados['valores'] as $valor) {
        // Limpa e formata os valores monetários
        $valorAposta = str_replace(['R$', ' ', '.'], '', $valor['valor_aposta']);
        $valorAposta = str_replace(',', '.', $valorAposta);
        
        $valorPremio = str_replace(['R$', ' ', '.'], '', $valor['valor_premio']);
        $valorPremio = str_replace(',', '.', $valorPremio);
        
        $stmt->execute([
            'jogo_id' => $jogoId,
            'valor_aposta' => floatval($valorAposta),
            'dezenas' => intval($valor['qtd_dezenas']),
            'valor_premio' => floatval($valorPremio)
        ]);
    }

    // Confirma a transação
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Jogo salvo com sucesso!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Erro ao salvar jogo: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Verifique o log de erros para mais detalhes'
    ]);
} 