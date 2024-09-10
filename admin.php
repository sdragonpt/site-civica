<?php
include 'config.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $imagem = $_POST['imagem'];

    $sql = "INSERT INTO produtos (nome, descricao, preco, imagem) VALUES ('$nome', '$descricao', '$preco', '$imagem')";

    if ($conn->query($sql) === TRUE) {
        echo "Novo produto adicionado com sucesso!";
    } else {
        echo "Erro: " . $sql . "<br>" . $conn->error;
    }
}
?>

<h1>Adicionar Produto</h1>
<form method="post" action="">
    Nome: <input type="text" name="nome" required><br>
    Descrição: <textarea name="descricao"></textarea><br>
    Preço: <input type="text" name="preco" required><br>
    Imagem: <input type="text" name="imagem"><br>
    <input type="submit" value="Adicionar Produto">
</form>
