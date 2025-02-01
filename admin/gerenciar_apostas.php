<?php
session_start();
require_once '../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'apostas';

$mensagem = '';
$erro = '';

// Processar ações de aprovação/rejeição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aposta_id = $_POST['aposta_id'];
    $acao = $_POST['acao'];
    
    try {
        if ($acao === 'aprovar') {
            // Buscar dados da aposta
            $stmt = $pdo->prepare("
                SELECT a.*, u.nome, u.email, u.whatsapp 
                FROM apostas a 
                JOIN usuarios u ON a.usuario_id = u.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$aposta_id]);
            $dados_aposta = $stmt->fetch();

            // Gerar nome do comprovante
            $comprovante = 'comprovante_' . $aposta_id . '_' . time() . '.html';
            
            // Criar diretório se não existir
            if (!file_exists('../comprovantes')) {
                mkdir('../comprovantes', 0777, true);
            }
            
            // Criar comprovante em HTML
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Comprovante de Aposta #' . $aposta_id . '</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; margin: 40px; }
                    .comprovante { max-width: 800px; margin: 0 auto; border: 2px solid #007bff; padding: 20px; }
                    .header { text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 20px; margin-bottom: 20px; }
                    .header h1 { color: #007bff; margin: 0; }
                    .info-grupo { margin-bottom: 20px; }
                    .info-grupo h2 { color: #0056b3; }
                    .info-item { margin-bottom: 10px; }
                    .info-label { font-weight: bold; color: #555; }
                    .numeros { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
                    .numero { 
                        width: 40px; height: 40px; 
                        display: flex; align-items: center; justify-content: center;
                        background: #007bff; color: white; 
                        border-radius: 50%; font-weight: bold;
                    }
                    .footer { 
                        margin-top: 30px; text-align: center; 
                        padding-top: 20px; border-top: 1px solid #ddd;
                        color: #666; font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class="comprovante">
                    <div class="header">
                        <h1>Comprovante de Aposta</h1>
                        <p>Sistema de Loteria</p>
                    </div>
                    
                    <div class="info-grupo">
                        <h2>Informações da Aposta</h2>
                        <div class="info-item">
                            <span class="info-label">Número da Aposta:</span>
                            <span>#' . str_pad($aposta_id, 6, '0', STR_PAD_LEFT) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Data:</span>
                            <span>' . date('d/m/Y H:i:s', strtotime($dados_aposta['created_at'])) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span style="color: #2e7d32;">Aprovada</span>
                        </div>
                    </div>
                    
                    <div class="info-grupo">
                        <h2>Dados do Apostador</h2>
                        <div class="info-item">
                            <span class="info-label">Nome:</span>
                            <span>' . htmlspecialchars($dados_aposta['nome']) . '</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span>' . htmlspecialchars($dados_aposta['email']) . '</span>
                        </div>
                        ' . ($dados_aposta['whatsapp'] ? '
                        <div class="info-item">
                            <span class="info-label">WhatsApp:</span>
                            <span>' . htmlspecialchars($dados_aposta['whatsapp']) . '</span>
                        </div>
                        ' : '') . '
                    </div>
                    
                    <div class="info-grupo">
                        <h2>Números Apostados</h2>
                        <div class="numeros">
                            ' . implode('', array_map(function($num) {
                                return '<div class="numero">' . trim($num) . '</div>';
                            }, explode(',', $dados_aposta['numeros']))) . '
                        </div>
                    </div>
                    
                    <div class="footer">
                        <p>Este comprovante é válido como confirmação da sua aposta.</p>
                        <p>Data de emissão: ' . date('d/m/Y H:i:s') . '</p>
                    </div>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                    }
                </script>
            </body>
            </html>';
            
            file_put_contents('../comprovantes/' . $comprovante, $html);
            
            $stmt = $pdo->prepare("UPDATE apostas SET status = 'aprovada', comprovante_path = ? WHERE id = ?");
            $stmt->execute([$comprovante, $aposta_id]);
            $mensagem = "Aposta aprovada com sucesso!";
        } else if ($acao === 'rejeitar') {
            $stmt = $pdo->prepare("UPDATE apostas SET status = 'rejeitada' WHERE id = ?");
            $stmt->execute([$aposta_id]);
            $mensagem = "Aposta rejeitada com sucesso!";
        }
    } catch(PDOException $e) {
        $erro = "Erro ao processar ação: " . $e->getMessage();
    }
}

