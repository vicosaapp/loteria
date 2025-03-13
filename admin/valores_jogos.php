<?php
require_once '../config/database.php';
require_once 'includes/header.php';

// Configurações para permitir exibição de mais registros
ini_set('memory_limit', '512M'); // Aumentado para 512MB
ini_set('max_execution_time', 600); // Aumentado para 10 minutos
set_time_limit(600);

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Filtros
$jogo_id = isset($_GET['jogo_id']) ? (int)$_GET['jogo_id'] : null;
$dezenas = isset($_GET['dezenas']) ? (int)$_GET['dezenas'] : null;

// Consulta base sem paginação
$sql_base = "SELECT vj.*, j.nome as nome_jogo 
             FROM valores_jogos vj 
             INNER JOIN jogos j ON j.id = vj.jogo_id 
             WHERE 1=1";
$params = [];

// Adiciona filtros se existirem
if ($jogo_id) {
    $sql_base .= " AND vj.jogo_id = ?";
    $params[] = $jogo_id;
}
if ($dezenas) {
    $sql_base .= " AND vj.dezenas = ?";
    $params[] = $dezenas;
}

// Ordenação
$sql_final = $sql_base . " ORDER BY vj.jogo_id, vj.dezenas, vj.valor_aposta";

$stmt = $pdo->prepare($sql_final);
$stmt->execute($params);
$valores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca jogos para o filtro
$jogos = $pdo->query("SELECT id, nome FROM jogos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Define constante para segurança
define('ADMIN', true);
?>

<style>
.container-fluid {
    padding: 1.5rem !important;
    padding-left: 1rem !important;
}

.card {
    border-radius: 0.35rem;
    margin: 0 !important;
}

.card-body {
    padding: 1rem !important;
}

.table-responsive {
    margin: 0 !important;
    padding: 0 !important;
}

.filtros {
    background-color: #f8f9fc;
    padding: 1rem;
    border-radius: 0.35rem;
    margin-bottom: 1rem;
}

/* Ajustes para melhor visualização de muitos registros */
.table td, .table th {
    padding: 0.5rem !important;
    font-size: 0.9rem;
}

/* Fixa o cabeçalho da tabela */
.table-responsive {
    max-height: calc(100vh - 250px);
    overflow-y: auto;
}

.table thead th {
    position: sticky;
    top: 0;
    background-color: #fff;
    z-index: 1;
    box-shadow: 0 1px 0 rgba(0,0,0,.1);
}
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-dollar-sign"></i> Valores dos Jogos
        </h1>
    </div>

    <!-- Filtros -->
    <div class="filtros">
        <form method="GET" class="row">
            <div class="col-md-4">
                <label for="jogo_id">Jogo</label>
                <select name="jogo_id" id="jogo_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($jogos as $jogo): ?>
                        <option value="<?php echo $jogo['id']; ?>" <?php echo $jogo_id == $jogo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($jogo['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="dezenas">Dezenas</label>
                <input type="number" name="dezenas" id="dezenas" class="form-control" value="<?php echo $dezenas; ?>" min="1">
            </div>
            <div class="col-md-4">
                <label>&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="valores_jogos.php" class="btn btn-secondary">Limpar</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Jogo</th>
                            <th>Dezenas</th>
                            <th>Valor Aposta</th>
                            <th>Valor Prêmio</th>
                            <th>Valor Total Prêmio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($valores as $valor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($valor['nome_jogo']); ?></td>
                                <td><?php echo $valor['dezenas']; ?></td>
                                <td>R$ <?php echo number_format($valor['valor_aposta'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($valor['valor_premio'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($valor['valor_total_premio'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Contador de registros -->
            <div class="mt-3">
                <small class="text-muted">
                    Total de registros: <?php echo count($valores); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/layout.php';
?> 