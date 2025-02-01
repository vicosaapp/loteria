<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT id, nome, email, whatsapp, comissao FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
    $stmt->execute([$id]);
    $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($revendedor) {
        echo json_encode(['success' => true, 'data' => $revendedor]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Revendedor não encontrado']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 