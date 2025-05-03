<?php
session_start();
require_once '../../config/database.php';

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Receber o timestamp da última verificação
$last_check = isset($_GET['last_check']) ? intval($_GET['last_check']) : 0;
if ($last_check == 0) {
    // Se não houver timestamp, usar 5 minutos atrás
    $last_check = time() - 300;
}

try {
    // Consultar apostas mais recentes que o último timestamp
    $stmt = $pdo->prepare("
        WITH ultimas_apostas AS (
            (SELECT 
                a.id,
                a.usuario_id,
                a.created_at,
                a.valor_aposta as valor,
                a.valor_premio as valor_premio,
                u.nome as apostador,
                r.nome as revendedor,
                j.nome as jogo_nome
            FROM apostas a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN usuarios r ON a.revendedor_id = r.id
            LEFT JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE a.created_at > FROM_UNIXTIME(?))
            
            UNION ALL
            
            (SELECT 
                ai.id,
                ai.usuario_id,
                ai.created_at,
                ai.valor_aposta as valor,
                ai.valor_premio as valor_premio,
                u.nome as apostador,
                r.nome as revendedor,
                COALESCE(j.nome, ai.jogo_nome) as jogo_nome
            FROM apostas_importadas ai
            LEFT JOIN usuarios u ON ai.usuario_id = u.id
            LEFT JOIN usuarios r ON ai.revendedor_id = r.id
            LEFT JOIN jogos j ON (
                CASE 
                    WHEN ai.jogo_nome LIKE '%MS' THEN 'Mega Sena'
                    WHEN ai.jogo_nome LIKE '%LF' THEN 'LotoFácil'
                    ELSE ai.jogo_nome 
                END = j.nome
            )
            WHERE ai.created_at > FROM_UNIXTIME(?))
        )
        SELECT * FROM ultimas_apostas
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute([$last_check, $last_check]);
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasNewApostas = !empty($apostas);
    $lastAposta = $hasNewApostas ? [
        'id' => $apostas[0]['id'],
        'apostador' => $apostas[0]['apostador'],
        'revendedor' => $apostas[0]['revendedor'] ?? 'N/A',
        'jogo' => $apostas[0]['jogo_nome'] ?? 'Normal',
        'valor' => number_format($apostas[0]['valor'], 2, ',', '.'),
        'valor_premio' => number_format($apostas[0]['valor_premio'], 2, ',', '.'),
        'time' => strtotime($apostas[0]['created_at'])
    ] : null;
    
    echo json_encode([
        'success' => true,
        'hasNewApostas' => $hasNewApostas,
        'lastCheck' => time(),
        'lastAposta' => $lastAposta,
        'count' => count($apostas)
    ]);
} catch (Exception $e) {
    // Registrar erro e retornar resposta de erro
    error_log("Erro ao verificar novas apostas: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar novas apostas',
        'error' => $e->getMessage()
    ]);
} 