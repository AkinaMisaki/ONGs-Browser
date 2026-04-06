async function enviarDados() {
    const dados = {
        nomeCompleto: document.querySelector('#nome').value,
        sigla: document.querySelector('#sigla').value,
        // ... outros campos
    };

    const response = await fetch('http://localhost:8080/api/ongs/cadastrar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dados)
    });

    if(response.ok) {
        alert("ONG cadastrada com sucesso!");
    }
}