<?php
// Configurações iniciais
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Função para verificar se uma tabela existe
function verificarTabelaExiste($pdo, $tabela) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Função para verificar se uma coluna existe em uma tabela
function verificarColunaExiste($pdo, $tabela, $coluna) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tabela` LIKE '$coluna'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Inicializar resultado
$resultado = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'tabela_numeros_sorteados' => [],
    'concursos' => [],
    'estatisticas' => []
];

try {
    if (!$pdo) {
        throw new Exception("Conexão com o banco falhou");
    }
    
    // Verificar se a tabela numeros_sorteados existe
    $tabela_existe = verificarTabelaExiste($pdo, 'numeros_sorteados');
    $resultado['tabela_numeros_sorteados']['existe'] = $tabela_existe;
    
    if ($tabela_existe) {
        // Verificar estrutura da tabela
        $stmt = $pdo->query("SHOW COLUMNS FROM `numeros_sorteados`");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado['tabela_numeros_sorteados']['colunas'] = $colunas;
        
        // Verificar contagem de registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM `numeros_sorteados`");
        $resultado['tabela_numeros_sorteados']['total_registros'] = (int)$stmt->fetchColumn();
        
        // Verificar se tabela está vazia
        $resultado['tabela_numeros_sorteados']['vazia'] = 
            $resultado['tabela_numeros_sorteados']['total_registros'] === 0;
        
        // Verificar integridade referencial com concursos
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM `numeros_sorteados` ns
            LEFT JOIN `concursos` c ON ns.concurso_id = c.id
            WHERE c.id IS NULL
        ");
        $resultado['tabela_numeros_sorteados']['orfaos'] = (int)$stmt->fetchColumn();
        
        // Obter distribuição por concurso_id
        $stmt = $pdo->query("
            SELECT concurso_id, COUNT(*) as total 
            FROM `numeros_sorteados` 
            GROUP BY concurso_id 
            ORDER BY concurso_id DESC
            LIMIT 10
        ");
        $resultado['tabela_numeros_sorteados']['distribuicao'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Verificar números duplicados
        $stmt = $pdo->query("
            SELECT concurso_id, numero, COUNT(*) as contagem
            FROM `numeros_sorteados`
            GROUP BY concurso_id, numero
            HAVING COUNT(*) > 1
            LIMIT 10
        ");
        $resultado['tabela_numeros_sorteados']['duplicados'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Concursos recentes e seus números
        $stmt = $pdo->query("
            SELECT c.id, c.jogo_id, c.codigo, c.data_sorteio, j.nome as jogo_nome
            FROM concursos c
            JOIN jogos j ON c.jogo_id = j.id
            ORDER BY c.id DESC
            LIMIT 5
        ");
        $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($concursos as &$concurso) {
            $stmt = $pdo->query("
                SELECT numero
                FROM numeros_sorteados
                WHERE concurso_id = {$concurso['id']}
                ORDER BY numero ASC
            ");
            $numeros = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $concurso['numeros'] = $numeros;
            $concurso['total_numeros'] = count($numeros);
        }
        
        $resultado['concursos'] = $concursos;
        
        // Estatísticas de números mais sorteados
        $stmt = $pdo->query("
            SELECT numero, COUNT(*) as frequencia
            FROM numeros_sorteados
            GROUP BY numero
            ORDER BY frequencia DESC
            LIMIT 10
        ");
        $resultado['estatisticas']['mais_sorteados'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $resultado['status'] = 'error';
    $resultado['mensagem'] = $e->getMessage();
    $resultado['trace'] = $e->getTraceAsString();
}

// Retornar resultado em JSON
echo json_encode($resultado, JSON_PRETTY_PRINT);
?> 