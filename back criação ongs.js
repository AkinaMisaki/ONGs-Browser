const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('imagemOng');
//imagem
if (dropZone && fileInput) {

    dropZone.onclick = () => fileInput.click();

    dropZone.ondragover = (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    };

    dropZone.ondragleave = () => dropZone.classList.remove('dragover');

    dropZone.ondrop = (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files; // Vincula o arquivo ao input
            dropZone.querySelector('span').innerText = `Arquivo: ${files[0].name}`;
        }
    };

    fileInput.onchange = () => {
        if (fileInput.files.length > 0) {
            dropZone.querySelector('span').innerText = `Arquivo: ${fileInput.files[0].name}`;
        }
    };
}
//cadastro

async function realizarCadastroUsuario() {
    const campoOng = document.getElementById('nomeOng').value.trim();
    const campoSigla = document.getElementById('SiglaOng').value.trim();
    const campoDescricao = document.getElementById('descricaoOng').value.trim();
    const arquivoImagem = fileInput.files[0]; // Pega o arquivo que está no input


    if (campoOng === '' || campoSigla === '' || campoDescricao === '') {
        alert('Atenção: Todos os campos de texto são obrigatórios!');
        return;
    }

    const dadosFormulario = new FormData();
    dadosFormulario.append('Ong', campoOng);
    dadosFormulario.append('Sigla', campoSigla);
    dadosFormulario.append('Descricao', campoDescricao);
    
    if (arquivoImagem) {
        dadosFormulario.append('imagemOng', arquivoImagem);
    }

    try {
        const resposta = await fetch('controller/criacaoOng.php', {
            method: 'POST',
            body: dadosFormulario
        });

        const resultado = await resposta.json();

        if (resultado.sucesso) {
            alert(resultado.mensagem);
            document.getElementById('formContato').reset();
            dropZone.querySelector('span').innerText = "Arraste sua imagem aqui"; // Reseta o texto da zona
        } else {
            alert('Erro no Cadastro:\n' + resultado.mensagem);
        }

    } catch (erro) {
        alert('Erro crítico: Falha de comunicação com o servidor.');
        console.error(erro);
    }
}