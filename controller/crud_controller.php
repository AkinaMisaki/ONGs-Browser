<?php
// ── Verificação de sessão ──────────────────────────────────────────────────
// Feita antes de qualquer coisa, inclusive antes do config.php.
// Como este arquivo é chamado via AJAX, retorna JSON em vez de redirecionar.
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('CRUD_IDLE_TIMEOUT', 1800); // 30 minutos — mesmo valor do admin_guard.php

// Não autenticado
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autenticado.', 'expirado' => true]);
    exit;
}

// Sessão expirou por inatividade
if (isset($_SESSION['admin_last_activity']) &&
    (time() - $_SESSION['admin_last_activity']) > CRUD_IDLE_TIMEOUT) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada por inatividade.', 'expirado' => true]);
    exit;
}

// Atualiza o timestamp — cada chamada AJAX conta como atividade
$_SESSION['admin_last_activity'] = time();

require_once __DIR__ . '/../config.php';

// Toda resposta desse controller é JSON
header('Content-Type: application/json; charset=utf-8');

// Pega a ação solicitada pelo front — se não vier nenhuma, cai no default
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'list_tables':      listTables();      break;
    case 'describe_table':   describeTable();   break;
    case 'list_records':     listRecords();     break;
    case 'create_record':    createRecord();    break;
    case 'update_record':    updateRecord();    break;
    case 'delete_record':    deleteRecord();    break;
    case 'broadcast_send':   broadcastSend();   break;
    case 'banip_list':       banIpList();       break;
    case 'banip_add':        banIpAdd();        break;
    case 'banip_remove':     banIpRemove();     break;
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
}

// ── Funções auxiliares ─────────────────────────────────────────────────────

// Detecta se uma coluna é de senha pelo nome (mesmo critério do registro_usuario.php)
function isPasswordColumn(string $name): bool {
    $name = strtolower($name);
    return str_contains($name, 'senha') || str_contains($name, 'password') || str_contains($name, 'passwd');
}

// Aplica Argon2ID com as mesmas configurações usadas no registro de usuários
function hashPassword(string $value): string {
    return password_hash($value, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost'   => 4,
        'threads'     => 2,
    ]);
}

// Retorna o nome do banco de dados conectado atualmente
function getDatabase() {
    global $conn;
    return $conn->query("SELECT DATABASE()")->fetch_row()[0];
}

// Valida se a tabela existe no banco antes de usar o nome em queries.
// Não dá pra usar prepared statements com identificadores (nomes de tabela/coluna),
// então consultamos o INFORMATION_SCHEMA pra garantir que o nome é legítimo.
function isValidTable($table) {
    global $conn;
    $db = getDatabase();
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
    );
    $stmt->bind_param("ss", $db, $table);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Mesma lógica da função acima, mas pra validar coluna dentro de uma tabela específica
