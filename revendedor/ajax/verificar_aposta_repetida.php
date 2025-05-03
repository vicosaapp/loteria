<?php
require_once '../../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Verificar campos obrigatórios
if (empty($_POST['jogo_id']) || empty($_POST['numeros'])) {
    echo json_encode(['success' => false, 'message' => 'Jogo e números são obrigatórios']);
    exit;
}

header('Content-Type: application/json');

try {
    // Sanitizar dados
    $jogo_id = filter_input(INPUT_POST, 'jogo_id', FILTER_VALIDATE_INT);
    $numeros = htmlspecialchars($_POST['numeros'] ?? '', ENT_QUOTES, 'UTF-8');
    $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT) ?: null;
    $revendedor_id = $_SESSION['usuario_id'];
    
    // Validar dados
    if (!$jogo_id || !$numeros) {
        throw new Exception('Dados inválidos');
    }
    
    // Verificar se já existe uma aposta idêntica (mesmo jogo e números) feita hoje,
    // independentemente de qual cliente fez a aposta
    $stmt = $pdo->prepare("
        SELECT a.id, u.nome as apostador
        FROM apostas a
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.tipo_jogo_id = ? 
        AND a.numeros = ? 
        AND DATE(a.created_at) = CURDATE()
    ");
    $stmt->execute([$jogo_id, $numeros]);
    
    $aposta_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'exists' => (bool)$aposta_existente,
        'aposta_id' => $aposta_existente ? $aposta_existente['id'] : null,
        'apostador' => $aposta_existente ? $aposta_existente['apostador'] : null
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 