<?php
require_once '../../config/database.php';
session_start();

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Verificar se tem clientes vinculados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE revendedor_id = ?");
    $stmt->execute([$data['id']]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Não é possível excluir este revendedor pois existem clientes vinculados a ele.");
    }

    // Excluir revendedor
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
    $stmt->execute([$data['id']]);

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 