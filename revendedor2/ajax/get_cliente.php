<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT id, nome, email, whatsapp 
        FROM usuarios 
        WHERE id = ? AND revendedor_id = ? AND tipo = 'usuario'
    ");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente) {
        echo json_encode(['success' => true, 'data' => $cliente]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 