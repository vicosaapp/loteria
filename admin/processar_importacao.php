<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido');
    }

    if (empty($_POST['apostas']) || empty($_POST['valor_aposta'])) {
        throw new Exception('Dados incompletos');
    }

    $apostas = trim($_POST['apostas']);
    $valor_aposta = floatval($_POST['valor_aposta']);
    $valor_premiacao = floatval(str_replace(',', '.', str_replace('R$ ', '', $_POST['valor_premiacao'])));

    // Divide as apostas em linhas
    $linhas = explode("\n", $apostas);
    $jogo_nome = trim($linhas[0]); // Primeira linha é o nome do jogo

    // Remove a primeira linha (nome do jogo)
    array_shift($linhas);

    // Inicia uma transação
    $pdo->beginTransaction();

    // Insere cada aposta
    foreach ($linhas as $linha) {
        $numeros = trim($linha);
        if (empty($numeros)) continue;

        $stmt = $pdo->prepare("
            INSERT INTO apostas_importadas 
            (usuario_id, revendedor_id, jogo_nome, numeros, valor_aposta, valor_premio_calculado) 
            VALUES 
            (:usuario_id, :revendedor_id, :jogo_nome, :numeros, :valor_aposta, :valor_premio)
        ");

        $stmt->execute([
            'usuario_id' => $_SESSION['usuario_id'], // Certifique-se de ter a sessão iniciada
            'revendedor_id' => $_SESSION['usuario_id'], // Ajuste conforme necessário
            'jogo_nome' => $jogo_nome,
            'numeros' => $numeros,
            'valor_aposta' => $valor_aposta,
            'valor_premio' => $valor_premiacao / count($linhas) // Divide o prêmio total pelo número de apostas
        ]);
    }

    // Confirma a transação
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'usuario_id' => $_SESSION['usuario_id'],
        'jogo_nome' => $jogo_nome
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 