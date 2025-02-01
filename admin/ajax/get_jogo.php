<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    $id = $_GET['id'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nome,
            numeros_total,
            minimo_numeros,
            maximo_numeros,
            acertos_premio,
            valor_aposta,
            valor_premio,
            status
        FROM jogos 
        WHERE id = ?
    ");
    
    $stmt->execute([$id]);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$jogo) {
        throw new Exception('Jogo não encontrado');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $jogo
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 