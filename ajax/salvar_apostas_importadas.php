<?php
// Desabilitar a exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Limpar qualquer saída anterior
if (ob_get_level()) ob_end_clean();

// Definir headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../../config/database.php';

try {
    // Receber dados
    $input = file_get_contents('php://input');
    $dados = json_decode($input, true);
    
    // Log para debug
    error_log("Dados recebidos: " . print_r($dados, true));

    // Validações básicas
    if (empty($dados['apostador_id'])) {
        throw new Exception('Apostador não selecionado');
    }
    if (empty($dados['revendedor_id'])) {
        throw new Exception('Revendedor não selecionado');
    }
    if (empty($dados['apostas'])) {
        throw new Exception('Nenhuma aposta informada');
    }

    // Buscar o jogo pelo identificador
    $stmt = $pdo->prepare("
        SELECT id, nome 
        FROM jogos 
        WHERE identificador_importacao = ?
    ");
    $stmt->execute([$dados['identificador_jogo']]);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$jogo) {
        throw new Exception('Jogo não encontrado para o identificador: ' . $dados['identificador_jogo']);
    }
    
    $jogo_id = $jogo['id'];
    
    // Preparar os valores
    $valor_aposta = floatval(str_replace(['R$', '.', ' '], '', str_replace(',', '.', $dados['valor_aposta'])));
    $valor_jogo = floatval(str_replace(['R$', '.', ' '], '', str_replace(',', '.', $dados['valor_jogo'])));
    
    // Contar dezenas da primeira aposta
    $primeira_aposta = $dados['apostas'][0];
    $qtd_dezenas = count(explode(' ', $primeira_aposta));

    error_log("Valores processados:");
    error_log("jogo_id: $jogo_id");
    error_log("valor_aposta: $valor_aposta");
    error_log("dezenas: $qtd_dezenas");
    error_log("valor_jogo: $valor_jogo");

    // Inserir na tabela valores_jogos
    $stmt = $pdo->prepare("
        INSERT INTO valores_jogos 
        (jogo_id, valor_aposta, dezenas, valor_premio) 
        VALUES 
        (?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $jogo_id,
        $valor_aposta,
        $qtd_dezenas,
        $valor_jogo
    ]);

    if (!$result) {
        throw new Exception("Erro ao salvar valores: " . print_r($stmt->errorInfo(), true));
    }

    // Continua com o código existente para salvar as apostas
    $stmt = $pdo->prepare("
        INSERT INTO apostas_importadas 
        (usuario_id, revendedor_id, jogo_nome, numeros, valor_aposta, 
         whatsapp, quantidade_dezenas) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?)
    ");

    // Contador de apostas inseridas
    $apostas_inseridas = 0;

    // Inserir cada aposta
    foreach ($dados['apostas'] as $numeros) {
        if (empty(trim($numeros))) continue;
        
        // Contar dezenas
        $dezenas = explode(' ', trim($numeros));
        $qtd_dezenas = count($dezenas);

        try {
            $result = $stmt->execute([
                $dados['apostador_id'],
                $dados['revendedor_id'],
                'Loterias Mobile: LF',
                trim($numeros),
                $dados['valor_aposta'],
                $dados['whatsapp'],
                $qtd_dezenas
            ]);

            if ($result) {
                $apostas_inseridas++;
            }
        } catch (PDOException $e) {
            error_log("Erro ao inserir aposta: " . $e->getMessage());
            continue;
        }
    }

    // Verificar se alguma aposta foi inserida
    if ($apostas_inseridas === 0) {
        throw new Exception('Nenhuma aposta foi salva');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Dados salvos com sucesso',
        'debug' => [
            'jogo_id' => $jogo_id,
            'valor_aposta' => $valor_aposta,
            'dezenas' => $qtd_dezenas,
            'valor_premio' => $valor_jogo
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'dados_recebidos' => $dados ?? null
        ]
    ]);
}