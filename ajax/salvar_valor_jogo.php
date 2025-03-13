<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    // Validar valor
    $valor = str_replace(['R$', '.', ' '], '', $dados['valor']);
    $valor = str_replace(',', '.', $valor);
    $valor = floatval($valor);
    
    if ($valor <= 0) {
        throw new Exception('Valor deve ser maior que zero');
    }

    $stmt = $pdo->prepare("
        INSERT INTO valores_jogos (valor) 
        VALUES (?)
    ");

    if ($stmt->execute([$valor])) {
        echo json_encode([
            'success' => true,
            'message' => 'Valor salvo com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao salvar valor');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 