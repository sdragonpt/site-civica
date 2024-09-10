<?php
session_start(); // Inicia a sessão

// Verificar se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para a página de login
    exit();
}

// Conectar ao banco de dados
include 'config.php';

// Aqui vai o seu código para exibir a página admin
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Civica</title>
    <style>
        /* Adicione seu estilo para a página admin aqui */
    </style>
</head>
<body>
    <h1>Painel Administrativo</h1>
    <p>Bem-vindo ao painel administrativo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    <!-- Adicione o conteúdo da sua página admin aqui -->
</body>
</html>
