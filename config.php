<?php
$servername = "mysql.civica.pt";
$username = "admin";
$password = "3(dy09qCno-3";
$dbname = "civica2018_backoffice-db";

// Criar a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

echo "Conexão bem-sucedida!";
?>
