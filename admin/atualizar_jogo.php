<?php

try {
    $stmt = $pdo->prepare("
        UPDATE jogos 
        SET nome = ?,
            identificador_importacao = ?,
            total_numeros = ?,
            dezenas = ?,
            dezenas_premiar = ?,
            valor = ?,
            premio = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['nome'],
        $_POST['identificador_importacao'],
        $_POST['total_numeros'],
        $_POST['dezenas'],
        $_POST['dezenas_premiar'],
        $_POST['valor'],
        $_POST['premio'],
        $_POST['id']
    ]);

    // ... resto do código existente ...
} catch (PDOException $e) {
    echo "Erro ao atualizar o jogo: " . $e->getMessage();
} 