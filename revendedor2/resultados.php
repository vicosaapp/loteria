<?php
// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar arquivos de inclusão
$config_path = '../config/database.php';
$layout_path = 'includes/layout.php';

// Função para exibir erros de forma amigável
function displayError($message) {
    echo "<!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <title>Erro</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='alert alert-danger' role='alert'>
                <h2>Ocorreu um erro</h2>
                <p>" . htmlspecialchars($message) . "</p>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

// Verificar existência dos arquivos
if (!file_exists($config_path)) {
    displayError("Arquivo de configuração do banco de dados não encontrado em $config_path");
}

if (!file_exists($layout_path)) {
    displayError("Arquivo de layout não encontrado em $layout_path");
}

// Incluir arquivos necessários
require_once $config_path;

// Definir a página atual para o menu
$currentPage = 'resultados';
$pageTitle = 'Resultados das Loterias';

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

// Início do buffer de saída
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Header com gradiente -->
    <div class="header-card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="text-white mb-0"><i class="fas fa-trophy"></i> Resultados Oficiais</h1>
                <p class="text-white-50 mb-0">Resultados das Loterias para Revendedores</p>
            </div>
            <button type="button" class="btn btn-light" id="btnAtualizarResultados">
                <i class="fas fa-sync-alt me-2"></i>Atualizar Resultados
            </button>
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
                <div class="lottery-card <?php echo htmlspecialchars(strtolower($resultado['identificador_api'] ?? '')); ?>">
                    <div class="lottery-header">
                        <div class="lottery-info">
                            <h3><?php echo htmlspecialchars($resultado['nome'] ?? ''); ?></h3>
                            <p class="mb-0">
                                Concurso <?php echo htmlspecialchars($resultado['numero_concurso'] ?? ''); ?>
                                <?php if (!empty($resultado['data_sorteio'])): ?>
                                    <span class="mx-2">•</span> <?php echo date('d/m/Y', strtotime($resultado['data_sorteio'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if (!empty($resultado['identificador_api'])): ?>
                            <img src="../assets/images/logos/<?php echo htmlspecialchars($resultado['identificador_api']); ?>.png" 
                                 alt="<?php echo htmlspecialchars($resultado['nome']); ?>"
                                 class="lottery-logo"
                                 onerror="this.src='../assets/images/logos/default.png'">
                        <?php endif; ?>
                    </div>
                    
                    <div class="lottery-body">
                        <?php if (!empty($resultado['dezenas'])): ?>
                            <div class="numbers-grid">
                                <?php 
                                $dezenas = explode(',', $resultado['dezenas']);
                                foreach ($dezenas as $dezena): 
                                ?>
                                    <div class="number"><?php echo str_pad($dezena, 2, '0', STR_PAD_LEFT); ?></div>
                                <?php endforeach; ?>
                            </div>



                            <?php if (!empty($resultado['data_proximo_concurso'])): ?>
                                <div class="next-draw">
                                    <div class="next-draw-header">Próximo Sorteio</div>
                                    <div class="next-draw-info">
                                        <div class="date">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo date('d/m/Y', strtotime($resultado['data_proximo_concurso'])); ?>
                                        </div>

                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
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
/* Estilos específicos para a página de resultados */
.header-card {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
    padding: 10px;
}

.lottery-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.lottery-header {
    padding: 20px;
    color: black;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.lottery-info h3 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
}

.lottery-info p {
    opacity: 0.9;
    font-size: 0.9rem;
    margin-top: 5px;
}

.lottery-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
}

/* Cores específicas para cada jogo */
.lottery-card.megasena .lottery-header { background: linear-gradient(135deg, #209869, #1a7d55); }
.lottery-card.lotofacil .lottery-header { background: linear-gradient(135deg,rgb(211, 5, 197),rgb(128, 14, 120)); }
.lottery-card.quina .lottery-header { background: linear-gradient(135deg,rgb(44, 125, 247),rgb(42, 86, 206)); }
.lottery-card.lotomania .lottery-header { background: linear-gradient(135deg, #F78100, #c66800); }
.lottery-card.timemania .lottery-header { background: linear-gradient(135deg,rgb(215, 230, 5),rgb(223, 238, 13)); }
.lottery-card.duplasena .lottery-header { background: linear-gradient(135deg, #A61324, #8a0f1e); }
.lottery-card.maismilionaria .lottery-header { background: linear-gradient(135deg, #742ce3, #7c37e7); }
.lottery-card.diadesorte .lottery-header { background: linear-gradient(135deg, #e9bb08, #bd9704); }

.lottery-body {
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
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
    color: white;
    transition: transform 0.2s;
}

/* Cores dos números para cada jogo */
.lottery-card.megasena .number { background: #209869; }
.lottery-card.lotofacil .number { background: #930089; }
.lottery-card.quina .number { background: #260085; }
.lottery-card.lotomania .number { background: #F78100; }
.lottery-card.timemania .number { background: #00ff48; color: #000; }
.lottery-card.duplasena .number { background: #A61324; }
.lottery-card.maismilionaria .number { background: #930089; }
.lottery-card.diadesorte .number { background: #CB8E37; }

.number:hover {
    transform: scale(1.1);
}

.info-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 20px;
}

.info-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.info-value {
    font-size: 1.3rem;
    font-weight: bold;
    color: #28a745;
}

.next-draw {
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.next-draw-header {
    font-size: 1rem;
    color: #495057;
    margin-bottom: 15px;
}

.next-draw-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.date {
    color: #6c757d;
}

.date i {
    margin-right: 8px;
}

.prize {
    text-align: right;
}

.prize-label {
    font-size: 0.85rem;
    color: #6c757d;
}

.prize-value {
    font-size: 1.1rem;
    font-weight: bold;
    color: #28a745;
}

@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: 1fr;
    }

    .lottery-header {
        padding: 15px;
    }

    .lottery-info h3 {
        font-size: 1.2rem;
    }

    .number {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .next-draw-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .prize {
        text-align: left;
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualização automática a cada 5 minutos
    setInterval(function() {
        location.reload();
    }, 300000);
    
    // Atualização de resultados via AJAX
    document.getElementById('btnAtualizarResultados').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Atualizando...';
    
        fetch('ajax/atualizar_resultados.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert('Erro ao atualizar resultados: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Atualizar Resultados';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar resultados');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Atualizar Resultados';
        });
    });
});
</script>

<?php
// Capturar o conteúdo do buffer
$content = ob_get_clean();

// Incluir o layout
require_once $layout_path;
?>