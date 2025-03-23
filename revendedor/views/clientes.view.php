<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Meus Clientes</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
        <i class="fas fa-user-plus"></i> Novo Cliente
    </button>
</div>

<!-- Tabela de Clientes -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Clientes Cadastrados</h6>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" id="searchCliente" class="form-control float-right" placeholder="Buscar cliente...">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-default">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="tabelaClientes">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                        <th>Total Apostas</th>
                        <th>Total Apostado</th>
                        <th>Cadastro</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-mini bg-primary rounded-circle me-2">
                                        <?php echo strtoupper(substr($cliente['nome'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($cliente['nome']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $cliente['whatsapp']); ?>" 
                                   class="btn btn-success btn-sm" target="_blank">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </td>
                            <td><?php echo number_format($cliente['total_apostas']); ?></td>
                            <td>R$ <?php echo number_format($cliente['total_apostado'], 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cliente['created_at'])); ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="editarCliente(<?php echo $cliente['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="apostas.php?cliente_id=<?php echo $cliente['id']; ?>" 
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-ticket-alt"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="excluirCliente(<?php echo $cliente['id']; ?>)">
                                        <i class="fas fa-trash"></i>
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

<!-- Modal Novo/Editar Cliente -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Novo Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="formCliente" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="senha" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword(this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" class="form-control" name="whatsapp" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formCliente" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-mini {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}
</style>

<script>
// Busca na tabela
document.getElementById('searchCliente').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const table = document.getElementById('tabelaClientes');
    const rows = table.getElementsByTagName('tr');

    for (let row of rows) {
        const cells = row.getElementsByTagName('td');
        let found = false;
        for (let cell of cells) {
            if (cell.textContent.toLowerCase().indexOf(searchText) > -1) {
                found = true;
                break;
            }
        }
        row.style.display = found ? '' : 'none';
    }
});

// Toggle senha
function togglePassword(button) {
    const input = button.previousElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        button.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

// Editar cliente
function editarCliente(id) {
    fetch(`ajax/get_cliente.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cliente = data.data;
                const form = document.getElementById('formCliente');
                
                form.reset();
                const oldId = form.querySelector('input[name="id"]');
                if (oldId) oldId.remove();
                
                form.querySelector('input[name="nome"]').value = cliente.nome;
                form.querySelector('input[name="email"]').value = cliente.email;
                form.querySelector('input[name="whatsapp"]').value = cliente.whatsapp;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = cliente.id;
                form.appendChild(idInput);
                
                form.querySelector('input[name="senha"]').removeAttribute('required');
                
                document.querySelector('#modalNovoCliente .modal-title').textContent = 'Editar Cliente';
                
                const modal = new bootstrap.Modal(document.getElementById('modalNovoCliente'));
                modal.show();
            } else {
                Swal.fire('Erro!', data.message, 'error');
            }
        });
}

// Excluir cliente
function excluirCliente(id) {
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
            fetch('ajax/excluir_cliente.php', {
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
                        'Excluído!',
                        'Cliente excluído com sucesso.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire(
                        'Erro!',
                        data.message,
                        'error'
                    );
                }
            });
        }
    });
}

// Submissão do formulário
document.getElementById('formCliente').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('ajax/salvar_cliente.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Resposta do servidor:', data);
        
        if (data.success) {
            // Exibir mensagem de sucesso
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Cliente salvo com sucesso!',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // Fechar o modal e recarregar a página
                const modalEl = document.getElementById('modalNovoCliente');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                location.reload();
            });
        } else {
            // Exibir mensagem de erro
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: data.message || 'Erro ao salvar cliente'
            });
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        
        // Exibir mensagem de erro em caso de falha na requisição
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Ocorreu um erro na comunicação com o servidor. Tente novamente.'
        });
    });
});

// Reset do formulário ao fechar modal
document.getElementById('modalNovoCliente').addEventListener('hidden.bs.modal', function () {
    const form = document.getElementById('formCliente');
    form.reset();
    form.querySelector('input[name="senha"]').setAttribute('required', 'required');
    const idInput = form.querySelector('input[name="id"]');
    if (idInput) idInput.remove();
    document.querySelector('#modalNovoCliente .modal-title').textContent = 'Novo Cliente';
});
</script> 