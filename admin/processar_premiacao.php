<?php
// Log de erros
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Conexão com o banco
require_once '../config/database.php';

// Log function
function logError($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data) {
        $log .= "\n" . print_r($data, true);
    }
    error_log($log . "\n----------------------------------------\n");
}

try {
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    if (!$dados || !isset($dados['apostas']) || !isset($dados['valorAposta'])) {
        throw new Exception("Dados inválidos");
    }

    $linhas = explode("\n", str_replace("\r", "", trim($dados['apostas'])));
    $linhas = array_values(array_filter($linhas, 'trim'));
    
    if (count($linhas) < 2) {
        throw new Exception('Formato inválido: precisa do nome do jogo e números');
    }

    $identificador_jogo = trim($linhas[0]);
    $primeira_aposta = trim($linhas[1]);
    $numeros = array_values(array_filter(
        explode(' ', preg_replace('/\s+/', ' ', $primeira_aposta)),
        'is_numeric'
    ));
    $quantidade_dezenas = count($numeros);
    $valor_aposta = floatval($dados['valorAposta']);

    // Busca o jogo
    $stmt = $pdo->prepare("SELECT * FROM jogos WHERE identificador_importacao = ?");
    $stmt->execute([$identificador_jogo]);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$jogo) {
        throw new Exception("Jogo não encontrado: " . $identificador_jogo);
    }

    // Busca a premiação
    $stmt = $pdo->prepare("
        SELECT * FROM valores_jogos 
        WHERE jogo_id = ? 
        AND dezenas = ? 
        AND valor_aposta = ?
    ");
    $stmt->execute([$jogo['id'], $quantidade_dezenas, $valor_aposta]);
    $premio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$premio) {
        throw new Exception("Premiação não encontrada para {$quantidade_dezenas} dezenas e R$ {$valor_aposta}");
    }

    echo json_encode([
        'success' => true,
        'valor_premio' => floatval($premio['valor_premio'])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 