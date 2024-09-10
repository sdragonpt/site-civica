<?php
$servername = "localhost"; // Como indicado no phpMyAdmin
$username = "civica2018_admin"; // Substitua pelo nome de usuário correto
$password = "3(dy09qCno-3"; // Substitua pela senha correta
$dbname = "civica2018_backoffice-db"; // Nome do banco de dados fornecido

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname, 3306);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
} else {
    echo "Conexão bem-sucedida!";
}
?>
