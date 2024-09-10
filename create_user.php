<?php
include 'config.php'; // Certifique-se que este ficheiro aponta corretamente para a sua configuração de conexão à base de dados

// Nome de utilizador e senha
$username = 'admin';  // Nome do utilizador
$password = password_hash('Civica2018paulo', PASSWORD_BCRYPT);  // Substitua 'senha_segura' por uma senha que escolher

// Inserir o utilizador na base de dados
$sql = "INSERT INTO usuarios (username, senha) VALUES ('$username', '$password')";

if ($conn->query($sql) === TRUE) {
    echo "Utilizador 'admin' criado com sucesso!";
} else {
    echo "Erro: " . $conn->error;
}

$conn->close();
?>
