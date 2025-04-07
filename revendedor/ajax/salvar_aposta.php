<?php
require_once '../../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Verificar campos obrigatórios
if (empty($_POST['cliente_id']) || empty($_POST['jogo_id']) || empty($_POST['numeros']) || empty($_POST['valor'])) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
    exit;
}

try {
    // Sanitizar dados
    $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $jogo_id = filter_input(INPUT_POST, 'jogo_id', FILTER_VALIDATE_INT);
    $numeros = filter_input(INPUT_POST, 'numeros', FILTER_SANITIZE_STRING);
    $valor_aposta = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
    $revendedor_id = $_SESSION['usuario_id'];
    
    // Valor do prêmio 
    $valor_premio = 0;
    if (isset($_POST['premio']) && is_numeric($_POST['premio'])) {
        $valor_premio = filter_input(INPUT_POST, 'premio', FILTER_VALIDATE_FLOAT);
    } else {
        // Buscar o valor do prêmio baseado no jogo
        $stmt = $pdo->prepare("SELECT premio FROM jogos WHERE id = ?");
        $stmt->execute([$jogo_id]);
        $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($jogo) {
            $valor_premio = $jogo['premio'];
        }
    }
    
    // Validar dados
    if (!$cliente_id || !$jogo_id || !$numeros || !$valor_aposta) {
        throw new Exception('Dados inválidos');
    }
    
    // Verificar se o cliente pertence ao revendedor
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND revendedor_id = ? AND tipo = 'usuario'");
    $stmt->execute([$cliente_id, $revendedor_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Cliente não encontrado ou não pertence a este revendedor');
    }
    
    // Verificar se já existe uma aposta idêntica (mesmo cliente, jogo e números)
    $stmt = $pdo->prepare("
        SELECT id FROM apostas 
        WHERE usuario_id = ? 
        AND tipo_jogo_id = ? 
        AND numeros = ? 
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$cliente_id, $jogo_id, $numeros]);
    
    if ($stmt->fetch()) {
        throw new Exception('Esta aposta já foi registrada hoje. Não são permitidas apostas duplicadas.');
    }
    
    // Inserir aposta
    $stmt = $pdo->prepare("
        INSERT INTO apostas (
            usuario_id, 
            tipo_jogo_id, 
            numeros, 
            valor_aposta,
            valor_premio,
            status,
            created_at,
            revendedor_id
        ) VALUES (?, ?, ?, ?, ?, 'aprovada', NOW(), ?)
    ");
    
    $result = $stmt->execute([
        $cliente_id,
        $jogo_id,
        $numeros,
        $valor_aposta,
        $valor_premio,
        $revendedor_id
    ]);
    
    if (!$result) {
        throw new Exception('Erro ao salvar a aposta');
    }
    
    echo json_encode(['success' => true, 'message' => 'Aposta registrada com sucesso']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 