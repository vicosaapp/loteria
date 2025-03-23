<?php
require_once '../config/database.php';
session_start();

// Retornar como JSON
header('Content-Type: application/json');

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não está logado']);
    exit;
}

// Pegar dados da requisição
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    // Primeiro, verificar se já existe uma aposta com os mesmos números
    $numeros = implode(',', $data['numeros']); // Converte array em string
    
    $stmt = $pdo->prepare("
        SELECT id 
        FROM apostas 
        WHERE usuario_id = ? 
        AND tipo_jogo_id = ? 
        AND numeros = ?
        AND status != 'rejeitada'
    ");
    
    $stmt->execute([
        $_SESSION['usuario_id'],
        $data['tipo_jogo_id'],
        $numeros
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Você já fez uma aposta com estes mesmos números neste jogo. Por favor, escolha números diferentes.'
        ]);
        exit;
    }

    // Se não existir aposta repetida, continua com o salvamento
    $stmt = $pdo->prepare("
        INSERT INTO apostas (
            usuario_id, 
            tipo_jogo_id, 
            numeros, 
            valor_aposta,
            status
        ) VALUES (?, ?, ?, ?, 'aprovada')
    ");

    $stmt->execute([
        $_SESSION['usuario_id'],
        $data['tipo_jogo_id'],
        $numeros,
        $data['valor_aposta'],
        'aprovada'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Aposta realizada com sucesso!',
        'aposta_id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar aposta: ' . $e->getMessage()
    ]);
} 