<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Validar dados recebidos
    if (!isset($_POST['apostador']) || !isset($_POST['apostas']) || !isset($_POST['valor_aposta'])) {
        throw new Exception('Dados incompletos');
    }

    $pdo->beginTransaction();

    // Processar as apostas
    $linhas = explode("\n", trim($_POST['apostas']));
    $jogo_nome = trim($linhas[0]);
    array_shift($linhas); // Remove a primeira linha (nome do jogo)

    foreach ($linhas as $linha) {
        if (empty(trim($linha))) continue;

        $sql = "INSERT INTO apostas_importadas (
                    usuario_id, 
                    revendedor_id,
                    jogo_nome,
                    numeros,
                    valor_aposta,
                    valor_premio,
                    whatsapp
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['apostador'],
            $_POST['revendedor'] ?: null,
            $jogo_nome,
            trim($linha),
            floatval($_POST['valor_aposta']),
            floatval($_POST['valor_premio']),
            $_POST['whatsapp']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Apostas salvas com sucesso'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro ao salvar apostas: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao salvar apostas: ' . $e->getMessage()
    ]);
} 