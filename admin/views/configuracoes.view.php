<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Configurações do Sistema</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form id="formConfiguracoes" method="post">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Horário de Funcionamento</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Horário de Início</label>
                            <input type="time" name="horario_inicio" class="form-control" 
                                   value="<?php echo htmlspecialchars($config['horario_inicio'] ?? '08:00'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Horário de Término</label>
                            <input type="time" name="horario_fim" class="form-control" 
                                   value="<?php echo htmlspecialchars($config['horario_fim'] ?? '22:00'); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5 class="mb-3">Dias de Funcionamento</h5>
                    <div class="row g-3">
                        <?php
                        $dias = explode(',', $config['dias_funcionamento'] ?? '1,2,3,4,5,6,7');
                        $nomes_dias = [
                            1 => 'Domingo',
                            2 => 'Segunda',
                            3 => 'Terça',
                            4 => 'Quarta',
                            5 => 'Quinta',
                            6 => 'Sexta',
                            7 => 'Sábado'
                        ];
                        foreach ($nomes_dias as $num => $nome): ?>
                            <div class="col-auto">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="dias_funcionamento[]" value="<?php echo $num; ?>"
                                           <?php echo in_array($num, $dias) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo $nome; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="mb-3">Status do Sistema</h5>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status_sistema" 
                               value="ativo" <?php echo ($config['status_sistema'] ?? 'ativo') == 'ativo' ? 'checked' : ''; ?>>
                        <label class="form-check-label">Ativo</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status_sistema" 
                               value="manutencao" <?php echo ($config['status_sistema'] ?? '') == 'manutencao' ? 'checked' : ''; ?>>
                        <label class="form-check-label">Manutenção</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </form>
    </div>
</div>

<script>
document.getElementById('formConfiguracoes').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar dias selecionados
    const dias = document.querySelectorAll('input[name="dias_funcionamento[]"]:checked');
    if (dias.length === 0) {
        Swal.fire('Erro!', 'Selecione pelo menos um dia de funcionamento', 'error');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('ajax/salvar_configuracoes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Configurações salvas com sucesso!',
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            Swal.fire('Erro!', data.message, 'error');
        }
    });
});
</script> 