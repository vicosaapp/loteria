<?php
require_once '../config/database.php';
session_start();

// Verificar se está logado e é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Processar aprovação/rejeição de apostas (se houver)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $aposta_id = isset($_POST['aposta_id']) ? intval($_POST['aposta_id']) : 0;
    $action = $_POST['action'];
    
    if ($aposta_id > 0) {
        if ($action === 'aprovar') {
            // Aprovar a aposta
            $stmt = $pdo->prepare("UPDATE apostas SET status = 'aprovada' WHERE id = ?");
            $stmt->execute([$aposta_id]);
            
            // Obter dados da aposta para enviar comprovante
            $stmt = $pdo->prepare("
                SELECT 
                    a.id, 
                    a.numeros, 
                    a.valor_aposta, 
                    a.valor_premio,
                    u.nome AS apostador_nome,
                    u.whatsapp AS apostador_whatsapp,
                    j.nome AS jogo_nome
                FROM 
                    apostas a
                JOIN 
                    usuarios u ON a.usuario_id = u.id
                JOIN 
                    jogos j ON a.tipo_jogo_id = j.id
                WHERE 
                    a.id = ?
            ");
            $stmt->execute([$aposta_id]);
            $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($aposta) {
                // Enfileirar para enviar comprovante por WhatsApp
                $stmt = $pdo->prepare("
                    INSERT INTO fila_envio_comprovantes (
                        aposta_id, 
                        status, 
                        data_enfileiramento,
                        tentativas
                    ) VALUES (?, 'pendente', NOW(), 0)
                ");
                $stmt->execute([$aposta_id]);
                
                $mensagem = "Aposta #$aposta_id aprovada e comprovante enfileirado para envio!";
                $tipo_mensagem = "success";
            }
        } elseif ($action === 'rejeitar') {
            // Rejeitar a aposta
            $stmt = $pdo->prepare("UPDATE apostas SET status = 'rejeitada' WHERE id = ?");
            $stmt->execute([$aposta_id]);
            
            $mensagem = "Aposta #$aposta_id foi rejeitada!";
            $tipo_mensagem = "warning";
        }
    }
}

// Buscar apostas pendentes
$stmt = $pdo->prepare("
    SELECT 
        a.id, 
        a.numeros, 
        a.valor_aposta, 
        a.valor_premio,
        a.created_at,
        u.nome AS apostador_nome,
        u.whatsapp AS apostador_whatsapp,
        j.nome AS jogo_nome
    FROM 
        apostas a
    JOIN 
        usuarios u ON a.usuario_id = u.id
    JOIN 
        jogos j ON a.tipo_jogo_id = j.id
    WHERE 
        a.status = 'pendente'
    ORDER BY 
        a.created_at DESC
");
$stmt->execute();
$apostas_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Definir a página atual para o menu
$currentPage = 'apostas_pendentes';

// Iniciar output buffer
ob_start();
?>

<div class="container-fluid mt-4">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-clock"></i> Apostas Pendentes</h1>
    <p class="mb-4">Gerencie as apostas que aguardam aprovação.</p>

    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Apostas Aguardando Aprovação</h6>
            <button type="button" class="btn btn-sm btn-primary" id="btn-refresh" onclick="window.location.reload();">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>
        <div class="card-body">
            <?php if (count($apostas_pendentes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Apostador</th>
                                <th>Jogo</th>
                                <th>Números</th>
                                <th>Valor</th>
                                <th>Prêmio</th>
                                <th>Data</th>
                                <th>WhatsApp</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apostas_pendentes as $aposta): ?>
                                <tr>
                                    <td><?php echo $aposta['id']; ?></td>
                                    <td><?php echo htmlspecialchars($aposta['apostador_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($aposta['jogo_nome']); ?></td>
                                    <td>
                                        <?php 
                                        $numeros = explode(',', $aposta['numeros']);
                                        foreach ($numeros as $num) {
                                            echo '<span class="badge bg-primary me-1">' . str_pad($num, 2, '0', STR_PAD_LEFT) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($aposta['valor_premio'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                                    <td>
                                        <?php if ($aposta['apostador_whatsapp']): ?>
                                            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $aposta['apostador_whatsapp']); ?>" target="_blank" class="btn btn-sm btn-success">
                                                <i class="fab fa-whatsapp"></i> <?php echo $aposta['apostador_whatsapp']; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Não disponível</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <form method="POST" style="margin-right: 5px;">
                                                <input type="hidden" name="aposta_id" value="<?php echo $aposta['id']; ?>">
                                                <input type="hidden" name="action" value="aprovar">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Tem certeza que deseja APROVAR esta aposta?')">
                                                    <i class="fas fa-check"></i> Aprovar
                                                </button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="aposta_id" value="<?php echo $aposta['id']; ?>">
                                                <input type="hidden" name="action" value="rejeitar">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja REJEITAR esta aposta?')">
                                                    <i class="fas fa-times"></i> Rejeitar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Não há apostas pendentes no momento.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informações Adicionais</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Como funciona o processo de aprovação:</h5>
                <ol>
                    <li>Quando você <strong>aprova</strong> uma aposta, ela é automaticamente incluída na fila de envio de comprovantes.</li>
                    <li>O sistema envia o comprovante para o WhatsApp do apostador em até 5 minutos após a aprovação.</li>
                    <li>Se o apostador não tiver um número de WhatsApp cadastrado, o comprovante não será enviado.</li>
                    <li>Você pode verificar o status dos envios na página de <a href="logs_comprovantes.php">Logs de Comprovantes</a>.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php
// Obter conteúdo do buffer
$content = ob_get_clean();

// Incluir o layout
require_once '../includes/admin_layout.php';
?> 