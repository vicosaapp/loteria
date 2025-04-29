<?php
// Configurações iniciais
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

// Verificar autorização
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
    exit;
}

// Verificar conexão com o banco de dados
if (!isset($pdo)) {
    echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados']);
    exit;
}

// Diagnóstico rápido do banco de dados
try {
    $resultado = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'tabelas' => [],
        'contagens' => [],
        'recomendacoes' => []
    ];
    
    // Lista de tabelas essenciais
    $tabelas_essenciais = ['jogos', 'concursos', 'numeros_sorteados', 'apostas', 'usuarios', 'valores_jogos'];
    
    // Verificar existência das tabelas
    foreach ($tabelas_essenciais as $tabela) {
        $existe = $pdo->query("SHOW TABLES LIKE '$tabela'")->rowCount() > 0;
        $resultado['tabelas'][$tabela] = $existe;
        
        if (!$existe) {
            $resultado['recomendacoes'][] = "Criar tabela '$tabela'";
        }
    }
    
    // Contagens rápidas
    $consultas = [
        'jogos' => "SELECT COUNT(*) FROM jogos",
        'concursos' => "SELECT COUNT(*) FROM concursos",
        'apostas' => "SELECT COUNT(*) FROM apostas",
        'apostas_processadas' => "SELECT COUNT(*) FROM apostas WHERE processado = 1",
        'apostas_pendentes' => "SELECT COUNT(*) FROM apostas WHERE processado = 0 OR processado IS NULL",
        'apostas_premiadas' => "SELECT COUNT(*) FROM apostas WHERE valor_premio > 0",
        'concursos_abertos' => "SELECT COUNT(*) FROM concursos WHERE status = 'aberto'",
        'concursos_finalizados' => "SELECT COUNT(*) FROM concursos WHERE status = 'finalizado'"
    ];
    
    foreach ($consultas as $chave => $sql) {
        if ($resultado['tabelas'][explode('_', $chave)[0]] ?? false) {
            try {
                $resultado['contagens'][$chave] = $pdo->query($sql)->fetchColumn();
            } catch (PDOException $e) {
                $resultado['contagens'][$chave] = "Erro: " . substr($e->getMessage(), 0, 100);
            }
        }
    }
    
    // Verificar último concurso
    if ($resultado['tabelas']['concursos']) {
        try {
            $stmt = $pdo->query("SELECT c.id, c.codigo, j.nome FROM concursos c 
                                JOIN jogos j ON c.jogo_id = j.id 
                                ORDER BY c.id DESC LIMIT 1");
            $resultado['ultimo_concurso'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar números sorteados
            if ($resultado['tabelas']['numeros_sorteados'] && isset($resultado['ultimo_concurso']['id'])) {
                $concurso_id = $resultado['ultimo_concurso']['id'];
                $stmt = $pdo->query("SELECT COUNT(*) FROM numeros_sorteados WHERE concurso_id = $concurso_id");
                $resultado['ultimo_concurso']['total_numeros'] = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            $resultado['ultimo_concurso'] = "Erro: " . substr($e->getMessage(), 0, 100);
        }
    }
    
    // Verificação básica de ambiente
    $resultado['ambiente'] = [
        'php_versao' => phpversion(),
        'memoria_limite' => ini_get('memory_limit'),
        'max_tempo_execucao' => ini_get('max_execution_time') . 's',
        'timezone' => date_default_timezone_get()
    ];
    
    // Saída do diagnóstico
    echo json_encode($resultado, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao executar diagnóstico: ' . $e->getMessage()
    ]);
} 