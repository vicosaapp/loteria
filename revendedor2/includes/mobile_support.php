<?php
/**
 * Arquivo de suporte para visualização móvel
 */
?>

<!-- CSS responsivo para mobile -->
<link rel="stylesheet" href="../assets/css/mobile.css">

<!-- Adicionando viewport meta tag se ainda não existir -->
<script>
    if (!document.querySelector('meta[name="viewport"]')) {
        const viewport = document.createElement('meta');
        viewport.name = 'viewport';
        viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
        document.head.appendChild(viewport);
    }
</script>

<!-- JavaScript para suporte mobile (será carregado no final da página) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Carregar script de suporte mobile
        const mobileScript = document.createElement('script');
        mobileScript.src = '../assets/js/mobile.js';
        document.body.appendChild(mobileScript);
    });
</script> 