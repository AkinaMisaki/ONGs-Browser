def replace_lines(filepath, start_line, new_content, end_line=None):
    """
    Substitui uma linha ou um intervalo de linhas em um arquivo.

    Args:
        filepath    : caminho para o arquivo
        start_line  : número da linha a substituir (começa em 1)
        new_content : string ou lista de strings a inserir
        end_line    : última linha do intervalo a substituir (inclusivo).
                      Se None, apenas start_line é substituída.
    """
    if end_line is None:
        end_line = start_line

    if isinstance(new_content, str):
        new_content = [new_content]

    # Garante que cada linha termine com quebra de linha
    new_lines = [
        line if line.endswith("\n") else line + "\n"
        for line in new_content
    ]

    with open(filepath, "r") as f:
        lines = f.readlines()

    total = len(lines)
    if start_line < 1 or end_line > total or start_line > end_line:
        raise ValueError(
            f"Intervalo inválido [{start_line}, {end_line}] para um arquivo com {total} linhas."
        )

    # Substitui o intervalo (converte para índice base 0)
    lines[start_line - 1 : end_line] = new_lines

    with open(filepath, "w") as f:
        f.writelines(lines)

    print(f"Linhas {start_line}–{end_line} substituídas em '{filepath}'.")


# ── Exemplos ──────────────────────────────────────────────────────────────────

# Substitui apenas a linha 3
#replace_lines("example.txt", start_line=3, new_content="linha nova")

# Substitui o intervalo de linhas 5 a 8 por novo conteúdo
#replace_lines(
#    "example.txt",
#    start_line=5,
#    end_line=8,
#    new_content=[
#        "def hello():",
#        "    print('Olá, mundo!')",
#        "",
#    ],
#)

replace_lines('index.php', start_line=22, new_content='echo "Olá, mundo!";')

replace_lines('index.php', start_line=22, new_content=['''<header class="barra-fixa">
    <div class="header-container">
        <h1 onclick="window.location.href='index.php'">ONGs Browser</h1>
        <nav>
            <button onclick="window.location.href='index.php'">Início</button>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <button onclick="window.location.href='view/gerenciar_conta.php'">Minha Conta</button>
                
                <?php if (isset($_SESSION['statusConta']) && $_SESSION['statusConta'] === 2): ?>
                    <button class="btn-sucesso" onclick="window.location.href='view/criacao_ong.php'">Criar ONG</button>
                <?php endif; ?>

                <button class="btn-perigo" onclick="window.location.href='controller/logout.php'">Sair</button>
            
            <?php else: ?>
                <button onclick="window.location.href='view/login.php'">Entrar</button>
                <button class="btn-sucesso" onclick="window.location.href='view/registrar.php'">Cadastrar</button>
            <?php endif; ?>
        </nav>
    </div>
</header>'''])

#Adicionar mais linhas para serem trocadas aqui. -Max