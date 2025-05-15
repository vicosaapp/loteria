<?php
require_once '../config/database.php';
session_start();

// Verificar se está logado e é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$itens_por_pagina = 15;
$offset = ($pagina - 1) * $itens_por_pagina;

// Parâmetros de filtro
$status = isset($_GET['status']) ? $_GET['status'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Construir a consulta
$query = "
    SELECT 
        f.id, 
        f.aposta_id,
        f.status,
        f.data_enfileiramento,
        f.data_processamento,
        f.tentativas,
        f.ultima_tentativa,
        f.resultado,
        a.numeros,
        a.valor_aposta,
        a.valor_premio,
        u.nome AS apostador_nome,
        u.whatsapp AS apostador_whatsapp,
        j.nome AS jogo_nome
    FROM 
        fila_envio_comprovantes f
    JOIN 
        apostas a ON f.aposta_id = a.id
    JOIN 
        usuarios u ON a.usuario_id = u.id
    JOIN 
        jogos j ON a.tipo_jogo_id = j.id
    WHERE 1=1
";

$params = [];

// Adicionar filtros à consulta
if ($status) {
    $query .= " AND f.status = ?";
    $params[] = $status;
}

if ($data_inicio) {
    $query .= " AND DATE(f.data_enfileiramento) >= ?";
    $params[] = $data_inicio;
}

if ($data_fim) {
    $query .= " AND DATE(f.data_enfileiramento) <= ?";
    $params[] = $data_fim;
}

// Contar o total de registros
$count_query = str_replace("SELECT \n        f.id, \n        f.aposta_id,", "SELECT COUNT(*) as total", $query);
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_registros = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Adicionar ordenação e limite à consulta
$query .= " ORDER BY f.data_enfileiramento DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $itens_por_pagina;

// Executar a consulta
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Definir a página atual para o menu
$currentPage = 'logs_comprovantes';

// Iniciar output buffer
ob_start();
?>

<div class="container-fluid mt-4">
    <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-alt"></i> Logs de Envio de Comprovantes</h1>
    <p class="mb-4">Visualize o histórico de envio de comprovantes por WhatsApp.</p>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="enviado" <?php echo $status === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                        <option value="falha" <?php echo $status === 'falha' ? 'selected' : ''; ?>>Falha</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Data Início</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Data Fim</label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="logs_comprovantes.php" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Histórico de Envios</h6>
            <button type="button" class="btn btn-sm btn-primary" onclick="window.location.reload();">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>
        <div class="card-body">
            <?php if (count($registros) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Aposta</th>
                                <th>Apostador</th>
                                <th>Jogo</th>
                                <th>Status</th>
                                <th>Enfileirado</th>
                                <th>Processado</th>
                                <th>Tentativas</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td><?php echo $registro['id']; ?></td>
                                    <td>
                                        <a href="gerar_comprovante.php?aposta_id=<?php echo $registro['aposta_id']; ?>" target="_blank">
                                            #<?php echo $registro['aposta_id']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($registro['apostador_nome']); ?>
                                        <?php if ($registro['apostador_whatsapp']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fab fa-whatsapp"></i> <?php echo $registro['apostador_whatsapp']; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($registro['jogo_nome']); ?></td>
                                    <td>
                                        <?php if ($registro['status'] === 'pendente'): ?>
                                            <span class="badge bg-warning text-dark">Pendente</span>
                                        <?php elseif ($registro['status'] === 'enviado'): ?>
                                            <span class="badge bg-success">Enviado</span>
                                        <?php elseif ($registro['status'] === 'falha'): ?>
                                            <span class="badge bg-danger">Falha</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($registro['data_enfileiramento'])); ?></td>
                                    <td>
                                        <?php if ($registro['data_processamento']): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($registro['data_processamento'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $registro['tentativas']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detalhesModal<?php echo $registro['id']; ?>">
                                            <i class="fas fa-eye"></i> Detalhes
                                        </button>
                                        
                                        <?php if ($registro['status'] === 'falha'): ?>
                                            <a href="reprocessar_comprovante.php?id=<?php echo $registro['id']; ?>" class="btn btn-sm btn-warning mt-1">
                                                <i class="fas fa-redo"></i> Reprocessar
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- Modal de Detalhes -->
                                <div class="modal fade" id="detalhesModal<?php echo $registro['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detalhes do Envio #<?php echo $registro['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h6>Informações do Envio</h6>
                                                <dl class="row">
                                                    <dt class="col-sm-4">Status:</dt>
                                                    <dd class="col-sm-8">
                                                        <?php if ($registro['status'] === 'pendente'): ?>
                                                            <span class="badge bg-warning text-dark">Pendente</span>
                                                        <?php elseif ($registro['status'] === 'enviado'): ?>
                                                            <span class="badge bg-success">Enviado</span>
                                                        <?php elseif ($registro['status'] === 'falha'): ?>
                                                            <span class="badge bg-danger">Falha</span>
                                                        <?php endif; ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Enfileirado:</dt>
                                                    <dd class="col-sm-8"><?php echo date('d/m/Y H:i:s', strtotime($registro['data_enfileiramento'])); ?></dd>
                                                    
                                                    <dt class="col-sm-4">Processado:</dt>
                                                    <dd class="col-sm-8">
                                                        <?php if ($registro['data_processamento']): ?>
                                                            <?php echo date('d/m/Y H:i:s', strtotime($registro['data_processamento'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Não processado</span>
                                                        <?php endif; ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Última tentativa:</dt>
                                                    <dd class="col-sm-8">
                                                        <?php if ($registro['ultima_tentativa']): ?>
                                                            <?php echo date('d/m/Y H:i:s', strtotime($registro['ultima_tentativa'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Não realizada</span>
                                                        <?php endif; ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Tentativas:</dt>
                                                    <dd class="col-sm-8"><?php echo $registro['tentativas']; ?></dd>
                                                </dl>
                                                
                                                <h6 class="mt-3">Resultado da Última Tentativa</h6>
                                                <div class="p-2 border rounded bg-light">
                                                    <?php if ($registro['resultado']): ?>
                                                        <?php echo nl2br(htmlspecialchars($registro['resultado'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Nenhum resultado registrado</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <h6 class="mt-3">Informações da Aposta</h6>
                                                <dl class="row">
                                                    <dt class="col-sm-4">ID da Aposta:</dt>
                                                    <dd class="col-sm-8">#<?php echo $registro['aposta_id']; ?></dd>
                                                    
                                                    <dt class="col-sm-4">Jogo:</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars($registro['jogo_nome']); ?></dd>
                                                    
                                                    <dt class="col-sm-4">Números:</dt>
                                                    <dd class="col-sm-8">
                                                        <?php 
                                                        $numeros = explode(',', $registro['numeros']);
                                                        foreach ($numeros as $num) {
                                                            echo '<span class="badge bg-primary me-1">' . str_pad($num, 2, '0', STR_PAD_LEFT) . '</span>';
                                                        }
                                                        ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Valor:</dt>
                                                    <dd class="col-sm-8">R$ <?php echo number_format($registro['valor_aposta'], 2, ',', '.'); ?></dd>
                                                    
                                                    <dt class="col-sm-4">Prêmio:</dt>
                                                    <dd class="col-sm-8">R$ <?php echo number_format($registro['valor_premio'], 2, ',', '.'); ?></dd>
                                                </dl>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                
                                                <?php if ($registro['status'] === 'falha'): ?>
                                                    <a href="reprocessar_comprovante.php?id=<?php echo $registro['id']; ?>" class="btn btn-warning">
                                                        <i class="fas fa-redo"></i> Reprocessar
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Navegação de páginas">
                        <ul class="pagination justify-content-center mt-3">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=1<?php echo $status ? '&status=' . $status : ''; ?><?php echo $data_inicio ? '&data_inicio=' . $data_inicio : ''; ?><?php echo $data_fim ? '&data_fim=' . $data_fim : ''; ?>">
                                        Primeira
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $data_inicio ? '&data_inicio=' . $data_inicio : ''; ?><?php echo $data_fim ? '&data_fim=' . $data_fim : ''; ?>">
                                        Anterior
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $inicio = max(1, $pagina - 2);
                            $fim = min($total_paginas, $pagina + 2);
                            
                            if ($inicio > 1) {
                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                            }
                            
                            for ($i = $inicio; $i <= $fim; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $data_inicio ? '&data_inicio=' . $data_inicio : ''; ?><?php echo $data_fim ? '&data_fim=' . $data_fim : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php
                            if ($fim < $total_paginas) {
                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                            }
                            ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $data_inicio ? '&data_inicio=' . $data_inicio : ''; ?><?php echo $data_fim ? '&data_fim=' . $data_fim : ''; ?>">
                                        Próxima
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $data_inicio ? '&data_inicio=' . $data_inicio : ''; ?><?php echo $data_fim ? '&data_fim=' . $data_fim : ''; ?>">
                                        Última
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Nenhum registro encontrado com os filtros atuais.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Obter conteúdo do buffer
$content = ob_get_clean();

// Incluir o layout
require_once '../includes/admin_layout.php';
?> 