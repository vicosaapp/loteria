<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Rota para listar jogos disponíveis
$app->get('/api/revendedor/jogos', function($request, $response) {
    $usuario = $request->getAttribute('usuario');
    
    if ($usuario['tipo'] !== 'revendedor') {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Acesso não autorizado'
        ], 403);
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->query("
            SELECT 
                id, 
                nome, 
                minimo_numeros,
                maximo_numeros, 
                numeros_disponiveis,
                dezenas,
                valor, 
                premio,
                status
            FROM jogos 
            WHERE status = 1 
            ORDER BY nome
        ");
        
        $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar valores específicos para cada jogo
        foreach ($jogos as &$jogo) {
            $stmt = $conn->prepare("
                SELECT dezenas, valor_aposta, valor_premio
                FROM valores_jogos
                WHERE jogo_id = ?
            ");
            $stmt->execute([$jogo['id']]);
            $jogo['valores'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $response->withJson([
            'status' => 'success',
            'data' => $jogos
        ]);
        
    } catch (Exception $e) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Erro ao buscar jogos'
        ], 500);
    }
});

// Rota para listar clientes do revendedor
$app->get('/api/revendedor/clientes', function($request, $response) {
    $usuario = $request->getAttribute('usuario');
    
    if ($usuario['tipo'] !== 'revendedor') {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Acesso não autorizado'
        ], 403);
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                id, 
                nome,
                email,
                telefone,
                created_at as data_cadastro
            FROM usuarios 
            WHERE revendedor_id = ? 
            AND tipo = 'apostador'
            ORDER BY nome
        ");
        
        $stmt->execute([$usuario['id']]);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $response->withJson([
            'status' => 'success',
            'data' => $clientes
        ]);
        
    } catch (Exception $e) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Erro ao buscar clientes'
        ], 500);
    }
});

// Rota para buscar apostas com filtros
$app->get('/api/revendedor/apostas', function($request, $response) {
    $usuario = $request->getAttribute('usuario');
    
    if ($usuario['tipo'] !== 'revendedor') {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Acesso não autorizado'
        ], 403);
    }
    
    $queryParams = $request->getQueryParams();
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $where = "WHERE u.revendedor_id = :revendedor_id";
        $params = [':revendedor_id' => $usuario['id']];
        
        if (!empty($queryParams['data_inicio'])) {
            $where .= " AND DATE(a.created_at) >= :data_inicio";
            $params[':data_inicio'] = $queryParams['data_inicio'];
        }
        
        if (!empty($queryParams['data_fim'])) {
            $where .= " AND DATE(a.created_at) <= :data_fim";
            $params[':data_fim'] = $queryParams['data_fim'];
        }
        
        if (!empty($queryParams['cliente_id'])) {
            $where .= " AND u.id = :cliente_id";
            $params[':cliente_id'] = $queryParams['cliente_id'];
        }
        
        if (!empty($queryParams['status'])) {
            $where .= " AND a.status = :status";
            $params[':status'] = $queryParams['status'];
        }
        
        $stmt = $conn->prepare("
            SELECT 
                a.*,
                u.nome as nome_apostador,
                j.nome as nome_jogo,
                j.valor as valor_minimo,
                j.premio as premio_maximo
            FROM apostas a
            JOIN usuarios u ON a.usuario_id = u.id
            JOIN jogos j ON a.tipo_jogo_id = j.id
            $where
            ORDER BY a.created_at DESC
            LIMIT 50
        ");
        
        $stmt->execute($params);
        $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $response->withJson([
            'status' => 'success',
            'data' => $apostas
        ]);
        
    } catch (Exception $e) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Erro ao buscar apostas'
        ], 500);
    }
}); 