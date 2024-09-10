<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Função para lidar com a exclusão de produtos
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Função para adicionar produtos
if (isset($_POST['add'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Manipula o upload de imagens
    $imagem = '';
    if (isset($_FILES['imagens']) && $_FILES['imagens']['error'][0] == UPLOAD_ERR_OK) {
        $target_dir = "images/";
        foreach ($_FILES['imagens']['name'] as $key => $name) {
            $target_file = $target_dir . basename($name);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES["imagens"]["tmp_name"][$key]);
            
            // Verifica se o arquivo é uma imagem
            if ($check !== false) {
                if (move_uploaded_file($_FILES["imagens"]["tmp_name"][$key], $target_file)) {
                    $stmt = $conn->prepare("INSERT INTO imagens (produto_id, imagem) VALUES (?, ?)");
                    $stmt->bind_param("is", $produto_id, $name);
                    $stmt->execute();
                } else {
                    echo "Desculpe, ocorreu um erro ao fazer upload da imagem.";
                }
            } else {
                echo "O arquivo não é uma imagem.";
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nome, $descricao, $preco);
    $stmt->execute();
    $produto_id = $stmt->insert_id;
    $stmt->close();
    
    // Manipula a associação das imagens ao produto
    if (isset($_FILES['imagens']) && $_FILES['imagens']['error'][0] == UPLOAD_ERR_OK) {
        foreach ($_FILES['imagens']['name'] as $key => $name) {
            $stmt = $conn->prepare("INSERT INTO imagens (produto_id, imagem) VALUES (?, ?)");
            $stmt->bind_param("is", $produto_id, $name);
            $stmt->execute();
        }
    }
}

// Função para procurar produtos
$search = '';
if (isset($_POST['search'])) {
    $search = $_POST['search'];
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE nome LIKE ?");
    $stmt->bind_param("s", $search);
    $search = "%$search%";
} else {
    $stmt = $conn->prepare("SELECT * FROM produtos");
}

// Executa a consulta
$stmt->execute();
$produtos = $stmt->get_result();
$stmt->close();

// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Carregar imagens associadas ao produto
function get_imagens($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM imagens WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}
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
            margin-right: 10%;
            margin-left: 10%;
        }
        .admin-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .logout-button {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #ff6600;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
        }
        .logout-button:hover {
            background-color: #cc5200;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            max-width: 300px;
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
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Gerenciar Produtos</h1>

        <!-- Formulário para adicionar produtos -->
        <h2>Adicionar Produto</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="nome" placeholder="Nome" required>
                <textarea name="descricao" placeholder="Descrição"></textarea>
                <input type="text" name="preco" placeholder="Preço" required>
                <input type="file" name="imagens[]" multiple>
            </div>
            <button type="submit" name="add">Adicionar Produto</button>
        </form>


        <!-- Formulário para procurar produtos -->
        <h2>Procurar Produtos</h2>
        <form method="post" action="">
            <div class="form-group">
                <input type="text" name="search" placeholder="Nome do Produto">
            </div>
            <button type="submit" name="search">Buscar</button>
        </form>

        <!-- Tabela de produtos -->
        <h2>Produtos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Imagens</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $produtos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                    <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                    <td><?php echo htmlspecialchars($row['preco']); ?></td>
                    <td>
                        <?php
                        $imagens = get_imagens($row['id']);
                        while ($img = $imagens->fetch_assoc()) {
                            echo '<img src="images/' . htmlspecialchars($img['imagem']) . '" alt="' . htmlspecialchars($row['nome']) . '" style="width: 100px; margin-right: 5px;">';
                        }
                        ?>
                    </td>
                    <td>
                        <!-- Formulário para deletar produto -->
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <button type="submit" name="delete" class="delete-button">Deletar</button>
                        </form>
                        <!-- Link para edição -->
                        <a style="text-decoration: none;" href="editar_produto.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="edit-button">Editar</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
