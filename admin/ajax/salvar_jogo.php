<?php
// Desativa exibição de erros
ini_set('display_errors', 0);
error_reporting(0);

// Função para retornar JSON e encerrar
function returnJson($data) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Captura todos os erros
set_error_handler(function($errno, $errstr) {
    returnJson([
        'success' => false,
        'message' => 'Erro interno: ' . $errstr
    ]);
});

try {
    // Inicia buffer
    ob_start();
    
    // Verifica sessão
    session_start();
    if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
        returnJson(['success' => false, 'message' => 'Não autorizado']);
    }

    // Conexão com banco
    require_once '../../config/database.php';

    // Pega dados do POST
    $input = file_get_contents('php://input');
    
    // Log dos dados recebidos
    error_log('Dados recebidos: ' . $input);
    
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        returnJson(['success' => false, 'message' => 'JSON inválido']);
    }

    // Log dos dados após decode
    error_log('Dados decodificados: ' . print_r($data, true));

    // Prepara dados com verificação mais rigorosa
    $id = isset($data['id']) ? intval($data['id']) : null;
    $nome = trim($data['nome'] ?? '');
    $identificador_importacao = trim($data['identificador_importacao'] ?? '');
    $identificador_importacao = trim($data['identificador_importacao'] ?? '');
    $min_numeros = intval($data['minimo_numeros'] ?? 0);
    $max_numeros = intval($data['maximo_numeros'] ?? 0);
    $acertos = intval($data['acertos_premio'] ?? 0);
    $status = intval($data['status'] ?? 1);
    
    // Tratamento específico para valores monetários e numéricos
    $valor_aposta = str_replace(',', '.', $data['valor_aposta'] ?? '0');
    $valor_aposta = number_format((float)$valor_aposta, 2, '.', ''); // Formata com 2 casas decimais
    
    $dezenas = intval($data['quantidade_dezenas'] ?? 0);
    
    $valor_premio = str_replace(',', '.', $data['valor_premio'] ?? '0');
    $valor_premio = number_format((float)$valor_premio, 2, '.', ''); // Formata com 2 casas decimais

    // Log dos valores processados
    error_log(sprintf(
        'Valores processados: valor_aposta=%s, dezenas=%d, valor_premio=%s',
        $valor_aposta,
        $dezenas,
        $valor_premio
    ));

    // Valida dados
    if (empty($nome)) {
        returnJson(['success' => false, 'message' => 'Nome é obrigatório']);
    }

    // Inicia transação
    $pdo->beginTransaction();

    if ($id) {
        // Atualiza jogo
        $stmt = $pdo->prepare("
            UPDATE jogos 
            SET nome = ?, 
                identificador_importacao = ?,
                identificador_importacao = ?, 
                minimo_numeros = ?, 
                maximo_numeros = ?, 
                acertos_premio = ?, 
                status = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $nome, 
            $identificador_importacao,
            $identificador_importacao, 
            $min_numeros, 
            $max_numeros, 
            $acertos, 
            $status, 
            $id
        ]);

        // Primeiro, remove os valores existentes para este jogo
        $stmt = $pdo->prepare("DELETE FROM valores_jogos WHERE jogo_id = ?");
        $stmt->execute([$id]);

        // Prepara o statement para inserção
        $stmt = $pdo->prepare("INSERT INTO valores_jogos (jogo_id, valor_aposta, dezenas, valor_premio) VALUES (?, ?, ?, ?)");
        
        // Insere todos os valores
        foreach ($data['valores'] as $valor) {
            // Remove R$ e espaços
            $valor_aposta = str_replace(['R$', ' '], '', $valor['valor_aposta']);
            $valor_premio = str_replace(['R$', ' '], '', $valor['valor_premio']);
            
            // Converte vírgula para ponto
            $valor_aposta = str_replace(',', '.', $valor_aposta);
            $valor_premio = str_replace(',', '.', $valor_premio);
            
            // Remove pontos de milhar
            $valor_aposta = str_replace('.', '', $valor_aposta);
            $valor_premio = str_replace('.', '', $valor_premio);
            
            // Adiciona o ponto decimal no lugar correto
            if (strlen($valor_aposta) > 2) {
                $valor_aposta = substr_replace($valor_aposta, '.', -2, 0);
            }
            if (strlen($valor_premio) > 2) {
                $valor_premio = substr_replace($valor_premio, '.', -2, 0);
            }
            
            $dezenas = intval($valor['dezenas']);
            
            // Log para debug
            error_log("Inserindo: aposta={$valor_aposta}, dezenas={$dezenas}, premio={$valor_premio}");
            
            $stmt->execute([$id, $valor_aposta, $dezenas, $valor_premio]);
        }
    } else {
        // Insere jogo
        $stmt = $pdo->prepare("
            INSERT INTO jogos (
                nome, 
                titulo_importacao,
                identificador_importacao, 
                minimo_numeros, 
                maximo_numeros, 
                acertos_premio, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nome, 
            $titulo_importacao,
            $identificador_importacao, 
            $min_numeros, 
            $max_numeros, 
            $acertos, 
            $status
        ]);
        
        $jogo_id = $pdo->lastInsertId();

        // Insere valores
        $stmt = $pdo->prepare("INSERT INTO valores_jogos (jogo_id, valor_aposta, dezenas, valor_premio) VALUES (?, ?, ?, ?)");
        $stmt->execute([$jogo_id, $valor_aposta, $dezenas, $valor_premio]);
    }

    // Commit
    $pdo->commit();

    returnJson([
        'success' => true,
        'message' => $id ? 'Jogo atualizado com sucesso!' : 'Jogo criado com sucesso!'
    ]);

} catch (Exception $e) {
    // Rollback em caso de erro
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log do erro com mais detalhes
    error_log('Erro em salvar_jogo.php: ' . $e->getMessage());
    error_log('Dados recebidos: ' . print_r($data, true));

    returnJson([
        'success' => false,
        'message' => 'Erro ao salvar: ' . $e->getMessage()
    ]);
}