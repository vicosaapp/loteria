<?php
session_start();
require_once '../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'usuarios';

// Busca usuários
try {
    // Modificada a consulta para usar apenas campos existentes
    $stmt = $pdo->query("
        SELECT 
            id,
            nome,
            email,
            telefone,
            tipo
        FROM usuarios 
        WHERE tipo = 'usuario' 
        ORDER BY nome
    ");
    $usuarios = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Erro ao buscar usuários: " . $e->getMessage());
}

// Inicia o buffer de saída
ob_start();
?>

<div class="page-header">
    <h1>Gerenciar Usuários</h1>
</div>

<div class="usuarios-container">
    <?php if(empty($usuarios)): ?>
        <div class="no-users">
            <i class="fas fa-users"></i>
            <p>Nenhum usuário cadastrado</p>
        </div>
    <?php else: ?>
        <table class="usuarios-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <?php if(!empty($usuario['telefone'])): ?>
                                <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $usuario['telefone']); ?>" 
                                   target="_blank" 
                                   class="btn-whatsapp" 
                                   title="Abrir WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                    <?php echo htmlspecialchars($usuario['telefone']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Não cadastrado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="editarUsuario(<?php echo $usuario['id']; ?>, 
                                '<?php echo htmlspecialchars($usuario['nome']); ?>', 
                                '<?php echo htmlspecialchars($usuario['email']); ?>',
                                '<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>')" 
                                class="btn-edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Modal de Edição -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Editar Usuário</h2>
            <span class="close">&times;</span>
        </div>
        <form id="editForm" onsubmit="salvarUsuario(event)">
            <input type="hidden" id="userId">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000">
            </div>
            <div class="form-group">
                <label for="senha">Nova Senha: (deixe em branco para manter a atual)</label>
                <input type="password" id="senha" name="senha">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
.usuarios-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.usuarios-table {
    width: 100%;
    border-collapse: collapse;
}

.usuarios-table th,
.usuarios-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.usuarios-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.btn-edit {
    padding: 6px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    background: #007bff;
    color: white;
}

.no-users {
    text-align: center;
    padding: 40px;
    color: #666;
}

.no-users i {
    font-size: 48px;
    margin-bottom: 10px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    width: 90%;
    max-width: 500px;
    margin: 50px auto;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close {
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.form-group {
    margin: 15px 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #666;
}

.form-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.btn-primary,
.btn-secondary {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 10px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-whatsapp {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: #25D366;
    color: white;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
}

.btn-whatsapp:hover {
    background: #128C7E;
}

.btn-whatsapp i {
    font-size: 16px;
}

.text-muted {
    color: #6c757d;
    font-style: italic;
}
</style>

<script>
function editarUsuario(id, nome, email, telefone) {
    document.getElementById('userId').value = id;
    document.getElementById('nome').value = nome;
    document.getElementById('email').value = email;
    document.getElementById('telefone').value = telefone || '';
    document.getElementById('senha').value = '';
    document.getElementById('editModal').style.display = 'block';
}

function fecharModal() {
    document.getElementById('editModal').style.display = 'none';
}

function salvarUsuario(event) {
    event.preventDefault();
    
    const id = document.getElementById('userId').value;
    const nome = document.getElementById('nome').value;
    const email = document.getElementById('email').value;
    const telefone = document.getElementById('telefone').value;
    const senha = document.getElementById('senha').value;
    
    fetch('ajax/salvar_usuario.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            nome: nome,
            email: email,
            telefone: telefone,
            senha: senha
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Usuário atualizado com sucesso!');
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao atualizar usuário');
        }
    });
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        fecharModal();
    }
}

// Fechar modal ao clicar no X
document.querySelector('.close').onclick = fecharModal;
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 