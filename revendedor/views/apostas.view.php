<!-- Estilos -->
<style>
/* Estilo para as bolinhas de números */
.numero-bolinha {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 28px !important;
    height: 28px !important;
    min-width: 28px !important;
    background-color: #4e73df !important;
    color: white !important;
    border-radius: 50% !important;
    margin: 2px !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    padding: 0 !important;
    line-height: 1 !important;
    text-align: center !important;
}

.numeros-container {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 3px !important;
    padding: 4px !important;
    max-width: 100% !important;
    margin: 0 !important;
    min-width: 200px !important;
}

/* Ajustes na tabela */
.table td {
    vertical-align: middle !important;
    padding: 0.75rem !important;
}

.table th {
    white-space: nowrap !important;
}

/* Coluna de números mais larga */
.table th:nth-child(4),
.table td:nth-child(4) {
    min-width: 280px !important;
    max-width: 400px !important;
}
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Apostas</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaAposta">
        <i class="fas fa-plus"></i> Nova Aposta
    </button>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?php echo $cliente['id']; ?>" 
                                <?php echo ($cliente_id == $cliente['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cliente['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="pendente" <?php echo ($status == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                    <option value="aprovada" <?php echo ($status == 'aprovada') ? 'selected' : ''; ?>>Aprovada</option>
                    <option value="rejeitada" <?php echo ($status == 'rejeitada') ? 'selected' : ''; ?>>Rejeitada</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="apostas.php" class="btn btn-secondary">
                    <i class="fas fa-eraser"></i> Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Apostas -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Jogo</th>
                        <th>Números</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apostas as $aposta): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($aposta['nome_apostador']); ?></td>
                            <td><?php echo htmlspecialchars($aposta['nome_jogo']); ?></td>
                            <td>
                                <div class="numeros-container">
                                    <?php 
                                    $numeros = explode(',', $aposta['numeros']);
                                    sort($numeros); // Ordena os números
                                    foreach ($numeros as $numero) {
                                        $num = str_pad(trim($numero), 2, '0', STR_PAD_LEFT);
                                        echo "<span class='numero-bolinha'>$num</span>";
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="fw-bold">R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                            <td>
                                <?php
                                $statusClass = [
                                    'pendente' => 'warning',
                                    'aprovada' => 'success',
                                    'rejeitada' => 'danger'
                                ][$aposta['status']];
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo ucfirst($aposta['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info btn-sm" 
                                            onclick="verDetalhes(<?php echo $aposta['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($aposta['status'] == 'pendente'): ?>
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="solicitarAprovacao(<?php echo $aposta['id']; ?>)">
                                            <i class="fas fa-clock"></i> Solicitar Aprovação
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalhes da Aposta -->
<div class="modal fade" id="modalDetalhesAposta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Aposta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalhesAposta"></div>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalhes(id) {
    fetch(`ajax/get_aposta.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const aposta = data.data;
                document.getElementById('detalhesAposta').innerHTML = `
                    <dl class="row">
                        <dt class="col-sm-4">Cliente</dt>
                        <dd class="col-sm-8">${aposta.nome_apostador}</dd>
                        
                        <dt class="col-sm-4">Jogo</dt>
                        <dd class="col-sm-8">${aposta.nome_jogo}</dd>
                        
                        <dt class="col-sm-4">Números</dt>
                        <dd class="col-sm-8">${aposta.numeros}</dd>
                        
                        <dt class="col-sm-4">Valor</dt>
                        <dd class="col-sm-8">R$ ${parseFloat(aposta.valor_aposta).toFixed(2)}</dd>
                        
                        <dt class="col-sm-4">Data</dt>
                        <dd class="col-sm-8">${new Date(aposta.created_at).toLocaleString()}</dd>
                        
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-${
                                aposta.status === 'aprovada' ? 'success' : 
                                (aposta.status === 'rejeitada' ? 'danger' : 'warning')
                            }">
                                ${aposta.status.charAt(0).toUpperCase() + aposta.status.slice(1)}
                            </span>
                        </dd>
                    </dl>
                `;
                
                new bootstrap.Modal(document.getElementById('modalDetalhesAposta')).show();
            } else {
                Swal.fire('Erro!', data.message, 'error');
            }
        });
}

function solicitarAprovacao(id) {
    Swal.fire({
        title: 'Solicitar Aprovação',
        text: "Deseja solicitar a aprovação desta aposta ao administrador?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, solicitar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/solicitar_aprovacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'Solicitado!',
                        'A solicitação de aprovação foi enviada com sucesso.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erro!', data.message, 'error');
                }
            });
        }
    });
}
</script> 