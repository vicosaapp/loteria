<?php
require_once '../../config/database.php';
session_start();

// Verificar se é apostador (usuário)
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'usuario') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter os dados enviados
$data = json_decode(file_get_contents('php://input'), true);

// Verificar se os dados são válidos
if (!isset($data['apostas']) || !is_array($data['apostas']) || empty($data['apostas'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Obter o ID do apostador da sessão
$usuario_id = $_SESSION['usuario_id'];

// Iniciar transação
$pdo->beginTransaction();

try {
    $apostasInseridas = 0;
    $erros = [];
    
    // Inserir cada aposta
    foreach ($data['apostas'] as $aposta) {
        // Validar dados da aposta
        if (!isset($aposta['jogo_id']) || !isset($aposta['dezenas']) || !isset($aposta['valor']) || !isset($aposta['premio'])) {
            $erros[] = 'Dados incompletos para uma ou mais apostas';
            continue;
        }
        
        $jogo_id = intval($aposta['jogo_id']);
        $dezenas = implode(',', $aposta['dezenas']);
        $valor_aposta = floatval($aposta['valor']);
        $valor_premio = floatval($aposta['premio']);
        
        // Verificar se o jogo existe
        $stmt = $pdo->prepare("SELECT id FROM jogos WHERE id = ? AND status = 1");
        $stmt->execute([$jogo_id]);
        
        if (!$stmt->fetch()) {
            $erros[] = "Jogo ID $jogo_id não encontrado ou inativo";
            continue;
        }
        
        // Verificar se já existe uma aposta idêntica para hoje
        $stmt = $pdo->prepare("
            SELECT id FROM apostas 
            WHERE usuario_id = ? 
            AND tipo_jogo_id = ? 
            AND numeros = ? 
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$usuario_id, $jogo_id, $dezenas]);
        
        if ($stmt->fetch()) {
            $erros[] = "Você já fez uma aposta com esses números hoje";
            continue;
        }
        
        // Inserir a aposta
        $stmt = $pdo->prepare("
            INSERT INTO apostas (
                usuario_id, 
                tipo_jogo_id, 
                numeros, 
                valor_aposta, 
                valor_premio,
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'pendente', NOW())
        ");
        
        $result = $stmt->execute([
            $usuario_id,
            $jogo_id,
            $dezenas,
            $valor_aposta,
            $valor_premio
        ]);
        
        if ($result) {
            $apostasInseridas++;
        } else {
            $erros[] = "Erro ao inserir aposta: " . implode(',', $pdo->errorInfo());
        }
    }
    
    // Se nenhuma aposta foi inserida, fazer rollback
    if ($apostasInseridas === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Nenhuma aposta foi salva', 'errors' => $erros]);
        exit;
    }
    
    // Commit das alterações
    $pdo->commit();
    
    // Retornar sucesso
    echo json_encode([
        'success' => true, 
        'message' => $apostasInseridas . ' aposta(s) salva(s) com sucesso!',
        'apostas_inseridas' => $apostasInseridas,
        'warnings' => $erros
    ]);
    
} catch (Exception $e) {
    // Rollback em caso de erro
    $pdo->rollBack();
    
    // Retornar erro
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao salvar apostas: ' . $e->getMessage()
    ]);
}
?> 