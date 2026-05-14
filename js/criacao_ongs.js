const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('imagemOng');
const form = document.getElementById('formContato');

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
            validarEAtribuirArquivo(files[0]);
        }
    };

    fileInput.onchange = () => {
        if (fileInput.files.length > 0) {
            validarEAtribuirArquivo(fileInput.files[0]);
        }
    };
}

function validarEAtribuirArquivo(arquivo) {
    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
    if (!tiposPermitidos.includes(arquivo.type)) {
        alert("Por favor, selecione apenas imagens (JPG, PNG ou WebP).");
        fileInput.value = "";
        return;
    }
    const tamanhoMaxMB = 20;
    if (arquivo.size > tamanhoMaxMB * 1024 * 1024) {
        alert(`A imagem deve ter no máximo ${tamanhoMaxMB}MB. O arquivo selecionado tem ${(arquivo.size / 1024 / 1024).toFixed(1)}MB.`);
        fileInput.value = "";
        return;
    }
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(arquivo);
    fileInput.files = dataTransfer.files;
    dropZone.querySelector('span').innerText = `Arquivo: ${arquivo.name}`;
}

if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault(); 
        await realizarCadastroUsuario();
    });
}

async function realizarCadastroUsuario() {
    const campoOng = document.getElementById('nomeOng').value.trim();
    const campoDescricao = document.getElementById('descricaoOng').value.trim();
    const arquivoImagem = fileInput.files[0];

    if (!campoOng || !campoDescricao || !arquivoImagem) {
        alert('Por favor, preencha todos os campos e selecione uma imagem.');
        return;
    }

    const dadosFormulario = new FormData();
    dadosFormulario.append('Ong', campoOng);
    dadosFormulario.append('Descricao', campoDescricao);
    dadosFormulario.append('imagemOng', arquivoImagem);

    try {
        const resposta = await fetch('../controller/criacaoOngcontroller.php', {
            method: 'POST',
            body: dadosFormulario
        });

        if (!resposta.ok) throw new Error('Erro na rede');

        const resultado = await resposta.json();
        
        if (resultado.sucesso) {
            form.reset();
            dropZone.querySelector('span').innerText = "Arraste sua imagem aqui";
            window.location.href = `../view/visualizar_ong.php?id=${resultado.id_ong}`;
        } else {
            alert('Erro: ' + resultado.mensagem);
        }
    } catch (erro) {
        alert('Erro crítico: Não foi possível conectar ao servidor.' + erro);
        console.error("Detalhes do erro:", erro);
    }
}