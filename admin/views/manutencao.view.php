<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gerenciamento de Manutenção</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Manutenção</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Status da Manutenção -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Status do Sistema</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-<?php echo $modo_manutencao ? 'warning' : 'success'; ?>">
                                            <i class="fas <?php echo $modo_manutencao ? 'fa-tools' : 'fa-check-circle'; ?>"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Status do Sistema</span>
                                            <span class="info-box-number" id="status-sistema">
                                                <?php echo $modo_manutencao ? 'Em Manutenção' : 'Operacional'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="btn-group">
                                        <button type="button" id="ativar-manutencao" class="btn btn-warning <?php echo $modo_manutencao ? 'disabled' : ''; ?>">
                                            <i class="fas fa-tools"></i> Ativar Manutenção
                                        </button>
                                        <button type="button" id="desativar-manutencao" class="btn btn-success <?php echo !$modo_manutencao ? 'disabled' : ''; ?>">
                                            <i class="fas fa-check"></i> Desativar Manutenção
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensagem de Manutenção -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Mensagem de Manutenção</h3>
                        </div>
                        <div class="card-body">
                            <form id="form-mensagem-manutencao">
                                <div class="form-group">
                                    <label for="mensagem">Mensagem a ser exibida durante a manutenção:</label>
                                    <textarea class="form-control" id="mensagem-manutencao" name="mensagem" rows="4"><?php echo htmlspecialchars($mensagem_manutencao); ?></textarea>
                                    <small class="form-text text-muted">Esta mensagem será exibida para os usuários durante o período de manutenção.</small>
                                </div>
                                <button type="submit" class="btn btn-primary">Atualizar Mensagem</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Histórico de Acessos em Manutenção -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Histórico de Tentativas de Acesso</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabela-logs" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tipo</th>
                                            <th>Usuário</th>
                                            <th>IP</th>
                                            <th>Data</th>
                                            <th>URL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs_manutencao as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td><?php echo $log['tipo_usuario']; ?></td>
                                            <td><?php echo $log['usuario_id'] ? $log['usuario_id'] : 'Não autenticado'; ?></td>
                                            <td><?php echo $log['ip']; ?></td>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($log['data_acesso'])); ?></td>
                                            <td><?php echo $log['url_acessada']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(function() {
    // Inicializar DataTable
    $('#tabela-logs').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
        },
        "order": [[4, 'desc']]
    });

    // Ativar manutenção
    $('#ativar-manutencao').on('click', function() {
        if (confirm('Tem certeza que deseja ativar o modo de manutenção? Todos os usuários serão desconectados.')) {
            $.ajax({
                url: 'atualizar_manutencao.php',
                type: 'POST',
                data: {
                    acao: 'ativar'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Erro ao processar a requisição');
                }
            });
        }
    });

    // Desativar manutenção
    $('#desativar-manutencao').on('click', function() {
        $.ajax({
            url: 'atualizar_manutencao.php',
            type: 'POST',
            data: {
                acao: 'desativar'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Erro ao processar a requisição');
            }
        });
    });

    // Atualizar mensagem de manutenção
    $('#form-mensagem-manutencao').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'atualizar_manutencao.php',
            type: 'POST',
            data: {
                acao: 'atualizar_mensagem',
                mensagem: $('#mensagem-manutencao').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Erro ao processar a requisição');
            }
        });
    });
});
</script> 