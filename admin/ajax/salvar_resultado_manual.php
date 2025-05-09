<?php
// Garante que nenhuma saída de buffer seja enviada antes dos cabeçalhos
ob_start();

// Desativar a exibição de erros para o usuário (só no log)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Definir o cabeçalho de tipo de conteúdo para JSON
header('Content-Type: application/json');

// Função para retornar resposta JSON de erro de maneira padronizada
function json_error($message) {
    ob_clean(); // Limpa qualquer saída anterior
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Iniciar sessão e verificar autenticação
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    json_error('Acesso negado. Você precisa estar logado como administrador.');
}

// Incluir arquivos necessários
try {
    require_once '../../config/database.php';
} catch (Exception $e) {
    json_error('Erro ao carregar dependências: ' . $e->getMessage());
}

// Verificar se é um POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método inválido. Apenas POST é permitido.');
}

try {
    // Capturar dados do formulário
    $jogo_id = isset($_POST['jogo_id']) ? intval($_POST['jogo_id']) : 0;
    $concurso = isset($_POST['concurso']) ? intval($_POST['concurso']) : 0;
    $data_sorteio = isset($_POST['data_sorteio']) ? $_POST['data_sorteio'] : null;
    $data_proximo = isset($_POST['data_proximo']) ? $_POST['data_proximo'] : null;
    $numeros_selecionados = isset($_POST['numeros_selecionados']) ? $_POST['numeros_selecionados'] : '';
    $valor_acumulado = isset($_POST['valor_acumulado']) ? floatval($_POST['valor_acumulado']) : 0;
    $valor_estimado = isset($_POST['valor_estimado']) ? floatval($_POST['valor_estimado']) : 0;

    // Log para debug
    error_log("Dados recebidos para salvar resultado: " . json_encode($_POST));

    // Validar dados
    if (!$jogo_id || !$concurso || !$data_sorteio || empty($numeros_selecionados)) {
        json_error('Dados obrigatórios não fornecidos.');
    }

    // Formatar datas
    try {
        $data_sorteio_obj = new DateTime($data_sorteio);
        $data_sorteio_formatada = $data_sorteio_obj->format('Y-m-d H:i:s');
        
        $data_proximo_formatada = null;
        if ($data_proximo) {
            $data_proximo_obj = new DateTime($data_proximo);
            $data_proximo_formatada = $data_proximo_obj->format('Y-m-d H:i:s');
        }
    } catch (Exception $e) {
        json_error('Erro ao processar datas: ' . $e->getMessage());
    }

    // Processar números selecionados
    $numeros = explode(',', $numeros_selecionados);
    if (empty($numeros)) {
        json_error('Nenhum número foi selecionado.');
    }

    // Validar números de acordo com o jogo
    try {
        // Obter informações do jogo
        $stmt = $pdo->prepare("SELECT nome FROM jogos WHERE id = ?");
        $stmt->execute([$jogo_id]);
        $jogo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$jogo) {
            throw new Exception("Jogo não encontrado com ID: $jogo_id");
        }
        
        $jogo_nome = strtolower($jogo['nome']);
        
        // Verificar quantidade mínima de números
        $min_numeros = 6; // Padrão para Mega-Sena
        
        if (strpos($jogo_nome, 'lotofácil') !== false || strpos($jogo_nome, 'lotofacil') !== false) {
            $min_numeros = 15;
        } elseif (strpos($jogo_nome, 'lotomania') !== false) {
            $min_numeros = 20;
        } elseif (strpos($jogo_nome, 'quina') !== false) {
            $min_numeros = 5;
        } elseif (strpos($jogo_nome, 'dia de sorte') !== false) {
            $min_numeros = 7;
        } elseif (strpos($jogo_nome, 'timemania') !== false) {
            $min_numeros = 10;
        }
        
        if (count($numeros) < $min_numeros) {
            throw new Exception("É necessário selecionar pelo menos $min_numeros números para $jogo_nome");
        }
        
    } catch (Exception $e) {
        json_error('Erro ao validar jogo: ' . $e->getMessage());
    }

    // Começar transação
    try {
        $pdo->beginTransaction();
        
        // Verificar se o concurso já existe
        $stmt = $pdo->prepare("SELECT id FROM concursos WHERE jogo_id = ? AND codigo = ?");
        $stmt->execute([$jogo_id, $concurso]);
        $concurso_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($concurso_existente) {
            // Atualizar concurso existente
            $stmt = $pdo->prepare("UPDATE concursos SET data_sorteio = ?, status = 'finalizado' WHERE id = ?");
            $stmt->execute([$data_sorteio_formatada, $concurso_existente['id']]);
            $concurso_id = $concurso_existente['id'];
            
            // Remover números antigos
            $stmt = $pdo->prepare("DELETE FROM numeros_sorteados WHERE concurso_id = ?");
            $stmt->execute([$concurso_id]);
        } else {
            // Inserir novo concurso
            $stmt = $pdo->prepare("INSERT INTO concursos (jogo_id, codigo, data_sorteio, status) VALUES (?, ?, ?, 'finalizado')");
            $stmt->execute([$jogo_id, $concurso, $data_sorteio_formatada]);
            $concurso_id = $pdo->lastInsertId();
        }
        
        // Inserir números sorteados
        $stmt = $pdo->prepare("INSERT INTO numeros_sorteados (concurso_id, numero) VALUES (?, ?)");
        foreach ($numeros as $numero) {
            $stmt->execute([$concurso_id, intval($numero)]);
        }
        
        // Atualizar informações adicionais do jogo
        $stmt = $pdo->prepare("UPDATE jogos SET 
            valor_acumulado = ?,
            data_proximo_concurso = ?,
            valor_estimado_proximo = ?,
            numero_concurso = ?
            WHERE id = ?");
        
        $stmt->execute([
            $valor_acumulado,
            $data_proximo_formatada,
            $valor_estimado,
            $concurso,
            $jogo_id
        ]);
        
        // Verificar apostas com números semelhantes - ignorando tipo_jogo_id
        $numeros_string = implode(',', $numeros);
        
        // Adicionar log para debug
        error_log("Buscando ganhadores para concurso_id: $concurso_id, jogo_id: $jogo_id");
        
        // Verificar se existem apostas que correspondem aos números sorteados
        $sql_verifica_apostas = "
            SELECT COUNT(*) as total, GROUP_CONCAT(DISTINCT tipo_jogo_id) as tipos_jogo
            FROM apostas 
            WHERE numeros = ? AND (status = 'aprovada' OR status = 'ativa')
        ";
        $stmt_verifica = $pdo->prepare($sql_verifica_apostas);
        $stmt_verifica->execute([$numeros_selecionados]);
        $verifica_result = $stmt_verifica->fetch(PDO::FETCH_ASSOC);
        
        error_log("Verificação de apostas: total=" . $verifica_result['total'] . ", tipos_jogo=" . $verifica_result['tipos_jogo']);
        
        // Query para verificar apostas que acertaram todos os números
        // Removendo a condição de tipo_jogo_id para encontrar todas as apostas com os números corretos
        $sql_ganhadores = "
            SELECT 
                ap.id AS aposta_id,
                ap.usuario_id,
                u.nome AS nome_usuario,
                ap.valor_aposta AS valor,
                ap.numeros,
                ap.tipo_jogo_id,
                ap.revendedor_id,
                (SELECT nome FROM usuarios WHERE id = ap.revendedor_id) AS nome_revendedor,
                COUNT(ns.numero) AS acertos
            FROM apostas ap
            JOIN usuarios u ON ap.usuario_id = u.id
            JOIN numeros_sorteados ns ON FIND_IN_SET(ns.numero, ap.numeros) > 0 AND ns.concurso_id = ?
            WHERE (ap.status = 'ativa' OR ap.status = 'aprovada')
            GROUP BY ap.id, ap.usuario_id, u.nome, ap.valor_aposta, ap.numeros, ap.revendedor_id
            HAVING acertos >= ?
            ORDER BY acertos DESC
        ";
        
        // Definir valor mínimo de acertos para considerar ganhador
        $min_acertos = round($min_numeros * 0.7); // Reduzindo para 70% dos números
        
        $stmt_ganhadores = $pdo->prepare($sql_ganhadores);
        $stmt_ganhadores->execute([$concurso_id, $min_acertos]);
        $ganhadores = $stmt_ganhadores->fetchAll(PDO::FETCH_ASSOC);
        
        // Log para depuração
        error_log("Número de ganhadores encontrados: " . count($ganhadores));
        if (!empty($ganhadores)) {
            error_log("Primeiro ganhador: " . json_encode($ganhadores[0]));
        }
        
        // Se não encontrou ganhadores, buscar diretamente pelos números
        if (empty($ganhadores)) {
            error_log("Tentando encontrar apostas pelo número exato: $numeros_string");
            
            $sql_busca_direta = "
                SELECT 
                    ap.id AS aposta_id,
                    ap.usuario_id,
                    u.nome AS nome_usuario,
                    ap.valor_aposta AS valor,
                    ap.numeros,
                    ap.tipo_jogo_id,
                    ap.revendedor_id,
                    (SELECT nome FROM usuarios WHERE id = ap.revendedor_id) AS nome_revendedor,
                    COUNT(DISTINCT ap.id) AS acertos
                FROM apostas ap
                JOIN usuarios u ON ap.usuario_id = u.id
                WHERE (ap.status = 'ativa' OR ap.status = 'aprovada')
                AND ap.numeros = ?
                GROUP BY ap.id, ap.usuario_id, u.nome, ap.valor_aposta, ap.numeros, ap.revendedor_id
                ORDER BY acertos DESC
            ";
            
            $stmt_busca_direta = $pdo->prepare($sql_busca_direta);
            $stmt_busca_direta->execute([$numeros_selecionados]);
            $ganhadores_diretos = $stmt_busca_direta->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($ganhadores_diretos)) {
                error_log("Encontrados " . count($ganhadores_diretos) . " ganhadores por busca direta");
                $ganhadores = $ganhadores_diretos;
                
                // Atribui um valor mínimo de acertos para cada ganhador direto
                foreach ($ganhadores as &$ganhador) {
                    $ganhador['acertos'] = $min_numeros;
                }
            }
        }
        
        // Processar prêmios para ganhadores
        $ganhadores_processados = [];
        
        if (!empty($ganhadores)) {
            // Agrupar por número de acertos
            $grupos_acertos = [];
            
            foreach ($ganhadores as $ganhador) {
                $acertos = $ganhador['acertos'];
                if (!isset($grupos_acertos[$acertos])) {
                    $grupos_acertos[$acertos] = [];
                }
                $grupos_acertos[$acertos][] = $ganhador;
            }
            
            // Valor base do prêmio para acerto total (parâmetro que pode ser ajustado)
            $premio_base = 1000.00; // R$ 1.000,00 para quem acertar todos
            
            // Processar cada grupo de acertos
            krsort($grupos_acertos); // Ordenar por número de acertos (maior para menor)
            
            foreach ($grupos_acertos as $acertos => $grupo) {
                // Definir valor do prêmio para este grupo
                // Quanto mais acertos, maior o prêmio
                $percentual_acerto = $acertos / $min_numeros;
                $premio_grupo = $premio_base * pow($percentual_acerto, 3); // Fórmula para cálculo do prêmio
                
                // Dividir prêmio entre ganhadores deste grupo
                $premio_individual = $premio_grupo / count($grupo);
                
                foreach ($grupo as $ganhador) {
                    // Registrar prêmio para cada ganhador
                    $ganhadores_processados[] = [
                        'aposta_id' => $ganhador['aposta_id'],
                        'usuario_id' => $ganhador['usuario_id'],
                        'nome_usuario' => $ganhador['nome_usuario'],
                        'nome_revendedor' => $ganhador['nome_revendedor'],
                        'valor_aposta' => $ganhador['valor'],
                        'tipo_jogo_id' => $ganhador['tipo_jogo_id'],
                        'acertos' => $ganhador['acertos'],
                        'premio' => $premio_individual
                    ];
                }
            }
        }
        
        // Confirmar transação
        $pdo->commit();
        
        // Limpar qualquer saída anterior e enviar resposta JSON
        ob_clean();
        
        // Retornar resposta de sucesso
        echo json_encode([
            'success' => true,
            'message' => 'Resultado salvo com sucesso!',
            'concurso_id' => $concurso_id,
            'ganhadores' => $ganhadores_processados,
            'debug' => [
                'numeros_selecionados' => $numeros_selecionados,
                'total_apostas_verificadas' => $verifica_result['total'] ?? 0,
                'tipos_de_jogos_encontrados' => $verifica_result['tipos_jogo'] ?? 'nenhum'
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback em caso de erro
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Erro ao salvar resultado: " . $e->getMessage());
        json_error('Erro ao salvar resultado: ' . $e->getMessage());
    } 
} catch (Exception $e) {
    error_log("Erro não tratado: " . $e->getMessage());
    json_error('Erro não tratado: ' . $e->getMessage());
}

// Encerrar e enviar a saída do buffer
ob_end_flush(); 