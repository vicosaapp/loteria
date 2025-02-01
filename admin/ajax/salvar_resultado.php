<?php
session_start();
require_once '../../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Pega os dados do POST
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Inicia a transação
    $pdo->beginTransaction();
    
    // Insere o resultado
    $stmt = $pdo->prepare("
        INSERT INTO resultados (tipo_jogo_id, numeros, data_sorteio) 
        VALUES (?, ?, ?)
    ");
    
    $numerosSorteados = implode(',', $data['numeros']);
    $stmt->execute([$data['jogo_id'], $numerosSorteados, $data['data_sorteio']]);
    $resultado_id = $pdo->lastInsertId();
    
    // Busca o jogo
    $stmt = $pdo->prepare("SELECT * FROM jogos WHERE id = ?");
    $stmt->execute([$data['jogo_id']]);
    $jogo = $stmt->fetch();
    
    // Busca todas as apostas ativas para este jogo
    $stmt = $pdo->prepare("
        SELECT a.*, u.id as usuario_id 
        FROM apostas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.tipo_jogo_id = ? AND a.status = 'aprovada'
    ");
    $stmt->execute([$data['jogo_id']]);
    $apostas = $stmt->fetchAll();
    
    // Array para armazenar os ganhadores
    $ganhadores = [];
    
    // Verifica cada aposta
    foreach ($apostas as $aposta) {
        $numerosAposta = explode(',', $aposta['numeros']);
        $numerosSorteados = explode(',', $numerosSorteados);
        
        // Conta quantos números o apostador acertou
        $acertos = count(array_intersect($numerosAposta, $numerosSorteados));
        
        // Se acertou o número necessário de dezenas
        if ($acertos >= $jogo['dezenas_premiar']) {
            // Insere o ganhador
            $stmt = $pdo->prepare("
                INSERT INTO ganhadores (
                    resultado_id, usuario_id, aposta_id, premio
                ) VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $resultado_id,
                $aposta['usuario_id'],
                $aposta['id'],
                $jogo['premio']
            ]);
            
            // Atualiza o status da aposta para 'ganhadora'
            $stmt = $pdo->prepare("
                UPDATE apostas 
                SET status = 'ganhadora' 
                WHERE id = ?
            ");
            $stmt->execute([$aposta['id']]);
            
            $ganhadores[] = [
                'usuario_id' => $aposta['usuario_id'],
                'premio' => $jogo['premio']
            ];
        }
    }
    
    // Confirma a transação
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Resultado salvo com sucesso!',
        'ganhadores' => count($ganhadores),
        'premio_total' => count($ganhadores) * $jogo['premio']
    ]);
    
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $pdo->rollBack();
    error_log("Erro ao salvar resultado: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 