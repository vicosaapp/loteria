<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['id'])) {
        throw new Exception('ID do resultado não fornecido');
    }

    // Primeiro, vamos verificar se o resultado existe
    $stmt = $pdo->prepare("SELECT id FROM resultados WHERE id = ?");
    $stmt->execute([$dados['id']]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Resultado não encontrado');
    }

    // Agora vamos excluir o resultado
    $pdo->beginTransaction();

    try {
        // Exclui o resultado
        $stmt = $pdo->prepare("DELETE FROM resultados WHERE id = ?");
        $stmt->execute([$dados['id']]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 