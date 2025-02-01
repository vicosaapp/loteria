<?php if (!defined('ADMIN')) exit; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-user-tie text-primary"></i> Gerenciar Revendedores</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoRevendedor">
        <i class="fas fa-plus"></i> Novo Revendedor
    </button>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Revendedores</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($revendedores); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Revendedores -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Revendedores Cadastrados</h6>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" id="searchRevendedor" class="form-control float-right" placeholder="Buscar revendedor...">
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
            <table class="table table-hover" id="tabelaRevendedores">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                        <th>Comissão</th>
                        <th>Cadastro</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revendedores as $revendedor): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-mini bg-primary rounded-circle me-2">
                                        <?php echo strtoupper(substr($revendedor['nome'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($revendedor['nome']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($revendedor['email']); ?></td>
                            <td>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $revendedor['whatsapp']); ?>" 
                                   class="btn btn-success btn-sm" target="_blank">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $revendedor['comissao']; ?>%"
                                         aria-valuenow="<?php echo $revendedor['comissao']; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo number_format($revendedor['comissao'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($revendedor['created_at'])); ?></td>
                            <td class="text-center">
                                <span class="badge bg-success">Ativo</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="editarRevendedor(<?php echo $revendedor['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" 
                                            onclick="verDetalhes(<?php echo $revendedor['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="excluirRevendedor(<?php echo $revendedor['id']; ?>)">
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

<!-- Modal Novo Revendedor -->
<div class="modal fade" id="modalNovoRevendedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Novo Revendedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="formRevendedor" class="row g-3">
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
                    
                    <div class="col-md-12">
                        <label class="form-label">Comissão (%)</label>
                        <input type="range" class="form-range" name="comissao" min="0" max="100" step="0.5" 
                               oninput="this.nextElementSibling.value = this.value + '%'">
                        <output>5%</output>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formRevendedor" class="btn btn-primary">
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

.border-left-primary {
    border-left: 4px solid #4e73df !important;
}

.progress {
    background-color: #eaecf4;
}

.progress-bar {
    background-color: #4e73df;
}

.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.table > :not(caption) > * > * {
    padding: 1rem 1rem;
}

.btn-group > .btn {
    margin: 0 2px;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Função para alternar visibilidade da senha
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

// Busca na tabela
document.getElementById('searchRevendedor').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const table = document.getElementById('tabelaRevendedores');
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

// Função para editar revendedor
function editarRevendedor(id) {
    fetch(`ajax/get_revendedor.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const revendedor = data.data;
                const form = document.getElementById('formRevendedor');
                
                // Limpar form e remover ID anterior se existir
                form.reset();
                const oldId = form.querySelector('input[name="id"]');
                if (oldId) oldId.remove();
                
                // Preencher dados
                form.querySelector('input[name="nome"]').value = revendedor.nome;
                form.querySelector('input[name="email"]').value = revendedor.email;
                form.querySelector('input[name="whatsapp"]').value = revendedor.whatsapp;
                form.querySelector('input[name="comissao"]').value = revendedor.comissao;
                form.querySelector('output').value = revendedor.comissao + '%';
                
                // Adicionar ID oculto
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = revendedor.id;
                form.appendChild(idInput);
                
                // Senha opcional na edição
                form.querySelector('input[name="senha"]').removeAttribute('required');
                
                // Atualizar título do modal
                const modalTitle = document.querySelector('#modalNovoRevendedor .modal-title');
                modalTitle.textContent = 'Editar Revendedor';
                
                // Abrir modal
                const modal = new bootstrap.Modal(document.getElementById('modalNovoRevendedor'));
                modal.show();
            } else {
                Swal.fire('Erro!', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Erro!', 'Erro ao carregar dados do revendedor', 'error');
        });
}

// Resetar formulário quando o modal for fechado
document.getElementById('modalNovoRevendedor').addEventListener('hidden.bs.modal', function () {
    const form = document.getElementById('formRevendedor');
    form.reset();
    form.querySelector('input[name="senha"]').setAttribute('required', 'required');
    const idInput = form.querySelector('input[name="id"]');
    if (idInput) idInput.remove();
    document.querySelector('#modalNovoRevendedor .modal-title').textContent = 'Novo Revendedor';
});

// Quando clicar no botão "Novo Revendedor"
document.querySelector('[data-bs-target="#modalNovoRevendedor"]').addEventListener('click', function() {
    const form = document.getElementById('formRevendedor');
    form.reset();
    const idInput = form.querySelector('input[name="id"]');
    if (idInput) idInput.remove();
    form.querySelector('input[name="senha"]').setAttribute('required', 'required');
    document.querySelector('#modalNovoRevendedor .modal-title').textContent = 'Novo Revendedor';
});

// Submissão do formulário
document.getElementById('formRevendedor').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('ajax/salvar_revendedor.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Revendedor salvo com sucesso!',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalNovoRevendedor')).hide();
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: data.message || 'Erro ao salvar revendedor'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Ocorreu um erro ao processar a requisição'
        });
    });
});
</script> 