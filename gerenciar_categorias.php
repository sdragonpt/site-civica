<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Função para obter todas as categorias
function get_categorias() {
    global $conn;
    return $conn->query("SELECT * FROM categorias");
}

// Função para adicionar uma nova categoria
if (isset($_POST['add_category'])) {
    $nome = $_POST['nome'];

    // Insere a nova categoria
    $stmt = $conn->prepare("INSERT INTO categorias (nome) VALUES (?)");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $stmt->close();

    header("Location: gerenciar_categorias.php");
    exit();
}

// Função para editar uma categoria
if (isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];

    // Atualiza a categoria
    $stmt = $conn->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
    $stmt->bind_param("si", $nome, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: gerenciar_categorias.php");
    exit();
}

// Função para excluir uma categoria
if (isset($_POST['delete_category'])) {
    $id = $_POST['id'];

    // Remove as associações da categoria com produtos
    $stmt = $conn->prepare("DELETE FROM produto_categoria WHERE categoria_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Remove a categoria
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: gerenciar_categorias.php");
    exit();
}

// Obtém categorias existentes
$categorias = get_categorias();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - Civica</title>
    <style>
        /* Adicione seu estilo aqui */
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
        .logout-button, .edit-button, .delete-button {
            padding: 10px 20px;
            background-color: #333;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .edit-button:hover, .delete-button:hover {
            background-color: #ff6600;
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
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .delete-button {
            background-color: #ff0000;
        }
        .delete-button:hover {
            background-color: #cc0000;
        }
        .info-box {
            background-color: #e0f7fa; /* Azul suave */
            border: 1px solid #b2ebf2;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Gerenciar Categorias</h1>

        <!-- Formulário para adicionar categoria -->
        <h2>Adicionar Categoria</h2>
        <form method="post" action="">
            <div class="form-group">
                <input type="text" name="nome" placeholder="Nome da Categoria" required>
            </div>
            <button type="submit" name="add_category">Adicionar Categoria</button>
        </form>

        <!-- Lista de categorias -->
        <h2>Categorias Existentes</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($categoria = $categorias->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($categoria['id']); ?></td>
                        <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                        <td>
                            <button class="edit-button" onclick="editCategory(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nome']); ?>')">Editar</button>
                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $categoria['id']; ?>">
                                <button class="delete-button" type="submit" name="delete_category">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Aviso -->
        <div class="info-box">
            Info: Após adicionar, editar ou excluir categorias, a página pode precisar ser atualizada para refletir as mudanças.
        </div>
    </div>

    <!-- Formulário de edição (oculto inicialmente) -->
    <div id="edit_category_modal" style="display:none;">
        <h2>Editar Categoria</h2>
        <form method="post" action="">
            <input type="hidden" id="edit_category_id" name="id">
            <div class="form-group">
                <input type="text" id="edit_category_name" name="nome" required>
            </div>
            <button type="submit" name="edit_category">Salvar Alterações</button>
            <button type="button" onclick="document.getElementById('edit_category_modal').style.display='none'">Cancelar</button>
        </form>
    </div>

    <script>
        function editCategory(id, name) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_category_modal').style.display = 'block';
        }
    </script>
</body>
</html>
