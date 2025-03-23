<?php
header('Content-Type: application/json');
require_once('../includes/conexao.php');
require_once('../includes/funcoes.php');

// Verifica o método da requisição
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));

// Função para verificar o token de autenticação
function verificarToken() {
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Token não fornecido']);
        exit;
    }
    
    global $pdo;
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE api_token = ? AND tipo = 'revendedor'");
    $stmt->execute([$token]);
    $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$revendedor) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Token inválido']);
        exit;
    }
    
    return $revendedor;
}

// Função de autenticação do revendedor
function autenticarRevendedor($email, $senha) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND tipo = 'revendedor' AND status = 1");
    $stmt->execute([$email]);
    $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($revendedor && password_verify($senha, $revendedor['senha'])) {
        // Gera token de acesso
        $token = bin2hex(random_bytes(32));
        
        // Salva o token no banco
        $stmt = $pdo->prepare("UPDATE usuarios SET api_token = ? WHERE id = ?");
        $stmt->execute([$token, $revendedor['id']]);
        
        return [
            'status' => 'success',
            'data' => [
                'token' => $token,
                'usuario' => [
                    'id' => $revendedor['id'],
                    'nome' => $revendedor['nome'],
                    'email' => $revendedor['email'],
                    'saldo' => $revendedor['saldo']
                ]
            ]
        ];
    }
    
    return ['status' => 'error', 'message' => 'Credenciais inválidas'];
}

// Rotas da API
$rota = $request[0] ?? '';
$subrota = $request[1] ?? '';
$id = $request[2] ?? null;
$acao = $request[3] ?? '';

