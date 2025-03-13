<?php
require_once 'includes/header.php';
require_once '../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'configuracoes';

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['salvar_pausa'])) {
            $status_apostas = isset($_POST['status_apostas']) ? 1 : 0;
            $horario_inicio = $_POST['horario_inicio'];
            $horario_fim = $_POST['horario_fim'];
            $dias_semana = isset($_POST['dias_semana']) ? implode(',', $_POST['dias_semana']) : '';
            $motivo_pausa = $_POST['motivo_pausa'];

            // Atualizar configurações
            $stmt = $pdo->prepare("
                UPDATE configuracoes SET 
                    status_apostas = ?,
                    horario_inicio = ?,
                    horario_fim = ?,
                    dias_semana = ?,
                    motivo_pausa = ?
                WHERE id = 1
            ");

            $stmt->execute([
                $status_apostas,
                $horario_inicio,
                $horario_fim,
                $dias_semana,
                $motivo_pausa
            ]);

            $mensagem = "Configurações de pausa salvas com sucesso!";
            $tipo_mensagem = "success";
        }
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar as configurações: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar configurações atuais
$stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Array com os dias da semana
$dias_semana = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado'
];

// Converter string de dias em array
$dias_selecionados = !empty($config['dias_semana']) ? explode(',', $config['dias_semana']) : [];
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-pause-circle"></i> Controle de Pausas</h1>
            <p>Configure as pausas automáticas do sistema</p>
        </div>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?>" role="alert">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="card">
            <div class="card-header">
                <h3>Controle de Pausas nas Apostas</h3>
            </div>
            <div class="card-body">
                <div class="form-section">
                    <div class="form-group">
                        <label class="control-label">Status das Apostas</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="status_apostas" 
                                   name="status_apostas" <?php echo ($config['status_apostas'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="status_apostas">
                                <span class="status-text">
                                    <?php echo ($config['status_apostas'] ?? 1) ? 'Apostas Liberadas' : 'Apostas Pausadas'; ?>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Horário de Início da Pausa</label>
                            <input type="time" class="form-control" name="horario_inicio" 
                                   value="<?php echo $config['horario_inicio'] ?? '23:00'; ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Horário de Fim da Pausa</label>
                            <input type="time" class="form-control" name="horario_fim" 
                                   value="<?php echo $config['horario_fim'] ?? '06:00'; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Dias da Semana para Pausa</label>
                        <div class="dias-semana-grid">
                            <?php foreach ($dias_semana as $valor => $dia): ?>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="dia_<?php echo $valor; ?>" 
                                           name="dias_semana[]" 
                                           value="<?php echo $valor; ?>"
                                           <?php echo in_array($valor, $dias_selecionados) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="dia_<?php echo $valor; ?>">
                                        <?php echo $dia; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Motivo da Pausa</label>
                        <textarea class="form-control" name="motivo_pausa" rows="3" 
                                  placeholder="Informe o motivo da pausa que será exibido aos usuários"><?php echo $config['motivo_pausa'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="salvar_pausa" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </div>
    </form>
</div>

<style>
.card {
    margin: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
}

.card-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.card-body {
    padding: 20px;
}

.form-section {
    background: #f8f9fc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #4e73df;
    margin-bottom: 8px;
    display: block;
}

.custom-switch {
    padding-left: 2.25rem;
}

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #1cc88a;
    border-color: #1cc88a;
}

.status-text {
    font-weight: 600;
    margin-left: 10px;
}

.dias-semana-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.form-actions {
    margin: 20px;
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.btn-primary {
    background: #4e73df;
    border: none;
    padding: 12px 30px;
    border-radius: 5px;
    color: white;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #2e59d9;
    transform: translateY(-1px);
}

.alert {
    margin: 20px;
    border-radius: 10px;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar texto do status quando mudar
    const statusSwitch = document.getElementById('status_apostas');
    const statusText = document.querySelector('.status-text');
    
    statusSwitch.addEventListener('change', function() {
        statusText.textContent = this.checked ? 'Apostas Liberadas' : 'Apostas Pausadas';
    });
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 