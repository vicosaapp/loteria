async function calcularPremiacao(dados) {
    try {
        console.log('Dados que serão enviados:', dados);
        
        const response = await fetch('processar_premiacao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                jogoNome: dados.jogoNome,
                apostas: dados.apostas.map(aposta => ({
                    ...aposta,
                    dezenas: 15 // Lotofácil sempre tem 15 números
                }))
            })
        });
        
        const result = await response.json();
        console.log('Resposta do servidor:', result);
        
        if (result.success) {
            document.querySelector('input[name="valor_premiacao"]').value = result.premiacao;
            return result.premiacao;
        } else {
            console.error('Erro retornado pelo servidor:', result.error);
            return 0;
        }
    } catch (error) {
        console.error('Erro ao calcular premiação:', error);
        return 0;
    }
}

function processarApostas(texto, valorAposta) {
    console.log('=== INICIANDO CÁLCULO ===');
    console.log('Texto das apostas:', texto);
    console.log('Valor da aposta original:', valorAposta);

    const linhas = texto.trim().split('\n');
    const jogoNome = linhas[0].trim();
    const apostas = [];

    for (let i = 1; i < linhas.length; i++) {
        if (linhas[i].trim()) {
            const numeros = linhas[i].trim().split(/\s+/);
            apostas.push({
                dezenas: numeros.join(' '),
                valorAposta: parseFloat(valorAposta)
            });
        }
    }

    console.log('Dados processados:');
    console.log('- Jogo:', jogoNome);
    console.log('- Primeira linha:', linhas[1] || '');
    console.log('- Dezenas:', apostas[0]?.dezenas || 0);
    console.log('- Valor numérico (após conversão):', valorAposta);

    return {
        jogoNome,
        apostas
    };
} 