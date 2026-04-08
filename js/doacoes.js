document.addEventListener('DOMContentLoaded', () => {
    preencherDadosOng();
    document.getElementById('valor_doacao').addEventListener('input', calcularConversao);
    document.getElementById('moeda_usuario').addEventListener('change', calcularConversao);

    document.getElementById('formDoacao').addEventListener('submit', confirmarDoacao);
});

/**
 * Função para ler os parâmetros da URL (GET) e preencher a tela
 * Exemplo de URL esperada: doacoes_ong.php?nome=SaveTheCats&moeda=USD
 * Estou analisando um meio melhor de recebermos essas informações do php, mas por enquanto isso é simples e funcional para o protótipo.
 */
function preencherDadosOng() {
    const parametrosUrl = new URLSearchParams(window.location.search);
    const nomeOng = parametrosUrl.get('nome') || 'ONG Não Identificada';
    const moedaOng = parametrosUrl.get('moeda') || 'USD'; 
    document.getElementById('display-nome-ong').textContent = nomeOng;
    document.getElementById('display-moeda-ong').textContent = moedaOng;

    // Preenche os inputs ocultos que serão enviados ao PHP no momento do submit
    document.getElementById('ong_nome').value = nomeOng;
    document.getElementById('ong_moeda').value = moedaOng;
}

/**
 * Função assíncrona que consome a AwesomeAPI para cotação de moedas
 */
async function calcularConversao() {
    const inputValor = document.getElementById('valor_doacao');
    const valorDigitado = parseFloat(inputValor.value);
    
    const moedaUsuario = document.getElementById('moeda_usuario').value;
    const moedaOng = document.getElementById('ong_moeda').value;
    
    const displayConvertido = document.getElementById('valor-convertido');
    const displayTaxa = document.getElementById('taxa-cotacao');

    if (isNaN(valorDigitado) || valorDigitado <= 0) {
        displayConvertido.textContent = "0.00";
        displayTaxa.textContent = "Aguardando valor válido...";
        return;
    }

    // Se a moeda escolhida for igual à moeda da ONG, não é necessário consultar a API
    if (moedaUsuario === moedaOng) {
        displayConvertido.textContent = formatarMoeda(valorDigitado, moedaOng);
        displayTaxa.textContent = "Mesma moeda (Sem taxa de conversão)";
        return;
    }

    // Feedback visual enquanto a API carrega
    displayTaxa.textContent = "A consultar cotação atual...";

    try {
        // A API espera o formato: MoedaOrigem-MoedaDestino (Ex: BRL-USD)
        const parMoedas = `${moedaUsuario}-${moedaOng}`;
        const url = `https://economia.awesomeapi.com.br/last/${parMoedas}`;

        const resposta = await fetch(url);
        
        if (!resposta.ok) {
            throw new Error('Serviço de cotação indisponível no momento.');
        }

        const dados = await resposta.json();
        
        // A API devolve um objeto com a chave concatenada (Ex: BRLUSD)
        const chaveRetorno = moedaUsuario + moedaOng;
        const cotacao = parseFloat(dados[chaveRetorno].bid); // 'bid' é o valor de compra

        // Realiza a conversão
        const valorFinal = valorDigitado * cotacao;
        displayConvertido.textContent = formatarMoeda(valorFinal, moedaOng);
        displayTaxa.textContent = `1 ${moedaUsuario} = ${cotacao.toFixed(4)} ${moedaOng}`;

    } catch (erro) {
        console.error("Erro ao buscar cotação:", erro);
        displayConvertido.textContent = "Erro";
        displayTaxa.textContent = "Não foi possível obter a cotação. Tente mais tarde.";
    }
}

/**
 * Função auxiliar para formatar números no padrão de moeda
 */
function formatarMoeda(valor, moeda) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: moeda
    }).format(valor);
}

function confirmarDoacao(event) {
    event.preventDefault();

    const valorUsuario = parseFloat(document.getElementById('valor_doacao').value);
    if (isNaN(valorUsuario) || valorUsuario <= 0) {
        alert("Por favor, introduza um valor de doação válido.");
        return;
    }

    const nomeOng = document.getElementById('ong_nome').value;
    const moedaUsuario = document.getElementById('moeda_usuario').value;
    const valorConvertidoText = document.getElementById('valor-convertido').textContent;
    const mensagem = `--- RESUMO DA DOAÇÃO ---\n\n` +
                     `A apoiar: ${nomeOng}\n` +
                     `Valor a pagar: ${valorUsuario} ${moedaUsuario}\n` +
                     `A ONG receberá: ${valorConvertidoText}\n\n` +
                     `Deseja confirmar esta doação?`;
    if (confirm(mensagem)) {
        event.target.submit();
    }
}