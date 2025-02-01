<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$resultado_id = filter_input(INPUT_GET, 'resultado_id', FILTER_VALIDATE_INT);
$jogo_id = filter_input(INPUT_GET, 'jogo_id', FILTER_VALIDATE_INT);

try {
    if ($resultado_id) {
        // Busca ganhadores de um resultado específico
        $stmt = $pdo->prepare("
            SELECT 
                g.*,
                u.nome as usuario_nome,
                u.email as usuario_email,
                a.numeros as numeros_apostados,
                r.numeros as numeros_sorteados,
                r.data_sorteio,
                j.nome as jogo_nome
            FROM ganhadores g
            JOIN usuarios u ON g.usuario_id = u.id
            JOIN apostas a ON g.aposta_id = a.id
            JOIN resultados r ON g.resultado_id = r.id
            JOIN jogos j ON r.jogo_id = j.id
            WHERE g.resultado_id = ?
            ORDER BY g.created_at DESC
        ");
        $stmt->execute([$resultado_id]);
    } else if ($jogo_id) {
        // Busca todos os ganhadores de um jogo
        $stmt = $pdo->prepare("
            SELECT 
                g.*,
                u.nome as usuario_nome,
                u.email as usuario_email,
                a.numeros as numeros_apostados,
                r.numeros as numeros_sorteados,
                r.data_sorteio,
                j.nome as jogo_nome
            FROM ganhadores g
            JOIN usuarios u ON g.usuario_id = u.id
            JOIN apostas a ON g.aposta_id = a.id
            JOIN resultados r ON g.resultado_id = r.id
            JOIN jogos j ON r.jogo_id = j.id
            WHERE j.id = ?
            ORDER BY r.data_sorteio DESC, g.created_at DESC
        ");
        $stmt->execute([$jogo_id]);
    } else {
        throw new Exception('ID do resultado ou jogo não fornecido');
    }
    
    $ganhadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formata os dados para exibição
    foreach ($ganhadores as &$ganhador) {
        $ganhador['premio_formatado'] = number_format($ganhador['premio'], 2, ',', '.');
        $ganhador['data_sorteio_formatada'] = date('d/m/Y', strtotime($ganhador['data_sorteio']));
        $ganhador['numeros_apostados'] = explode(',', $ganhador['numeros_apostados']);
        $ganhador['numeros_sorteados'] = explode(',', $ganhador['numeros_sorteados']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $ganhadores
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 