// Buscar todas as apostas agrupadas por apostador
$stmt = $pdo->query("
    WITH todas_apostas AS (
        (SELECT 
            a.id,
            'normal' as tipo_aposta,
            a.numeros,
            a.valor_aposta as valor,
            a.created_at as data_aposta,
            a.usuario_id,
            u.nome as apostador_nome,
            u.whatsapp as apostador_whatsapp,
            u.telefone as apostador_telefone,
            r.nome as revendedor_nome,
            'Normal' as jogo_nome
        FROM apostas a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        LEFT JOIN usuarios r ON a.revendedor_id = r.id)
        
        UNION ALL
        
        (SELECT 
            ai.id,
            'importada' as tipo_aposta,
            ai.numeros,
            ai.valor_aposta as valor,
            ai.created_at as data_aposta,
            ai.usuario_id,
            u.nome as apostador_nome,
            COALESCE(ai.whatsapp, u.whatsapp) as apostador_whatsapp,
            u.telefone as apostador_telefone,
            r.nome as revendedor_nome,
            ai.jogo_nome as jogo_nome
        FROM apostas_importadas ai
        LEFT JOIN usuarios u ON ai.usuario_id = u.id
        LEFT JOIN usuarios r ON ai.revendedor_id = r.id)
    )
    SELECT 
        usuario_id,
        apostador_nome,
        apostador_whatsapp,
        apostador_telefone,
        revendedor_nome,
        jogo_nome,
        COUNT(*) as total_apostas,
        GROUP_CONCAT(numeros SEPARATOR '|') as todas_apostas,
        SUM(valor) as valor_total,
        MAX(data_aposta) as ultima_aposta
    FROM todas_apostas
    GROUP BY usuario_id, apostador_nome, apostador_whatsapp, apostador_telefone, revendedor_nome, jogo_nome
    ORDER BY ultima_aposta DESC
");

$apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicia o buffer de saída
ob_start();
?>

<div class="page-header">
    <h1>Gerenciar Apostas</h1>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-success">
        <?php echo $mensagem; ?>
    </div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="alert alert-danger">
        <?php echo $erro; ?>
    </div>
<?php endif; ?>

<!-- Modal para exibir números -->
<div class="modal fade" id="numerosModal" tabindex="-1" role="dialog" aria-labelledby="numerosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="numerosModalLabel">Apostas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar" onclick="$('#numerosModal').modal('hide')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="apostasContainer"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#numerosModal').modal('hide')">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <h1 class="mb-4">
        <i class="fas fa-ticket-alt"></i> Gerenciar Apostas
    </h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Apostas Registradas</h6>
            <a href="importar_apostas.php" class="btn btn-primary btn-sm">
                <i class="fas fa-file-import"></i> Importar Apostas
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="apostasTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>ID</th>
                            <th>Apostador</th>
                            <th>Jogo</th>
                            <th>Contato</th>
                            <th>Revendedor</th>
                            <th>Total Apostas</th>
                            <th>Valor Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apostas as $aposta): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($aposta['ultima_aposta'])); ?></td>
                                <td><?php echo $aposta['usuario_id']; ?></td>
                                <td><?php echo htmlspecialchars($aposta['apostador_nome']); ?></td>
                                <td><?php echo htmlspecialchars($aposta['jogo_nome']); ?></td>
                                <td>
                                    <?php if ($aposta['apostador_whatsapp'] || $aposta['apostador_telefone']): ?>
                                        <?php 
                                        $telefone = preg_replace('/[^0-9]/', '', 
                                            $aposta['apostador_whatsapp'] ?: $aposta['apostador_telefone']
                                        );
                                        $mensagem = "Olá, " . $aposta['apostador_nome'];
                                        ?>
                                        <a href="https://wa.me/55<?php echo $telefone; ?>?text=<?php echo urlencode($mensagem); ?>" 
                                           target="_blank" 
                                           class="btn btn-success btn-sm whatsapp-btn">
                                            <i class="fab fa-whatsapp"></i> 
                                            <?php echo $aposta['apostador_whatsapp'] ?: $aposta['apostador_telefone']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Não informado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($aposta['revendedor_nome'] ?: 'Admin'); ?></td>
                                <td><?php echo $aposta['total_apostas']; ?></td>
                                <td>R$ <?php echo number_format($aposta['valor_total'], 2, ',', '.'); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-info btn-sm" 
                                                onclick="verApostas('<?php echo htmlspecialchars($aposta['todas_apostas'] ?? ''); ?>', '<?php echo htmlspecialchars($aposta['apostador_nome'] ?? ''); ?>')">
                                            <i class="fas fa-eye"></i> Ver apostas
                                        </button>
                                        <button class="btn btn-success btn-sm" 
                                                onclick="gerarComprovante(<?php echo $aposta['usuario_id']; ?>, '<?php echo htmlspecialchars($aposta['jogo_nome'] ?? ''); ?>')">
                                            <i class="fas fa-file-pdf"></i> Comprovante
                                        </button>
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="excluirApostas(
                                                    <?php echo (int)$aposta['usuario_id']; ?>, 
                                                    '<?php echo htmlspecialchars($aposta['jogo_nome'] ?? ''); ?>', 
                                                    '<?php echo htmlspecialchars($aposta['apostador_nome'] ?? ''); ?>'
                                                )">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.apostas-container {
    background: white;
    padding: 140px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
}

