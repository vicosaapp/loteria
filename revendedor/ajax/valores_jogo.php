<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if (!isset($_POST['jogo_id']) || !isset($_POST['dezenas'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros obrigatórios ausentes']);
    exit;
}

$jogo_id = intval($_POST['jogo_id']);
$dezenas = intval($_POST['dezenas']);

try {
    $stmt = $pdo->prepare("SELECT valor_aposta, valor_premio FROM valores_jogos WHERE jogo_id = ? AND dezenas = ? ORDER BY valor_aposta ASC");
    $stmt->execute([$jogo_id, $dezenas]);
    $valores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$valores) {
        echo json_encode(['success' => false, 'message' => 'Nenhum valor disponível para esta configuração']);
        exit;
    }

    echo json_encode(['success' => true, 'valores' => $valores]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar valores: ' . $e->getMessage()]);
    exit;
} 