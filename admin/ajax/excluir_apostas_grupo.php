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
    
    if (!$dados || !isset($dados['usuario_id']) || !isset($dados['jogo_nome'])) {
        throw new Exception('Dados inválidos');
    }
    
    // Inicia a transação
    $pdo->beginTransaction();
    
    // Exclui apostas importadas
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
    // Em caso de erro, reverte a transação
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro ao excluir apostas: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao excluir apostas: ' . $e->getMessage()
    ]);
} 