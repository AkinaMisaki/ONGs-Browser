<?php
/**
 * S.3.2.d — Recupera registros do BD, descriptografa e imprime no console.
 * Executar no servidor: php controller/recuperar_bd.php
 *
 * Formato de saída: usuario:hostname>informações do cadastro
 */

include __DIR__ . '/../config.php';

if ($DB_ENCRYPT_KEY === null) {
    echo "ERRO: DB_ENCRYPT_KEY nao configurada. Execute generate_db_key.php primeiro.\n";
    exit(1);
}

$usuarioSO = trim(shell_exec('whoami') ?: get_current_user());
$hostname  = gethostname();

$result = $conn->query(
    "SELECT u.id_usuario, u.nome_usuario, u.usuario_login, u.email, p.cpf, p.rg, p.telefone
     FROM usuario u
     LEFT JOIN proprietario_ong p ON p.fk_usuario = u.id_usuario
     ORDER BY u.id_usuario"
);

if (!$result) {
    echo "ERRO ao consultar BD: " . $conn->error . "\n";
    exit(1);
}

echo "\n";
echo "+------------------------------------------------------------+\n";
echo "|  S.3.2.d - Recuperacao e Descriptografia de Registros     |\n";
echo "+------------------------------------------------------------+\n\n";

if ($result->num_rows === 0) {
    echo "Nenhum registro encontrado na tabela usuario.\n";
    $conn->close();
    exit(0);
}

while ($row = $result->fetch_assoc()) {
    $nomeDecifrado  = db_decrypt($row['nome_usuario'], $DB_ENCRYPT_KEY);
    $emailDecifrado = db_decrypt($row['email'],        $DB_ENCRYPT_KEY);
    $cpfDecifrado      = $row['cpf']      !== null ? db_decrypt($row['cpf'],      $DB_ENCRYPT_KEY) : null;
    $rgDecifrado       = $row['rg']       !== null ? db_decrypt($row['rg'],       $DB_ENCRYPT_KEY) : null;
    $telefoneDecifrado = $row['telefone'] !== null ? db_decrypt($row['telefone'], $DB_ENCRYPT_KEY) : null;

    // S.3.2.d: usuario:hostname>informações do cadastro
    echo "{$usuarioSO}:{$hostname}> ";
    echo "id={$row['id_usuario']} | ";
    echo "nome={$nomeDecifrado} | ";
    echo "login={$row['usuario_login']} | ";
    echo "email={$emailDecifrado}";
    if ($cpfDecifrado !== null) {
        echo " | cpf={$cpfDecifrado} | rg={$rgDecifrado} | telefone={$telefoneDecifrado}";
    }
    echo "\n";
}

echo "\nTotal: {$result->num_rows} registro(s) descriptografado(s).\n";

$conn->close();
