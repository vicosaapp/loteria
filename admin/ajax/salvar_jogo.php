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
    // Receber dados do POST
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!$dados) {
        throw new Exception('Dados inválidos');
    }

    $pdo->beginTransaction();

    // Inserir ou atualizar jogo
    if (empty($dados['id'])) {
        $stmt = $pdo->prepare("
            INSERT INTO jogos (nome, minimo_numeros, maximo_numeros, acertos_premio, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $dados['nome'],
            $dados['minimo_numeros'],
            $dados['maximo_numeros'],
            $dados['acertos_premio'],
            $dados['status']
        ]);
        
        $jogo_id = $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare("
            UPDATE jogos 
            SET nome = ?, 
                minimo_numeros = ?, 
                maximo_numeros = ?, 
                acertos_premio = ?, 
                status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $dados['nome'],
            $dados['minimo_numeros'],
            $dados['maximo_numeros'],
            $dados['acertos_premio'],
            $dados['status'],
            $dados['id']
        ]);
        
        $jogo_id = $dados['id'];
        
        // Remover valores antigos
        $stmt = $pdo->prepare("DELETE FROM valores_jogos WHERE jogo_id = ?");
        $stmt->execute([$jogo_id]);
    }

    // Inserir novos valores
    $valores = json_decode($dados['valores'], true);
    $stmt = $pdo->prepare("
        INSERT INTO valores_jogos (jogo_id, valor_aposta, dezenas, valor_premio)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($valores as $valor) {
        $stmt->execute([
            $jogo_id,
            $valor['valor_aposta'],
            $valor['dezenas'],
            $valor['valor_premio']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Jogo salvo com sucesso!'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 