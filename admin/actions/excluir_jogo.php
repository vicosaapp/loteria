<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM jogos WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 