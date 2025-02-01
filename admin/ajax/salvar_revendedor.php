<?php
require_once '../../config/database.php';
session_start();

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    $comissao = $_POST['comissao'];
    
    if ($id) { // Edição
        $sql = "UPDATE usuarios SET nome = ?, email = ?, whatsapp = ?, comissao = ?";
        $params = [$nome, $email, $whatsapp, $comissao];
        
        if (!empty($_POST['senha'])) {
            $sql .= ", senha = ?";
            $params[] = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ? AND tipo = 'revendedor'";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
    } else { // Novo
        if (empty($_POST['senha'])) {
            throw new Exception('Senha é obrigatória para novo revendedor');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, whatsapp, tipo, comissao) 
            VALUES (?, ?, ?, ?, 'revendedor', ?)
        ");
        
        $stmt->execute([
            $nome,
            $email,
            password_hash($_POST['senha'], PASSWORD_DEFAULT),
            $whatsapp,
            $comissao
        ]);
    }
    
    echo json_encode(['success' => true]);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 