<?php
require_once '../config/database.php';

function formatarValor($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$mensagem = '';
$tipo_mensagem = '';
$resultados_banco = [];

try {
    // Buscar resultados do banco
    $sql = "
        SELECT 
            j.id,
            j.nome,
            j.identificador_api,
            j.numero_concurso,
            c.codigo as numero_concurso_atual,
            c.data_sorteio,
            GROUP_CONCAT(DISTINCT ns.numero ORDER BY ns.numero ASC) as dezenas,
            j.valor_acumulado,
            j.data_proximo_concurso,
            j.valor_estimado_proximo,
            c.id as concurso_id,
            (
                SELECT COUNT(*)
                FROM apostas_importadas ai
                WHERE ai.jogo_nome = j.nome
                AND ai.numeros = GROUP_CONCAT(DISTINCT ns.numero ORDER BY ns.numero ASC)
            ) as total_ganhadores,
            (
                SELECT GROUP_CONCAT(DISTINCT CONCAT(u.nome, ':', ai.valor_premio) SEPARATOR '|')
                FROM apostas_importadas ai
                JOIN usuarios u ON ai.revendedor_id = u.id
                WHERE ai.jogo_nome = j.nome
                AND ai.numeros = GROUP_CONCAT(DISTINCT ns.numero ORDER BY ns.numero ASC)
            ) as ganhadores_info
        FROM jogos j
        LEFT JOIN (
            SELECT c1.*
            FROM concursos c1
            INNER JOIN (
                SELECT jogo_id, MAX(data_sorteio) as ultima_data
                FROM concursos
                WHERE status = 'finalizado'
                GROUP BY jogo_id
            ) c2 ON c1.jogo_id = c2.jogo_id AND c1.data_sorteio = c2.ultima_data
        ) c ON j.id = c.jogo_id
        LEFT JOIN numeros_sorteados ns ON ns.concurso_id = c.id
        WHERE j.status = 1 AND j.identificador_api IS NOT NULL
        GROUP BY 
            j.id, j.nome, j.identificador_api, j.numero_concurso,
            c.codigo, c.data_sorteio, j.valor_acumulado,
            j.data_proximo_concurso, j.valor_estimado_proximo,
            c.id
        ORDER BY j.nome ASC
    ";
    
    $stmt = $pdo->query($sql);
    if ($stmt) {
        $resultados_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar resultados: " . $e->getMessage();
    $tipo_mensagem = "danger";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados das Loterias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid py-4">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-trophy"></i> Resultados das Loterias</h1>
            <p>Confira os últimos resultados das Loterias</p>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="results-grid">
        <?php if (empty($resultados_banco)): ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Nenhum resultado disponível no momento.
            </div>
        <?php else: ?>
            <?php foreach ($resultados_banco as $resultado): ?>
                <div class="result-card <?php echo htmlspecialchars($resultado['identificador_api'] ?? ''); ?>">
                    <div class="card-header">
                        <div class="header-content">
                            <h3><?php echo htmlspecialchars($resultado['nome'] ?? ''); ?></h3>
                            <?php if (!empty($resultado['numero_concurso'])): ?>
                                <span class="concurso">Concurso <?php echo htmlspecialchars($resultado['numero_concurso']); ?></span>
                                <span class="data"><?php echo !empty($resultado['data_sorteio']) ? date('d/m/Y', strtotime($resultado['data_sorteio'])) : ''; ?></span>
                            <?php else: ?>
                                <span class="concurso">Aguardando resultados</span>
                            <?php endif; ?>
                        </div>
                        <div class="logo-container">
                            <?php if (!empty($resultado['identificador_api'])): ?>
                                <img src="../assets/images/logos/<?php echo htmlspecialchars($resultado['identificador_api']); ?>.png" 
                                     alt="<?php echo htmlspecialchars($resultado['nome']); ?>"
                                     class="jogo-logo"
                                     onerror="this.src='../assets/images/logos/default.png'">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($resultado['dezenas'])): ?>
                            <div class="numbers-grid">
                                <?php 
                                $dezenas = explode(',', $resultado['dezenas']);
                                foreach ($dezenas as $dezena): 
                                ?>
                                    <div class="number"><?php echo str_pad($dezena, 2, '0', STR_PAD_LEFT); ?></div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($resultado['valor_acumulado'])): ?>
                                <div class="premio-info">
                                    <div class="acumulado">
                                        <span class="label">Acumulado para próximo concurso</span>
                                        <span class="value"><?php echo formatarValor($resultado['valor_acumulado']); ?></span>
                                    </div>
                                </div>

                                <?php if (isset($resultado['total_ganhadores']) && $resultado['total_ganhadores'] > 0): ?>
                                    <div class="ganhadores-info">
                                        <h4>
                                            <i class="fas fa-trophy me-2"></i>
                                            Ganhadores deste Concurso
                                        </h4>
                                        <div class="ganhadores-lista">
                                            <?php
                                            $ganhadores = explode('|', $resultado['ganhadores_info']);
                                            foreach ($ganhadores as $ganhador) {
                                                list($nome, $premio) = explode(':', $ganhador);
                                            ?>
                                                <div class="ganhador-item">
                                                    <div class="ganhador-nome">
                                                        <i class="fas fa-user me-2"></i>
                                                        <?php echo htmlspecialchars($nome); ?>
                                                    </div>
                                                    <div class="ganhador-premio">
                                                        <?php echo formatarValor($premio); ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="total-ganhadores">
                                            Total de Ganhadores: <?php echo $resultado['total_ganhadores']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!empty($resultado['data_proximo_concurso'])): ?>
                                <div class="proximo-sorteio">
                                    <h4>Próximo Sorteio</h4>
                                    <div class="info">
                                        <div class="data">
                                            <i class="far fa-calendar-alt"></i>
                                            <span><?php echo date('d/m/Y', strtotime($resultado['data_proximo_concurso'])); ?></span>
                                        </div>
                                        <?php if (!empty($resultado['valor_estimado_proximo'])): ?>
                                            <div class="estimativa">
                                                <span class="label">Prêmio Estimado:</span>
                                                <span class="value"><?php echo formatarValor($resultado['valor_estimado_proximo']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhum resultado disponível para este jogo.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #2ecc71;
    --warning-color: #f1c40f;
    --danger-color: #e74c3c;
    --light-color: #ecf0f1;
    --dark-color: #2c3e50;
    --white-color: #ffffff;
}

/* Base styles */
* {
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f5f7fa;
    margin: 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 25px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 15px;
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.page-header p {
    margin: 5px 0 0;
    opacity: 0.9;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
    padding: 10px;
}

.result-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.result-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Cores específicas para cada jogo */
.result-card.megasena .card-header { background: linear-gradient(135deg, #209869, #1a7d55); }
.result-card.lotofacil .card-header { background: linear-gradient(135deg, #930089, #6d0066); }
.result-card.quina .card-header { background: linear-gradient(135deg, #260085, #1c0061); }
.result-card.lotomania .card-header { background: linear-gradient(135deg, #F78100, #c66800); }
.result-card.timemania .card-header { background: linear-gradient(135deg, #00ff48, #00cc3a); }
.result-card.duplasena .card-header { background: linear-gradient(135deg, #A61324, #8a0f1e); }
.result-card.maismilionaria .card-header { background: linear-gradient(135deg, #930089, #6d0066); }
.result-card.diadesorte .card-header { background: linear-gradient(135deg, #CB8E37, #a87429); }

.card-header {
    padding: 20px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-content {
    flex-grow: 1;
}

.card-header h3 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 700;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.concurso {
    font-size: 0.95rem;
    opacity: 0.9;
    display: block;
    margin-top: 5px;
}

.data {
    font-size: 0.9rem;
    opacity: 0.8;
}

.logo-container {
    width: 60px;
    height: 60px;
    margin-left: 15px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    padding: 5px;
}

.jogo-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.card-body {
    padding: 25px;
}

.numbers-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin-bottom: 25px;
}

.number {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    color: white;
    background: var(--primary-color);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.number:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
}

/* Cores dos números para cada jogo */
.megasena .number { background: #209869; }
.lotofacil .number { background: #930089; }
.quina .number { background: #260085; }
.lotomania .number { background: #F78100; }
.timemania .number { background: #00FF48; color: #000; }
.duplasena .number { background: #A61324; }
.maismilionaria .number { background: #930089; }
.diadesorte .number { background: #CB8E37; }

.premio-info {
    margin-top: 25px;
    padding: 20px;
    background: var(--light-color);
    border-radius: 12px;
}

.acumulado {
    text-align: center;
}

.acumulado .label {
    display: block;
    font-size: 0.95rem;
    color: var(--dark-color);
    margin-bottom: 8px;
}

.acumulado .value {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--success-color);
}

.proximo-sorteio {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid var(--light-color);
}

.proximo-sorteio h4 {
    font-size: 1.1rem;
    color: var(--dark-color);
    margin-bottom: 15px;
}

.proximo-sorteio .info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.proximo-sorteio .data {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    color: var(--dark-color);
}

.estimativa {
    text-align: right;
}

.estimativa .label {
    display: block;
    font-size: 0.9rem;
    color: var(--dark-color);
}

.estimativa .value {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--success-color);
}

.alert {
    border: none;
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.alert-success {
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: white;
}

.alert-danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.alert-info {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.ganhadores-info {
    margin-top: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.ganhadores-info h4 {
    color: var(--dark-color);
    font-size: 1.1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.ganhadores-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ganhador-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.2s ease;
}

.ganhador-item:hover {
    transform: translateX(5px);
}

.ganhador-nome {
    font-size: 0.95rem;
    color: var(--dark-color);
    display: flex;
    align-items: center;
}

.ganhador-premio {
    font-weight: bold;
    color: var(--success-color);
}

.total-ganhadores {
    margin-top: 15px;
    text-align: center;
    font-weight: bold;
    color: var(--primary-color);
    padding: 10px;
    background: rgba(255,255,255,0.5);
    border-radius: 8px;
}

/* Media Queries para responsividade */
@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
        padding: 15px;
    }
    
    .proximo-sorteio .info {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .estimativa {
        text-align: left;
        width: 100%;
    }
    
    .number {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .numbers-grid {
        gap: 8px;
    }
    
    .card-header {
        padding: 15px;
    }
    
    .card-body {
        padding: 15px;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .ganhador-nome {
        font-size: 0.85rem;
        max-width: 170px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .ganhador-premio {
        font-size: 0.85rem;
    }
}
</style>

<script>
// Animação dos números
document.querySelectorAll('.number').forEach(number => {
    number.addEventListener('mouseover', function() {
        this.style.transform = 'scale(1.1)';
    });
    
    number.addEventListener('mouseout', function() {
        this.style.transform = 'scale(1)';
    });
});

// Atualização automática a cada 5 minutos
setInterval(function() {
    location.reload();
}, 300000);
</script>

</body>
</html> 