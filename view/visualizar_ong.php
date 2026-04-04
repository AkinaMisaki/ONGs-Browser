<?php
$host = '127.0.0.1';
$user = 'root'; 
$pass = 'root';     
$db   = 'universidade';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão com o banco local: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$ong  = null;
$erro = null;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    $erro = "ID inválido ou não informado na URL (ex: ?id=1).";
} else {
    $stmt = $conn->prepare("SELECT nome_ong, descricao FROM ong WHERE id_ong = ?");
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $ong = $result->fetch_assoc(); 
        
        if (!$ong) {
            $erro = "Nenhuma ONG encontrada com esse ID no seu banco local.";
        }
        
        $stmt->close();
    } else {
        error_log("Erro no prepare: " . $conn->error);
        $erro = "Erro ao montar a consulta no banco de dados.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar ONG</title>
    <style>
        body { font-family: sans-serif; background-color: #1a1a1a; color: #fff; padding: 20px; }
        .error { color: #ff4c4c; font-weight: bold; }
        pre { background: #333; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Teste Local - Visualizar ONG</h2>
    
    <?php 
    if ($ong) {
        echo "<pre>";
        var_dump($ong);
        echo "</pre>";
    } 
    ?>
    
    <?php 
    if ($erro) {
        echo "<p class='error'>$erro</p>";
    } 
    ?>
</body>
</html>