switch($rota) {
    case 'auth':
        if ($subrota == 'login' && $method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(autenticarRevendedor($data['email'], $data['senha']));
        }
        break;
        
    case 'revendedor':
        $revendedor = verificarToken();
        
        switch($subrota) {
            case 'dashboard':
                if ($method == 'GET') {
                    // Buscar dados do dashboard
                    $stmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total_apostas,
                            SUM(valor) as valor_total,
                            SUM(valor * (comissao_percentual/100)) as total_comissoes
                        FROM apostas a
                        JOIN usuarios u ON u.id = a.usuario_id
                        WHERE a.revendedor_id = ?
                        AND DATE(a.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                    ");
                    $stmt->execute([$revendedor['id']]);
                    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['status' => 'success', 'data' => $dados]);
                }
                break;
                
            case 'apostas':
                if ($method == 'GET') {
                    // Listar apostas com filtros
                    $where = "WHERE a.revendedor_id = :revendedor_id";
                    $params = [':revendedor_id' => $revendedor['id']];
                    
                    if (!empty($_GET['data_inicio'])) {
                        $where .= " AND DATE(a.created_at) >= :data_inicio";
                        $params[':data_inicio'] = $_GET['data_inicio'];
                    }
                    
                    if (!empty($_GET['data_fim'])) {
                        $where .= " AND DATE(a.created_at) <= :data_fim";
                        $params[':data_fim'] = $_GET['data_fim'];
                    }
                    
                    if (!empty($_GET['cliente_id'])) {
                        $where .= " AND u.id = :cliente_id";
                        $params[':cliente_id'] = $_GET['cliente_id'];
                    }
                    
                    if (!empty($_GET['status'])) {
                        $where .= " AND a.status = :status";
                        $params[':status'] = $_GET['status'];
                    }
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            a.*,
                            u.nome as nome_apostador,
                            j.nome as nome_jogo
                        FROM apostas a
                        JOIN usuarios u ON a.usuario_id = u.id
                        JOIN jogos j ON a.jogo_id = j.id
                        $where
                        ORDER BY a.created_at DESC
                        LIMIT 50
                    ");
                    
                    $stmt->execute($params);
                    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['status' => 'success', 'data' => $apostas]);
                }
                else if ($method == 'POST') {
                    // Criar nova aposta
                    $data = json_decode(file_get_contents('php://input'), true);
                    
                    try {
                        $pdo->beginTransaction();
                        
                        // Verificar saldo
                        if ($revendedor['saldo'] < $data['valor']) {
                            throw new Exception('Saldo insuficiente');
                        }
                        
                        // Registrar aposta
                        $stmt = $pdo->prepare("
                            INSERT INTO apostas (
                                usuario_id,
                                revendedor_id,
                                jogo_id,
                                numeros,
                                valor,
                                status,
                                created_at
                            ) VALUES (?, ?, ?, ?, ?, 'aprovada', NOW())
                        ");
                        
                        $stmt->execute([
                            $data['cliente_id'],
                            $revendedor['id'],
                            $data['jogo_id'],
                            $data['numeros'],
                            $data['valor']
                        ]);
                        
                        // Atualizar saldo
                        $stmt = $pdo->prepare("
                            UPDATE usuarios 
                            SET saldo = saldo - ? 
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([$data['valor'], $revendedor['id']]);
                        
                        $pdo->commit();
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Aposta registrada com sucesso'
                        ]);
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        http_response_code(400);
                        echo json_encode([
                            'status' => 'error',
                            'message' => $e->getMessage()
                        ]);
                    }
                }
                else if ($method == 'PUT' && $id && $acao == 'cancelar') {
                    // Cancelar aposta
                    try {
                        $pdo->beginTransaction();
                        
                        // Verificar se a aposta existe e pertence ao revendedor
                        $stmt = $pdo->prepare("
                            SELECT * FROM apostas 
                            WHERE id = ? AND revendedor_id = ? AND status = 'ativa'
                        ");
                        $stmt->execute([$id, $revendedor['id']]);
                        $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$aposta) {
                            throw new Exception('Aposta não encontrada ou não pode ser cancelada');
                        }
                        
                        // Cancelar aposta
                        $stmt = $pdo->prepare("
                            UPDATE apostas 
                            SET status = 'cancelada' 
                            WHERE id = ?
                        ");
                        $stmt->execute([$id]);
                        
                        // Devolver valor ao saldo
                        $stmt = $pdo->prepare("
                            UPDATE usuarios 
                            SET saldo = saldo + ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$aposta['valor'], $revendedor['id']]);
                        
                        $pdo->commit();
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Aposta cancelada com sucesso'
                        ]);
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        http_response_code(400);
                        echo json_encode([
                            'status' => 'error',
                            'message' => $e->getMessage()
                        ]);
                    }
                }
                break;
                
            case 'clientes':
                if ($method == 'GET') {
                    // Listar clientes
                    $stmt = $pdo->prepare("
                        SELECT id, nome, email, telefone, created_at
                        FROM usuarios
                        WHERE revendedor_id = ?
                        AND tipo = 'apostador'
                        ORDER BY nome
                    ");
                    
                    $stmt->execute([$revendedor['id']]);
                    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['status' => 'success', 'data' => $clientes]);
                }
                break;
                
            case 'jogos':
                if ($method == 'GET') {
                    // Listar jogos disponíveis
                    $stmt = $pdo->prepare("
                        SELECT 
                            j.*,
                            (
                                SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        'dezenas', vj.dezenas,
                                        'valor_aposta', vj.valor_aposta,
                                        'valor_premio', vj.valor_premio
                                    )
                                )
                                FROM valores_jogos vj
                                WHERE vj.jogo_id = j.id
                            ) as opcoes_apostas
                        FROM jogos j
                        WHERE j.status = 1
                        ORDER BY j.nome
                    ");
                    
                    $stmt->execute();
                    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Converter string JSON para array nas opções de apostas
                    foreach ($jogos as &$jogo) {
                        $jogo['opcoes_apostas'] = json_decode($jogo['opcoes_apostas'], true);
                    }
                    
                    echo json_encode(['status' => 'success', 'data' => $jogos]);
                }
                break;
                
            case 'financeiro':
                if ($method == 'GET') {
                    // Buscar dados financeiros
                    $stmt = $pdo->prepare("
                        SELECT 
                            u.saldo,
                            u.comissao_percentual,
                            (
                                SELECT SUM(valor * (comissao_percentual/100))
                                FROM apostas a2
                                WHERE a2.revendedor_id = u.id
                                AND DATE(a2.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                            ) as comissoes_mes,
                            (
                                SELECT COUNT(*)
                                FROM apostas a3
                                WHERE a3.revendedor_id = u.id
                                AND DATE(a3.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                            ) as apostas_mes
                        FROM usuarios u
                        WHERE u.id = ?
                    ");
                    
                    $stmt->execute([$revendedor['id']]);
                    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['status' => 'success', 'data' => $dados]);
                }
                break;
                
            case 'saques':
                if ($method == 'POST') {
                    // Solicitar saque
                    $data = json_decode(file_get_contents('php://input'), true);
                    
                    try {
                        $pdo->beginTransaction();
                        
                        // Verificar saldo
                        if ($revendedor['saldo'] < $data['valor']) {
                            throw new Exception('Saldo insuficiente para saque');
                        }
                        
                        // Registrar solicitação
                        $stmt = $pdo->prepare("
                            INSERT INTO saques (
                                usuario_id,
                                valor,
                                status,
                                created_at
                            ) VALUES (?, ?, 'pendente', NOW())
                        ");
                        
                        $stmt->execute([$revendedor['id'], $data['valor']]);
                        
                        // Bloquear valor no saldo
                        $stmt = $pdo->prepare("
                            UPDATE usuarios 
                            SET saldo = saldo - ? 
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([$data['valor'], $revendedor['id']]);
                        
                        $pdo->commit();
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Solicitação de saque registrada com sucesso'
                        ]);
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        http_response_code(400);
                        echo json_encode([
                            'status' => 'error',
                            'message' => $e->getMessage()
                        ]);
                    }
                }
                break;
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Rota não encontrada']);
        break;
} 