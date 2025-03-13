<div class="form-group">
    <label for="titulo_importacao">Título de Importação</label>
    <input type="text" 
           class="form-control" 
           id="titulo_importacao" 
           name="titulo_importacao" 
           value="<?php echo htmlspecialchars($jogo['titulo_importacao'] ?? ''); ?>"
           placeholder="Ex: Loterias Mobile: LF"
           required>
    <small class="form-text text-muted">
        Este título deve corresponder exatamente à primeira linha do texto importado
    </small>
</div> 