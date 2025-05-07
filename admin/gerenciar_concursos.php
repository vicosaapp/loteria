<?php
require_once '../config/database.php';
session_start();

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Inserção de novo concurso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'inserir') {
    $jogo_id = $_POST['jogo_id'] ?? null;
    $codigo = $_POST['codigo'] ?? null;
    $data_sorteio = $_POST['data_sorteio'] ?? null;
    $status = $_POST['status'] ?? 'aguardando';
    if ($jogo_id && $codigo && $data_sorteio) {
        $stmt = $pdo->prepare("INSERT INTO concursos (jogo_id, codigo, data_sorteio, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$jogo_id, $codigo, $data_sorteio, $status]);
        $msg = 'Concurso cadastrado com sucesso!';
    } else {
        $msg = 'Preencha todos os campos.';
    }
}

// Exclusão de concurso
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $stmt = $pdo->prepare("DELETE FROM concursos WHERE id = ?");
    $stmt->execute([$_GET['excluir']]);
    $msg = 'Concurso excluído com sucesso!';
}

// Buscar jogos
$jogos = $pdo->query("SELECT id, nome FROM jogos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
// Buscar concursos
$concursos = $pdo->query("SELECT c.*, j.nome as nome_jogo FROM concursos c JOIN jogos j ON c.jogo_id = j.id ORDER BY c.data_sorteio DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);

// Definir página atual para menu
$currentPage = 'concursos';

ob_start();
?>
<div class="container py-4">
    <h1 class="mb-4">Gerenciar Concursos</h1>
    <?php if (isset($msg)): ?>
        <div class="alert alert-info"> <?= htmlspecialchars($msg) ?> </div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="acao" value="inserir">
                <div class="col-md-3">
                    <label class="form-label">Jogo</label>
                    <select name="jogo_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($jogos as $jogo): ?>
                            <option value="<?= $jogo['id'] ?>"><?= htmlspecialchars($jogo['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nº Concurso</label>
                    <input type="text" name="codigo" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data/Hora Sorteio</label>
                    <input type="datetime-local" name="data_sorteio" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="aguardando">Aguardando</option>
                        <option value="finalizado">Finalizado</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Últimos Concursos</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Jogo</th>
                            <th>Nº Concurso</th>
                            <th>Data Sorteio</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($concursos as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['nome_jogo']) ?></td>
                            <td><?= htmlspecialchars($c['codigo']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($c['data_sorteio'])) ?></td>
                            <td><?= ucfirst($c['status']) ?></td>
                            <td>
                                <a href="?excluir=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este concurso?')">Excluir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once 'includes/layout.php'; 