<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Verificar se tem apostas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apostas WHERE usuario_id = ?");
    $stmt->execute([$data['id']]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Não é possível excluir este cliente pois existem apostas vinculadas a ele.");
    }

    // Excluir cliente
    $stmt = $pdo->prepare("
        DELETE FROM usuarios 
        WHERE id = ? AND revendedor_id = ? AND tipo = 'usuario'
    ");
    $stmt->execute([$data['id'], $_SESSION['usuario_id']]);

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 