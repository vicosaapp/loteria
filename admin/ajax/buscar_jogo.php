<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$jogo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("
        SELECT j.*, 
               (SELECT COUNT(*) FROM apostas WHERE tipo_jogo_id = j.id) as total_apostas
        FROM jogos j 
        WHERE j.id = ?
    ");
    $stmt->execute([$jogo_id]);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jogo) {
        echo json_encode([
            'success' => true,
            'data' => $jogo
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Jogo não encontrado'
        ]);
    }
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar jogo: ' . $e->getMessage()
    ]);
} 