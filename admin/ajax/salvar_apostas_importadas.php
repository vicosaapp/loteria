<?php
require_once '../../config/database.php';
session_start();

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

try {
    // Recebe os dados do POST
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!$dados) {
        throw new Exception('Dados inválidos');
    }
    
    // Validações mais rigorosas
    if (!isset($dados['apostador_id']) || !is_numeric($dados['apostador_id'])) {
        throw new Exception('Apostador inválido');
    }
    
    if (empty($dados['apostas']) || !is_array($dados['apostas'])) {
        throw new Exception('Nenhuma aposta válida encontrada');
    }
    
    if (!isset($dados['valor_aposta']) || !is_numeric($dados['valor_aposta']) || $dados['valor_aposta'] <= 0) {
        throw new Exception('Valor da aposta inválido');
    }

    if (empty($dados['jogo'])) {
        throw new Exception('Nome do jogo não informado');
    }

    // Valida se o apostador existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'usuario'");
    $stmt->execute([$dados['apostador_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Apostador não encontrado');
    }

    // Inicia a transação
    $pdo->beginTransaction();
    
    // Prepara a query de inserção
    $stmt = $pdo->prepare("
        INSERT INTO apostas_importadas 
        (usuario_id, revendedor_id, jogo_nome, numeros, valor_aposta, whatsapp, created_at) 
        VALUES 
        (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $apostas_salvas = 0;
    $erros = [];
    
    // Processa cada aposta
    foreach ($dados['apostas'] as $index => $numeros) {
        // Valida os números
        if (!is_array($numeros) || empty($numeros)) {
            $erros[] = "Aposta " . ($index + 1) . ": números inválidos";
            continue;
        }
        
        // Valida quantidade de números
        if (count($numeros) < 6) { // Ajuste este número conforme sua regra
            $erros[] = "Aposta " . ($index + 1) . ": quantidade de números insuficiente";
            continue;
        }
        
        // Remove duplicatas e ordena
        $numeros = array_unique($numeros);
        sort($numeros);
        
        // Converte para string
        $numeros_str = implode(' ', $numeros);
        
        try {
            $resultado = $stmt->execute([
                $dados['apostador_id'],
                $dados['revendedor_id'] ?: null,
                $dados['jogo'],
                $numeros_str,
                $dados['valor_aposta'],
                $dados['whatsapp'] ?: null
            ]);
            
            if ($resultado) {
                $apostas_salvas++;
            }
        } catch (PDOException $e) {
            $erros[] = "Erro ao salvar aposta " . ($index + 1) . ": " . $e->getMessage();
        }
    }
    
    // Se nenhuma aposta foi salva, reverte a transação
    if ($apostas_salvas === 0) {
        throw new Exception('Nenhuma aposta foi salva. Erros: ' . implode(', ', $erros));
    }
    
    // Confirma a transação
    $pdo->commit();
    
    // Retorna sucesso com detalhes
    echo json_encode([
        'success' => true,
        'message' => "Total de {$apostas_salvas} apostas importadas com sucesso!",
        'total_apostas' => $apostas_salvas,
        'erros' => $erros // Inclui erros, se houver
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro na importação de apostas: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 