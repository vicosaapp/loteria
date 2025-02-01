<!-- Modal de Resultado -->
<div id="resultadoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Adicionar Resultado</h2>
            <button type="button" class="close" onclick="fecharModalResultado()">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="resultadoForm" onsubmit="salvarResultado(event)">
                <input type="hidden" id="jogoId">
                
                <div class="form-group">
                    <label for="dataSorteio">Data do Sorteio</label>
                    <input type="date" 
                           id="dataSorteio" 
                           class="form-control" 
                           required 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Números Sorteados</label>
                    <div id="numerosContador" class="numeros-contador">0/0 números selecionados</div>
                    <div id="numerosGrid" class="numbers-grid-select">
                        <!-- Números serão gerados via JavaScript -->
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="fecharModalResultado()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        Salvar Resultado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Estilos do Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    overflow-y: auto;
    padding: 20px;
}

.modal-content {
    background: white;
    border-radius: 10px;
    margin: 20px auto;
    width: 95%;
    max-width: 900px;
    position: relative;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.modal-header {
    padding-bottom: 15px;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #2c3e50;
}

.modal-header h2 small {
    font-size: 0.8em;
    color: #666;
    margin-left: 10px;
}

.modal-body {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    padding: 0 10px;
}

.numbers-grid-select {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 8px;
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fc;
    border-radius: 8px;
}

.number-select {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid #ddd;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.number-select:hover {
    background: #e8f0fe;
    border-color: #4e73df;
}

.number-select.selected {
    background: #4e73df;
    color: white;
    border-color: #4e73df;
}

.numeros-contador {
    text-align: center;
    color: #4e73df;
    font-weight: 600;
    margin: 10px 0;
    padding: 5px;
    background: #f8f9fc;
    border-radius: 4px;
}

/* Responsividade */
@media (max-width: 768px) {
    .numbers-grid-select {
        grid-template-columns: repeat(5, 1fr);
        gap: 5px;
    }

    .number-select {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }

    .modal-content {
        margin: 10px;
        padding: 15px;
    }
}
</style> 