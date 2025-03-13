<?php
require_once '../../config/database.php';
session_start();

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Recebe os dados
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($dados['usuario_id']) || !isset($dados['jogo_nome'])) {
        throw new Exception('Dados incompletos');
    }
    
    // Inicia a transação
    $pdo->beginTransaction();
    
    // Exclui as apostas do grupo
    $stmt = $pdo->prepare("
        DELETE FROM apostas_importadas 
        WHERE usuario_id = ? 
        AND jogo_nome = ?
    ");
    
    $stmt->execute([$dados['usuario_id'], $dados['jogo_nome']]);
    
    // Confirma a transação
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Apostas excluídas com sucesso!'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 