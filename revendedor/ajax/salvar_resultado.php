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
    // Depuração - Registrar os dados recebidos
    error_log("POST data: " . print_r($_POST, true));
    
    // Validar dados recebidos
    if (empty($_POST['jogo_id']) || empty($_POST['concurso']) || empty($_POST['numeros'])) {
        throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
    }
    
    // Preparar dados
    $jogo_id = filter_input(INPUT_POST, 'jogo_id', FILTER_VALIDATE_INT);
    $concurso = filter_input(INPUT_POST, 'concurso', FILTER_VALIDATE_INT);
    $numeros = explode(',', $_POST['numeros']);
    
    // Tratar a data do sorteio
    if (empty($_POST['data_sorteio'])) {
        $data_sorteio = date('Y-m-d H:i:s');
    } else {
        // Converter para formato MySQL
        $date_obj = new DateTime($_POST['data_sorteio']);
        $data_sorteio = $date_obj->format('Y-m-d H:i:s');
    }
    
    // Validar valor acumulado
    $valor_acumulado_str = isset($_POST['valor_acumulado']) ? $_POST['valor_acumulado'] : '0';
    $valor_acumulado_str = preg_replace('/[^0-9,.]/', '', $valor_acumulado_str);
    $valor_acumulado_str = str_replace(',', '.', $valor_acumulado_str);
    $valor_acumulado = floatval($valor_acumulado_str) ?: 0;
    
    // Validar valor estimado
    $valor_estimado_str = isset($_POST['valor_estimado']) ? $_POST['valor_estimado'] : '0';
    $valor_estimado_str = preg_replace('/[^0-9,.]/', '', $valor_estimado_str);
    $valor_estimado_str = str_replace(',', '.', $valor_estimado_str);
    $valor_estimado = floatval($valor_estimado_str) ?: 0;
    
    // Tratar data do próximo sorteio
    if (empty($_POST['data_proximo'])) {
        // Sem data próxima, usar data atual + 7 dias
        $date_obj = new DateTime();
        $date_obj->add(new DateInterval('P7D'));
        $data_proximo = $date_obj->format('Y-m-d H:i:s');
    } else {
        try {
            $date_obj = new DateTime($_POST['data_proximo']);
            $data_proximo = $date_obj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // Em caso de erro, usar data atual + 7 dias
            $date_obj = new DateTime();
            $date_obj->add(new DateInterval('P7D'));
            $data_proximo = $date_obj->format('Y-m-d H:i:s');
        }
    }
    
    // Validar números
    if (empty($numeros)) {
        throw new Exception('Nenhum número selecionado');
    }
    
    foreach ($numeros as $numero) {
        if (!is_numeric($numero) || intval($numero) <= 0) {
            throw new Exception('Números inválidos detectados');
        }
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Verificar se o concurso já existe
    $stmt = $pdo->prepare("SELECT id FROM concursos WHERE jogo_id = ? AND codigo = ?");
    $stmt->execute([$jogo_id, $concurso]);
    $concurso_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar se a coluna valor_acumulado existe na tabela concursos
    $stmt = $pdo->query("SHOW COLUMNS FROM concursos LIKE 'valor_acumulado'");
    $coluna_valor_acumulado_existe = $stmt->rowCount() > 0;
    
    if ($concurso_existente) {
        // Construir a consulta SQL com base nas colunas existentes
        $campos_atualizacao = [
            "data_sorteio = ?",
            "status = 'finalizado'",
            "updated_at = NOW()"
        ];
        
        $parametros = [$data_sorteio];
        
        // Adicionar campos se existirem
        if ($coluna_valor_acumulado_existe) {
            $campos_atualizacao[] = "valor_acumulado = ?";
            $parametros[] = $valor_acumulado;
        }
        
        // Verificar se data_proximo_concurso existe
        $stmt = $pdo->query("SHOW COLUMNS FROM concursos LIKE 'data_proximo_concurso'");
        if ($stmt->rowCount() > 0) {
            $campos_atualizacao[] = "data_proximo_concurso = ?";
            $parametros[] = $data_proximo;
        }
        
        // Verificar se valor_estimado_proximo existe
        $stmt = $pdo->query("SHOW COLUMNS FROM concursos LIKE 'valor_estimado_proximo'");
        if ($stmt->rowCount() > 0) {
            $campos_atualizacao[] = "valor_estimado_proximo = ?";
            $parametros[] = $valor_estimado;
        }
        
        // Adicionar o ID do concurso como último parâmetro
        $parametros[] = $concurso_existente['id'];
        
        // Construir a consulta SQL final
        $sql = "UPDATE concursos SET " . implode(", ", $campos_atualizacao) . " WHERE id = ?";
        
        // Executar consulta
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        
        // Limpar números antigos
        $stmt = $pdo->prepare("DELETE FROM numeros_sorteados WHERE concurso_id = ?");
        $stmt->execute([$concurso_existente['id']]);
        
        $concurso_id = $concurso_existente['id'];
    } else {
        // Construir a consulta SQL com base nas colunas existentes
        $campos = [
            "jogo_id",
            "codigo",
            "data_sorteio",
            "status",
            "created_at",
            "updated_at"
        ];
        
        $placeholders = ["?", "?", "?", "'finalizado'", "NOW()", "NOW()"];
        $parametros = [$jogo_id, $concurso, $data_sorteio];
        
        // Adicionar campos se existirem
        if ($coluna_valor_acumulado_existe) {
            $campos[] = "valor_acumulado";
            $placeholders[] = "?";
            $parametros[] = $valor_acumulado;
        }
        
        // Verificar se data_proximo_concurso existe
        $stmt = $pdo->query("SHOW COLUMNS FROM concursos LIKE 'data_proximo_concurso'");
        if ($stmt->rowCount() > 0) {
            $campos[] = "data_proximo_concurso";
            $placeholders[] = "?";
            $parametros[] = $data_proximo;
        }
        
        // Verificar se valor_estimado_proximo existe
        $stmt = $pdo->query("SHOW COLUMNS FROM concursos LIKE 'valor_estimado_proximo'");
        if ($stmt->rowCount() > 0) {
            $campos[] = "valor_estimado_proximo";
            $placeholders[] = "?";
            $parametros[] = $valor_estimado;
        }
        
        // Construir a consulta SQL final
        $sql = "INSERT INTO concursos (" . implode(", ", $campos) . ") VALUES (" . implode(", ", $placeholders) . ")";
        
        // Executar consulta
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        
        $concurso_id = $pdo->lastInsertId();
    }
    
    // Inserir números sorteados
    $stmt = $pdo->prepare("INSERT INTO numeros_sorteados (concurso_id, numero) VALUES (?, ?)");
    foreach ($numeros as $numero) {
        $stmt->execute([$concurso_id, intval($numero)]);
    }
    
    // Atualizar informações do jogo
    // Verificar se as colunas existem na tabela jogos
    $stmt = $pdo->query("DESCRIBE jogos");
    $colunas_jogos = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $campos_atualizacao = [];
    $parametros_jogos = [];
    
    // Verificar e adicionar campos disponíveis
    if (in_array("numero_concurso", $colunas_jogos)) {
        $campos_atualizacao[] = "numero_concurso = ?";
        $parametros_jogos[] = $concurso;
    }
    
    if (in_array("valor_acumulado", $colunas_jogos)) {
        $campos_atualizacao[] = "valor_acumulado = ?";
        $parametros_jogos[] = $valor_acumulado;
    }
    
    if (in_array("data_proximo_concurso", $colunas_jogos)) {
        $campos_atualizacao[] = "data_proximo_concurso = ?";
        $parametros_jogos[] = $data_proximo;
    }
    
    if (in_array("valor_estimado_proximo", $colunas_jogos)) {
        $campos_atualizacao[] = "valor_estimado_proximo = ?";
        $parametros_jogos[] = $valor_estimado;
    }
    
    if (in_array("data_atualizacao", $colunas_jogos)) {
        $campos_atualizacao[] = "data_atualizacao = NOW()";
    } else if (in_array("updated_at", $colunas_jogos)) {
        $campos_atualizacao[] = "updated_at = NOW()";
    }
    
    // Apenas atualizar se houver campos para atualizar
    if (!empty($campos_atualizacao)) {
        $parametros_jogos[] = $jogo_id;
        $sql_jogos = "UPDATE jogos SET " . implode(", ", $campos_atualizacao) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql_jogos);
        $stmt->execute($parametros_jogos);
    }
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Resultado salvo com sucesso'
    ]);
    exit;
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro ao salvar resultado: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar resultado: ' . $e->getMessage()
    ]);
    exit;
} 