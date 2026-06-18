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

function toBase64(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
}

function converterParaBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
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
    
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Por favor, selecione uma imagem.');
        return;
    }
    const arquivoImagem = fileInput.files[0];

    if (!campoOng || !campoDescricao) {
        alert('Por favor, preencha todos os campos.');
        return;
    }

    try {
        const derkey = await fetch('../public.der');
        const derbuffer = await derkey.arrayBuffer();
        const pubkey = await crypto.subtle.importKey('spki', derbuffer, { name: 'RSA-OAEP', hash: 'SHA-1' }, false, ['encrypt']);
        
        const imagemBase64 = await converterParaBase64(arquivoImagem);
        const dadosOriginais = JSON.stringify({ Ong: campoOng, Descricao: campoDescricao, imagem: imagemBase64 });
        
        const chaveAES = await crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt', 'decrypt']);
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const dadosCifrados = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: iv }, chaveAES, new TextEncoder().encode(dadosOriginais));
        
        const chaveAESRaw = await crypto.subtle.exportKey('raw', chaveAES);
        const chaveAESCifrada = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, pubkey, chaveAESRaw);

        const dadosFormulario = new FormData();
        dadosFormulario.append('keyAESCifrada', toBase64(chaveAESCifrada));
        dadosFormulario.append('msg_cifrada', toBase64(dadosCifrados));
        dadosFormulario.append('iv', toBase64(iv));

        const resposta = await fetch('../controller/criacaoOngcontroller.php', {
            method: 'POST',
            body: dadosFormulario
        });

        const resultado = await resposta.json();
        
        if (resultado.sucesso) {
            form.reset();
            dropZone.querySelector('span').innerText = "Arraste sua imagem aqui";
            window.location.href = `../view/visualizar_ong.php?id=${resultado.id_ong}`;
        } else {
            alert('Erro: ' + resultado.mensagem);
        }
    } catch (erro) {
        alert('Erro crítico: Falha na criptografia ou conexão.');
        console.error("Detalhes do erro:", erro);
    }
}