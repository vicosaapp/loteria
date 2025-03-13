<?php
require_once '../../config/database.php';
session_start();

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$id) {
            throw new Exception('ID da aposta inválido');
        }
        
        // Inicia a transação
        $pdo->beginTransaction();
        
        // Exclui a aposta
        $stmt = $pdo->prepare("DELETE FROM apostas_importadas WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        if (!$success) {
            throw new Exception('Erro ao excluir aposta');
        }
        
        // Se nenhuma linha foi afetada
        if ($stmt->rowCount() === 0) {
            throw new Exception('Aposta não encontrada');
        }
        
        // Confirma a transação
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Aposta excluída com sucesso!'
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Erro ao excluir aposta: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ]);
} 