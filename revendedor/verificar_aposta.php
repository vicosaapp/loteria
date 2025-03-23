<?php
// Configurações de exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir arquivo de conexão com o banco de dados
require_once '../config/database.php';

// Título da página
$pageTitle = 'Verificar Aposta';

// Verificar se foi enviado o ID da aposta
$aposta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$resultados = [];

if ($aposta_id > 0) {
    try {
        // Buscar dados da aposta
        $sql = "SELECT a.*, u.nome as nome_usuario, j.nome as nome_jogo
                FROM apostas a
                JOIN usuarios u ON a.usuario_id = u.id
                JOIN jogos j ON a.tipo_jogo_id = j.id
                WHERE a.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$aposta_id]);
        $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$aposta) {
            $resultados[] = "Aposta ID $aposta_id não encontrada.";
        } else {
            $resultados[] = "Aposta ID: " . $aposta['id'];
            $resultados[] = "Usuário: " . $aposta['nome_usuario'];
            $resultados[] = "Jogo: " . $aposta['nome_jogo'];
            $resultados[] = "Números: " . $aposta['numeros'];
            $resultados[] = "Status: " . $aposta['status'];
            $resultados[] = "Concurso: " . ($aposta['concurso'] ?: "Não definido");
            $resultados[] = "Processado: " . ($aposta['processado'] ? "Sim" : "Não");
            $resultados[] = "Valor do Prêmio: R$ " . number_format(($aposta['valor_premio'] ?: 0), 2, ',', '.');
            
            // Buscar últimos concursos do jogo
            $sql = "SELECT c.id, c.codigo, c.data_sorteio, c.status, 
                    GROUP_CONCAT(ns.numero ORDER BY ns.numero ASC) as numeros_sorteados
                    FROM concursos c
                    LEFT JOIN numeros_sorteados ns ON ns.concurso_id = c.id
                    WHERE c.jogo_id = ?
                    GROUP BY c.id
                    ORDER BY c.data_sorteio DESC
                    LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$aposta['tipo_jogo_id']]);
            $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($concursos)) {
                $resultados[] = "Nenhum concurso encontrado para este jogo.";
            } else {
                $resultados[] = "\nÚltimos concursos:";
                foreach ($concursos as $concurso) {
                    $resultados[] = "Concurso ID: " . $concurso['id'] . " - Código: " . $concurso['codigo'] . 
                                   " - Data: " . $concurso['data_sorteio'] . " - Status: " . $concurso['status'];
                    
                    if (!empty($concurso['numeros_sorteados'])) {
                        $numeros_sorteados = explode(',', $concurso['numeros_sorteados']);
                        $numeros_apostados = explode(',', $aposta['numeros']);
                        
                        // Limpar e converter para inteiros
                        $numeros_sorteados = array_map('intval', $numeros_sorteados);
                        $numeros_apostados = array_map('intval', array_map('trim', $numeros_apostados));
                        
                        $resultados[] = "Números sorteados: " . implode(', ', $numeros_sorteados);
                        $resultados[] = "Números apostados: " . implode(', ', $numeros_apostados);
                        
                        $acertos = array_intersect($numeros_apostados, $numeros_sorteados);
                        $resultados[] = "Acertos neste concurso: " . count($acertos) . " - " . implode(', ', $acertos);
                        
                        // Verificar valor do prêmio para este número de acertos
                        $sql = "SELECT valor_premio FROM valores_jogos WHERE jogo_id = ? AND dezenas = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$aposta['tipo_jogo_id'], count($acertos)]);
                        $premio = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($premio) {
                            $resultados[] = "Valor do prêmio para " . count($acertos) . " acertos: R$ " . 
                                           number_format($premio['valor_premio'], 2, ',', '.');
                        } else {
                            $resultados[] = "Não há prêmio configurado para " . count($acertos) . " acertos.";
                        }
                    } else {
                        $resultados[] = "Nenhum número sorteado para este concurso.";
                    }
                    
                    $resultados[] = "------------------------";
                }
                
                // Botão para processar manualmente a aposta
                $html_botao = "<div style='margin-top: 20px;'>
                                <form method='post' action='ajax/processar_ganhador_manual.php'>
                                    <input type='hidden' name='aposta_id' value='{$aposta['id']}'>
                                    <input type='hidden' name='jogo_id' value='{$aposta['tipo_jogo_id']}'>
                                    <select name='concurso' class='form-select'>
                                        <option value=''>Selecione o concurso</option>";
                                        
                foreach ($concursos as $concurso) {
                    $html_botao .= "<option value='{$concurso['codigo']}'>{$concurso['codigo']} - " . date('d/m/Y', strtotime($concurso['data_sorteio'])) . "</option>";
                }
                
                $html_botao .= "</select>
                                <button type='submit' class='btn btn-primary mt-2'>Processar Aposta</button>
                               </form>
                              </div>";
            }
        }
    } catch (Exception $e) {
        $resultados[] = "Erro: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Aposta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Verificar Aposta</h1>
        
        <form method="get" class="mb-4">
            <div class="input-group">
                <input type="number" name="id" class="form-control" placeholder="ID da Aposta" value="<?php echo $aposta_id; ?>" required>
                <button type="submit" class="btn btn-primary">Verificar</button>
            </div>
        </form>
        
        <?php if (!empty($resultados)): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Resultados da Verificação</h2>
                </div>
                <div class="card-body">
                    <pre><?php echo implode("\n", $resultados); ?></pre>
                    
                    <?php if (isset($html_botao)) echo $html_botao; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="resultados.php" class="btn btn-secondary">Voltar para Resultados</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 