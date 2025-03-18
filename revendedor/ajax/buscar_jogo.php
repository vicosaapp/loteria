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
    if (!isset($_POST['nome']) || !isset($_POST['valor_aposta'])) {
        throw new Exception('Nome do jogo e valor da aposta são obrigatórios');
    }

    $texto_completo = trim($_POST['nome']);
    $valor_aposta = str_replace(['R$', ' '], '', $_POST['valor_aposta']);
    $valor_aposta = str_replace('.', '', $valor_aposta);
    $valor_aposta = str_replace(',', '.', $valor_aposta);
    $valor_aposta = floatval($valor_aposta);
    
    // Separar nome do jogo da primeira linha
    $linhas = array_filter(explode("\n", $texto_completo), 'trim');
    if (count($linhas) < 2) {
        throw new Exception("Formato inválido: necessário nome do jogo e pelo menos uma aposta");
    }
    
    $nome_jogo = array_shift($linhas); // Remove e retorna primeira linha (nome do jogo)
    $primeira_aposta = array_shift($linhas); // Remove e retorna segunda linha (primeira aposta)
    
    // Contar números da primeira aposta
    preg_match_all('/\d+/', $primeira_aposta, $matches);
    $numeros = $matches[0];
    $num_dezenas = count($numeros);
    
    if ($num_dezenas == 0) {
        throw new Exception("Nenhum número encontrado na primeira aposta");
    }
    
    // Debug
    error_log("Nome do jogo: " . $nome_jogo);
    error_log("Números da primeira aposta: " . implode(',', $numeros));
    error_log("Total de dezenas: " . $num_dezenas);
    error_log("Valor da aposta: " . $valor_aposta);
    
    // Buscar o jogo
    $sql = "SELECT j.id, j.nome, j.titulo_importacao 
            FROM jogos j 
            WHERE j.titulo_importacao = :nome 
            LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['nome' => $nome_jogo]);
    
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jogo) {
        // Buscar o valor base do prêmio para o número de dezenas
        $sql_premio = "SELECT valor_premio, valor_aposta 
                      FROM valores_jogos 
                      WHERE jogo_id = :jogo_id 
                      AND dezenas = :dezenas
                      ORDER BY valor_aposta ASC
                      LIMIT 1";
                      
        $stmt_premio = $pdo->prepare($sql_premio);
        $stmt_premio->execute([
            'jogo_id' => $jogo['id'],
            'dezenas' => $num_dezenas
        ]);
        
        $premio = $stmt_premio->fetch(PDO::FETCH_ASSOC);
        
        if (!$premio) {
            throw new Exception("Valor não encontrado para {$num_dezenas} dezenas");
        }

        // Calcular o valor do prêmio proporcional ao valor da aposta
        $valor_base_aposta = floatval($premio['valor_aposta']);
        $valor_base_premio = floatval($premio['valor_premio']);
        
        // Calcular multiplicador (proporção direta)
        $multiplicador = $valor_aposta / $valor_base_aposta;
        
        // Calcular valor final do prêmio
        $valor_premio_final = $valor_base_premio * $multiplicador;

        error_log("Valor base aposta: " . $valor_base_aposta);
        error_log("Valor base prêmio: " . $valor_base_premio);
        error_log("Multiplicador: " . $multiplicador);
        error_log("Valor prêmio final: " . $valor_premio_final);

        $response = [
            'success' => true,
            'jogo' => [
                'id' => (int)$jogo['id'],
                'nome' => (string)$jogo['nome'],
                'titulo_importacao' => (string)$jogo['titulo_importacao'],
                'valor_premio' => $valor_premio_final,
                'dezenas' => $num_dezenas,
                'numeros' => $numeros,
                'debug' => [
                    'valor_base_aposta' => $valor_base_aposta,
                    'valor_base_premio' => $valor_base_premio,
                    'valor_aposta_atual' => $valor_aposta,
                    'multiplicador' => $multiplicador
                ]
            ]
        ];
    } else {
        // Debug - listar jogos disponíveis
        $sql_check = "SELECT titulo_importacao FROM jogos";
        $stmt_check = $pdo->query($sql_check);
        $titulos = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
        
        $response = [
            'success' => false,
            'message' => 'Jogo não encontrado: ' . $nome_jogo,
            'debug' => [
                'titulos_disponiveis' => $titulos
            ]
        ];
    }
    
    die(json_encode($response, JSON_UNESCAPED_UNICODE));

} catch (Exception $e) {
    error_log("Erro em buscar_jogo.php: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
} 