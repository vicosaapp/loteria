<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    
    // Verificar email duplicado
    $stmt = $pdo->prepare("
        SELECT id FROM usuarios 
        WHERE email = ? AND id != ? AND tipo = 'usuario'
    ");
    $stmt->execute([$email, $id ?? 0]);
    if ($stmt->fetch()) {
        throw new Exception('Este email já está cadastrado');
    }
    
    if ($id) { // Edição
        $sql = "UPDATE usuarios SET nome = ?, email = ?, whatsapp = ?";
        $params = [$nome, $email, $whatsapp];
        
        if (!empty($_POST['senha'])) {
            $sql .= ", senha = ?";
            $params[] = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ? AND revendedor_id = ? AND tipo = 'usuario'";
        $params[] = $id;
        $params[] = $_SESSION['usuario_id'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
    } else { // Novo
        if (empty($_POST['senha'])) {
            throw new Exception('Senha é obrigatória para novo cliente');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, whatsapp, tipo, revendedor_id) 
            VALUES (?, ?, ?, ?, 'usuario', ?)
        ");
        
        $stmt->execute([
            $nome,
            $email,
            password_hash($_POST['senha'], PASSWORD_DEFAULT),
            $whatsapp,
            $_SESSION['usuario_id']
        ]);
    }
    
    echo json_encode(['success' => true]);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 