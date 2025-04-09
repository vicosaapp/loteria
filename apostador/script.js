let apostas = [];
const numerosPorAposta = 18;

function gerarNumeros() {
    const container = document.querySelector('.numeros-container');
    container.innerHTML = '';
    
    for (let i = 1; i <= 25; i++) {
        const numero = document.createElement('div');
        numero.className = 'numero';
        numero.textContent = i.toString().padStart(2, '0');
        numero.onclick = () => toggleNumero(numero);
        container.appendChild(numero);
    }
}

function toggleNumero(elemento) {
    const numerosSelecioandos = document.querySelectorAll('.numero.selecionado').length;
    
    if (!elemento.classList.contains('selecionado') && numerosSelecioandos >= numerosPorAposta) {
        alert(`Você só pode selecionar ${numerosPorAposta} números!`);
        return;
    }
    
    elemento.classList.toggle('selecionado');
}

function adicionarAposta() {
    const numerosSelecioandos = Array.from(document.querySelectorAll('.numero.selecionado'))
        .map(el => el.textContent)
        .sort((a, b) => parseInt(a) - parseInt(b));
    
    if (numerosSelecioandos.length !== numerosPorAposta) {
        alert(`Selecione exatamente ${numerosPorAposta} números!`);
        return;
    }
    
    apostas.push(numerosSelecioandos);
    atualizarApostasVisualizacao();
    limparSelecao();
}

function limparSelecao() {
    document.querySelectorAll('.numero.selecionado').forEach(el => {
        el.classList.remove('selecionado');
    });
}

function atualizarApostasVisualizacao() {
    const container = document.getElementById('apostasRealizadas');
    container.innerHTML = apostas.map((aposta, index) => `
        <div class="aposta">
            ${aposta.join(' ')}
            <button class="btn btn-sm btn-danger float-end" onclick="removerAposta(${index})">Remover</button>
        </div>
    `).join('');
}

function removerAposta(index) {
    apostas.splice(index, 1);
    atualizarApostasVisualizacao();
}

function enviarParaWhatsApp() {
    if (apostas.length === 0) {
        alert('Faça pelo menos uma aposta!');
        return;
    }
    
    const jogo = document.getElementById('jogoSelecionado').value;
    const mensagem = `Loterias Mobile: ${jogo}\n\n${apostas.map(aposta => aposta.join(' ')).join('\n')}`;
    
    const urlWhatsApp = `https://wa.me/?text=${encodeURIComponent(mensagem)}`;
    window.open(urlWhatsApp, '_blank');
}

// Inicializar a página
window.onload = () => {
    gerarNumeros();
};