function isValidColumn($table, $column) {
    global $conn;
    $db = getDatabase();
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->bind_param("sss", $db, $table, $column);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Retorna todas as colunas de uma tabela com suas propriedades:
// nome, tipo de dado, se é chave primária, se tem auto_increment, etc.
function getColumns($table) {
    global $conn;
    $db = getDatabase();
    $stmt = $conn->prepare(
        "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY, EXTRA, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
         ORDER BY ORDINAL_POSITION"
    );
    $stmt->bind_param("ss", $db, $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row;
    }
    $stmt->close();
    return $columns;
}

// ── Ações ──────────────────────────────────────────────────────────────────

// Lista todas as tabelas do banco em ordem alfabética
function listTables() {
    global $conn;
    $db = getDatabase();
    $stmt = $conn->prepare(
        "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME"
    );
    $stmt->bind_param("s", $db);
    $stmt->execute();
    $result = $stmt->get_result();
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    $stmt->close();
    echo json_encode(['sucesso' => true, 'data' => $tables]);
}

// Retorna a estrutura da tabela (colunas e metadados) pra o front montar o formulário
function describeTable() {
    $table = $_GET['table'] ?? '';
    if (!$table || !isValidTable($table)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tabela inválida.']);
        return;
    }
    echo json_encode(['sucesso' => true, 'data' => getColumns($table)]);
}

// Busca todos os registros da tabela (limite de 500 pra não sobrecarregar)
function listRecords() {
    global $conn;
    $table = $_GET['table'] ?? '';
    if (!$table || !isValidTable($table)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tabela inválida.']);
        return;
    }
    $result = $conn->query("SELECT * FROM `$table` LIMIT 500");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode(['sucesso' => true, 'data' => $rows]);
}

// Insere um novo registro na tabela.
// Colunas com auto_increment são ignoradas — o banco gera o valor sozinho.
function createRecord() {
    global $conn;
    $table = $_POST['__table'] ?? '';
    if (!$table || !isValidTable($table)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tabela inválida.']);
        return;
    }

    $columns   = getColumns($table);
    $colNames  = [];
    $values    = [];

    foreach ($columns as $col) {
        $name = $col['COLUMN_NAME'];
        // Ignora colunas auto_increment (ex: id) — o banco preenche automaticamente
        if (stripos($col['EXTRA'], 'auto_increment') !== false) continue;
        // Só processa os campos que vieram no POST
        if (!array_key_exists($name, $_POST)) continue;
        $colNames[] = "`$name`";
        $raw = $_POST[$name];
        // Colunas de senha são hasheadas com Argon2ID antes de salvar
        if ($raw !== '' && isPasswordColumn($name)) {
            $raw = hashPassword($raw);
        }
        // String vazia vira NULL pra respeitar campos opcionais
        $values[] = $raw === '' ? null : $raw;
    }

    if (empty($colNames)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum campo para inserir.']);
        return;
    }

    // Monta a query com placeholders (?) e usa spread operator pra bind dinâmico
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $sql  = "INSERT INTO `$table` (" . implode(', ', $colNames) . ") VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($values)), ...$values);

    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Registro criado com sucesso!']);
    } else {
        error_log($conn->error);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $conn->error]);
    }
    $stmt->close();
}

// Atualiza um registro existente.
// O front envia __pk_col (nome da PK) e __pk_val (valor da PK) pra identificar o registro.
function updateRecord() {
    global $conn;
    $table  = $_POST['__table']   ?? '';
    $pkCol  = $_POST['__pk_col']  ?? '';  // ex: "id"
    $pkVal  = $_POST['__pk_val']  ?? '';  // ex: "42"

    if (!$table || !isValidTable($table)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tabela inválida.']);
        return;
    }
    if (!$pkCol || !isValidColumn($table, $pkCol)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Chave primária inválida.']);
        return;
    }

    $columns    = getColumns($table);
    $setClauses = [];
    $values     = [];

    foreach ($columns as $col) {
        $name = $col['COLUMN_NAME'];
        // A PK não vai no SET — ela só vai no WHERE
        if ($name === $pkCol) continue;
        if (!array_key_exists($name, $_POST)) continue;
        $raw = $_POST[$name];
        // Campo de senha em branco no update = admin não quer alterar, então pula
        if ($raw === '' && isPasswordColumn($name)) continue;
        // Senha preenchida? Aplica Argon2ID antes de salvar
        if ($raw !== '' && isPasswordColumn($name)) {
            $raw = hashPassword($raw);
        }
        $setClauses[] = "`$name` = ?";
        $values[]     = $raw === '' ? null : $raw;
    }

    if (empty($setClauses)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum campo para atualizar.']);
        return;
    }

    // O valor da PK vai por último no array, correspondendo ao ? do WHERE
    $values[] = $pkVal;
    $sql  = "UPDATE `$table` SET " . implode(', ', $setClauses) . " WHERE `$pkCol` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($values)), ...$values);

    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Registro atualizado com sucesso!']);
    } else {
        error_log($conn->error);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $conn->error]);
    }
    $stmt->close();
}

