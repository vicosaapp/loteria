<?php
require_once '../../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Inicializar array de resposta
$response = [
    'success' => false,
    'message' => '',
    'jogo' => null
];

try {
    // Verificar parâmetros
    if (!isset($_POST['nome']) || empty($_POST['nome'])) {
        throw new Exception('Nome do jogo não fornecido');
    }
    
    // Obter parâmetros
    $nomeJogo = trim(explode("\n", $_POST['nome'])[0]);
    $valorAposta = isset($_POST['valor_aposta']) && is_numeric($_POST['valor_aposta']) 
        ? floatval($_POST['valor_aposta']) 
        : 0;
    
    // Buscar jogo pelo nome
    $stmt = $pdo->prepare("
        SELECT id, nome, valor, premio, minimo_numeros, maximo_numeros 
        FROM jogos 
        WHERE LOWER(nome) LIKE LOWER(?) 
        AND status = 1 
        LIMIT 1
    ");
    $stmt->execute(["%{$nomeJogo}%"]);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$jogo) {
        throw new Exception('Jogo não encontrado');
    }
    
    // Obter valores específicos para o jogo
    $stmtValores = $pdo->prepare("
        SELECT jogo_id, dezenas, valor_aposta, valor_premio 
        FROM valores_jogos 
        WHERE jogo_id = ? 
        ORDER BY dezenas, valor_aposta
    ");
    $stmtValores->execute([$jogo['id']]);
    $valores = $stmtValores->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular valor de premiação com base no valor da aposta
    $valorBasePremio = floatval($jogo['premio']);
    $valorBaseAposta = floatval($jogo['valor']);
    
    // Se não houver valor específico, usar o valor base
    if (empty($valores) || $valorAposta <= 0) {
        $valorPremio = $valorBasePremio;
    } else {
        // Buscar o valor mais próximo na tabela de valores
        $valorMaisProximo = null;
        $diferencaMinima = PHP_FLOAT_MAX;
        
        foreach ($valores as $valor) {
            $diferenca = abs(floatval($valor['valor_aposta']) - $valorAposta);
            if ($diferenca < $diferencaMinima) {
                $diferencaMinima = $diferenca;
                $valorMaisProximo = $valor;
            }
        }
        
        if ($valorMaisProximo) {
            $valorPremio = floatval($valorMaisProximo['valor_premio']);
        } else {
            // Calcular proporcionalmente
            $multiplicador = $valorAposta / $valorBaseAposta;
            $valorPremio = $valorBasePremio * $multiplicador;
        }
    }
    
    // Formatar a resposta
    $response = [
        'success' => true,
        'jogo' => [
            'id' => $jogo['id'],
            'nome' => $jogo['nome'],
            'valor_aposta' => $valorAposta,
            'valor_premio' => number_format($valorPremio, 2, '.', ''),
            'debug' => [
                'valor_base_aposta' => $valorBaseAposta,
                'valor_base_premio' => $valorBasePremio,
                'multiplicador' => $valorAposta > 0 ? $valorAposta / $valorBaseAposta : 1
            ]
        ]
    ];

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// Retornar resposta em JSON
header('Content-Type: application/json');
echo json_encode($response);
?> 