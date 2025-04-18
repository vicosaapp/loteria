<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    // Validar dados recebidos
    if (empty($_POST['jogo_id']) || empty($_POST['concurso']) || empty($_POST['data_sorteio']) || empty($_POST['numeros'])) {
        throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
    }
    
    // Preparar dados
    $jogo_id = filter_input(INPUT_POST, 'jogo_id', FILTER_VALIDATE_INT);
    $concurso = filter_input(INPUT_POST, 'concurso', FILTER_VALIDATE_INT);
    $data_sorteio = filter_input(INPUT_POST, 'data_sorteio', FILTER_SANITIZE_STRING);
    $numeros = explode(',', $_POST['numeros']);
    
    // Validar valor acumulado
    $valor_acumulado = str_replace(['R$', ' ', '.'], '', $_POST['valor_acumulado'] ?? '0');
    $valor_acumulado = str_replace(',', '.', $valor_acumulado);
    $valor_acumulado = filter_var($valor_acumulado, FILTER_VALIDATE_FLOAT) ?: 0;
    
    // Validar valor estimado
    $valor_estimado = str_replace(['R$', ' ', '.'], '', $_POST['valor_estimado'] ?? '0');
    $valor_estimado = str_replace(',', '.', $valor_estimado);
    $valor_estimado = filter_var($valor_estimado, FILTER_VALIDATE_FLOAT) ?: 0;
    
    // Validar data do próximo sorteio
    $data_proximo = !empty($_POST['data_proximo']) ? $_POST['data_proximo'] : null;
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Verificar se o concurso já existe
    $stmt = $pdo->prepare("SELECT id FROM concursos WHERE jogo_id = ? AND codigo = ?");
    $stmt->execute([$jogo_id, $concurso]);
    $concurso_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($concurso_existente) {
        // Atualizar concurso existente
        $stmt = $pdo->prepare("
            UPDATE concursos 
            SET 
                data_sorteio = ?,
                valor_acumulado = ?,
                data_proximo_concurso = ?,
                valor_estimado_proximo = ?,
                status = 'finalizado',
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data_sorteio,
            $valor_acumulado,
            $data_proximo,
            $valor_estimado,
            $concurso_existente['id']
        ]);
        
        // Limpar números antigos
        $stmt = $pdo->prepare("DELETE FROM numeros_sorteados WHERE concurso_id = ?");
        $stmt->execute([$concurso_existente['id']]);
        
        $concurso_id = $concurso_existente['id'];
    } else {
        // Inserir novo concurso
        $stmt = $pdo->prepare("
            INSERT INTO concursos (
                jogo_id,
                codigo,
                data_sorteio,
                valor_acumulado,
                data_proximo_concurso,
                valor_estimado_proximo,
                status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'finalizado', NOW(), NOW())
        ");
        
        $stmt->execute([
            $jogo_id,
            $concurso,
            $data_sorteio,
            $valor_acumulado,
            $data_proximo,
            $valor_estimado
        ]);
        
        $concurso_id = $pdo->lastInsertId();
    }
    
    // Inserir números sorteados
    $stmt = $pdo->prepare("INSERT INTO numeros_sorteados (concurso_id, numero) VALUES (?, ?)");
    foreach ($numeros as $numero) {
        $stmt->execute([$concurso_id, $numero]);
    }
    
    // Atualizar informações do jogo
    $stmt = $pdo->prepare("
        UPDATE jogos 
        SET 
            numero_concurso = ?,
            valor_acumulado = ?,
            data_proximo_concurso = ?,
            valor_estimado_proximo = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $concurso,
        $valor_acumulado,
        $data_proximo,
        $valor_estimado,
        $jogo_id
    ]);
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Resultado salvo com sucesso'
    ]);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro ao salvar resultado: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar resultado: ' . $e->getMessage()
    ]);
} 