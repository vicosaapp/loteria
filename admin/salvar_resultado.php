<?php
require_once '../config/database.php';
session_start();

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar e processar os dados
        $jogo = trim($_POST['jogo']);
        $data_sorteio = $_POST['data_sorteio'];
        $numeros = trim($_POST['numeros']);

        // Validações básicas
        if (empty($jogo) || empty($data_sorteio) || empty($numeros)) {
            throw new Exception("Todos os campos são obrigatórios.");
        }

        // Validar formato dos números
        $numeros_array = explode(' ', $numeros);
        foreach ($numeros_array as $numero) {
            if (!is_numeric($numero) || strlen($numero) !== 2) {
                throw new Exception("Os números devem estar no formato correto (ex: 01 02 03).");
            }
        }

        // Inserir no banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO resultados (jogo, data_sorteio, numeros, processado, created_at)
            VALUES (?, ?, ?, 0, NOW())
        ");
        
        $stmt->execute([$jogo, $data_sorteio, $numeros]);
        
        $_SESSION['sucesso'] = "Resultado cadastrado com sucesso!";
    } catch (Exception $e) {
        $_SESSION['erro'] = "Erro ao salvar resultado: " . $e->getMessage();
    }
}

header('Location: gerenciar_resultados.php');
exit; 