<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo "Acesso não autorizado";
    exit;
}

// Função para formatar texto SQL
function formatarSQL($sql) {
    return highlight_string("<?php\n" . $sql . "\n?>", true);
}

// Inicializar variáveis
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$mensagem = '';
$tipo_mensagem = '';
$resultados = [];

// Executar ação solicitada
if ($acao == 'diagnostico') {
    try {
        // Verificar conexão com o banco
        if (!$pdo) {
            throw new Exception("Falha na conexão com o banco de dados");
        }

        // Listar tabelas
        $tabelas = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tabelas[] = $row[0];
        }
        $resultados['tabelas'] = $tabelas;

        // Verificar tabelas necessárias
        $tabelas_necessarias = ['concursos', 'numeros_sorteados', 'jogos'];
        $tabelas_faltantes = [];
        foreach ($tabelas_necessarias as $tabela) {
            if (!in_array($tabela, $tabelas)) {
                $tabelas_faltantes[] = $tabela;
            }
        }
        $resultados['tabelas_faltantes'] = $tabelas_faltantes;

        // Verificar existência de jogos
        if (in_array('jogos', $tabelas)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM jogos");
            $resultados['total_jogos'] = $stmt->fetchColumn();
        } else {
            $resultados['total_jogos'] = 0;
        }

        $mensagem = "Diagnóstico concluído com sucesso";
        $tipo_mensagem = "success";
    } catch (Exception $e) {
        $mensagem = "Erro ao realizar diagnóstico: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
} elseif ($acao == 'criar_tabelas') {
    try {
        $url = 'ajax/criar_tabelas_resultado.php';
        $resultados = json_decode(file_get_contents($url), true);
        
        $mensagem = "Criação de tabelas concluída: " . $resultados['message'];
        $tipo_mensagem = ($resultados['status'] == 'success') ? "success" : "danger";
    } catch (Exception $e) {
        $mensagem = "Erro ao criar tabelas: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
} elseif ($acao == 'testar_formulario') {
    // Testar a funcionalidade de formulário diretamente
    $html_teste = true;
} elseif ($acao == 'salvar_teste') {
    // Processar o formulário de teste
    try {
        // Preparar dados
        $jogo_id = filter_input(INPUT_POST, 'jogo_id', FILTER_VALIDATE_INT);
        $concurso = filter_input(INPUT_POST, 'concurso', FILTER_VALIDATE_INT);
        $numeros = explode(',', $_POST['numeros']);
        $data_sorteio = date('Y-m-d H:i:s');
        $valor_acumulado = 0;
        $data_proximo = date('Y-m-d H:i:s', strtotime('+7 days'));
        $valor_estimado = 0;
        
        // Validar números
        if (empty($numeros)) {
            throw new Exception('Nenhum número selecionado');
        }
        
        foreach ($numeros as $numero) {
            if (!is_numeric($numero) || intval($numero) <= 0) {
                throw new Exception('Números inválidos detectados');
            }
        }
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Inserir novo concurso
        $stmt = $pdo->prepare("
            INSERT INTO concursos (
                jogo_id,
                codigo,
                data_sorteio,
                valor_acumulado,
                data_proximo_concurso,
                valor_estimado_proximo,
                status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'finalizado', NOW(), NOW())
        ");
        
        $stmt->execute([
            $jogo_id,
            $concurso,
            $data_sorteio,
            $valor_acumulado,
            $data_proximo,
            $valor_estimado
        ]);
        
        $concurso_id = $pdo->lastInsertId();
        
        // Inserir números sorteados
        $stmt = $pdo->prepare("INSERT INTO numeros_sorteados (concurso_id, numero) VALUES (?, ?)");
        foreach ($numeros as $numero) {
            $stmt->execute([$concurso_id, intval($numero)]);
        }
        
        // Atualizar informações do jogo
        $stmt = $pdo->prepare("
            UPDATE jogos 
            SET 
                numero_concurso = ?,
                valor_acumulado = ?,
                data_proximo_concurso = ?,
                valor_estimado_proximo = ?,
                data_atualizacao = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $concurso,
            $valor_acumulado,
            $data_proximo,
            $valor_estimado,
            $jogo_id
        ]);
        
        // Confirmar transação
        $pdo->commit();
        
        $mensagem = "Teste de inserção concluído com sucesso! Concurso ID: " . $concurso_id;
        $tipo_mensagem = "success";
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $mensagem = "Erro ao inserir teste: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar jogos para o formulário de teste
$jogos = [];
try {
    $stmt = $pdo->query("SELECT id, nome, quantidade_dezenas FROM jogos ORDER BY nome");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silenciar erro
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Resultados - Loteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Ferramenta de Diagnóstico - Resultados</h1>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Ferramentas de Diagnóstico</h5>
            </div>
            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <a href="?acao=diagnostico" class="btn btn-outline-primary">
                        <i class="fas fa-search me-2"></i>Realizar Diagnóstico
                    </a>
                    <a href="?acao=criar_tabelas" class="btn btn-outline-success">
                        <i class="fas fa-database me-2"></i>Criar/Atualizar Tabelas
                    </a>
                    <a href="?acao=testar_formulario" class="btn btn-outline-warning">
                        <i class="fas fa-vial me-2"></i>Testar Formulário Direto
                    </a>
                    <a href="ajax/diagnostico_db.php" target="_blank" class="btn btn-outline-info">
                        <i class="fas fa-code me-2"></i>Ver Diagnóstico JSON
                    </a>
                </div>
                
                <?php if ($acao == 'diagnostico' && !empty($resultados)): ?>
                    <div class="alert alert-info">
                        <h5>Resultado do Diagnóstico:</h5>
                        <ul>
                            <li><strong>Tabelas encontradas:</strong> <?php echo count($resultados['tabelas']); ?></li>
                            <li><strong>Tabelas faltantes:</strong> <?php echo empty($resultados['tabelas_faltantes']) ? 'Nenhuma' : implode(', ', $resultados['tabelas_faltantes']); ?></li>
                            <li><strong>Total de jogos cadastrados:</strong> <?php echo $resultados['total_jogos']; ?></li>
                        </ul>
                        
                        <?php if (!empty($resultados['tabelas_faltantes'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Tabelas necessárias estão faltando. Use a opção "Criar/Atualizar Tabelas" para corrigir.
                            </div>
                        <?php elseif ($resultados['total_jogos'] == 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Não há jogos cadastrados. Use a opção "Criar/Atualizar Tabelas" para inserir dados iniciais.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                A estrutura do banco de dados parece estar correta.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($html_teste)): ?>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Formulário de Teste Direto</h5>
                        </div>
                        <div class="card-body">
                            <form action="?acao=salvar_teste" method="post" id="formTesteDireto">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="jogo_id" class="form-label">Jogo</label>
                                        <select class="form-select" id="jogo_id" name="jogo_id" required>
                                            <option value="">Selecione o jogo</option>
                                            <?php foreach ($jogos as $jogo): ?>
                                                <option value="<?php echo $jogo['id']; ?>" data-dezenas="<?php echo $jogo['quantidade_dezenas']; ?>">
                                                    <?php echo htmlspecialchars($jogo['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="concurso" class="form-label">Número do Concurso</label>
                                        <input type="number" class="form-control" id="concurso" name="concurso" value="1" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Números (separados por vírgula)</label>
                                    <input type="text" class="form-control" id="numeros" name="numeros" required>
                                    <div class="form-text">
                                        Exemplo para Mega-Sena: 1,2,3,4,5,6
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Salvar Teste</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <h5 class="mt-4">Logs de erro recentes:</h5>
                <div class="bg-dark text-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                    <?php
                    $log_path = ini_get('error_log');
                    if (file_exists($log_path)) {
                        $logs = [];
                        $handle = fopen($log_path, "r");
                        if ($handle) {
                            $i = 0;
                            $found = false;
                            while (($line = fgets($handle)) !== false && $i < 100) {
                                if (strpos($line, 'salvar_resultado') !== false || 
                                    strpos($line, 'erro') !== false || 
                                    strpos($line, 'Erro') !== false || 
                                    strpos($line, 'POST data') !== false) {
                                    echo htmlspecialchars($line) . "<br>";
                                    $found = true;
                                    $i++;
                                }
                            }
                            fclose($handle);
                            
                            if (!$found) {
                                echo "<em>Nenhum log relevante encontrado.</em>";
                            }
                        } else {
                            echo "<em>Não foi possível ler o arquivo de log.</em>";
                        }
                    } else {
                        echo "<em>Arquivo de log não encontrado em: " . htmlspecialchars($log_path) . "</em>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-code me-2"></i>Testar JavaScript</h5>
            </div>
            <div class="card-body">
                <p>Use o console do navegador (F12) para ver os logs e depurar o JavaScript.</p>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" id="btnTestarFetch">
                        Testar Fetch para salvar_resultado.php
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnTestarFormData">
                        Testar FormData
                    </button>
                    <button type="button" class="btn btn-outline-success" id="btnTestarEventos">
                        Testar Eventos
                    </button>
                </div>
                
                <div class="mt-3" id="divResultadoTeste"></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Botão para testar fetch
            const btnTestarFetch = document.getElementById('btnTestarFetch');
            if (btnTestarFetch) {
                btnTestarFetch.addEventListener('click', function() {
                    console.log('Testando fetch para ajax/salvar_resultado.php...');
                    
                    const divResultado = document.getElementById('divResultadoTeste');
                    divResultado.innerHTML = '<div class="alert alert-info">Testando conexão...</div>';
                    
                    // Criar dados de teste
                    const formData = new FormData();
                    formData.append('jogo_id', '1');
                    formData.append('concurso', '999');
                    formData.append('numeros', '1,2,3,4,5,6');
                    formData.append('data_sorteio', '2023-01-01 00:00:00');
                    formData.append('teste', 'true');
                    
                    // Fazer requisição usando fetch
                    fetch('ajax/salvar_resultado.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Resposta do fetch:', response);
                        return response.text(); // Obter texto completo para diagnóstico
                    })
                    .then(text => {
                        console.log('Texto da resposta:', text);
                        
                        try {
                            const data = JSON.parse(text);
                            console.log('Dados JSON:', data);
                            
                            if (data.success) {
                                divResultado.innerHTML = '<div class="alert alert-success">Conexão com sucesso! Resposta: ' + 
                                    JSON.stringify(data) + '</div>';
                            } else {
                                divResultado.innerHTML = '<div class="alert alert-warning">Erro recebido: ' + 
                                    data.message + '</div>';
                            }
                        } catch (e) {
                            divResultado.innerHTML = '<div class="alert alert-danger">Erro ao analisar resposta como JSON. Resposta completa:<br>' + 
                                '<pre>' + text + '</pre></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Erro na requisição:', error);
                        divResultado.innerHTML = '<div class="alert alert-danger">Erro na requisição: ' + error.message + '</div>';
                        
                        // Tentar com caminho absoluto
                        divResultado.innerHTML += '<div class="alert alert-info">Tentando com caminho absoluto...</div>';
                        
                        fetch('/revendedor/ajax/salvar_resultado.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('Resposta do segundo fetch:', response);
                            return response.text();
                        })
                        .then(text => {
                            console.log('Texto da resposta (2):', text);
                            
                            try {
                                const data = JSON.parse(text);
                                console.log('Dados JSON (2):', data);
                                
                                if (data.success) {
                                    divResultado.innerHTML += '<div class="alert alert-success">Segundo teste com sucesso!</div>';
                                } else {
                                    divResultado.innerHTML += '<div class="alert alert-warning">Erro no segundo teste: ' + 
                                        data.message + '</div>';
                                }
                            } catch (e) {
                                divResultado.innerHTML += '<div class="alert alert-danger">Erro ao analisar segunda resposta. Texto completo:<br>' + 
                                    '<pre>' + text + '</pre></div>';
                            }
                        })
                        .catch(error2 => {
                            console.error('Erro na segunda requisição:', error2);
                            divResultado.innerHTML += '<div class="alert alert-danger">Erro na segunda requisição: ' + 
                                error2.message + '</div>';
                        });
                    });
                });
            }
            
            // Botão para testar FormData
            const btnTestarFormData = document.getElementById('btnTestarFormData');
            if (btnTestarFormData) {
                btnTestarFormData.addEventListener('click', function() {
                    console.log('Testando FormData...');
                    
                    const divResultado = document.getElementById('divResultadoTeste');
                    divResultado.innerHTML = '<div class="alert alert-info">Testando FormData...</div>';
                    
                    // Criar FormData
                    const formData = new FormData();
                    formData.append('jogo_id', '1');
                    formData.append('concurso', '999');
                    formData.append('numeros', '1,2,3,4,5,6');
                    
                    // Verificar conteúdo
                    const formEntries = [];
                    for (const pair of formData.entries()) {
                        formEntries.push(pair[0] + ': ' + pair[1]);
                    }
                    
                    divResultado.innerHTML = '<div class="alert alert-success">FormData criado com sucesso!<br>' + 
                        '<pre>' + formEntries.join('\n') + '</pre></div>';
                    
                    console.log('FormData criado:', Object.fromEntries(formData));
                });
            }
            
            // Botão para testar eventos
            const btnTestarEventos = document.getElementById('btnTestarEventos');
            if (btnTestarEventos) {
                btnTestarEventos.addEventListener('click', function() {
                    console.log('Testando eventos...');
                    
                    const divResultado = document.getElementById('divResultadoTeste');
                    divResultado.innerHTML = '<div class="alert alert-info">Testando eventos...</div>';
                    
                    // Criar um botão temporário
                    const btnTemp = document.createElement('button');
                    btnTemp.className = 'btn btn-primary';
                    btnTemp.id = 'btnTeste';
                    btnTemp.textContent = 'Botão de Teste';
                    btnTemp.style.display = 'none';
                    document.body.appendChild(btnTemp);
                    
                    // Adicionar evento ao botão
                    btnTemp.addEventListener('click', function() {
                        console.log('Botão de teste clicado!');
                    });
                    
                    // Simular clique
                    btnTemp.click();
                    
                    // Remover botão
                    document.body.removeChild(btnTemp);
                    
                    divResultado.innerHTML = '<div class="alert alert-success">Teste de eventos concluído! Veja o console.</div>';
                });
            }
            
            // Manipular formulário de teste direto
            const formTesteDireto = document.getElementById('formTesteDireto');
            if (formTesteDireto) {
                // Quando o jogo mudar, atualizar exemplo
                const selectJogo = document.getElementById('jogo_id');
                const inputNumeros = document.getElementById('numeros');
                
                selectJogo.addEventListener('change', function() {
                    const option = this.options[this.selectedIndex];
                    if (option && option.value) {
                        const qtdDezenas = option.getAttribute('data-dezenas') || 6;
                        
                        // Gerar exemplo de números
                        const numeros = [];
                        for (let i = 1; i <= qtdDezenas; i++) {
                            numeros.push(i);
                        }
                        
                        inputNumeros.placeholder = numeros.join(',');
                        document.querySelector('.form-text').textContent = 
                            `Exemplo para ${option.textContent.trim()}: ${numeros.join(',')}`;
                    }
                });
            }
        });
    </script>
</body>
</html> 