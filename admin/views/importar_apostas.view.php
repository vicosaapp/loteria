<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Importar Apostas</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <form id="formImportacao">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Apostador</label>
                    <input type="text" class="form-control" name="nome_apostador" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" name="whatsapp_apostador" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Revendedor</label>
                    <select class="form-select" name="revendedor_id">
                        <option value="">Selecione um revendedor (opcional)</option>
                        <?php foreach ($revendedores as $revendedor): ?>
                            <option value="<?php echo $revendedor['id']; ?>">
                                <?php echo htmlspecialchars($revendedor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valor por Aposta</label>
                    <input type="number" class="form-control" name="valor_aposta" required min="1" step="0.01">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Cole as apostas aqui</label>
                <textarea class="form-control" name="apostas_texto" rows="10" required 
                          placeholder="Exemplo:&#10;Loterias Mobile: LF&#10;&#10;01 02 03 04 05 07 08 09 11 12 13 15 16 18 19 21 22 23&#10;01 03 05 06 07 08 09 10 12 13 14 16 18 19 20 22 23 24"></textarea>
            </div>

            <div class="alert alert-info" id="previewApostas" style="display: none;">
                <!-- Preview será mostrado aqui -->
            </div>

            <button type="button" class="btn btn-secondary me-2" onclick="previewApostas()">
                <i class="fas fa-eye"></i> Visualizar
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Apostas
            </button>
        </form>
    </div>
</div>

<script>
function previewApostas() {
    const texto = document.querySelector('[name="apostas_texto"]').value;
    const preview = document.getElementById('previewApostas');
    
    try {
        const resultado = processarTextoApostas(texto);
        
        if (!resultado.jogo) {
            throw new Error('Não foi possível identificar o jogo');
        }
        
        let html = `<h5>Preview das Apostas</h5>`;
        html += `<p><strong>Jogo:</strong> ${resultado.jogo}</p>`;
        html += `<p><strong>Total de Apostas:</strong> ${resultado.apostas.length}</p>`;
        html += `<p><strong>Números por Aposta:</strong> ${resultado.apostas[0].length}</p>`;
        
        html += '<div class="table-responsive"><table class="table table-sm">';
        html += '<thead><tr><th>Aposta</th><th>Números</th></tr></thead><tbody>';
        
        resultado.apostas.forEach((aposta, index) => {
            html += `<tr>
                <td>#${index + 1}</td>
                <td>${aposta.join(', ')}</td>
            </tr>`;
        });
        
        html += '</tbody></table></div>';
        
        preview.innerHTML = html;
        preview.style.display = 'block';
    } catch (error) {
        Swal.fire('Erro!', error.message, 'error');
        preview.style.display = 'none';
    }
}

function processarTextoApostas(texto) {
    // Dividir o texto em linhas
    const linhas = texto.trim().split('\n');
    
    // Primeira linha deve conter o nome do jogo
    const primeiraLinha = linhas[0].trim();
    const match = primeiraLinha.match(/Loterias Mobile:\s*(\w+)/i);
    if (!match) {
        throw new Error('Formato inválido. A primeira linha deve conter "Loterias Mobile: [NOME_DO_JOGO]"');
    }
    
    const nomeJogo = match[1];
    
    // Processar as apostas (ignorando linhas vazias)
    const apostas = linhas.slice(1)
        .map(linha => linha.trim())
        .filter(linha => linha.length > 0)
        .map(linha => {
            const numeros = linha.split(/\s+/).map(n => parseInt(n, 10));
            
            // Validar números
            if (numeros.some(n => isNaN(n))) {
                throw new Error('Formato inválido. Cada linha deve conter apenas números separados por espaço');
            }
            
            return numeros;
        });
    
    // Validar se todas as apostas têm o mesmo número de dezenas
    const numerosDezenas = apostas[0].length;
    if (apostas.some(aposta => aposta.length !== numerosDezenas)) {
        throw new Error('Todas as apostas devem ter o mesmo número de dezenas');
    }
    
    return {
        jogo: nomeJogo,
        apostas: apostas
    };
}

document.getElementById('formImportacao').addEventListener('submit', function(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(this);
        const resultado = processarTextoApostas(formData.get('apostas_texto'));
        
        // Adicionar dados processados ao FormData
        formData.append('jogo', resultado.jogo);
        formData.append('apostas', JSON.stringify(resultado.apostas));
        
        fetch('ajax/salvar_importacao.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: `${resultado.apostas.length} apostas foram importadas com sucesso!`,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            Swal.fire('Erro!', error.message, 'error');
        });
    } catch (error) {
        Swal.fire('Erro!', error.message, 'error');
    }
});
</script> 