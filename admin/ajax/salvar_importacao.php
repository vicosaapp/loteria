<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Buscar jogo pelo nome
    $stmt = $pdo->prepare("SELECT id FROM jogos WHERE nome LIKE ? AND status = 'ativo'");
    $stmt->execute(['%' . $_POST['jogo'] . '%']);
    $jogo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$jogo) {
        throw new Exception('Jogo não encontrado');
    }

    // Criar ou buscar usuário
    $stmt = $pdo->prepare("
        SELECT id FROM usuarios 
        WHERE (nome = ? OR whatsapp = ?) 
        AND tipo = 'usuario'
        LIMIT 1
    ");
    $stmt->execute([$_POST['nome_apostador'], $_POST['whatsapp_apostador']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        // Criar novo usuário
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, whatsapp, tipo, revendedor_id)
            VALUES (?, ?, 'usuario', ?)
        ");
        $stmt->execute([
            $_POST['nome_apostador'],
            $_POST['whatsapp_apostador'],
            $_POST['revendedor_id'] ?: null
        ]);
        $usuario_id = $pdo->lastInsertId();
    } else {
        $usuario_id = $usuario['id'];
    }

    // Inserir apostas
    $apostas = json_decode($_POST['apostas'], true);
    $valor_aposta = floatval($_POST['valor_aposta']);

    foreach ($apostas as $numeros) {
        $stmt = $pdo->prepare("
            INSERT INTO apostas (
                usuario_id,
                tipo_jogo_id,
                numeros,
                valor_aposta,
                status
            ) VALUES (?, ?, ?, ?, 'aprovada')
        ");

        $stmt->execute([
            $usuario_id,
            $jogo['id'],
            implode(',', $numeros),
            $valor_aposta
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 