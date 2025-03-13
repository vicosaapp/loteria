<?php
ob_start();
session_start();
require_once '../../config/database.php';
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erro ao decodificar JSON: ' . json_last_error_msg());
    }

    // Validação dos dados
    if (!isset($data['jogo_id']) || !isset($data['valor_aposta']) || !isset($data['dezenas'])) {
        throw new Exception('Dados incompletos');
    }

    $stmt = $pdo->prepare("
        DELETE FROM valores_jogos 
        WHERE jogo_id = ? 
        AND valor_aposta = ? 
        AND dezenas = ?
    ");

    $stmt->execute([
        $data['jogo_id'],
        $data['valor_aposta'],
        $data['dezenas']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Valor excluído com sucesso!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 