<?php
// Desabilitar a exibição de erros
ini_set('display_errors', 0);
error_reporting(0);

// Limpar qualquer saída anterior
if (ob_get_level()) ob_end_clean();

// Definir headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../../config/database.php';

try {
    // Receber e validar dados
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    if (!$dados) {
        throw new Exception('Dados inválidos');
    }

    // Tratar valor da premiação
    $valor_premio = str_replace('.', '', $dados['valor_premio_2']);
    $valor_premio = str_replace(',', '.', $valor_premio);
    
    // Contar dezenas
    $quantidade_dezenas = count(array_filter($dados['apostas'], 'trim'));
    
    // Preparar query
    $stmt = $pdo->prepare("
        INSERT INTO apostas_importadas 
        (usuario_id, revendedor_id, jogo_nome, numeros, valor_aposta, valor_premio_2, quantidade_dezenas, whatsapp) 
        VALUES 
        (?, ?, ?, ?, 1.00, ?, ?, ?)
    ");

    // Inserir apostas
    foreach ($dados['apostas'] as $numeros) {
        if (empty(trim($numeros))) continue;
        
        $stmt->execute([
            $dados['apostador_id'],
            $dados['revendedor_id'],
            'Loterias Mobile: LF',
            trim($numeros),
            $valor_premio,
            $quantidade_dezenas,
            $dados['whatsapp']
        ]);
    }

    die(json_encode([
        'success' => true,
        'message' => 'Apostas salvas com sucesso',
        'debug' => [
            'valor_premio' => $valor_premio,
            'quantidade_dezenas' => $quantidade_dezenas
        ]
    ]));

} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]));
}