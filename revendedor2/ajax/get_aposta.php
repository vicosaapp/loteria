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
        SELECT 
            a.*,
            u.nome as nome_apostador,
            j.nome as nome_jogo
        FROM apostas a
        JOIN usuarios u ON a.usuario_id = u.id
        JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE a.id = ? AND u.revendedor_id = ?
    ");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($aposta) {
        echo json_encode(['success' => true, 'data' => $aposta]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aposta não encontrada']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 