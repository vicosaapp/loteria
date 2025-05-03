<?php
session_start();
require_once '../../config/database.php';

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado']);
    exit;
}

// Receber dados
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['aposta_id']) || empty($data['aposta_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID da aposta não fornecido']);
    exit;
}

$aposta_id = $data['aposta_id'];

try {
    // Verificar se a aposta existe nas apostas normais
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM apostas WHERE id = ?");
    $stmt->execute([$aposta_id]);
    $resultado = $stmt->fetch();
    
    if ($resultado['count'] > 0) {
        // Excluir aposta normal
        $stmt = $pdo->prepare("DELETE FROM apostas WHERE id = ?");
        $stmt->execute([$aposta_id]);
    } else {
        // Verificar se a aposta existe nas apostas importadas
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM apostas_importadas WHERE id = ?");
        $stmt->execute([$aposta_id]);
        $resultado = $stmt->fetch();
        
        if ($resultado['count'] > 0) {
            // Excluir aposta importada
            $stmt = $pdo->prepare("DELETE FROM apostas_importadas WHERE id = ?");
            $stmt->execute([$aposta_id]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Aposta não encontrada']);
            exit;
        }
    }
    
    // Registrar log
    error_log("Aposta ID {$aposta_id} excluída pelo admin {$_SESSION['usuario_id']} em " . date('Y-m-d H:i:s'));
    
    echo json_encode(['success' => true, 'message' => 'Aposta excluída com sucesso']);
} catch (PDOException $e) {
    error_log("Erro ao excluir aposta: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao excluir aposta: ' . $e->getMessage()]);
} 