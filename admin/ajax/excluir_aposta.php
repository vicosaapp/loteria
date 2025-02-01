<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $id = $_GET['id'];
        $tipo = $_GET['tipo'];
        
        $tabela = ($tipo === 'normal') ? 'apostas' : 'apostas_importadas';
        
        $stmt = $pdo->prepare("DELETE FROM {$tabela} WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} 