document.addEventListener('DOMContentLoaded', async () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    if (!id) {
        mostrarErro('Nenhum ID especificado.');
        return;
    }

    try {
        const resposta = await fetch(`../controller/visualizar_ong.php?id=${encodeURIComponent(id)}`);

        if (!resposta.ok) throw new Error('Erro na rede.');

        const resultado = await resposta.json();

        if (resultado.sucesso) {
            document.title = resultado.nome_ong + ' — ONGs Browser';
            document.getElementById('ong-nome').textContent = resultado.nome_ong;
            document.getElementById('ong-proprietario').textContent = 'Proprietário: ' + resultado.proprietario;
            document.getElementById('ong-descricao').textContent = resultado.descricao;

            const img = document.getElementById('ong-imagem');
            img.src = resultado.caminho_arquivo || '../uploads/placeholder.png';
            img.alt = 'Imagem da ' + resultado.nome_ong;
            document.getElementById('ong-imagem-container').style.display = 'block';

            document.getElementById('ong-doar-link').href = `doacaoOng.php?id=${encodeURIComponent(id)}`;

            document.getElementById('ong-card').style.display = 'block';
            document.getElementById('loading').style.display = 'none';
        } else {
            mostrarErro(resultado.mensagem);
        }
    } catch (erro) {
        mostrarErro('Não foi possível carregar a ONG.');
        console.error(erro);
    }
});

function mostrarErro(mensagem) {
    document.getElementById('loading').style.display = 'none';
    document.getElementById('erro-mensagem').textContent = mensagem;
    document.getElementById('erro-container').style.display = 'block';
}
