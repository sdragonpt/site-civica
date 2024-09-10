<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redireciona para login.php se não estiver autenticado
    exit();
}

include 'config.php'; // Inclui a configuração de conexão com o banco de dados

// Função para obter produtos
function get_produtos() {
    global $conn;
    return $conn->query("SELECT * FROM produtos");
}

// Função para obter categorias associadas ao produto
function get_categorias($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT c.* FROM categorias c JOIN produto_categoria pc ON c.id = pc.categoria_id WHERE pc.produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Função para obter imagens associadas ao produto
function get_imagens($produto_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM imagens WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Função para excluir um produto
if (isset($_POST['delete'])) {
    $produto_id = $_POST['id'];

    // Remove imagens associadas ao produto
    $imagens = get_imagens($produto_id);
    while ($imagem = $imagens->fetch_assoc()) {
        if (file_exists("images/" . $imagem['imagem'])) {
            unlink("images/" . $imagem['imagem']);
        }
    }

    // Remove as categorias associadas ao produto
    $stmt = $conn->prepare("DELETE FROM produto_categoria WHERE produto_id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();

    // Remove o produto
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php");
    exit();
}

// Adicionar produto
if (isset($_POST['add'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    // Insere o produto
    $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nome, $descricao, $preco);
    $stmt->execute();
    $produto_id = $stmt->insert_id;
    $stmt->close();

    // Associa categorias ao produto
    if (isset($_POST['categorias']) && is_array($_POST['categorias'])) {
        foreach ($_POST['categorias'] as $categoria_id) {
            $stmt = $conn->prepare("INSERT INTO produto_categoria (produto_id, categoria_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $produto_id, $categoria_id);
            $stmt->execute();
        }
    }

    // Manipula o upload de imagens
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
}

// Obtém produtos e categorias existentes
$produtos = get_produtos();
$categorias_result = $conn->query("SELECT * FROM categorias");

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Civica</title>
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
        .category-selector {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .category-box {
            padding: 10px;
            margin: 5px;
            background-color: #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
        }
        .category-box.selected {
            background-color: #00cc00;
            color: #fff;
        }
        .category-box:hover {
            background-color: #cccccc;
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
    <script>
        function toggleCategory(categoryBox, categoryId) {
            categoryBox.classList.toggle('selected');
            const selectedCategories = document.getElementById('selected_categories');
            const index = Array.from(selectedCategories.options).findIndex(option => option.value == categoryId);
            if (categoryBox.classList.contains('selected')) {
                if (index === -1) {
                    const option = document.createElement('option');
                    option.value = categoryId;
                    option.textContent = categoryBox.textContent;
                    selectedCategories.appendChild(option);
                }
            } else {
                if (index !== -1) {
                    selectedCategories.remove(index);
                }
            }
        }
    </script>
</head>
<body>
    <div class="admin-container">
        <button class="logout-button" onclick="window.location.href='admin.php?logout=true'">Logout</button>
        <h1>Admin - Civica</h1>

        <!-- Formulário para adicionar produto -->
        <h2>Adicionar Produto</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="nome" placeholder="Nome do Produto" required>
                <textarea name="descricao" placeholder="Descrição" required></textarea>
                <input type="text" name="preco" placeholder="Preço" required>
                <input type="file" name="imagens[]" multiple>
                <div class="form-group">
                    <h3>Selecionar Categorias</h3>
                    <div class="category-selector">
                        <?php while ($categoria = $categorias_result->fetch_assoc()): ?>
                            <div class="category-box" onclick="toggleCategory(this, <?php echo $categoria['id']; ?>)">
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <select id="selected_categories" name="categorias[]" multiple style="display:none;"></select>
                </div>
            </div>
            <button type="submit" name="add">Adicionar Produto</button>
        </form>

        <!-- Lista de produtos -->
        <h2>Produtos</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Imagens</th>
                    <th>Categorias</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($produto = $produtos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produto['id']); ?></td>
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                        <td><?php echo htmlspecialchars($produto['preco']); ?></td>
                        <td>
                            <?php 
                                $imagens = get_imagens($produto['id']);
                                while ($imagem = $imagens->fetch_assoc()): 
                            ?>
                                <img src="images/<?php echo htmlspecialchars($imagem['imagem']); ?>" alt="Imagem do produto" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php endwhile; ?>
                        </td>
                        <td>
                            <?php 
                                $categorias = get_categorias($produto['id']);
                                while ($categoria = $categorias->fetch_assoc()): 
                            ?>
                                <?php echo htmlspecialchars($categoria['nome']); ?><br>
                            <?php endwhile; ?>
                        </td>
                        <td>
                            <button class="edit-button" onclick="window.location.href='editar_produto.php?id=<?php echo $produto['id']; ?>'">Editar</button>
                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">
                                <button class="delete-button" type="submit" name="delete">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Aviso -->
        <div class="info-box">
            Info: Após adicionar ou excluir produtos, a página pode precisar ser atualizada para refletir as mudanças.
        </div>
    </div>
</body>
</html>
