<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Verifica se há um termo de busca
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Prepara a consulta para buscar produtos
$query = "SELECT * FROM produtos WHERE nome LIKE ? OR descricao LIKE ?";
$stmt = $conn->prepare($query);
$searchTermLike = "%" . $searchTerm . "%";
$stmt->bind_param("ss", $searchTermLike, $searchTermLike);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Civica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .admin-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group button {
            padding: 10px 20px;
            background-color: #ff6600;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #cc5200;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .back-button, .logout-button {
            padding: 10px 20px;
            background-color: #ff6600;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .back-button {
            margin-right: 10px;
        }
        .back-button:hover, .logout-button:hover {
            background-color: #cc5200;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <button class="back-button" onclick="window.location.href='admin.php'">Voltar</button>
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Produtos</h1>

        <!-- Formulário de busca -->
        <form method="get" action="admin.php">
            <input type="text" name="search" placeholder="Pesquisar produtos" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Buscar</button>
        </form>

        <!-- Lista de produtos -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($produto = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produto['id']); ?></td>
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                        <td><?php echo htmlspecialchars($produto['preco']); ?></td>
                        <td><a href="editar_produto.php?id=<?php echo htmlspecialchars($produto['id']); ?>">Editar</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