// Remove um registro da tabela usando a chave primária pra identificar qual deletar
function deleteRecord() {
    global $conn;
    $table  = $_POST['__table']   ?? '';
    $pkCol  = $_POST['__pk_col']  ?? '';
    $pkVal  = $_POST['__pk_val']  ?? '';

    if (!$table || !isValidTable($table)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tabela inválida.']);
        return;
    }
    if (!$pkCol || !isValidColumn($table, $pkCol)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Chave primária inválida.']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$pkCol` = ?");
    $stmt->bind_param("s", $pkVal);

    if ($stmt->execute()) {
        // affected_rows = 0 significa que o registro não existia
        if ($stmt->affected_rows > 0) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Registro excluído com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Registro não encontrado.']);
        }
    } else {
        error_log($conn->error);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao excluir registro.']);
    }
    $stmt->close();
}

// ── Broadcast via Telegram ─────────────────────────────────────────────────
// Envia uma mensagem para todos os usuários com telegram_id cadastrado.
function broadcastSend() {
    global $conn, $env, $TELEGRAM_BOT_TOKEN, $DB_ENCRYPT_KEY;

    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A mensagem não pode estar vazia.']);
        return;
    }

    $botToken = $TELEGRAM_BOT_TOKEN ?? '';
    if (empty($botToken)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Token do bot Telegram não configurado.']);
        return;
    }

    // Busca todos os telegram_ids cadastrados
    $result = $conn->query("SELECT telegram_id FROM usuario_verificacao WHERE telegram_id IS NOT NULL AND telegram_id != ''");
    if (!$result) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao buscar usuários do Telegram.']);
        return;
    }

    $chatIds = [];
    while ($row = $result->fetch_assoc()) {
        $chatIds[] = db_decrypt($row['telegram_id'], $DB_ENCRYPT_KEY);
    }

    if (empty($chatIds)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum usuário com Telegram conectado.']);
        return;
    }

    $url     = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $enviados = 0;
    $falhas   = 0;

    foreach ($chatIds as $chatId) {
        $payload = json_encode(['chat_id' => $chatId, 'text' => $message]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST,          true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,    $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER,    ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,       8);
        $resposta = curl_exec($ch);
        $erro     = curl_error($ch);
        curl_close($ch);

        if ($erro) {
            $falhas++;
            continue;
        }
        $json = json_decode($resposta, true);
        if ($json && $json['ok']) {
            $enviados++;
        } else {
            $falhas++;
        }
    }

    echo json_encode([
        'sucesso'  => true,
        'mensagem' => "Broadcast concluído: {$enviados} enviado(s), {$falhas} falha(s).",
        'enviados' => $enviados,
        'falhas'   => $falhas,
    ]);
}

// ── IPs Banidos ────────────────────────────────────────────────────────────

function banIpList() {
    global $conn;
    $result = $conn->query("SELECT id, ip_address, reason, banned_at, banned_by FROM banned_ips ORDER BY banned_at DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode(['sucesso' => true, 'data' => $rows]);
}

function banIpAdd() {
    global $conn;

    $ip     = trim($_POST['ip_address'] ?? '');
    $reason = trim($_POST['reason']     ?? '');
    $by     = $_SESSION['admin_user_login'] ?? 'admin';

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Endereço IP inválido.']);
        return;
    }

    if ($ip === $_SERVER['REMOTE_ADDR']) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você não pode banir seu próprio IP.']);
        return;
    }

    $stmt = $conn->prepare(
        "INSERT INTO banned_ips (ip_address, reason, banned_by) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE reason = VALUES(reason), banned_by = VALUES(banned_by), banned_at = NOW()"
    );
    $stmt->bind_param("sss", $ip, $reason, $by);

    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => "IP {$ip} banido com sucesso."]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao banir IP: ' . $conn->error]);
    }
    $stmt->close();
}

function banIpRemove() {
    global $conn;

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM banned_ips WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'IP desbanido com sucesso.']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'IP não encontrado.']);
    }
    $stmt->close();
}
