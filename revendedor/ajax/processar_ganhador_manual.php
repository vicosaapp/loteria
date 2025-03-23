<?php
// Habilitar exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuração do banco de dados
require_once '../../config/database.php';

// Definir o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Verificar se foram enviados os parâmetros necessários
if (empty($_POST['aposta_id']) || empty($_POST['concurso']) || empty($_POST['jogo_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros inválidos'
    ]);
    exit;
}

// Obter os parâmetros da requisição
$aposta_id = intval($_POST['aposta_id']);
$concurso = $_POST['concurso'];
$jogo_id = intval($_POST['jogo_id']);

try {
    // Buscar os números sorteados para este concurso
    $sql = "SELECT GROUP_CONCAT(ns.numero ORDER BY ns.numero ASC) as numeros_sorteados
            FROM concursos c
            JOIN numeros_sorteados ns ON ns.concurso_id = c.id
            WHERE c.codigo = ? AND c.jogo_id = ?
            GROUP BY c.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$concurso, $jogo_id]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resultado) {
        echo json_encode([
            'success' => false,
            'message' => 'Concurso não encontrado'
        ]);
        exit;
    }
    
    // Buscar a aposta
    $sql = "SELECT a.*, u.nome as nome_usuario
            FROM apostas a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$aposta_id]);
    $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aposta) {
        echo json_encode([
            'success' => false,
            'message' => 'Aposta não encontrada'
        ]);
        exit;
    }
    
    // Verificar os acertos
    $numeros_sorteados = explode(',', $resultado['numeros_sorteados']);
    $numeros_apostados = explode(',', $aposta['numeros']);
    
    // Limpar e converter para inteiros
    $numeros_sorteados = array_map('intval', $numeros_sorteados);
    $numeros_apostados = array_map('intval', array_map('trim', $numeros_apostados));
    
    // Contar acertos
    $acertos = count(array_intersect($numeros_apostados, $numeros_sorteados));
    
    // Buscar valor do prêmio baseado nos acertos
    $sql = "SELECT valor_premio FROM valores_jogos 
            WHERE jogo_id = ? AND dezenas = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$jogo_id, $acertos]);
    $premio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$premio) {
        // Prêmio padrão baseado no número de acertos
        $valor_premio = $acertos * 50.00;
    } else {
        $valor_premio = $premio['valor_premio'];
    }
    
    // Atualizar a aposta
    $sql = "UPDATE apostas 
            SET processado = 1, 
                concurso = ?, 
                valor_premio = ? 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$concurso, $valor_premio, $aposta_id]);
    
    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Aposta processada com sucesso',
        'premio' => $valor_premio,
        'acertos' => $acertos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar aposta: ' . $e->getMessage()
    ]);
} 