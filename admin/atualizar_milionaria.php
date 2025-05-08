<?php
require_once '../config/database.php';

try {
    $stmt = $pdo->prepare("UPDATE jogos SET numeros_disponiveis = 50, total_numeros = 50 WHERE nome LIKE '%Milionária%'");
    $stmt->execute();
    
    echo "Atualização realizada com sucesso!";
} catch (PDOException $e) {
    echo "Erro ao atualizar: " . $e->getMessage();
} 