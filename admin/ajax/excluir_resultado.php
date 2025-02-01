<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Inicia a transação
    $pdo->beginTransaction();
    
    // Primeiro, atualiza o status das apostas ganhadoras
    $stmt = $pdo->prepare("
        UPDATE apostas a 
        JOIN ganhadores g ON a.id = g.aposta_id 
        WHERE g.resultado_id = ?
        SET a.status = 'aprovada'
    ");
    $stmt->execute([$data['resultado_id']]);
    
    // Remove os ganhadores
    $stmt = $pdo->prepare("DELETE FROM ganhadores WHERE resultado_id = ?");
    $stmt->execute([$data['resultado_id']]);
    
    // Remove o resultado
    $stmt = $pdo->prepare("DELETE FROM resultados WHERE id = ?");
    $stmt->execute([$data['resultado_id']]);
    
    // Confirma a transação
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Resultado excluído com sucesso!'
    ]);
    
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir resultado: ' . $e->getMessage()
    ]);
} 