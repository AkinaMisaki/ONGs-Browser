<?php
session_start();

// Verifica se está logado E se tem o perfil necessário
// Dica: 'doador_autorizado' é um ótimo controle, certifique-se de que isso existe no seu banco na hora do login!
if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] !== 'doador_autorizado') {
    // Redireciona de volta para uma página segura
    header("Location: ../view/dashboard_comum.php?erro=acesso_negado");
    exit();
}
?>

<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Doações</title>
    <link rel="stylesheet" href="css/doacoes.css">
</head>
<body>

    <header class="barra-fixa">
        <h1 style="color: white; margin: 0;">Painel de Doações</h1>
        <nav>
            <button onclick="window.location.href='dashboard_comum.php'">Voltar</button>
            <button onclick="window.location.href='../controller/logout.php'" style="background-color: #dc3545; border-color: #c82333;">Sair</button>
        </nav>
    </header>

    <main>
        <section id="nova-doacao">
            <div class="container-doacao">
                
                <h2>Você está apoiando: <span id="display-nome-ong">Carregando...</span></h2>

                <form id="formDoacao" action="../controller/processar_doacao.php" method="POST">
                    
                    <input type="hidden" id="ong_nome" name="ong_nome">
                    <input type="hidden" id="ong_moeda" name="ong_moeda">

                    <div class="info-ong">
                        <p>A moeda nativa utilizada por esta ONG é: <strong id="display-moeda-ong">-</strong></p>
                    </div>

                    <div class="campo-form">
                        <label for="moeda_usuario">Qual moeda você vai usar para pagar?</label>
                        <select id="moeda_usuario" name="moeda_usuario">
                            <option value="BRL">Real Brasileiro (BRL)</option>
                            <option value="USD">Dólar Americano (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                        </select>
                    </div>

                    <div class="campo-form">
                        <label for="valor_doacao">Valor da Doação:</label>
                        <input type="number" id="valor_doacao" name="valor_doacao" step="0.01" min="1" placeholder="Ex: 50.00" required>
                    </div>

                    <div class="resultado-conversao">
                        <p>A ONG receberá aproximadamente:</p>
                        <h3 id="valor-convertido">0.00</h3>
                        <small id="taxa-cotacao">Aguardando valor...</small>
                    </div>

                    <button type="submit" class="btn-doar">Confirmar Doação</button>
                </form>
            </div>
        </section>

        <section id="historico-doacoes">
            </section>
    </main>

    <script src="../js/doacao_cotacao.js"></script>
</body>
</html>