<?php
/**
 * Endpoint para atualizar configurações de manutenção
 * Este arquivo permite ativar/desativar o modo de manutenção e atualizar a mensagem
 */

// Verificar se a requisição é AJAX
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Iniciar sessão
session_start();

// Verificar se o usuário é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Incluir configuração do banco de dados
require_once '../config/database.php';

// Verificar se os parâmetros foram enviados
if (!isset($_POST['acao'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Registrar a ação nos logs
    $acao = $_POST['acao'];
    $admin_id = $_SESSION['usuario_id'];
    $admin_nome = $_SESSION['nome'] ?? 'Admin desconhecido';
    $data_acao = date('Y-m-d H:i:s');
    
    // Obter o status atual para log
    $stmt = $pdo->query("SELECT modo_manutencao FROM configuracoes WHERE id = 1");
    $config_atual = $stmt->fetch(PDO::FETCH_ASSOC);
    $status_anterior = $config_atual['modo_manutencao'] ?? 0;
    
    switch ($acao) {
        case 'ativar':
            // Ativar modo manutenção
            $stmt = $pdo->prepare("UPDATE configuracoes SET modo_manutencao = 1 WHERE id = 1");
            $stmt->execute();
            
            // Log da ação
            $pdo->prepare("INSERT INTO logs_admin (admin_id, acao, descricao, data) VALUES (?, ?, ?, ?)")
                ->execute([$admin_id, 'manutencao_ativada', "Modo de manutenção ativado por {$admin_nome}", $data_acao]);
                
            echo json_encode(['success' => true, 'message' => 'Modo de manutenção ativado com sucesso']);
            break;
            
        case 'desativar':
            // Desativar modo manutenção
            $stmt = $pdo->prepare("UPDATE configuracoes SET modo_manutencao = 0 WHERE id = 1");
            $stmt->execute();
            
            // Log da ação
            $pdo->prepare("INSERT INTO logs_admin (admin_id, acao, descricao, data) VALUES (?, ?, ?, ?)")
                ->execute([$admin_id, 'manutencao_desativada', "Modo de manutenção desativado por {$admin_nome}", $data_acao]);
                
            echo json_encode(['success' => true, 'message' => 'Modo de manutenção desativado com sucesso']);
            break;
            
        case 'atualizar_mensagem':
            // Verificar se a mensagem foi enviada
            if (!isset($_POST['mensagem'])) {
                echo json_encode(['success' => false, 'message' => 'Mensagem não informada']);
                exit;
            }
            
            // Atualizar mensagem de manutenção
            $mensagem = trim($_POST['mensagem']);
            $stmt = $pdo->prepare("UPDATE configuracoes SET mensagem_manutencao = ? WHERE id = 1");
            $stmt->execute([$mensagem]);
            
            // Log da ação
            $pdo->prepare("INSERT INTO logs_admin (admin_id, acao, descricao, data) VALUES (?, ?, ?, ?)")
                ->execute([$admin_id, 'mensagem_manutencao_atualizada', "Mensagem de manutenção atualizada por {$admin_nome}", $data_acao]);
                
            echo json_encode(['success' => true, 'message' => 'Mensagem de manutenção atualizada com sucesso']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar configurações: ' . $e->getMessage()]);
}
?> 