.btn-whatsapp {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #25D366;
    color: white;
    border-radius: 50%;
    margin-left: 5px;
    text-decoration: none;
}

.btn-whatsapp:hover {
    background: #128C7E;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.pendente {
    background: #ffc107;
    color: #000;
}

.status-badge.aprovada {
    background: #28a745;
    color: white;
}

.status-badge.rejeitada {
    background: #dc3545;
    color: white;
}

.numeros-apostados {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.numero {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #007bff;
    color: white;
    border-radius: 50%;
    font-size: 12px;
}

.btn {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
}

.btn-sm {
    padding: 2px 6px;
    font-size: 12px;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.no-data-message {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.no-data-message i {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
}

.no-data-message p {
    font-size: 16px;
    margin: 0;
}

.numero-bola {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: #007bff;
    color: white;
    border-radius: 50%;
    margin: 0 2px;
    font-size: 0.85rem;
}

.whatsapp-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #25d366;
    color: white !important;
    border-radius: 50%;
    margin-left: 8px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(37, 211, 102, 0.4);
}

.whatsapp-link:hover {
    transform: scale(1.15);
    background: #128c7e;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.6);
}

.whatsapp-link i {
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));
}

/* Animação de pulso */
@keyframes whatsappPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}

.whatsapp-link {
    animation: whatsappPulse 2s infinite;
}

.whatsapp-link:hover {
    animation: none;
}

