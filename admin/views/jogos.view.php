<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gerenciar Jogos</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalJogo">
        <i class="fas fa-plus"></i> Novo Jogo
    </button>
</div>

<!-- Lista de Jogos -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="tabelaJogos">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Total Números</th>
                        <th>Mín/Máx Apostas</th>
                        <th>Acertos Prêmio</th>
                        <th>Valor Aposta</th>
                        <th>Valor Prêmio</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jogos as $jogo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($jogo['nome']); ?></td>
                        <td><?php echo $jogo['numeros_total']; ?></td>
                        <td><?php echo $jogo['minimo_numeros']; ?>/<?php echo $jogo['maximo_numeros']; ?></td>
                        <td><?php echo $jogo['acertos_premio']; ?></td>
                        <td>R$ <?php echo number_format($jogo['valor_aposta'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($jogo['valor_premio'], 2, ',', '.'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $jogo['status'] == 'ativo' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($jogo['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="editarJogo(<?php echo $jogo['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="excluirJogo(<?php echo $jogo['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Novo/Editar Jogo -->
<div class="modal fade" id="modalJogo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalJogoTitle">Novo Jogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formJogo">
                    <input type="hidden" name="id" id="jogo_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Jogo</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Total de Números Disponíveis</label>
                            <input type="number" class="form-control" name="numeros_disponiveis" min="1" max="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Números para Prêmio</label>
                            <input type="number" class="form-control" name="numeros_premiacao" min="1" required>
                            <small class="text-muted">Quantidade necessária para ganhar</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mínimo de Números</label>
                            <input type="number" class="form-control" name="min_numeros" min="1" required>
                            <small class="text-muted">Mínimo por aposta</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Máximo de Números</label>
                            <input type="number" class="form-control" name="max_numeros" min="1" required>
                            <small class="text-muted">Máximo por aposta</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Valor da Aposta (R$)</label>
                            <input type="number" class="form-control" name="valor_aposta" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor do Prêmio (R$)</label>
                            <input type="number" class="form-control" name="valor_premio" min="0.01" step="0.01" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarJogo()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
function editarJogo(id) {
    fetch(`ajax/get_jogo.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const jogo = data.jogo;
                const form = document.getElementById('formJogo');
                
                form.querySelector('[name="id"]').value = jogo.id;
                form.querySelector('[name="nome"]').value = jogo.nome;
                form.querySelector('[name="descricao"]').value = jogo.descricao;
                form.querySelector('[name="numeros_disponiveis"]').value = jogo.numeros_disponiveis;
                form.querySelector('[name="numeros_premiacao"]').value = jogo.numeros_premiacao;
                form.querySelector('[name="min_numeros"]').value = jogo.min_numeros;
                form.querySelector('[name="max_numeros"]').value = jogo.max_numeros;
                form.querySelector('[name="valor_aposta"]').value = jogo.valor_aposta;
                form.querySelector('[name="valor_premio"]').value = jogo.valor_premio;
                form.querySelector('[name="status"]').value = jogo.status;

                document.getElementById('modalJogoTitle').textContent = 'Editar Jogo';
                const modal = new bootstrap.Modal(document.getElementById('modalJogo'));
                modal.show();
            }
        });
}

function salvarJogo() {
    const form = document.getElementById('formJogo');
    const formData = new FormData(form);

    // Validações adicionais
    const minNumeros = parseInt(formData.get('min_numeros'));
    const maxNumeros = parseInt(formData.get('max_numeros'));
    const numerosPremiacao = parseInt(formData.get('numeros_premiacao'));
    const numerosDisponiveis = parseInt(formData.get('numeros_disponiveis'));

    if (minNumeros > maxNumeros) {
        Swal.fire('Erro!', 'O mínimo de números não pode ser maior que o máximo', 'error');
        return;
    }

    if (maxNumeros > numerosDisponiveis) {
        Swal.fire('Erro!', 'O máximo de números não pode ser maior que o total disponível', 'error');
        return;
    }

    if (numerosPremiacao > maxNumeros) {
        Swal.fire('Erro!', 'Os números para premiação não podem ser maiores que o máximo permitido', 'error');
        return;
    }

    fetch('ajax/salvar_jogo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Jogo salvo com sucesso!',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Erro!', data.message, 'error');
        }
    });
}

function excluirJogo(id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: "Esta ação não poderá ser revertida!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`ajax/excluir_jogo.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire('Erro!', data.message, 'error');
                    }
                });
        }
    });
}
</script> 