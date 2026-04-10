<?php 
header('Content-Type: text/html; charset=utf-8');
include '../conn/config.php'; //alterar pro caminho correto

$id = ($_GET['id']) ?? null;

if ($id > 0 ){
    $sql = "SELECT nome_ong , descricao  FROM  ong WHERE id_ong = ?";
    $stmt = $conn -> prepare($sql);
    if($stmt){
        $stmt -> bind_param("i" , $id);
        $stmt ->execute();
        $resultado = $stmt ->get_result();

        if($linha = $resultado->fetch_assoc()){
            $nome_ong = htmlspecialchars($linha['nome_ong'] , ENT_QUOTES , 'UTF-8' );
            $descricao = htmlspecialchars($linha['descricao'] ,  ENT_QUOTES , 'UTF-8' );
        } else{
            echo "não há nada salvo neste id";
        }
        
    }else{
        echo "não foi possivel preparar a query";
            $nome_ong = null;
            $descricao = null;
    }
} else{
    echo "id nao especificado  corretamente , ex(?id=1)";
    $nome_ong = null;
    $descricao = null;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login_usuario.css">
</head>
<body>

    <header class="barra-fixa">
        <button><a href="../index.php">Voltar</a></button>

        </header>

    <main>
        <h1><?php echo $nome_ong;?></h1>
        <h2><?php echo $descricao;?></h2>
        

    </main>

</html>