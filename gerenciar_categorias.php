<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Função para adicionar categorias
if (isset($_POST['add_category'])) {
    $nome_categoria = $_POST['nome_categoria'];
    $stmt = $conn->prepare("INSERT INTO categorias (nome) VALUES (?)");
    $stmt->bind_param("s", $nome_categoria);
    $stmt->execute();
    $stmt->close();
}

// Função para editar categorias
if (isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $nome_categoria = $_POST['nome_categoria'];
    $stmt = $conn->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
    $stmt->bind_param("si", $nome_categoria, $id);
    $stmt->execute();
    $stmt->close();
}

// Função para remover categorias
if (isset($_GET['delete_category'])) {
    $id = $_GET['delete_category'];
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Obtém todas as categorias
$categorias_result = $conn->query("SELECT * FROM categorias");

// Obtém o ID da categoria para editar
$id_categoria = isset($_GET['edit_category']) ? intval($_GET['edit_category']) : 0;
$categoria = [];
if ($id_categoria > 0) {
    $stmt = $conn->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->bind_param("i", $id_categoria);
    $stmt->execute();
    $categoria = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - Civica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            margin-left: 20%;
            margin-right: 20%;
        }
        .admin-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .logout-button, .back-button {
            position: absolute;
            top: 20px;
            padding: 10px 20px;
            background-color: #333;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .back-button {
            right: 120px;
        }
        .logout-button {
            right: 20px;
        }
        .back-button:hover, .logout-button:hover {
            background-color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input {
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
            background-color: #333;
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
        .delete-button, .edit-button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .delete-button {
            background-color: #ff0000;
            color: #fff;
        }
        .delete-button:hover {
            background-color: #cc0000;
        }
        .edit-button {
            background-color: #ffcc00;
            color: #fff;
        }
        .edit-button:hover {
            background-color: #cca700;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <button class="back-button" onclick="window.location.href='admin.php'">Voltar</button>
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1 style="color: #ffcc00">Gerenciar Categorias</h1>

        <!-- Formulário para adicionar ou editar categorias -->
        <h2><?php echo $id_categoria > 0 ? 'Editar Categoria' : 'Adicionar Categoria'; ?></h2>
        <form method="post" action="">
            <div class="form-group">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_categoria); ?>">
                <input type="text" name="nome_categoria" value="<?php echo htmlspecialchars($categoria['nome'] ?? ''); ?>" placeholder="Nome da Categoria" required>
            </div>
            <button type="submit" name="<?php echo $id_categoria > 0 ? 'edit_category' : 'add_category'; ?>">
                <?php echo $id_categoria > 0 ? 'Salvar Alterações' : 'Adicionar Categoria'; ?>
            </button>
        </form>

        <!-- Tabela de categorias -->
        <h2>Categorias</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $categorias_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                    <td>
                        <!-- Link para editar categoria -->
                        <a style="text-decoration: none;" href="gerenciar_categorias.php?edit_category=<?php echo htmlspecialchars($row['id']); ?>" class="edit-button">Editar</a>
                        <!-- Link para deletar categoria -->
                        <a style="text-decoration: none;" href="gerenciar_categorias.php?delete_category=<?php echo htmlspecialchars($row['id']); ?>" class="delete-button" onclick="return confirm('Tem certeza de que deseja excluir esta categoria?');">Deletar</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>