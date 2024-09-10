<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verificar se o usuário existe
    $sql = "SELECT id, username, senha FROM usuarios WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verificar a senha
        if (password_verify($password, $row['senha'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            header("Location: admin.php"); // Redirecionar para o backoffice
            exit;
        } else {
            echo "Senha incorreta!";
        }
    } else {
        echo "Usuário não encontrado!";
    }
}
?>

<h1>Login</h1>
<form method="post" action="">
    Username: <input type="text" name="username" required><br>
    Senha: <input type="password" name="password" required><br>
    <input type="submit" value="Login">
</form>
