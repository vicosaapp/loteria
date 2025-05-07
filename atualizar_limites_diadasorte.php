<?php
// Conectar ao banco de dados
require_once 'config/database.php';

try {
    // Atualizar os limites do jogo Dia de Sorte
    $stmt = $pdo->prepare("
        UPDATE jogos 
        SET minimo_numeros = 15, 
            maximo_numeros = 22 
        WHERE nome LIKE '%DIA DE SORTE%' 
           OR nome LIKE '%Dia de Sorte%'
    ");
    
    $result = $stmt->execute();
    
    if ($result) {
        echo "Limites do jogo Dia de Sorte atualizados com sucesso!<br>";
        echo "- Mínimo de números: 15<br>";
        echo "- Máximo de números: 22<br>";
    } else {
        echo "Erro ao atualizar os limites do jogo Dia de Sorte.";
    }
    
    // Verificar configuração atual
    $stmt = $pdo->prepare("
        SELECT id, nome, minimo_numeros, maximo_numeros
        FROM jogos
        WHERE nome LIKE '%DIA DE SORTE%' 
           OR nome LIKE '%Dia de Sorte%'
    ");
    
    $stmt->execute();
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($jogos) > 0) {
        echo "<h3>Configuração atual dos jogos:</h3>";
        echo "<ul>";
        foreach ($jogos as $jogo) {
            echo "<li>ID: {$jogo['id']} - Nome: {$jogo['nome']} - Min: {$jogo['minimo_numeros']} - Max: {$jogo['maximo_numeros']}</li>";
        }
        echo "</ul>";
    } else {
        echo "Nenhum jogo Dia de Sorte encontrado no banco de dados.";
    }
    
} catch (PDOException $e) {
    echo "Erro no banco de dados: " . $e->getMessage();
}
?> 