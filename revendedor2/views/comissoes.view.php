<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Minhas Comissões</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarPDF()">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarExcel()">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Taxa de Comissão</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($taxa_comissao, 1); ?>%
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percent fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total em Comissões</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            R$ <?php echo number_format($total_geral_comissao, 2, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total de Apostas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_geral_apostas); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Total Apostado</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            R$ <?php echo number_format($total_geral_apostado, 2, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
            </div>
            
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="comissoes.php" class="btn btn-secondary">
                    <i class="fas fa-eraser"></i> Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Comissões -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Comissões por Dia</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Total Apostas</th>
                        <th>Total Apostado</th>
                        <th>Comissão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comissoes_diarias as $comissao): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($comissao['data'])); ?></td>
                            <td><?php echo number_format($comissao['total_apostas']); ?></td>
                            <td>R$ <?php echo number_format($comissao['total_apostado'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($comissao['total_comissao'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <th>Total Geral</th>
                        <th><?php echo number_format($total_geral_apostas); ?></th>
                        <th>R$ <?php echo number_format($total_geral_apostado, 2, ',', '.'); ?></th>
                        <th>R$ <?php echo number_format($total_geral_comissao, 2, ',', '.'); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }
.border-left-warning { border-left: 4px solid #f6c23e !important; }

.text-gray-300 { color: #dddfeb !important; }
.text-gray-800 { color: #5a5c69 !important; }
</style>

<script>
function exportarPDF() {
    // Implementar exportação PDF
    Swal.fire({
        icon: 'info',
        title: 'Em desenvolvimento',
        text: 'A exportação para PDF será implementada em breve!'
    });
}

function exportarExcel() {
    // Implementar exportação Excel
    Swal.fire({
        icon: 'info',
        title: 'Em desenvolvimento',
        text: 'A exportação para Excel será implementada em breve!'
    });
}
</script> 