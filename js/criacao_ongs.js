//  LÓGICA DO DRAG & DROP
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
        fileInput.value = ""; // Limpa o input
        return;
    }


    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(arquivo);
    fileInput.files = dataTransfer.files;

    dropZone.querySelector('span').innerText = `Arquivo: ${arquivo.name}`;
}

// FUNÇÃO DE ENVIO 


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

    // Validação
    if (!campoOng || !campoDescricao || !arquivoImagem) {
        alert('Por favor, preencha todos os campos e selecione uma imagem.');
        return;
    }

    const dadosFormulario = new FormData();
    dadosFormulario.append('Ong', campoOng);
    dadosFormulario.append('Descricao', campoDescricao);
    dadosFormulario.append('imagemOng', arquivoImagem);

    try {
        const resposta = await fetch('controller/criacaoOng.php', {
            method: 'POST',
            body: dadosFormulario
        });

        
        if (!resposta.ok) throw new Error('Erro na rede');

        const resultado = await resposta.json();
        
        if (resultado.sucesso) {
            alert(resultado.mensagem);
            form.reset();
            dropZone.querySelector('span').innerText = "Arraste sua imagem aqui";
        } else {
            alert('Erro: ' + resultado.mensagem);
        }

    } catch (erro) {
        alert('Erro crítico: Não foi possível conectar ao servidor.');
        console.error("Detalhes do erro:", erro);
    }
}