.badge {
    padding: 5px 10px;
    border-radius: 4px;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.table td {
    vertical-align: middle;
}

.badge.bg-success {
    background: #00ff66 !important; /* Verde fluorescente */
    color: #000 !important; /* Texto preto para melhor contraste */
    font-weight: 600;
    text-shadow: 0 0 10px rgba(0,255,102,0.5);
    box-shadow: 0 0 10px rgba(0,255,102,0.3);
}

.badge.bg-danger {
    background: #ff0000 !important; /* Vermelho vivo */
    color: #fff !important;
    font-weight: 600;
    text-shadow: 0 0 10px rgba(255,0,0,0.5);
    box-shadow: 0 0 10px rgba(255,0,0,0.3);
}

.badge.bg-warning {
    background: #ffd700 !important; /* Amarelo para pendente */
    color: #000 !important;
    font-weight: 600;
}

/* Efeito hover suave */
.badge {
    transition: all 0.3s ease;
}

.badge:hover {
    transform: scale(1.05);
}

.apostas-list {
    max-height: 400px;
    overflow-y: auto;
}

.aposta-item {
    padding: 10px;
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.aposta-item:last-child {
    border-bottom: none;
}

.numeros {
    font-family: monospace;
    font-size: 1.1em;
    margin-top: 5px;
}

.table th {
    background-color: #4e73df;
    color: white;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.btn-success {
    background-color: #1cc88a;
    border-color: #1cc88a;
}

.btn-success:hover {
    background-color: #169b6b;
    border-color: #169b6b;
}

.whatsapp-btn {
    background-color: #25d366;
    border-color: #25d366;
    color: white;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.whatsapp-btn:hover {
    background-color: #128c7e;
    border-color: #128c7e;
    color: white;
}

.whatsapp-btn i {
    font-size: 1.2em;
}

.modal-header .close {
    padding: 1rem;
    margin: -1rem -1rem -1rem auto;
    cursor: pointer;
}

.modal-header .close:hover {
    opacity: 0.7;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1rem;
}

.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-secondary:hover {
    color: #fff;
    background-color: #5a6268;
    border-color: #545b62;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function aprovarAposta(id) {
    Swal.fire({
        title: 'Confirmar aprovação',
        text: 'Deseja realmente aprovar esta aposta?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#00ff66',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, aprovar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Processando...',
                text: 'Aguarde enquanto a aposta é aprovada',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fazer requisição AJAX
            fetch('ajax/atualizar_status_aposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    status: 'aprovada'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Aposta aprovada com sucesso!',
                        icon: 'success',
                        confirmButtonColor: '#00ff66'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao aprovar aposta');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Erro!',
                    text: error.message || 'Ocorreu um erro ao aprovar a aposta',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}

function rejeitarAposta(id) {
    Swal.fire({
        title: 'Confirmar rejeição',
        text: 'Deseja realmente rejeitar esta aposta?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, rejeitar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Processando...',
                text: 'Aguarde enquanto a aposta é rejeitada',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fazer requisição AJAX
            fetch('ajax/atualizar_status_aposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    status: 'rejeitada'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Concluído!',
                        text: 'Aposta rejeitada com sucesso!',
                        icon: 'success',
                        confirmButtonColor: '#d33'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao rejeitar aposta');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Erro!',
                    text: error.message || 'Ocorreu um erro ao rejeitar a aposta',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}

// Função para excluir aposta
function excluirApostas(usuarioId, jogoNome, apostadorNome) {
    if (!usuarioId || !jogoNome || !apostadorNome) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'Dados inválidos para exclusão'
        });
        return;
    }

    Swal.fire({
        title: 'Confirmar exclusão?',
        html: `Deseja excluir todas as apostas de:<br>
              <strong>${apostadorNome}</strong><br>
              Jogo: <strong>${jogoNome}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            console.log('Enviando requisição de exclusão:', { usuarioId, jogoNome }); // Debug

            fetch('ajax/excluir_apostas_grupo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    usuario_id: usuarioId,
                    jogo_nome: jogoNome
                })
            })
            .then(response => {
                console.log('Resposta recebida:', response); // Debug
                return response.json();
            })
            .then(data => {
                console.log('Dados processados:', data); // Debug
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message || 'Apostas excluídas com sucesso!'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.error || 'Erro ao excluir apostas');
                }
            })
            .catch(error => {
                console.error('Erro:', error); // Debug
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message || 'Erro ao processar a exclusão'
                });
            });
        }
    });
}

$(document).ready(function() {
    $('#apostasTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
        },
        "order": [[0, "desc"]]
    });
});

function verApostas(apostasStr, apostador) {
    const apostas = apostasStr.split('|');
    let html = `<h4>${apostador}</h4><div class="apostas-list">`;
    
    apostas.forEach((aposta, index) => {
        html += `
            <div class="aposta-item">
                <strong>Aposta ${index + 1}:</strong>
                <div class="numeros">${aposta}</div>
            </div>
        `;
    });
    
    html += '</div>';
    
    document.getElementById('apostasContainer').innerHTML = html;
    $('#numerosModal').modal({
        backdrop: 'static',
        keyboard: true
    });
}

// Adicionar evento para fechar modal com ESC
$(document).keyup(function(e) {
    if (e.key === "Escape") {
        $('#numerosModal').modal('hide');
    }
});

function gerarComprovante(usuarioId, jogoNome) {
    window.open(`gerar_comprovante.php?usuario_id=${usuarioId}&jogo=${encodeURIComponent(jogoNome)}`, '_blank');
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 