<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO jogos (nome, dezenas, max_numeros, valor, premio, status) VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $_POST['nome'],
        $_POST['dezenas'],
        $_POST['max_numeros'],
        $_POST['valor'],
        $_POST['premio'],
        $_POST['status']
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 